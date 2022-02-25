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

namespace Smile\HipaySyliusPlugin\Api;

use Exception;
use HiPay\Fullservice\Gateway\Client\GatewayClient;
use HiPay\Fullservice\Gateway\Model\Transaction;
use HiPay\Fullservice\HTTP\Configuration\Configuration;
use HiPay\Fullservice\HTTP\SimpleHTTPClient;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Security\TokenInterface;
use Smile\HipaySyliusPlugin\Context\PaymentContext;
use Smile\HipaySyliusPlugin\Factory\OrderRequestFactory;
use Smile\HipaySyliusPlugin\Gateway\GatewayFactoryNameGetterTrait;
use Smile\HipaySyliusPlugin\Oney\OneyCustomerValidator;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayMotoCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney3GatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney4GatewayFactory;
use Smile\HipaySyliusPlugin\Registry\ApiCredentialRegistry;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @see https://github.com/hipay/openapi-hipay/blob/master/enterprise/gateway.yaml
 * for transaction request fields
 */
final class CreateTransaction
{
    use GatewayFactoryNameGetterTrait;

    protected ApiCredentialRegistry $apiCredentialRegistry;
    protected PaymentContext $paymentContext;
    protected OneyCustomerValidator $customerValidator;

    public function __construct(
        ApiCredentialRegistry $apiCredentialRegistry,
        PaymentContext $paymentContext,
        OneyCustomerValidator $customerValidator
    ) {
        $this->apiCredentialRegistry = $apiCredentialRegistry;
        $this->paymentContext = $paymentContext;
        $this->customerValidator = $customerValidator;
    }

    /**
     * @param PaymentInterface              $payment
     * @param PaymentSecurityTokenInterface $payumToken
     * @param TokenInterface                $notifyToken
     *
     * @return Transaction
     * @throws Exception
     */
    public function create(
        PaymentInterface $payment,
        TokenInterface $payumToken,
        TokenInterface $notifyToken
    ): Transaction {
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();
        /** @var GatewayConfigInterface $gatewayConfig */
        $gatewayConfig = $paymentMethod->getGatewayConfig();
        $gatewayConfigArray = $gatewayConfig->getConfig();
        $gatewayFactoryName = $this->getGatewayFactoryName($gatewayConfig);
        $gatewayClient = new GatewayClient(
            new SimpleHTTPClient($this->getHttpConfiguration($gatewayFactoryName))
        );
        $orderRequest = null;
        $paymentProduct = null;

        // Hipay Credit Card Basic
        if (HipayCardGatewayFactory::FACTORY_NAME === $gatewayFactoryName) {
            $orderRequest = OrderRequestFactory::createForCreditCardBasicPayment(
                $payment,
                $this->paymentContext->get(PaymentContext::HIPAY_PAYMENT_PRODUCT),
                $this->paymentContext->get(PaymentContext::HIPAY_TOKEN)
            );
        }

        // Hipay Credit Card Mo/TO
        if (HipayMotoCardGatewayFactory::FACTORY_NAME === $gatewayFactoryName) {
            $orderRequest = OrderRequestFactory::createForCreditCardMotoPayment(
                $payment,
                $this->paymentContext->get(PaymentContext::HIPAY_PAYMENT_PRODUCT),
                $this->paymentContext->get(PaymentContext::HIPAY_TOKEN)
            );
        }

        // Oney 3x/4x payments
        if (HipayOney3GatewayFactory::FACTORY_NAME === $gatewayFactoryName || HipayOney4GatewayFactory::FACTORY_NAME === $gatewayFactoryName) {
            if (!isset($gatewayConfigArray['fees'])) {
                throw new InvalidArgumentException('Unable to find fees information for Oney payment Method');
            }
            if (empty($gatewayConfigArray['codeOPC'])) {
                throw new InvalidArgumentException('Unable to find code OPC for Oney payment Method');
            }

            $customer = $payment->getOrder()->getCustomer();
            if (!$this->customerValidator->isValid($customer)) {
                $errors = [];
                /** @var ConstraintViolationInterface $error */
                foreach ($this->customerValidator->getValidationErrors($customer) as $error) {
                    $errors[] = $error->getMessage();
                }
                throw new InvalidArgumentException(
                    sprintf(
                        'Customer #%d profile is invalid, the following errors occurred: %s',
                        $customer->getId(),
                        implode(', ', $errors)
                    )
                );
            }

            if (HipayOney3GatewayFactory::FACTORY_NAME === $gatewayFactoryName) {
                $paymentProduct = (true === $gatewayConfigArray['fees']) ? OrderRequestFactory::PAYMENT_PRODUCT_3XCB : OrderRequestFactory::PAYMENT_PRODUCT_3XCB_NO_FEES;
            }

            if (HipayOney4GatewayFactory::FACTORY_NAME === $gatewayFactoryName) {
                $paymentProduct = (true === $gatewayConfigArray['fees']) ? OrderRequestFactory::PAYMENT_PRODUCT_4XCB : OrderRequestFactory::PAYMENT_PRODUCT_4XCB_NO_FEES;
            }

            $orderRequest = OrderRequestFactory::createForOneyPayment(
                $payment,
                $paymentProduct,
                $gatewayConfigArray['codeOPC']
            );
        }

        if (null === $orderRequest) {
            throw new InvalidArgumentException(sprintf('Unknown factory for transaction creation (%s)', $gatewayFactoryName));
        }

        // Set the callback urls
        $orderRequest->notify_url = $notifyToken->getTargetUrl();
        $orderRequest->accept_url = $payumToken->getAfterUrl();
        $orderRequest->cancel_url = $payumToken->getAfterUrl();
        $orderRequest->decline_url = $payumToken->getAfterUrl();
        $orderRequest->pending_url = $payumToken->getAfterUrl();

        return $gatewayClient->requestNewOrder($orderRequest);
    }

    private function getHttpConfiguration(string $gatewayFactoryName): Configuration
    {
        /** @var ApiCredential $apiCredentials */
        $apiCredentials = $this->apiCredentialRegistry->getApiConfig($gatewayFactoryName);

        return new Configuration([
            'apiUsername' => $apiCredentials->getUsername(),
            'apiPassword' => $apiCredentials->getPassword(),
        ]);
    }
}
