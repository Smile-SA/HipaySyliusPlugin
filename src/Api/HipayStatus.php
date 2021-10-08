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

namespace Smile\HipaySyliusPlugin\Api;

use HiPay\Fullservice\Enum\Transaction\TransactionStatus;

class HipayStatus
{

    public const CODE_STATUS_BLOCKED            = TransactionStatus::BLOCKED;

    public const CODE_STATUS_DENIED             = TransactionStatus::DENIED;

    public const CODE_STATUS_AUTHORIZED_PENDING = TransactionStatus::AUTHORIZED_AND_PENDING;

    public const CODE_STATUS_REFUSED            = TransactionStatus::REFUND_REFUSED;

    public const CODE_STATUS_EXPIRED            = TransactionStatus::EXPIRED;

    public const CODE_STATUS_CANCELLED          = TransactionStatus::CANCELLED;

    public const CODE_STATUS_CAPTURED           = TransactionStatus::CAPTURED;

    public const CODE_STATUS_PENDING            = TransactionStatus::PENDING_PAYMENT;

    public const CODE_STATUS_PARTIALLY_REFUNDED = TransactionStatus::PARTIALLY_REFUNDED;

    public const CODE_STATUS_REFUNDED           = TransactionStatus::REFUNDED;
}
