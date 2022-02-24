<?php

/*
 * This file is part of the HipaySyliusPlugin
 *
 * (c) Smile <dirtech@smile.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Smile\HipaySyliusPlugin\Gateway;

use Payum\Core\Model\GatewayConfigInterface;

trait GatewayFactoryNameGetterTrait
{
    protected function getGatewayFactoryName(GatewayConfigInterface $gatewayConfig): string
    {
        try {
            /**
             * @see GatewayConfigInterface
             * method getFactoryName()
             * will be soon removed
             */
            return $gatewayConfig->getFactoryName();
        } catch (\Error $e) {
            return $gatewayConfig->getConfig()['factory_name'];
        }
    }

    protected function getGatewayName(GatewayConfigInterface $gatewayConfig): string
    {
        try {
            /**
             * @see GatewayConfigInterface
             * method getGatewayName()
             * will be soon removed
             */
            return $gatewayConfig->getGatewayName();
        } catch (\Error $e) {
            return $gatewayConfig->getConfig()['gateway_name'];
        }
    }
}
