<?php

namespace Smile\HipaySyliusPlugin\Builder;

use Smile\HipaySyliusPlugin\Api\ApiCredential;
use Smile\HipaySyliusPlugin\Api\ApiCredentialInterface;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayMotoCardGatewayFactory;

class ApiCredentialMotoBuilder implements ApiCredentialBuilderInterface
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
        $this->stage = $stage;
        $this->locale = $locale;
        $this->secretPassphrase = $secretPassphrase;
    }

    public function supports(string $gateway): bool
    {
        return $gateway === HipayMotoCardGatewayFactory::FACTORY_NAME;
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