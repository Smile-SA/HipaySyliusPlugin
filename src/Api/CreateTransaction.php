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

use HiPay\Fullservice\Enum\ThreeDSTwo\DeviceChannel;
use HiPay\Fullservice\Gateway\Client\GatewayClient;
use HiPay\Fullservice\Gateway\Model\Cart\Cart;
use HiPay\Fullservice\Gateway\Model\Cart\Item;
use HiPay\Fullservice\Gateway\Model\Transaction;
use HiPay\Fullservice\Gateway\Request\Info\CustomerBillingInfoRequest;
use HiPay\Fullservice\Gateway\Request\Info\CustomerShippingInfoRequest;
use HiPay\Fullservice\Gateway\Request\Order\OrderRequest;
use HiPay\Fullservice\Gateway\Request\PaymentMethod\CardTokenPaymentMethod;
use HiPay\Fullservice\Gateway\Request\PaymentMethod\XTimesCreditCardPaymentMethod;
use HiPay\Fullservice\HTTP\Configuration\Configuration;
use HiPay\Fullservice\HTTP\SimpleHTTPClient;
use HiPay\Fullservice\Model\AbstractModel;
use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Reply\HttpPostRedirect;
use Smile\HipaySyliusPlugin\Context\PaymentContext;
use Smile\HipaySyliusPlugin\Exception\HipayException;
use Smile\HipaySyliusPlugin\Gateway\GatewayFactoryNameGetterTrait;
use Smile\HipaySyliusPlugin\Oney\OneyCustomerValidator;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayMotoCardGatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney3GatewayFactory;
use Smile\HipaySyliusPlugin\Payum\Factory\HipayOney4GatewayFactory;
use Smile\HipaySyliusPlugin\Registry\ApiCredentialRegistry;
use Sylius\Bundle\PayumBundle\Model\PaymentSecurityTokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Webmozart\Assert\Assert;

/**
 * @see https://github.com/hipay/openapi-hipay/blob/master/enterprise/gateway.yaml
 * for transaction request fields
 */
final class CreateTransaction
{
    use GatewayFactoryNameGetterTrait;

    private const ECI_ECOMMERCE_SSL_TLS = 7;
    private const ECI_MO_TO = 1;

    private const PAYMENT_PRODUCT_3XCB = '3xcb';
    private const PAYMENT_PRODUCT_3XCB_NO_FEES = '3xcb-no-fees';
    private const PAYMENT_PRODUCT_4XCB = '4xcb-no-fees';
    private const PAYMENT_PRODUCT_4XCB_NO_FEES = '4xcb-no-fees';

    private const OPERATION_SALE = 'Sale';

    protected ApiCredentialRegistry $apiCredentialRegistry;
    protected PaymentContext $paymentContext;
    protected ?Request $request;
    protected OneyCustomerValidator $customerValidator;
    protected RouterInterface $router;

    public function __construct(
        ApiCredentialRegistry $apiCredentialRegistry,
        PaymentContext $paymentContext,
        RequestStack $requestStack = null,
        OneyCustomerValidator $customerValidator,
        RouterInterface $router
    )
    {
        $this->apiCredentialRegistry = $apiCredentialRegistry;
        $this->paymentContext = $paymentContext;
        $this->request = $requestStack ? $requestStack->getCurrentRequest() : null;
        $this->customerValidator = $customerValidator;
        $this->router = $router;
    }

    /**
     * @param PaymentInterface $payment
     * @param PaymentSecurityTokenInterface $payumToken
     *
     * @return Transaction
     *
     * @throws \Exception
     */
    public function create(PaymentInterface $payment, GatewayConfigInterface $gatewayConfig, PaymentSecurityTokenInterface $payumToken): Transaction
    {
        $gatewayFactory = $this->getGatewayFactoryName($gatewayConfig);
        $config = $this->getConfiguration($gatewayFactory);
        $clientProvider = new SimpleHTTPClient($config);
        $gatewayClient = new GatewayClient($clientProvider);

        $orderRequest = new OrderRequest();
        $orderRequest->orderid = $payment->getOrder()->getNumber();
        //$orderRequest->cid = $payment->getOrder()->getNumber();
        $orderRequest->payment_product = $this->paymentContext->get(PaymentContext::HIPAY_PAYMENT_PRODUCT);
        $orderRequest->description = 'TODO Description';
        $orderRequest->device_channel = DeviceChannel::BROWSER;
        $orderRequest->operation = self::OPERATION_SALE;
        $orderRequest->currency = $payment->getCurrencyCode();
        $orderRequest->amount = ($payment->getAmount() / 100);
        $orderRequest->shipping = ($payment->getOrder()->getShippingTotal() / 100);
        $orderRequest->tax = $payment->getOrder()->getTaxTotal() > 0 ? $payment->getOrder()->getTaxTotal() / 100 : 0;
        $orderRequest->ipaddr = $this->request->getClientIp();
        $orderRequest->language = $payment->getOrder()->getLocaleCode();
        $orderRequest->notify_url = $this->getNotifyUrl($gatewayConfig);
        $orderRequest->accept_url = $payumToken->getAfterUrl();
        $orderRequest->cancel_url = $payumToken->getAfterUrl();
        $orderRequest->decline_url = $payumToken->getAfterUrl();
        $orderRequest->pending_url = $payumToken->getAfterUrl();

        $paymentMethod = new CardTokenPaymentMethod();
        $paymentMethod->cardtoken = $this->paymentContext->get(PaymentContext::HIPAY_TOKEN);

        // Hipay Credit Card Basic
        if (HipayCardGatewayFactory::FACTORY_NAME === $gatewayFactory) {
            $paymentMethod->eci = self::ECI_ECOMMERCE_SSL_TLS;
            $paymentMethod->authentication_indicator = 1;
        }

        // Hipay Credit Card Mo/TO
        if (HipayMotoCardGatewayFactory::FACTORY_NAME === $gatewayFactory) {
            $paymentMethod->eci = self::ECI_MO_TO;
            $paymentMethod->authentication_indicator = 0;
        }

        $orderRequest->paymentMethod = $paymentMethod;

        return $gatewayClient->requestNewOrder($orderRequest);
    }

    /**
     * @param PaymentInterface $payment
     * @param string $gatewayFactory
     *
     * @param array $gatewayConfig
     * @return Transaction
     *
     * @throws \Exception
     */
    public function createOney(PaymentInterface $payment, GatewayConfigInterface $gatewayConfig, PaymentSecurityTokenInterface $payumToken): Transaction
    {
        $gatewayFactory = $this->getGatewayFactoryName($gatewayConfig);
        $gatewayConfigArray = $gatewayConfig->getConfig();
        $config = $this->getConfiguration($gatewayFactory);

        $clientProvider = new SimpleHTTPClient($config);
        $gatewayClient = new GatewayClient($clientProvider);
        $order = $payment->getOrder();
        Assert::notNull($order, 'Order must not be null');

        $customer = $order->getCustomer();
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();
        Assert::notNull($customer, 'Customer must not be null');
        Assert::notNull($shippingAddress, 'Shipping address must not be null');
        Assert::notNull($billingAddress, 'Billing address not be null');

        if(!$this->customerValidator->isValid($customer))
        {
            $errors = [];
            /** @var ConstraintViolationInterface $error */
            foreach ($this->customerValidator->getValidationErrors($customer) as $error) {
                $errors[] = $error->getMessage();
            }
            throw new HipayException(
                sprintf(
                    'Customer #%d profile is invalid, the following errors occurred: %s',
                    $customer->getId(),
                    implode(', ', $errors)
                )
            );
        }

        $orderRequest = new OrderRequest();
        $orderRequest->eci = self::ECI_ECOMMERCE_SSL_TLS;
        $orderRequest->cid = $customer->getId();
        $orderRequest->device_channel = DeviceChannel::BROWSER;
        $orderRequest->orderid = $order->getNumber();
        $orderRequest->description = 'Order #' . $order->getNumber();// @todo translate
        $orderRequest->operation = self::OPERATION_SALE;
        $orderRequest->currency = $payment->getCurrencyCode();
        $orderRequest->amount = ($payment->getAmount() / 100);
        $orderRequest->shipping = ($payment->getOrder()->getShippingTotal() / 100);
        $orderRequest->tax = $payment->getOrder()->getTaxTotal() > 0 ? $payment->getOrder()->getTaxTotal() / 100 : 0;
        $orderRequest->ipaddr = $this->request->getClientIp();
        $orderRequest->language = $payment->getOrder()->getLocaleCode();
        $orderRequest->notify_url = $this->getNotifyUrl($gatewayConfig);
        $orderRequest->accept_url = $payumToken->getAfterUrl();
        $orderRequest->cancel_url = $payumToken->getAfterUrl();
        $orderRequest->decline_url = $payumToken->getAfterUrl();
        $orderRequest->pending_url = $payumToken->getAfterUrl();

        $customerShippingInfo = new CustomerShippingInfoRequest();
        $customerShippingInfo->shipto_streetaddress = $shippingAddress->getStreet();
        $customerShippingInfo->shipto_streetaddress2 = '';// Not supported by Sylius ATM
        $customerShippingInfo->shipto_zipcode = $shippingAddress->getPostcode();
        $customerShippingInfo->shipto_city = $shippingAddress->getCity();
        $customerShippingInfo->shipto_country = $shippingAddress->getCountryCode();
        $customerShippingInfo->shipto_firstname = $shippingAddress->getFirstName();
        $customerShippingInfo->shipto_lastname = $shippingAddress->getLastName();
        $customerShippingInfo->shipto_phone = $shippingAddress->getPhoneNumber() ?: $customer->getPhoneNumber();
        $customerShippingInfo->shipto_gender = $shippingAddress->getCustomer()
            ? strtoupper( $shippingAddress->getCustomer()->getGender())
            : 'U';

        $customerBillingInfo = new CustomerBillingInfoRequest();
        $customerBillingInfo->email = $customer->getEmailCanonical();
        $customerBillingInfo->firstname = $billingAddress->getFirstName();
        $customerBillingInfo->lastname = $billingAddress->getLastName();
        $customerBillingInfo->birthdate = $customer->getBirthday()->format('Ymd');
        $customerBillingInfo->phone = $billingAddress->getPhoneNumber() ?: $customer->getPhoneNumber();
        $customerBillingInfo->streetaddress = $billingAddress->getStreet();
        $customerBillingInfo->zipcode = $billingAddress->getPostcode();
        $customerBillingInfo->city = $billingAddress->getCity();
        $customerBillingInfo->country = $billingAddress->getCountryCode();

        $orderRequest->customerBillingInfo = $customerBillingInfo;
        $orderRequest->customerShippingInfo = $customerShippingInfo;

        $basket = new Cart();
        $item = new Item();
        /**
         * @todo May we want to be more specific
         * and list all products precisely ?
         * This would be great but its
         * open to discussion with Hipay
         */
        $item->setUnitPrice((float) $order->getTotal()/100);
        $item->setTotalAmount((float) $order->getTotal()/100);
        $item->setName('Order #' . $order->getNumber());// @todo translate
        //$item->setProductCategory(8);
        $item->setQuantity(1);
        $item->setTaxRate(20);// @todo get real tax rate
        $item->setProductReference($order->getNumber());
        $item->setType('good');
        $basket->addItem($item);

        $orderRequest->basket = $basket;

        if (!isset($gatewayConfigArray['fees'])) {
            throw new HipayException('Unable to find fees information for Oney payment Method');
        }
        if ($gatewayFactory === HipayOney3GatewayFactory::FACTORY_NAME) {
            $orderRequest->payment_product =
                (true === $gatewayConfigArray['fees']) ? self::PAYMENT_PRODUCT_3XCB : self::PAYMENT_PRODUCT_3XCB_NO_FEES;
        }

        if ($gatewayFactory === HipayOney4GatewayFactory::FACTORY_NAME) {
            $orderRequest->payment_product =
                (true === $gatewayConfigArray['fees']) ? self::PAYMENT_PRODUCT_4XCB : self::PAYMENT_PRODUCT_4XCB_NO_FEES;
                self::PAYMENT_PRODUCT_4XCB_NO_FEES;
        }

        if (empty($gatewayConfigArray['codeOPC'])) {
            throw new HipayException('Unable to find code OPC for Oney payment Method');
        }

        $orderRequest->payment_product_parameters = [
            'merchant_promotion' => $gatewayConfigArray['codeOPC']
        ];

        $xTimesCreditCardPaymentMethod = new XTimesCreditCardPaymentMethod();
        $xTimesCreditCardPaymentMethod->shipto_gender = strtoupper($customer->getGender());
        $xTimesCreditCardPaymentMethod->shipto_phone = $customer->getPhoneNumber();
        $xTimesCreditCardPaymentMethod->shipto_msisdn = $customer->getPhoneNumber();

        /**
         * @todo Get somehow real carrier information ?
         */
        $xTimesCreditCardPaymentMethod->delivery_method = ['mode' => 'CARRIER', 'shipping' => 'STANDARD'];
        $xTimesCreditCardPaymentMethod->delivery_date = (new \DateTime('+1 week'))->format('Y-m-d');

        $orderRequest->paymentMethod = $xTimesCreditCardPaymentMethod;

        return $gatewayClient->requestNewOrder($orderRequest);
    }

    private function getConfiguration(string $gateway): Configuration
    {
        /** @var \Smile\HipaySyliusPlugin\Api\ApiCredential $apiCredentials */
        $apiCredentials = $this->apiCredentialRegistry->getApiConfig($gateway);

        return new Configuration(
            [
                'apiUsername' => $apiCredentials->getUsername(),
                'apiPassword' => $apiCredentials->getPassword(),
            ]
        );
    }

    private function getNotifyUrl(GatewayConfigInterface $gatewayConfig): ?string
    {
        $gatewayFactory = $this->getGatewayFactoryName($gatewayConfig);
        /** @var \Smile\HipaySyliusPlugin\Api\ApiCredential $apiCredentials */
        $apiCredentials = $this->apiCredentialRegistry->getApiConfig($gatewayFactory);

        return $apiCredentials->getNotifyUrl() ?: $this->getDefaultNotifyUrl($gatewayConfig->getGatewayName());
    }

    private function getDefaultNotifyUrl(string $gatewayFactory): string
    {
        return $this->router->generate(
            'sylius_hipay_plugin_notify_generic',
            ['gateway' => $gatewayFactory],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
