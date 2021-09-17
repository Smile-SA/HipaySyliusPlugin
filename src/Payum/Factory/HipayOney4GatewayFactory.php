<?php

namespace Smile\HipaySyliusPlugin\Payum\Factory;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class HipayOney4GatewayFactory extends GatewayFactory
{
    public const FACTORY_NAME = 'hipay_oney4';

    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults(
            [
                'payum.factory_name' => self::FACTORY_NAME,
                'payum.factory_title' => 'Hipay Oney 4x sans frais',
            ]
        );
    }
}
