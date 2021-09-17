<?php


namespace Smile\HipaySyliusPlugin\Api;


class ApiCredential implements ApiCredentialInterface
{
    private string $username;
    private string $password;
    private string $secretPassphrase;
    private string $stage;
    private string $locale;

    public function __construct(string $username, string $password, string $secretPassphrase, string $stage, string $locale)
    {
        $this->username = $username;
        $this->password = $password;
        $this->secretPassphrase = $secretPassphrase;
        $this->stage = $stage;
        $this->locale = $locale;
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
}
