<?php

namespace Smile\HipaySyliusPlugin\Api;

use HiPay\Fullservice\Gateway\Client\GatewayClient;
use HiPay\Fullservice\Gateway\Model\Transaction;
use HiPay\Fullservice\Gateway\Request\Order\OrderRequest;
use HiPay\Fullservice\Gateway\Request\PaymentMethod\CardTokenPaymentMethod;
use HiPay\Fullservice\Gateway\Request\PaymentMethod\XTimesCreditCardPaymentMethod;
use HiPay\Fullservice\HTTP\Configuration\Configuration;
use HiPay\Fullservice\HTTP\SimpleHTTPClient;
use HiPay\Fullservice\Model\AbstractModel;
use Smile\HipaySyliusPlugin\Context\PaymentContext;
use Smile\HipaySyliusPlugin\Exception\HipayException;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayMotoCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney3GatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney4GatewayFactory;
use Smile\HipaySyliusPlugin\Registry\ApiCredentialRegistry;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;

final class CreateTransaction
{
    private const ECI_ECOMMERCE_SSL_TLS = 7;
    private const ECI_MO_TO = 1;

    private const PAYMENT_PRODUCT_3XCB = '3xcb';
    private const PAYMENT_PRODUCT_3XCB_NO_FEES = '3xcb-no-fees';
    private const PAYMENT_PRODUCT_4XCB = '4xcb-no-fees';
    private const PAYMENT_PRODUCT_4XCB_NO_FEES = '4xcb-no-fees';

    private const OPERATION_SALE = 'Sale';

    private ApiCredentialRegistry $apiCredentialRegistry;
    private PaymentContext $paymentContext;

    public function __construct(ApiCredentialRegistry $apiCredentialRegistry, PaymentContext $paymentContext)
    {
        $this->apiCredentialRegistry = $apiCredentialRegistry;
        $this->paymentContext = $paymentContext;
    }

    /**
     * @param PaymentInterface $payment
     * @param PaymentSecurityTokenInterface $payumToken
     *
     * @return Transaction|AbstractModel
     *
     * @throws \Exception
     */
    public function create(PaymentInterface $payment, PaymentSecurityTokenInterface $payumToken)
    {
        $gateway = $payumToken->getGatewayName();

        $config = $this->getConfiguration($gateway);
        $clientProvider = new SimpleHTTPClient($config);
        $gatewayClient = new GatewayClient($clientProvider);

        $orderRequest = new OrderRequest();
        $orderRequest->orderid = $payment->getOrder()->getId();
        $orderRequest->payment_product = $this->paymentContext->get(PaymentContext::HIPAY_PAYMENT_PRODUCT);
        $orderRequest->description = 'TODO Description';
        $orderRequest->operation = self::OPERATION_SALE;
        $orderRequest->currency = $payment->getCurrencyCode();
        $orderRequest->amount = ($payment->getAmount() / 100);
        $orderRequest->shipping = ($payment->getOrder()->getShippingTotal() / 100);
        $orderRequest->tax = $payment->getOrder()->getTaxTotal();
        $orderRequest->ipaddr = '127.0.0.1';
        $orderRequest->language = $payment->getOrder()->getLocaleCode();
        $orderRequest->notify_url = $payumToken->getAfterUrl();
        $orderRequest->accept_url = $payumToken->getAfterUrl();
        $orderRequest->cancel_url = $payumToken->getAfterUrl();
        $orderRequest->decline_url = $payumToken->getAfterUrl();
        $orderRequest->pending_url = $payumToken->getAfterUrl();


        $paymentMethod = new CardTokenPaymentMethod();
        $paymentMethod->cardtoken = $this->paymentContext->get(PaymentContext::HIPAY_TOKEN);

        // Hipay Credit Card Basic
        if (HipayCardGatewayFactory::FACTORY_NAME === $gateway) {
            $paymentMethod->eci = self::ECI_ECOMMERCE_SSL_TLS;
            $paymentMethod->authentication_indicator = 1;
        }

        // Hipay Credit Card Mo/TO
        if (HipayMotoCardGatewayFactory::FACTORY_NAME === $gateway) {
            $paymentMethod->eci = self::ECI_MO_TO;
            $paymentMethod->authentication_indicator = 0;
        }

        $orderRequest->paymentMethod = $paymentMethod;

        return $gatewayClient->requestNewOrder($orderRequest);
    }

    /**
     * @param PaymentInterface $payment
     * @param string $gateway
     *
     * @param array $gatewayConfig
     * @return Transaction|AbstractModel
     *
     * @throws \Exception
     */
    public function createOney(PaymentInterface $payment, string $gateway, array $gatewayConfig)
    {
        $config = $this->getConfiguration($gateway);
        $clientProvider = new SimpleHTTPClient($config);
        $gatewayClient = new GatewayClient($clientProvider);

        $orderRequest = new OrderRequest();
        $orderRequest->orderid = $payment->getOrder()->getId();
        if (!isset($gatewayConfig['fees'])) {
            throw new HipayException('Unable to find fees information for Oney payment Method');
        }
        if ($gateway === HipayOney3GatewayFactory::FACTORY_NAME) {
            $orderRequest->payment_product =
                (true === $gatewayConfig['fees']) ? self::PAYMENT_PRODUCT_3XCB : self::PAYMENT_PRODUCT_3XCB_NO_FEES;
        }

        if ($gateway === HipayOney4GatewayFactory::FACTORY_NAME) {
            $orderRequest->payment_product =
                (true === $gatewayConfig['fees']) ? self::PAYMENT_PRODUCT_4XCB : self::PAYMENT_PRODUCT_4XCB_NO_FEES;
                self::PAYMENT_PRODUCT_4XCB_NO_FEES;
        }

        $orderRequest->description = 'TODO Description';
        $orderRequest->operation = self::OPERATION_SALE;
        $orderRequest->currency = $payment->getCurrencyCode();
        $orderRequest->amount = ($payment->getAmount() / 100);
        $orderRequest->shipping = ($payment->getOrder()->getShippingTotal() / 100);
        $orderRequest->tax = $payment->getOrder()->getTaxTotal();
        $orderRequest->ipaddr = '127.0.0.1';
        $orderRequest->language = $payment->getOrder()->getLocaleCode();
        if (empty($gatewayConfig['codeOPC'])) {
            throw new HipayException('Unable to find code OPC for Oney payment Method');
        }
        $orderRequest->payment_product_parameters = json_encode(['merchant_promotion' => $gatewayConfig['codeOPC']]);

        $xTimesCreditCardPaymentMethod = new XTimesCreditCardPaymentMethod();
        $xTimesCreditCardPaymentMethod->shipto_gender = 'U';
        $xTimesCreditCardPaymentMethod->shipto_phone = '0659595959';
        $xTimesCreditCardPaymentMethod->shipto_msisdn = '0659595959';

        $orderRequest->paymentMethod = $xTimesCreditCardPaymentMethod;

        return $gatewayClient->requestNewOrder($orderRequest);
    }

    private function getConfiguration(string $gateway): Configuration
    {
        $apiCredentials = $this->apiCredentialRegistry->getApiConfig($gateway);
        return new Configuration(
            ['apiUsername' => $apiCredentials->getUsername(), 'apiPassword' => $apiCredentials->getPassword()]
        );
    }
}
