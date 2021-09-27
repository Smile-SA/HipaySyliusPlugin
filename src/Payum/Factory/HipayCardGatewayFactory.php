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

namespace Smile\HipaySyliusPlugin\Payum\Factory;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class HipayCardGatewayFactory extends GatewayFactory
{
    public const FACTORY_NAME = 'hipay_card';

    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults(
            [
                'payum.factory_name' => self::FACTORY_NAME,
                'payum.factory_title' => 'Hipay CB',
            ]
        );
    }
}
