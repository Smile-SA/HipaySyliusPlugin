<?php

namespace Smile\HipaySyliusPlugin\Builder;

use Smile\HipaySyliusPlugin\Api\ApiCredentialInterface;

interface ApiCredentialBuilderInterface
{
    public function supports(string $gateway): bool;

    public function create(): ApiCredentialInterface;
}
