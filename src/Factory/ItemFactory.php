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

use HiPay\Fullservice\Enum\Cart\TypeItems;
use HiPay\Fullservice\Gateway\Model\Cart\Item;
use Sylius\Component\Core\Model\OrderItemInterface;

class ItemFactory
{
    // None of the Item static methods are satisfying
    public static function createFromOrderItem(OrderItemInterface $orderItem): Item
    {
        $item = new Item();
        $item->setUnitPrice((float) $orderItem->getUnitPrice() / 100);
        $item->setTotalAmount((float) $orderItem->getTotal() / 100);
        $item->setName($orderItem->getVariant()->getName());
        $item->setQuantity($orderItem->getQuantity());
        $item->setTaxRate(
            round(100.0 * ($orderItem->getTaxTotal() / $orderItem->getTotal()), 2)
        );
        $item->setProductReference($orderItem->getVariant()->getCode());
        $item->setType(TypeItems::GOOD);

        return $item;
    }
}