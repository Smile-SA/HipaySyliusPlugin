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

use HiPay\Fullservice\Enum\Transaction\ECI;
use HiPay\Fullservice\Gateway\Request\PaymentMethod\CardTokenPaymentMethod;

class CardTokenPaymentMethodFactory
{
    private const THREEDS_BYPASS = 0; // Bypass 3-D Secure authentication
    private const THREEDS_IF_AVAILABLE = 1; // 3-D Secure authentication if available
    private const THREEDS_MANDATORY = 2; // 3-D Secure authentication mandatory

    public static function createFromToken(string $cardToken): CardTokenPaymentMethod
    {
        $cardTokenPaymentMethod = new CardTokenPaymentMethod();
        $cardTokenPaymentMethod->cardtoken = $cardToken;

        return $cardTokenPaymentMethod;
    }

    public static function createForCreditCardBasicPayment(string $cardToken): CardTokenPaymentMethod
    {
        $cardTokenPaymentMethod = self::createFromToken($cardToken);
        $cardTokenPaymentMethod->eci = ECI::SECURE_ECOMMERCE;
        $cardTokenPaymentMethod->authentication_indicator = self::THREEDS_IF_AVAILABLE;

        return $cardTokenPaymentMethod;
    }

    public static function createForCreditCardMoToPayment(string $cardToken): CardTokenPaymentMethod
    {
        $cardTokenPaymentMethod = self::createFromToken($cardToken);
        $cardTokenPaymentMethod->eci = ECI::MOTO;
        $cardTokenPaymentMethod->authentication_indicator = self::THREEDS_BYPASS;

        return $cardTokenPaymentMethod;
    }
}
