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

use HiPay\Fullservice\Enum\ThreeDSTwo\DeviceChannel;
use HiPay\Fullservice\Gateway\Model\Cart\Cart;
use HiPay\Fullservice\Gateway\Request\Order\OrderRequest;
use Sylius\Component\Core\Model\PaymentInterface;

class OrderRequestFactory
{
    public const PAYMENT_PRODUCT_3XCB = '3xcb';
    public const PAYMENT_PRODUCT_3XCB_NO_FEES = '3xcb-no-fees';
    public const PAYMENT_PRODUCT_4XCB = '4xcb-no-fees';
    public const PAYMENT_PRODUCT_4XCB_NO_FEES = '4xcb-no-fees';

    private const OPERATION_SALE = 'Sale';

    public static function createFromPayment(PaymentInterface $payment, string $paymentProduct): OrderRequest
    {
        $orderRequest = new OrderRequest();
        $orderRequest->orderid = $payment->getOrder()->getNumber();
        $orderRequest->cid = $payment->getOrder()->getCustomer()->getId();
        $orderRequest->payment_product = $paymentProduct;
        $orderRequest->description = 'Order #' . $payment->getOrder()->getNumber();
        $orderRequest->device_channel = DeviceChannel::BROWSER;
        $orderRequest->operation = self::OPERATION_SALE;
        $orderRequest->currency = $payment->getCurrencyCode();
        $orderRequest->amount = ($payment->getAmount() / 100);
        $orderRequest->shipping = ($payment->getOrder()->getShippingTotal() / 100);
        $orderRequest->tax = ($payment->getOrder()->getTaxTotal() / 100);
        $orderRequest->ipaddr = $payment->getOrder()->getCustomerIp();
        $orderRequest->language = $payment->getOrder()->getLocaleCode();
        $orderRequest->customerShippingInfo = CustomerShippingInfoRequestFactory::createFromShippingAddressAndCustomer(
            $payment->getOrder()->getShippingAddress(),
            $payment->getOrder()->getCustomer()
        );
        $orderRequest->customerBillingInfo = CustomerBillingInfoRequestFactory::createFromBillingAddressAndCustomer(
            $payment->getOrder()->getBillingAddress(),
            $payment->getOrder()->getCustomer()
        );
        $basket = new Cart();
        foreach ($payment->getOrder()->getItems() as $orderItem) {
            $basket->addItem(ItemFactory::createFromOrderItem($orderItem));
        }
        $orderRequest->basket = $basket;

        return $orderRequest;
    }

    public static function createForCreditCardBasicPayment(PaymentInterface $payment, string $paymentProduct, string $cardToken): OrderRequest
    {
        $orderRequest = self::createFromPayment($payment, $paymentProduct);
        $orderRequest->paymentMethod = CardTokenPaymentMethodFactory::createForCreditCardBasicPayment($cardToken);

        return $orderRequest;
    }

    public static function createForCreditCardMoToPayment(PaymentInterface $payment, string $paymentProduct, string $cardToken): OrderRequest
    {
        $orderRequest = self::createFromPayment($payment, $paymentProduct);
        $orderRequest->paymentMethod = CardTokenPaymentMethodFactory::createForCreditCardMoToPayment($cardToken);

        return $orderRequest;
    }

    public static function createForOneyPayment(PaymentInterface $payment, string $paymentProduct, string $codeOPC): OrderRequest
    {
        $orderRequest = self::createFromPayment($payment, $paymentProduct);
        $orderRequest->paymentMethod = XTimesCreditCardPaymentMethodFactory::createFromPayment($payment);
        $orderRequest->payment_product_parameters = ['merchant_promotion' => $codeOPC];

        return $orderRequest;
    }
}
