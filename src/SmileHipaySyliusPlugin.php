<?php

namespace Smile\HipaySyliusPlugin;

use Smile\HipaySyliusPlugin\DependencyInjection\SmileHipaySyliusPluginExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SmileHipaySyliusPlugin extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SmileHipaySyliusPluginExtension();
    }
}

