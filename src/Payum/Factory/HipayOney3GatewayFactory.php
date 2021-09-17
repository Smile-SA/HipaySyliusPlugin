<?php

namespace Smile\HipaySyliusPlugin\Payum\Factory;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Smile\HipaySyliusPlugin\Api\ApiOneyConfig;

final class HipayOney3GatewayFactory extends GatewayFactory
{
    public const FACTORY_NAME = 'hipay_oney3';

    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults(
            [
                'payum.factory_name' => self::FACTORY_NAME,
                'payum.factory_title' => 'Hipay Oney 3x sans frais',
            ]
        );

        $config['payum.oney_api'] = function (ArrayObject $config) {
            return new ApiOneyConfig(
                $config['codeOPC'],
                $config['fess'],
                $config['carAmountMini'],
                $config['carAmountMax']
            );
        };
    }
}
