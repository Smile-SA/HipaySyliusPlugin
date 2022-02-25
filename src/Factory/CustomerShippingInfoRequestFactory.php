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

use HiPay\Fullservice\Gateway\Request\Info\CustomerShippingInfoRequest;
use Sylius\Component\Addressing\Model\AddressInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

class CustomerShippingInfoRequestFactory
{
    public static function createFromShippingAddressAndCustomer(AddressInterface $address, CustomerInterface $customer): CustomerShippingInfoRequest
    {
        $customerShippingInfoRequest = new CustomerShippingInfoRequest();
        $customerShippingInfoRequest->shipto_streetaddress = $address->getStreet();
        $customerShippingInfoRequest->shipto_streetaddress2 = '';// Not supported by Sylius ATM
        $customerShippingInfoRequest->shipto_zipcode = $address->getPostcode();
        $customerShippingInfoRequest->shipto_city = $address->getCity();
        $customerShippingInfoRequest->shipto_country = $address->getCountryCode();
        $customerShippingInfoRequest->shipto_firstname = $address->getFirstName();
        $customerShippingInfoRequest->shipto_lastname = $address->getLastName();
        $customerShippingInfoRequest->shipto_phone = $address->getPhoneNumber() ?: $customer->getPhoneNumber();
        $customerShippingInfoRequest->shipto_gender = strtoupper($customer->getGender());

        return $customerShippingInfoRequest;
    }
}