This plugin is designed to add a new gateway on Sylius with Payum to support Hipay payment.

## Installation 

```bash
$ composer require smile/hipay-sylius-plugin
```

Enable it in your application Kernel.

```php
<?php
// config/bundles.php
return [
    //...
   Smile\HipaySyliusPlugin\SmileHipaySyliusPlugin::class => ['dev' => true, 'test' => true],
];
```

Import routing

```yaml
# config/routes.yaml

sylius_hipay_routing:
    resource: "@SmileHipaySyliusPlugin/config/routing.yaml"
```

Configure your credentials.
In the first node called `api`, you must fill in the main account credentials.
In the second node called `api_moto`, you must fill in the Mo/To account credentials.

```yaml
# config/rpackages/sylius_hipay.yaml

sylius_hipay:
    # The "api" key works for credit cards (as hosted field) but also for paiement 3x/4x
    api:
        api_private_username: 'Username for api'
        api_private_password: 'Password for api'
        api_secret_passphrase: 'Secret Passphrase for api'
        stage: 'stage or prod'
        locale: 'fr'
        # notify_url: '' # Optional; for development purpose, e.g a link to a requestbin listener.

    # The "api_moto" key is a dedicated creddential for Mo/To paiements
    api_moto:
        api_private_username: 'Username for api Mo/TO'
        api_private_password: 'Password for api Mo/TO'
        api_secret_passphrase: 'Secret Passphrase for api Mo/TO'
        stage: 'stage or prod'
        locale: 'fr'
        # notify_url: '' # Optional; for development purpose, e.g a link to a requestbin listener.
```

## Configuration

Override twig file

Add block javascripts at the end of file after your others overrides blocks
```twig
# templates/bundles/SyliusShopBundle/Order/show.html.twig

{% block javascripts %}
    {{ parent() }}
    <script src="https://libs.hipay.com/js/sdkjs.js"></script>
    <script src="{{ asset('bundles/smilehipaysyliusplugin/hostedfield.js') }}"></script>
{% endblock %}
```

```twig
# templates/bundles/SyliusShopBundle/Checkout/selectPayment.html.twig

{% block javascripts %}
    {{ parent() }}
    <script src="https://libs.hipay.com/js/sdkjs.js"></script>
    <script src="{{ asset('bundles/smilehipaysyliusplugin/hostedfield.js') }}"></script>
{% endblock %}
```

And you can override this file to activate the hosted field for hipay classic and hipay moto and add restrictions for cartAmount (configured in backoffice)
```twig
# templates/bundles/SyliusShopBundle/Checkout/SelectPayment/_choice.html.twig

{% set cartAmount = order.total / 100 %}
{% set cartAmountMin = method.gatewayConfig.config.cartAmountMin ?? null %}
{% set cartAmountMax = method.gatewayConfig.config.cartAmountMax ?? null %}

{% if (cartAmountMin is null or cartAmountMin <= cartAmount) and (cartAmountMax is null or cartAmountMax >= cartAmount) %}
    <div class="item" {{ sylius_test_html_attribute('payment-item') }}>
        <div class="field">
            <div class="ui radio checkbox" {{ sylius_test_html_attribute('payment-method-checkbox') }}>
                {{ form_widget(form, sylius_test_form_attribute('payment-method-select')) }}
            </div>
        </div>
        <div class="content">
            <a class="header">{{ form_label(form, null, {'label_attr': {'data-test-payment-method-label': ''}}) }}</a>
            {% if method.description is not null %}
                <div class="description">
                    <p>{{ method.description }}</p>
                </div>
            {% endif %}
            {% if method.gatewayConfig.factoryName == constant("Smile\\HipaySyliusPlugin\\Payum\\Factory\\HipayMotoCardGatewayFactory::FACTORY_NAME") %}
                {{ render(controller('Smile\\HipaySyliusPlugin\\Controller\\HostedFieldController:renderHostedFieldsAction', {
                    'orderId': order.id, 'gateway': method.gatewayConfig.factoryName
                })) }}
            {% endif %}
            {% if method.gatewayConfig.factoryName == constant("Smile\\HipaySyliusPlugin\\Payum\\Factory\\HipayCardGatewayFactory::FACTORY_NAME") %}
                {{ render(controller('Smile\\HipaySyliusPlugin\\Controller\\HostedFieldController:renderHostedFieldsAction', {
                    'orderId': order.id, 'gateway': constant("Smile\\HipaySyliusPlugin\\Payum\\Factory\\HipayCardGatewayFactory::FACTORY_NAME")
                })) }}
            {% endif %}
            {% if method.gatewayConfig.factoryName == constant("Smile\\HipaySyliusPlugin\\Payum\\Factory\\HipayOney3GatewayFactory::FACTORY_NAME")
                or method.gatewayConfig.factoryName == constant("Smile\\HipaySyliusPlugin\\Payum\\Factory\\HipayOney4GatewayFactory::FACTORY_NAME")
            %}
                {{ render(controller('Smile\\HipaySyliusPlugin\\Controller\\OneyCustomerController:renderOneyCustomerIframe', {
                    'gateway': method.gatewayConfig.factoryName
                })) }}
            {% endif %}
            {% if method.gatewayConfig.factoryName == 'sylius.pay_pal' %}
                {{ render(controller('Sylius\\PayPalPlugin\\Controller\\PayPalButtonsController:renderPaymentPageButtonsAction', {'orderId': order.id})) }}
            {% endif %}
        </div>
    </div>
{% endif %}
```
