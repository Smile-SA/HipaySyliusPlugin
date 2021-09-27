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

use App\Entity\Payment\Payment;
use Payum\Bundle\PayumBundle\Controller\PayumController;
use Symfony\Component\HttpFoundation\Request;

class NotifyController extends PayumController
{
    // Here you can change the fields which contains order id in the Request
    private const FIELDS_ORDER_ID = 'order.id';

    public function doAction(Request $request)
    {
        $orderId = $request->request->get(self::FIELDS_ORDER_ID);
        $payment = $this->container->get('sylius.repository.payment')->findOneBy(
            ['orderId' => $orderId]
        );

        if (!$payment instanceof Payment) {
            throw new \LogicException(sprintf('Undefined payment with order_id %s', $orderId));
        }

        $details = $payment->getDetails();
        if (!isset($details['payum_token'])) {
            throw new \LogicException(
                sprintf('Undefined payum token in details for payment with id %s', $payment->getId())
            );
        }

        return $this->forward('PayumBundle:Notify:do', [
            'payum_token' => $details['payum_token'],
        ]);
    }
}
