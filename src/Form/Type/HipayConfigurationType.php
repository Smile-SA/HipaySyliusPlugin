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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
