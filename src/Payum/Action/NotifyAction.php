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
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Smile\HipaySyliusPlugin\Gateway\GatewayFactoryNameGetterTrait;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayMotoCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney3GatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney4GatewayFactory;
use Sylius\Component\Core\Model\PaymentInterface;

/**
 * This Action is used to store data in the payment when a notification is received.
 * The payment status will be updated in the dedicated StatusAction
 */
final class NotifyAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use GatewayFactoryNameGetterTrait;

    /** @param Notify $request */
    public function execute($request): void
    {
        // Extract POST data
        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);
        $payumResponse = $getHttpRequest->request;

        // Check data => @todo : upgrade this if needed
        if ($payumResponse && isset($payumResponse['state'])) {
            /** @var PaymentInterface $payment */
            $payment = $request->getModel();
            $paymentDetails = $payment->getDetails();
            $paymentDetails['hipay_responses'][] = $payumResponse;
            $payment->setDetails($paymentDetails);
        }
    }

    public function supports($request): bool
    {
        if (!$request instanceof Notify || !$request->getFirstModel() instanceof PaymentInterface) {
            return false;
        }

        return in_array(
            $this->getGatewayFactoryName($request->getModel()->getMethod()->getGatewayConfig()),
            [
                HipayCardGatewayFactory::FACTORY_NAME,
                HipayMotoCardGatewayFactory::FACTORY_NAME,
                HipayOney3GatewayFactory::FACTORY_NAME,
                HipayOney4GatewayFactory::FACTORY_NAME,
            ]
        );
    }
}
