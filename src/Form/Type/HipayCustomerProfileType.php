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

namespace Smile\HipaySyliusPlugin\Form\Type;

use Sylius\Bundle\CustomerBundle\Form\Type\CustomerProfileType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class HipayCustomerProfileType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'sylius.form.customer.email',
            'disabled' => true,
        ])->add('birthday', BirthdayType::class, [
            'label' => 'sylius.form.customer.birthday',
            'widget' => 'single_text',
            'required' => true,
        ])->add('phoneNumber', TextType::class, [
            'required' => true,
            'label' => 'sylius.form.customer.phone_number',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_hipay_customer_profile';
    }
}
