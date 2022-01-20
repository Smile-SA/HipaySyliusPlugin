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
    private string $notifyUrl;
    private bool $doRefunds;

    public function __construct(
        string $apiUsername,
        string $apiPassword,
        string $secretPassphrase,
        string $stage,
        string $locale,
        string $notifyUrl,
        bool $doRefunds
    ) {
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;
        $this->secretPassphrase = $secretPassphrase;
        $this->stage = $stage;
        $this->locale = $locale;
        $this->notifyUrl = $notifyUrl;
        $this->doRefunds = $doRefunds;
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
            $this->locale,
            $this->notifyUrl,
            $this->doRefunds
        );
    }
}
