<?php

namespace Omnibus\JdlExpress\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\JdlExpress\Api;
use Omnibus\Request\Cancel;
use Omnibus\Request\Request;

/** /ecap/v1/orders/cancel. */
final class CancelAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Cancel;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Cancel);
        $data = $this->api->call('/ecap/v1/orders/cancel', ['customerCode' => $this->api->customerCode, 'waybillCode' => $request->trackingNumber, 'cancelReason' => 'cancelled by the shipper', 'orderOrigin' => 1]);
        $request->setResult(!isset($data['cancelResult']) || \in_array($data['cancelResult'], [true, 1, '1', 'SUCCESS'], true));
    }
}
