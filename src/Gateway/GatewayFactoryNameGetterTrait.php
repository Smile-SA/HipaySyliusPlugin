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
use Sylius\Component\Customer\Model\CustomerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait GatewayFactoryNameGetterTrait
{
    protected function getGatewayFactoryName(GatewayConfigInterface $gatewayConfig)
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
}
