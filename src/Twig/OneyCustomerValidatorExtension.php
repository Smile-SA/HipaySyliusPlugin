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

namespace Smile\HipaySyliusPlugin\Twig;

use Smile\HipaySyliusPlugin\Oney\OneyCustomerValidator;
use Sylius\Component\Customer\Model\CustomerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class OneyCustomerValidatorExtension extends AbstractExtension
{
    protected OneyCustomerValidator $customerValidator;

    public function __construct(OneyCustomerValidator $customerValidator)
    {
        $this->customerValidator = $customerValidator;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('isOneyCustomerValid', [$this, 'isOneyCustomerValid']),
        ];
    }

    public function isOneyCustomerValid(CustomerInterface $customer): bool
    {
        return $this->customerValidator->isValid($customer);
    }
}
