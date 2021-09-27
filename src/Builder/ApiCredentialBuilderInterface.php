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

namespace Smile\HipaySyliusPlugin\Builder;

use Smile\HipaySyliusPlugin\Api\ApiCredentialInterface;

interface ApiCredentialBuilderInterface
{
    public function supports(string $gateway): bool;

    public function create(): ApiCredentialInterface;
}
