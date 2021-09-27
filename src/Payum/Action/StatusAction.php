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
use Smile\HipaySyliusPlugin\Api\HipayStatus;
use Smile\HipaySyliusPlugin\Context\PaymentContext;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;

final class StatusAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    private GetHttpRequest $getHttpRequest;
    private PaymentContext $paymentContext;

    public function __construct(GetHttpRequest $getHttpRequest, PaymentContext $paymentContext)
    {
        $this->getHttpRequest = $getHttpRequest;
        $this->paymentContext = $paymentContext;
    }

    /** @param GetStatus $request */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $this->clearPaymentContext();

        $this->gateway->execute($this->getHttpRequest); // Get POST/GET data and query from request
        /** @var PaymentInterface $payment */
        $payment = $request->getModel();

        $paymentDetails = $payment->getDetails();
        $response = $this->getHttpRequest->query;
        $status = $response['status'] ?? $paymentDetails['status'] ?? null;

        if ($status === HipayStatus::CODE_STATUS_CANCELLED) {
            $request->markCanceled();
            $payment->setDetails(array_merge($paymentDetails, ['status' => HipayStatus::CODE_STATUS_CANCELLED]));

            return;
        }

        if ($status === HipayStatus::CODE_STATUS_CAPTURED) {
            $request->markCaptured();
            $payment->setDetails(array_merge($paymentDetails, ['status' => HipayStatus::CODE_STATUS_CAPTURED]));

            return;
        }

        if ($status === HipayStatus::CODE_STATUS_EXPIRED) {
            $request->markExpired();
            $payment->setDetails(array_merge($paymentDetails, ['status' => HipayStatus::CODE_STATUS_EXPIRED]));

            return;
        }

        if ($status === HipayStatus::CODE_STATUS_PENDING && $status === HipayStatus::CODE_STATUS_AUTHORIZED_PENDING ) {
            $request->markPending();
            $payment->setDetails(array_merge($paymentDetails, ['status' => HipayStatus::CODE_STATUS_PENDING]));

            return;
        }

        if ($status === HipayStatus::CODE_STATUS_BLOCKED) {
            $request->markSuspended();
            $payment->setDetails(array_merge($paymentDetails, ['status' => HipayStatus::CODE_STATUS_BLOCKED]));


            return;
        }

        $request->markFailed();
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatus &&
            $request->getFirstModel() instanceof PaymentInterface
            ;
    }

    private function clearPaymentContext()
    {
        $this->paymentContext->remove(PaymentContext::HIPAY_TOKEN);
        $this->paymentContext->remove(PaymentContext::HIPAY_PAYMENT_PRODUCT);
    }
}
