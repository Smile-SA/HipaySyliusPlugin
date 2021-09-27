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

class HipayStatus
{
    public const CODE_STATUS_BLOCKED = '110';
    public const CODE_STATUS_DENIED = '111';
    public const CODE_STATUS_AUTHORIZED_PENDING = '112';
    public const CODE_STATUS_REFUSED = '113';
    public const CODE_STATUS_EXPIRED = '114';
    public const CODE_STATUS_CANCELLED = '115';
    public const CODE_STATUS_CAPTURED = '118';
    public const CODE_STATUS_PENDING = '200';
}
