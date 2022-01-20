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

namespace Smile\HipaySyliusPlugin\Controller;

use Payum\Core\Model\ArrayObject;
use Payum\Core\Request\Notify;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\Payment;
use Sylius\Component\Core\Model\Order;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Payum\Bundle\PayumBundle\Controller\NotifyController as PayumControllerNotifyController;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NotifyController extends PayumController
{
    // Here you can change the fields which contains order id in the Request
    private const FIELDS_ORDER_ID = 'order.id';

    public function doAction(Request $request, PaymentRepositoryInterface $paymentRepository, OrderRepositoryInterface $orderRepository)
    {
        $orderId = $request->request->get('order')['id'];

        $order = $orderRepository->findOneBy(
            ['number' => $orderId]
        );

        if (!$order instanceof Order) {
            throw new \LogicException(sprintf('Undefined order with number %s', $orderId));
        }

        $payment = $paymentRepository->findOneBy(
            ['order' => $order->getId()]
        );

        if (!$payment instanceof Payment) {
            throw new \LogicException(sprintf('Undefined payment with order_id %s', $orderId));
        }

        $gateway = $this->getPayum()->getGateway($request->get('gateway'));

        $gateway->execute(new GetStatus($payment));

        return new Response('OK', 200);
    }
}
