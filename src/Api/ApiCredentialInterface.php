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

interface ApiCredentialInterface
{
    public function getUsername(): string;

    public function setUsername(string $username): void;

    public function getPassword(): string;

    public function setPassword(string $password): void;

    public function getStage(): string;

    public function setStage(string $stage): void;

    public function getLocale(): string;

    public function setLocale(string $locale): void;

    public function getDoRefunds(): bool;

    public function setDoRefunds($doRefunds): void;
}
