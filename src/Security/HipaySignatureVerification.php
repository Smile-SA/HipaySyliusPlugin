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

namespace Smile\HipaySyliusPlugin\Security;

use Payum\Core\GatewayFactory;
use Payum\Core\GatewayInterface;
use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Request\GetHttpRequest;
use Smile\HipaySyliusPlugin\Api\ApiCredentialInterface;
use Smile\HipaySyliusPlugin\Exception\HipayException;
use Smile\HipaySyliusPlugin\Gateway\GatewayFactoryNameGetterTrait;
use Smile\HipaySyliusPlugin\Registry\ApiCredentialRegistry;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class HipaySignatureVerification
{
    use GatewayFactoryNameGetterTrait;

    public const HEADERS_NAME_SIGNATURE = 'x-allopass-signature';
    public const QUERY_NAME_SIGNATURE = 'hash';

    private ApiCredentialRegistry $apiCredentialRegistry;
    protected ?Request $request;
    private PaymentMethodRepositoryInterface $paymentMethodRepository;

    public function __construct(
        ApiCredentialRegistry $apiCredentialRegistry,
        RequestStack $requestStack = null,
        PaymentMethodRepositoryInterface $paymentMethodRepository
    ){
        $this->apiCredentialRegistry = $apiCredentialRegistry;
        $this->request = $requestStack ? $requestStack->getCurrentRequest() : null;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function isAnHipayRequest()
    {
        return
            (
                $this->request->query->has('state')
                && $this->request->query->has('amount')
                && $this->request->query->has('hash')
            )
            ||
            (
                $this->request->request->has('amount')
                && $this->request->request->has('amount')
                && $this->request->headers->has(self::QUERY_NAME_SIGNATURE)
            );
    }

    public function verifyHttpRequest(?string $gatewayName = null, ?string $signature = null): bool
    {
        if($gatewayName === null){
            $gatewayName = $this->request->get('gateway');
            if($gatewayName === null){
                throw new HipayException('Unable to determine the gameway used');
            }
        }

        if($signature === null){
            $signature = $signature = $this->request->headers->get(self::HEADERS_NAME_SIGNATURE);
            if($signature === null){
                $signature = $signature = $this->request->query->get(self::QUERY_NAME_SIGNATURE);
                if($signature === null){
                    throw new HipayException('Unable to determine the signature used');
                }
            }
        }

        /** @var PaymentMethodInterface $gatewayFactory */
        $gatewayFactory = $this->paymentMethodRepository->findOneByCode($gatewayName);
        $apiCredentials = $this->apiCredentialRegistry->getApiConfig(
            $this->getGatewayFactoryName($gatewayFactory->getGatewayConfig())
        );

        $method = strtoupper($this->request->getMethod());
        $hashAlgo = self::getHashAlgorithm($signature);

        if($method === 'POST'){
            $string2Compute = $this->request->getContent() . $apiCredentials->getSecretPassphrase();
            return \hash($hashAlgo, $string2Compute) === $signature;
        } else if ($method === 'GET'){
            $string2Compute = $this->buildGetQueryString2Compute($this->request->query->all(), $apiCredentials->getSecretPassphrase());

            return \hash($hashAlgo, $string2Compute) === $signature;
        }

        return false;
    }

    protected function buildGetQueryString2Compute(array $parameters, string $passphrase): string
    {
        // @see https://developer.hipay.com/payment-fundamentals/requirements/signature-verification
        unset($parameters[self::QUERY_NAME_SIGNATURE]);
        unset($parameters['payum_token']);
        ksort($parameters);
        $string2Compute = '';

        foreach ($parameters as $key => $parameter) {
            if($parameter !== ''){
                // @todo custom_data special treatment
                $string2Compute .= $key . $parameter . $passphrase;
            }
        }

        return $string2Compute;
    }

    protected static function getHashAlgorithm(string $signature): string
    {
        /**
         * You can fin the hash algorithm setting here:
         * https://stage-merchant.hipay-tpp.com/maccount/ACCOUNT_ID/settings/security
         * Hipay actually supports: SHA-1, SHA-256, SHA-512
         */
        switch (\strlen($signature))
        {
            case 40:
                return 'sha1';
                break;
            case 64:
                return 'sha256';
                break;
            case 128:
                return 'sha512';
                break;
            default:
                throw new HipayException('Unable to determine hash algorithm.');
        }
    }
}
