<?php

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
        $headers = $this->getHttpRequest->headers[self::HEADERS_NAME_SIGNATURE] ?? null;

        /** @var PaymentSecurityToken $token */
        $token = $request->getToken();
        $gateway = $token->getGatewayName();

        $apiCredentials = $this->apiCredentialRegistry->getApiConfig($gateway);

        if (false == $this->verifySignature($headers, $apiCredentials->getSecretPassphrase())) {
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
        $string2compute = file_get_contents("php://input"). $secretPassphrase;
        $computedSignature = sha1($string2compute);

        return $computedSignature === $signature;
    }
}
