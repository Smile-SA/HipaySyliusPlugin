const configs = document.getElementsByClassName('hipay-div-config');
const paymentMethods = document.querySelectorAll('form[name="sylius_checkout_select_payment"] .items > .item');

paymentMethods.forEach((element) => {
  const inputField = element.querySelector('.field input[type="radio"]');
  inputField.addEventListener('change', () => {
    paymentMethods.forEach((subElement) => {
      const HipayForm = subElement.querySelector('.hipay-form');
      if (HipayForm) {
        HipayForm.style.display = 'none';
      }
    });
    const HipayForm = element.querySelector('.hipay-form');
    if (HipayForm) {
      HipayForm.style.display = 'block';
    }
  });
  if(inputField.checked) {
    inputField.dispatchEvent(new Event('change'));
  }
});


configs.forEach((config) => {
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
        hideCardTypeLogo: false,
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
      },
    },
  };


  // Init the hostedfields card
  const cardHipay = hipay.create('card', configCardHipay);
  const formPaymentMethod = document.getElementsByName('sylius_checkout_select_payment')[0];
  formPaymentMethod.addEventListener('submit', function(event) {
    let paymentMethod = '';

    if (document.querySelector('input[name^="sylius_checkout_select_payment[payments]"][type="radio"]') !== null) {
      paymentMethod = document.querySelector('input[name^="sylius_checkout_select_payment[payments]"][type="radio"]:checked').value;
    }

    if (paymentMethod === gateway) {
      event.preventDefault();
      cardHipay.getPaymentData().then(const configs = document.getElementsByClassName('hipay-div-config');
      const paymentMethods = document.querySelectorAll('form[name="sylius_checkout_select_payment"] .items > .item');

      paymentMethods.forEach((element) => {
        const inputField = element.querySelector('.field input[type="radio"]');
        inputField.addEventListener('change', () => {
          paymentMethods.forEach((subElement) => {
            const HipayForm = subElement.querySelector('.hipay-form');
            if (HipayForm) {
              HipayForm.style.display = 'none';
            }
          });
          const HipayForm = element.querySelector('.hipay-form');
          if (HipayForm) {
            HipayForm.style.display = 'block';
          }
        });
        if(inputField.checked) {
          inputField.dispatchEvent(new Event('change'));
        }
      });


      configs.forEach((config) => {
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
              hideCardTypeLogo: false,
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
            },
          },
        };

        const cardHipay = hipay.create('card', configCardHipay);
        const formPaymentMethod = document.getElementsByName('sylius_checkout_select_payment')[0];

        /**
         * On form submit
         */
        formPaymentMethod.addEventListener('submit', function(event) {
          let paymentMethod = '';

          if (document.querySelector('input[name^="sylius_checkout_select_payment[payments]"][type="radio"]') !== null) {
            let selectedPaymentMethodInput = document.querySelector('input[name^="sylius_checkout_select_payment[payments]"][type="radio"]:checked');
            paymentMethod = selectedPaymentMethodInput
              .closest('div.item')
              .querySelector('.hipay-div-config')
              .dataset
              .configGateway;
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
              },
            );
          }
        });
      });

      function(response) {
          document.getElementById("hipay-result-token-".concat(gateway)).value = response.token;
          document.getElementById("hipay-result-payment-product-".concat(gateway)).value = response.payment_product;
          formPaymentMethod.submit();
        },
        function(error) {
          //TODO error management
        },
      );
    }
  });
});
