<?php

namespace Smile\HipaySyliusPlugin\Builder;

use Smile\HipaySyliusPlugin\Api\ApiCredential;
use Smile\HipaySyliusPlugin\Api\ApiCredentialInterface;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney3GatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney4GatewayFactory;

class ApiCredentialBuilder implements ApiCredentialBuilderInterface
{
    private string $apiUsername;
    private string $apiPassword;
    private string $secretPassphrase;
    private string $stage;
    private string $locale;

    public function __construct(
        string $apiUsername,
        string $apiPassword,
        string $secretPassphrase,
        string $stage,
        string $locale
    ) {
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
        $this->secretPassphrase = $secretPassphrase;
        $this->stage = $stage;
        $this->locale = $locale;
    }

    public function supports(string $gateway): bool
    {
        return in_array(
            $gateway,
            [
                HipayCardGatewayFactory::FACTORY_NAME,
                HipayOney3GatewayFactory::FACTORY_NAME,
                HipayOney4GatewayFactory::FACTORY_NAME,
            ]
        );
    }

    public function create(): ApiCredentialInterface
    {
        return new ApiCredential(
            $this->apiUsername,
            $this->apiPassword,
            $this->secretPassphrase,
            $this->stage,
            $this->locale
        );
    }
}
