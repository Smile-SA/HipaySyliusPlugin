const configs = document.getElementsByClassName('hipay-div-config');

configs.forEach(config => {
  const username = config.getAttribute('data-config-username');
  const password = config.getAttribute('data-config-password');
  const stage = config.getAttribute('data-config-stage');
  const locale = config.getAttribute('data-config-locale');
  const gateway = config.getAttribute('data-config-gateway');

  const hipay = HiPay({
    username: username,
    password: password,
    environment: stage,
    lang: locale,
  });


  // Config hostedfields card object
  const configCardHipay = {
    selector: 'hipay-form-'.concat(gateway),
    multi_use: false,
    fields: {
      cardHolder: {
        selector: 'hipay-card-holder-'.concat(gateway),
      },
      cardNumber: {
        selector: 'hipay-card-number-'.concat(gateway),
        hideCardTypeLogo: false
      },
      expiryDate: {
        selector: 'hipay-date-expiry-'.concat(gateway),
      },
      cvc: {
        selector: 'hipay-cvc-'.concat(gateway),
      },
    },

    styles: {
      base: {
        fontFamily: 'Roboto',
        color: '#000000',
        fontSize: '15px',
        fontWeight: 400,
        caretColor: '#00ADE9',
        iconColor: '#00ADE9',
      },
      invalid: {
        color: '#D50000',
        caretColor: '#D50000',
      }
    }
  };

  // Init the hostedfields card
  const cardHipay = hipay.create('card', configCardHipay);

  //TODO Define form name
  const formPaymentMethod = document.getElementById('form-select-payment');
  formPaymentMethod.addEventListener("submit", function(event) {
    let paymentMethod = '';

    if (document.querySelector('input[name="sylius_checkout_select_payment[payments][0][method]"]') !== null) {
      paymentMethod = document.querySelector('input[name="sylius_checkout_select_payment[payments][0][method]"]:checked').value;
    }
    if (document.querySelector('input[name="sylius_checkout_select_payment[payments][1][method]"]') !== null) {
      paymentMethod = document.querySelector('input[name="sylius_checkout_select_payment[payments][1][method]"]:checked').value;
    }

    if (paymentMethod === gateway) {
      event.preventDefault();
      cardHipay.getPaymentData().then(
        function(response) {
          document.getElementById("hipay-result-token-".concat(gateway)).value = response.token;
          document.getElementById("hipay-result-payment-product-".concat(gateway)).value = response.payment_product;
          formPaymentMethod.submit();
        },
        function(error) {
          //TODO error management
        }
      );
    }
  });
});
