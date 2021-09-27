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

class ApiOneyConfig
{
    private string $codeOPC;
    private ?bool $fees;
    private ?int $cartAmountMin;
    private ?int $cartAmountMax;

    public function __construct(string $codeOPC, ?bool $fees, ?int $cartAmountMin, ?int $cartAmountMax)
    {
        $this->codeOPC = $codeOPC;
        $this->fees = $fees;
        $this->cartAmountMin = $cartAmountMin;
        $this->cartAmountMax = $cartAmountMax;
    }

    public function getCodeOPC(): string
    {
        return $this->codeOPC;
    }

    public function setCodeOPC(string $codeOPC): void
    {
        $this->codeOPC = $codeOPC;
    }

    public function getFees(): ?bool
    {
        return $this->fees;
    }

    public function setFees(?bool $fees): void
    {
        $this->fees = $fees;
    }

    public function getCartAmountMin(): ?int
    {
        return $this->cartAmountMin;
    }

    public function setCartAmountMin(?int $cartAmountMin): void
    {
        $this->cartAmountMin = $cartAmountMin;
    }

    public function getCartAmountMax(): ?int
    {
        return $this->cartAmountMax;
    }

    public function setCartAmountMax(?int $cartAmountMax): void
    {
        $this->cartAmountMax = $cartAmountMax;
    }


}
