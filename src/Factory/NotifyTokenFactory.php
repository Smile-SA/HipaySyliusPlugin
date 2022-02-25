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

namespace Smile\HipaySyliusPlugin\Factory;

use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\TokenInterface;
use Smile\HipaySyliusPlugin\Api\ApiCredential;
use Smile\HipaySyliusPlugin\Gateway\GatewayFactoryNameGetterTrait;
use Smile\HipaySyliusPlugin\Registry\ApiCredentialRegistry;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\Routing\RouterInterface;

class NotifyTokenFactory implements GenericTokenFactoryAwareInterface
{
    use GenericTokenFactoryAwareTrait;
    use GatewayFactoryNameGetterTrait;

    protected ApiCredentialRegistry $apiCredentialRegistry;
    protected RouterInterface $router;

    public function __construct(
        ApiCredentialRegistry $apiCredentialRegistry,
        RouterInterface $router
    ) {
        $this->apiCredentialRegistry = $apiCredentialRegistry;
        $this->router = $router;
    }

    public function getNotifyToken(PaymentInterface $payment, GenericTokenFactoryInterface $genericTokenFactory): TokenInterface
    {
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();
        /** @var GatewayConfigInterface $gatewayConfig */
        $gatewayConfig = $paymentMethod->getGatewayConfig();

        $gatewayFactory = $this->getGatewayFactoryName($gatewayConfig);
        /** @var ApiCredential $apiCredentials */
        $apiCredentials = $this->apiCredentialRegistry->getApiConfig($gatewayFactory);

        if ($apiCredentials->getNotifyUrl()) {
            $backupHost = $this->router->getContext()->getHost();
            $this->router->getContext()->setHost($apiCredentials->getNotifyUrl());
            $notifyToken = $genericTokenFactory->createNotifyToken($this->getGatewayName($gatewayConfig), $payment);
            $this->router->getContext()->setHost($backupHost);
        } else {
            $notifyToken = $genericTokenFactory->createNotifyToken($this->getGatewayName($gatewayConfig), $payment);
        }

        return $notifyToken;
    }
}