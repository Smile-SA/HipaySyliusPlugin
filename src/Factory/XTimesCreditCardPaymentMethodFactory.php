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

use DateTime;
use HiPay\Fullservice\Gateway\Request\PaymentMethod\XTimesCreditCardPaymentMethod;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

class XTimesCreditCardPaymentMethodFactory
{
    public static function createFromPayment(PaymentInterface $payment): XTimesCreditCardPaymentMethod
    {
        /** @var CustomerInterface $customer */
        $customer = $payment->getOrder()->getCustomer();
        $xTimesCreditCardPaymentMethod = new XTimesCreditCardPaymentMethod();
        $xTimesCreditCardPaymentMethod->shipto_gender = strtoupper($customer->getGender());
        $xTimesCreditCardPaymentMethod->shipto_phone = $customer->getPhoneNumber();
        $xTimesCreditCardPaymentMethod->shipto_msisdn = $customer->getPhoneNumber();

        /**
         * @todo Get somehow real carrier information ?
         */
        $xTimesCreditCardPaymentMethod->delivery_method = ['mode' => 'CARRIER', 'shipping' => 'STANDARD'];
        $xTimesCreditCardPaymentMethod->delivery_date = (new DateTime('+1 week'))->format('Y-m-d');

        return $xTimesCreditCardPaymentMethod;
    }
}
