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

class ApiCredential implements ApiCredentialInterface
{
    private string $username;
    private string $password;
    private string $secretPassphrase;
    private string $stage;
    private string $locale;
    private string $notifyUrl;
    private bool $doRefunds;

    public function __construct(
        string $username,
        string $password,
        string $secretPassphrase,
        string $stage,
        string $locale,
        string $notifyUrl,
        bool $doRefunds
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->secretPassphrase = $secretPassphrase;
        $this->stage = $stage;
        $this->locale = $locale;
        $this->notifyUrl = $notifyUrl;
        $this->doRefunds = $doRefunds;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getSecretPassphrase(): string
    {
        return $this->secretPassphrase;
    }

    public function setSecretPassphrase(string $secretPassphrase): void
    {
        $this->secretPassphrase = $secretPassphrase;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function setStage(string $stage): void
    {
        $this->stage = $stage;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getNotifyUrl(): string
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl(string $notifyUrl): void
    {
        $this->notifyUrl = $notifyUrl;
    }

    public function getDoRefunds(): bool
    {
        return $this->doRefunds;
    }

    public function setDoRefunds($doRefunds): void
    {
        $this->doRefunds = $doRefunds;
    }
}
