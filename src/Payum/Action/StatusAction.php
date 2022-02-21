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
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use SM\Factory\FactoryInterface;
use Smile\HipaySyliusPlugin\Api\HipayStatus;
use Smile\HipaySyliusPlugin\Context\PaymentContext;
use Smile\HipaySyliusPlugin\Exception\HipayException;
use Smile\HipaySyliusPlugin\Gateway\GatewayFactoryNameGetterTrait;
use Smile\HipaySyliusPlugin\Registry\ApiCredentialRegistry;
use Smile\HipaySyliusPlugin\Security\HipaySignatureVerification;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Payment\PaymentTransitions;


final class StatusAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use GatewayFactoryNameGetterTrait;

    private GetHttpRequest $getHttpRequest;
    private PaymentContext $paymentContext;
    private HipaySignatureVerification $hipaySignatureVerification;
    protected ApiCredentialRegistry $apiCredentialRegistry;
    private FactoryInterface $stateMachineFactory;

    public function __construct(
        GetHttpRequest             $getHttpRequest,
        PaymentContext             $paymentContext,
        HipaySignatureVerification $hipaySignatureVerification,
        ApiCredentialRegistry $apiCredentialRegistry,
        FactoryInterface $stateMachineFactory
    )
    {
        $this->getHttpRequest = $getHttpRequest;
        $this->paymentContext = $paymentContext;
        $this->hipaySignatureVerification = $hipaySignatureVerification;
        $this->apiCredentialRegistry = $apiCredentialRegistry;
        $this->stateMachineFactory = $stateMachineFactory;
    }

    /** @param GetStatus $request */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $this->clearPaymentContext();
        $this->gateway->execute($this->getHttpRequest);

        /** @var PaymentInterface $payment */
        $payment = $request->getModel();
        $paymentDetails = $payment->getDetails();
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();
        $gatewayConfig = $paymentMethod->getGatewayConfig();

        if ($this->hipaySignatureVerification->isAnHipayRequest()
            && !$this->hipaySignatureVerification->verifyHttpRequest($payment->getMethod()->getCode())
        ) {
            throw new HipayException('Unable to verify the Hipay signature !');
        }

        $queryResponse = $this->getHttpRequest->query;// User browser redirection
        $requestResponse = $this->getHttpRequest->request;// Server to server notification
        $doRefunds = $this->apiCredentialRegistry
            ->getApiConfig($this->getGatewayFactoryName($gatewayConfig))
            ->getDoRefunds();

        $status = $queryResponse['status'] ?? $requestResponse['status'] ?? $paymentDetails['status'] ?? null;
        $order = $payment->getOrder();
        $stateMachine = $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH);

        switch ($status) {
            /**
             * User pressed/clicked cancer/return to merchand button/link
             */
            case HipayStatus::CODE_STATUS_CANCELLED:
                $request->markCanceled();
                $order->setState(OrderInterface::STATE_CANCELLED);
                $order->setPaymentState(OrderPaymentStates::STATE_CANCELLED);
                $payment->setState(PaymentInterface::STATE_CANCELLED);
                $payment->setDetails(array_merge($paymentDetails, ['status' => $status]));
                $payment->setUpdatedAt(new \DateTime());
                $order->setUpdatedAt(new \DateTime());

                return;

            /**
             * Eveything is good !
             */
            case HipayStatus::CODE_STATUS_CAPTURED:
                $request->markCaptured();
                $order->setState(OrderInterface::STATE_NEW);
                $order->setPaymentState(OrderPaymentStates::STATE_PAID);
                $payment->setState(PaymentInterface::STATE_COMPLETED);
                $payment->setDetails(array_merge($paymentDetails, ['status' => $status]));
                $payment->setUpdatedAt(new \DateTime());
                $order->setUpdatedAt(new \DateTime());

                return;

            /**
             * Timeout caused by the user leaving the
             * payement gateway
             */
            case HipayStatus::CODE_STATUS_EXPIRED:
                $request->markExpired();
                $order->setState(OrderInterface::STATE_CANCELLED);
                $order->setPaymentState(OrderPaymentStates::STATE_CANCELLED);
                $payment->setState(PaymentInterface::STATE_CANCELLED);
                $payment->setDetails(array_merge($paymentDetails, ['status' => $status]));
                $payment->setUpdatedAt(new \DateTime());
                $order->setUpdatedAt(new \DateTime());
                return;

            /**
             * Issuer banque denied the capture due to
             * a unknown reason, often inssuficient funds,
             * held account or expired card
             */
            case HipayStatus::CODE_STATUS_REFUSED:
            case HipayStatus::CODE_STATUS_CAPTURE_REFUSED:
                $request->markFailed();
                $order->setState(OrderInterface::STATE_NEW);
                $order->setPaymentState(OrderPaymentStates::STATE_AWAITING_PAYMENT);
                $payment->setState(PaymentInterface::STATE_FAILED);
                $payment->setDetails(array_merge($paymentDetails, ['status' => $status]));
                $payment->setUpdatedAt(new \DateTime());
                $order->setUpdatedAt(new \DateTime());
                return;
            /**
             * Sentinel/security/fraude issue
             * the order must be marked as state "FAILED"
             * to not be payable again.
             */
            case HipayStatus::CODE_STATUS_BLOCKED:
            case HipayStatus::CODE_STATUS_DENIED:
                $request->markCanceled();
                $order->setState(OrderInterface::STATE_CANCELLED);
                $order->setPaymentState(OrderPaymentStates::STATE_CANCELLED);
                $payment->setState(PaymentInterface::STATE_FAILED);
                $payment->setDetails(array_merge($paymentDetails, ['status' => $status]));
                $payment->setUpdatedAt(new \DateTime());
                $order->setUpdatedAt(new \DateTime());
                return;

            /**
             * Intermediate status when user
             * is traveling in payment gateway
             * (3DS, app bank confirmation, etc)
             * or also when the user asked a 3x/4x
             * paiement facility and the
             * authorization is still awaiting
             * for final approval
             */
            case HipayStatus::CODE_STATUS_PENDING:
            case HipayStatus::CODE_STATUS_AUTHORIZED_PENDING:
            case HipayStatus::CODE_STATUS_AUTHORIZED:
            case HipayStatus::CODE_STATUS_AUTHORIZATION_REQUESTED:
                $request->markPending();
                $order->setState(OrderInterface::STATE_NEW);
                $order->setPaymentState(OrderPaymentStates::STATE_AUTHORIZED);
                $payment->setState(PaymentInterface::STATE_AUTHORIZED);
                $payment->setDetails(array_merge($paymentDetails, ['status' => $status]));
                $payment->setUpdatedAt(new \DateTime());
                $order->setUpdatedAt(new \DateTime());
                return;

            /**
             * Refund request initiated by the merchand
             */
            case HipayStatus::CODE_STATUS_PARTIALLY_REFUNDED:
            case HipayStatus::CODE_STATUS_REFUNDED:
                if($doRefunds){
                    $request->markRefunded();

                    if ($status === HipayStatus::CODE_STATUS_PARTIALLY_REFUNDED) {
                        $payment->setAmount(((int)$requestResponse['refunded_amount'] * 100));
                    } else {
                        $payment->setAmount($payment->getAmount());
                    }
                    $payment->setState(PaymentInterface::STATE_REFUNDED);
                    $order->setState(OrderInterface::STATE_FULFILLED);
                    $order->setPaymentState(OrderPaymentStates::STATE_REFUNDED);
                    $payment->setUpdatedAt(new \DateTime());
                    $order->setUpdatedAt(new \DateTime());
                }

                return;
            case HipayStatus::CODE_STATUS_REFUND_REQUESTED:
            case HipayStatus::CODE_STATUS_REFUND_REFUSED:
            case HipayStatus::CODE_STATUS_CAPTURE_REQUESTED:
                // Gently ignore them as they are intermediate statuses
                return;
        }

        $request->markFailed();
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatus &&
            $request->getFirstModel() instanceof PaymentInterface;
    }

    private function clearPaymentContext()
    {
        $this->paymentContext->remove(PaymentContext::HIPAY_TOKEN);
        $this->paymentContext->remove(PaymentContext::HIPAY_PAYMENT_PRODUCT);
    }
}
