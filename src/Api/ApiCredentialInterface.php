<?php

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
}
