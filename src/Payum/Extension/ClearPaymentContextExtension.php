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

namespace Smile\HipaySyliusPlugin\Payum\Extension;

use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetStatusInterface;
use Smile\HipaySyliusPlugin\Context\PaymentContext;

final class ClearPaymentContextExtension implements ExtensionInterface
{
    private PaymentContext $paymentContext;

    public function __construct(PaymentContext $paymentContext)
    {
        $this->paymentContext = $paymentContext;
    }

    public function onPreExecute(Context $context): void
    {
    }

    public function onExecute(Context $context): void
    {
    }

    public function onPostExecute(Context $context): void
    {
        $previousStack = $context->getPrevious();
        $previousStackSize = count($previousStack);

        if ($previousStackSize > 1) {
            return;
        }

        if ($previousStackSize === 1) {
            $previousActionClassName = get_class($previousStack[0]->getAction());
            if (false === stripos($previousActionClassName, 'NotifyNullAction')) {
                return;
            }
        }

        $request = $context->getRequest();

        if (!$request instanceof Generic) {
            return;
        }

        if (!$request instanceof GetStatusInterface) {
            return;
        }

        if (
            $request->isCaptured()
            || $request->isCanceled()
            || $request->isFailed()
            || $request->isExpired()
            || $request->isRefunded()
        ) {
            $this->paymentContext->remove(PaymentContext::HIPAY_TOKEN);
            $this->paymentContext->remove(PaymentContext::HIPAY_PAYMENT_PRODUCT);
        }
    }
}
