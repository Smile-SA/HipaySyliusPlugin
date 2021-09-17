<?php

declare(strict_types=1);

namespace Smile\HipaySyliusPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class HipayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('cartAmountMin', IntegerType::class, [
            'required' => false,
            'label' => 'smile_hipay_sylius_plugin.admin.cartAmountMin',
        ]);
        $builder->add('cartAmountMax', IntegerType::class, [
            'required' => false,
            'label' => 'smile_hipay_sylius_plugin.admin.cartAmountMax',
        ]);
    }
}
