<?php

namespace Omnibus\JdlExpress;

use Omnibus\Core\GatewayFactory;

class JdlExpressGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'omnibus.factory_name' => 'jdl-express',
            'omnibus.factory_title' => 'JDL Express'
        ]);
    }
}