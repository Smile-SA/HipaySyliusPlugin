<?php

namespace Smile\HipaySyliusPlugin\Registry;

class ApiCredentialRegistry
{
    private iterable $factories;

    public function __construct(iterable $factories)
    {
        $this->factories = $factories;
    }

    /**
     * @param string $gateway
     *
     * @return mixed
     */
    public function getApiConfig(string $gateway)
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($gateway)) {
                return $factory->create();
            }
        }
    }
}
