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

namespace Smile\HipaySyliusPlugin\EventSubscriber;

use Smile\HipaySyliusPlugin\Context\PaymentContext;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayMotoCardGatewayFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;

class HipayStoreTokenPaymentEventSubscriber implements EventSubscriberInterface
{
    private const AUTHORIZED_ROUTE = [
        'sylius_shop_checkout_select_payment',
        'sylius_shop_order_pay',
        'sylius_shop_order_show'

    ];
    private PaymentContext $paymentContext;

    public function __construct(PaymentContext $paymentContext)
    {
        $this->paymentContext = $paymentContext;
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.controller' => 'handle',
        ];
    }

    public function handle(KernelEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if (!in_array($route, self::AUTHORIZED_ROUTE)) {
            return;
        }

        $hipayTokenCard = $request->request->get(
            sprintf('%s_%s', PaymentContext::HIPAY_TOKEN, HipayCardGatewayFactory::FACTORY_NAME)
        );
        $hipayPaymentProductCard = $request->request->get(
            sprintf('%s_%s', PaymentContext::HIPAY_PAYMENT_PRODUCT, HipayCardGatewayFactory::FACTORY_NAME)
        );

        if ($hipayTokenCard !== '') {
            $token = $hipayTokenCard;
        } else {
            $token = $request->request->get(
                sprintf('%s_%s', PaymentContext::HIPAY_TOKEN, HipayMotoCardGatewayFactory::FACTORY_NAME)
            ) ?? null;
        }

        if ($hipayPaymentProductCard !== '') {
            $paymentProduct = $hipayPaymentProductCard;
        } else {
            $paymentProduct = $request->request->get(
                sprintf('%s_%s', PaymentContext::HIPAY_PAYMENT_PRODUCT, HipayMotoCardGatewayFactory::FACTORY_NAME)
            ) ?? null;
        }

        if ($token !== null) {
            $this->paymentContext->set(PaymentContext::HIPAY_TOKEN, $token);
            $this->paymentContext->set(PaymentContext::HIPAY_PAYMENT_PRODUCT, $paymentProduct);
        }
    }
}
