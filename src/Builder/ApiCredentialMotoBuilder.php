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
    private string $notifyUrl;

    public function __construct(
        string $apiUsername,
        string $apiPassword,
        string $secretPassphrase,
        string $stage,
        string $locale,
        string $notifyUrl
    ) {
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
        $this->stage = $stage;
        $this->locale = $locale;
        $this->secretPassphrase = $secretPassphrase;
        $this->notifyUrl = $notifyUrl;
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
            $this->locale,
            $this->notifyUrl
        );
    }
}
