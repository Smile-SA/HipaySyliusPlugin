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

use HiPay\Fullservice\Enum\Transaction\TransactionStatus;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use Smile\HipaySyliusPlugin\Exception\HipayException;
use Smile\HipaySyliusPlugin\Gateway\GatewayFactoryNameGetterTrait;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayMotoCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney3GatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney4GatewayFactory;
use Smile\HipaySyliusPlugin\Security\HipaySignatureVerification;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;

/**
 * This Action is used to check payment status after a notification or any payum call
 * This is done by checking the payment details notification data and updating the request state
 * The payment object is eventually updated in the dedicated UpdatePaymentStateExtension
 */
final class StatusAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use GatewayFactoryNameGetterTrait;

    protected HipaySignatureVerification $signatureVerification;

    public function __construct(HipaySignatureVerification $signatureVerification)
    {
        $this->signatureVerification = $signatureVerification;
    }

    /**
     * @param GetStatus $request
     */
    public function execute($request): void
    {
        /** @var PaymentInterface $payment */
        $payment = $request->getModel();

        if ($this->signatureVerification->isAnHipayRequest()
            && !$this->signatureVerification->verifyHttpRequest($payment->getMethod()->getCode())
        ) {
            throw new HipayException('Unable to verify the Hipay signature !');
        }

        $paymentDetails = $payment->getDetails();

        // Extract GET data (if any)
        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);
        $payumResponse = $getHttpRequest->query;

        // Check data => @todo : upgrade this if needed
        if ($payumResponse && isset($payumResponse['state'])) {
            /** @var PaymentInterface $payment */
            $paymentDetails['hipay_responses'][] = $payumResponse;
            $payment->setDetails($paymentDetails);
        }

        $hipayResponses = $paymentDetails['hipay_responses'] ?? [];

        if ($hipayResponses) {
            $lastResponse = end($hipayResponses);
            $lastStatus = $lastResponse['status'] ?? TransactionStatus::UNKNOWN;

            switch ($lastStatus) {
                case TransactionStatus::AUTHORIZED:
                    $request->markAuthorized();
                    break;
                case TransactionStatus::PENDING_PAYMENT:
                case TransactionStatus::AUTHENTICATION_REQUESTED:
                case TransactionStatus::AUTHORIZATION_REQUESTED:
                case TransactionStatus::CAPTURE_REQUESTED:
                    $request->markPending();
                    break;
                case TransactionStatus::CAPTURED:
                    $request->markCaptured();
                    break;
                case TransactionStatus::BLOCKED:
                case TransactionStatus::DENIED:
                case TransactionStatus::CANCELLED:
                    $request->markCanceled();
                    break;
                case TransactionStatus::AUTHENTICATION_FAILED:
                case TransactionStatus::REFUSED:
                case TransactionStatus::CAPTURE_REFUSED:
                    $request->markFailed();
                    break;
                case TransactionStatus::EXPIRED:
                    $request->markExpired();
                    break;
                case TransactionStatus::REFUNDED:
                case TransactionStatus::PARTIALLY_REFUNDED: // @todo: check this situation
                    $request->markRefunded();
                    break;
                default:
                    break;
            }
        }
    }

    public function supports($request): bool
    {
        if (!$request instanceof GetStatus || !$request->getFirstModel() instanceof PaymentInterface) {
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
