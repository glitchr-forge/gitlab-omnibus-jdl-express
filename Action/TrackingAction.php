<?php

namespace Omnibus\JdlExpress\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\JdlExpress\Api;
use Omnibus\Model\Tracking as TrackingModel;
use Omnibus\Model\TrackingEvent;
use Omnibus\Model\TrackingStatus;
use Omnibus\Request\Request;
use Omnibus\Request\Tracking;

/** /ecap/v1/orders/trace/query: the waybill's traces, oldest first. */
final class TrackingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Tracking;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Tracking);
        $data = $this->api->call('/ecap/v1/orders/trace/query', ['customerCode' => $this->api->customerCode, 'waybillCode' => $request->trackingNumber]);
        $events = [];
        foreach ($data['traceDetails'] ?? $data[0]['traceDetails'] ?? [] as $trace) {
            $events[] = new TrackingEvent(new \DateTimeImmutable((string) ($trace['operateTime'] ?? 'now'), new \DateTimeZone('Asia/Shanghai')), self::status($trace['operateState'] ?? $trace['state'] ?? null, $trace['operateRemark'] ?? $trace['remark'] ?? null), (string) ($trace['operateRemark'] ?? $trace['remark'] ?? ''), $trace['operateSite'] ?? null, isset($trace['operateState']) ? (string) $trace['operateState'] : null);
        }
        usort($events, static fn (TrackingEvent $a, TrackingEvent $b) => $a->at <=> $b->at);
        $request->setResult(new TrackingModel('jdl-express', $request->trackingNumber, $events ? $events[array_key_last($events)]->status : TrackingStatus::UNKNOWN, $events));
    }

    private static function status(mixed $state, ?string $remark): TrackingStatus
    {
        $r = (string) $remark;
        $state = null === $state ? null : (string) $state;

        return match (true) {
            \in_array($state, ['150', '160', '170'], true) || str_contains($r, '已签收') || str_contains($r, '妥投') || str_contains($r, 'delivered') => TrackingStatus::DELIVERED,
            '140' === $state || str_contains($r, '派送中') || str_contains($r, '正在派送') || str_contains($r, 'out for delivery') => TrackingStatus::OUT_FOR_DELIVERY,
            \in_array($state, ['180', '190'], true) || str_contains($r, '拒收') || str_contains($r, '退回') || str_contains($r, 'return') => TrackingStatus::RETURNED,
            str_contains($r, '异常') || str_contains($r, 'exception') => TrackingStatus::EXCEPTION,
            \in_array($state, ['10', '20'], true) || str_contains($r, '已下单') || str_contains($r, '等待揽收') => TrackingStatus::PENDING,
            null === $state && '' === $r => TrackingStatus::UNKNOWN,
            default => TrackingStatus::IN_TRANSIT,
        };
    }
}
