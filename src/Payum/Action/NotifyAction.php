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

namespace Smile\HipaySyliusPlugin\Payum\Action;

use App\Entity\Payment\PaymentSecurityToken;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Smile\HipaySyliusPlugin\Exception\HipayException;
use Smile\HipaySyliusPlugin\Registry\ApiCredentialRegistry;
use Symfony\Component\HttpFoundation\Response;

class NotifyAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    private const HEADERS_NAME_SIGNATURE = 'x-allopass-signature';
    private GetHttpRequest $getHttpRequest;
    private ApiCredentialRegistry $apiCredentialRegistry;

    public function __construct(GetHttpRequest $getHttpRequest, ApiCredentialRegistry $apiCredentialRegistry)
    {
        $this->getHttpRequest = $getHttpRequest;
        $this->apiCredentialRegistry = $apiCredentialRegistry;
    }

    /**
     * {@inheritDoc}
     *
     * @param $request Notify
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($this->getHttpRequest);
        $signature = $this->getHttpRequest->headers[self::HEADERS_NAME_SIGNATURE] ?? null;

        /** @var PaymentSecurityToken $token */
        $token = $request->getToken();
        $gateway = $token->getGatewayName();

        $apiCredentials = $this->apiCredentialRegistry->getApiConfig($gateway);

        if (false == $this->verifySignature($signature, $apiCredentials->getSecretPassphrase())) {
            throw new HttpResponse('The notification is invalid. ', Response::HTTP_BAD_REQUEST);
        }

        throw new HttpResponse('OK', Response::HTTP_OK);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }

    private function verifySignature(string $signature, string $secretPassphrase): bool
    {
        $string2compute = file_get_contents("php://input") . $secretPassphrase;

        return hash($this->getHashAlgorithm($signature), $string2compute) === $signature;
    }

    private function getHashAlgorithm(string $signature): string
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
                throw new HipayException('Unable to determine hash function.');
        }
    }
}
