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

use HiPay\Fullservice\Gateway\Request\Info\CustomerBillingInfoRequest;
use Sylius\Component\Addressing\Model\AddressInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

class CustomerBillingInfoRequestFactory
{
    public static function createFromBillingAddressAndCustomer(AddressInterface $address, CustomerInterface $customer): CustomerBillingInfoRequest
    {
        $customerBillingInfoRequest = new CustomerBillingInfoRequest();
        $customerBillingInfoRequest->email = $customer->getEmailCanonical();
        $customerBillingInfoRequest->firstname = $address->getFirstName();
        $customerBillingInfoRequest->lastname = $address->getLastName();
        $customerBillingInfoRequest->birthdate = $customer->getBirthday()->format('Ymd');
        $customerBillingInfoRequest->phone = $address->getPhoneNumber() ?: $customer->getPhoneNumber();
        $customerBillingInfoRequest->streetaddress = $address->getStreet();
        $customerBillingInfoRequest->zipcode = $address->getPostcode();
        $customerBillingInfoRequest->city = $address->getCity();
        $customerBillingInfoRequest->country = $address->getCountryCode();

        return $customerBillingInfoRequest;
    }
}