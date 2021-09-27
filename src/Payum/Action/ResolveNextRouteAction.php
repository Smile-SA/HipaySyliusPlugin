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

namespace Smile\HipaySyliusPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayMotoCardGatewayFactory;
use Sylius\Bundle\PayumBundle\Model\GatewayConfigInterface;
use Sylius\Bundle\PayumBundle\Request\ResolveNextRoute;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;

final class ResolveNextRouteAction implements ActionInterface
{
    /** @param ResolveNextRoute $request */
    public function execute($request): void
    {
        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        if ($payment->getState() === PaymentInterface::STATE_COMPLETED) {
            $request->setRouteName('sylius_shop_order_thank_you');

            return;
        }

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        $request->setRouteName('sylius_shop_order_show');
        $request->setRouteParameters(['tokenValue' => $order->getTokenValue()]);
    }

    public function supports($request)
    {
        if (
            !$request instanceof ResolveNextRoute ||
            !$request->getFirstModel() instanceof PaymentInterface
        ) {
            return false;
        }

        /** @var PaymentInterface $model */
        $model = $request->getFirstModel();
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $model->getMethod();
        /** @var GatewayConfigInterface $gatewayConfig */
        $gatewayConfig = $paymentMethod->getGatewayConfig();

        return in_array(
            $gatewayConfig->getGatewayName(),
            [
                HipayCardGatewayFactory::FACTORY_NAME,
                HipayMotoCardGatewayFactory::FACTORY_NAME
            ]
        );
    }
}
