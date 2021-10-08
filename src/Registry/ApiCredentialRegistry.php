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

namespace Smile\HipaySyliusPlugin\Registry;

use Payum\Core\GatewayFactory;
use Payum\Core\GatewayInterface;
use Smile\HipaySyliusPlugin\Api\ApiCredentialInterface;

class ApiCredentialRegistry
{
    /** @var GatewayFactory[] GatewayFactory */
    private iterable $factories;

    public function __construct(iterable $factories)
    {
        $this->factories = $factories;
    }

    /**
     * @param string $gateway
     *
     * @return null|ApiCredentialInterface
     */
    public function getApiConfig(string $gateway): ?ApiCredentialInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($gateway)) {
                return $factory->create();
            }
        }

        return null;
    }
}
