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

namespace Smile\HipaySyliusPlugin\Oney;

use Sylius\Component\Customer\Model\CustomerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OneyCustomerValidator
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $session)
    {
        $this->validator = $session;
    }

    public function isValid(CustomerInterface $customer): bool
    {
        return $this->validator->validate($customer, null, [
            'sylius',
            'sylius_customer_profile',
            'hipay'
        ])->count() === 0;
    }

    public function getValidationErrors(CustomerInterface $customer): ConstraintViolationListInterface
    {
        return $this->validator->validate($customer, null, [
                'sylius',
                'sylius_customer_profile',
                'hipay'
            ]);
    }
}
