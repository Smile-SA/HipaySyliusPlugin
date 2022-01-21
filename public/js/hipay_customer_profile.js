(function(){
  const paymentMethods = document.querySelectorAll('form[name="sylius_checkout_select_payment"] .items > .item');
  const customerProfileIframes = document.querySelectorAll('form[name="sylius_checkout_select_payment"] iframe.hipay-customer-profile');
  let customerProfileValid = false;
  let nextStepButton = document.querySelector('#next-step');

  window.addEventListener('message', (event) => {
    if (window.location.origin === event.origin) {
      try {
        const data = JSON.parse(event.data);
        if (data.hipay_customer_profile && typeof data.hipay_customer_profile.is_valid !== 'undefined') {
          customerProfileValid = data.hipay_customer_profile.is_valid;
          const paymentMethodSelected = document.querySelector('form[name="sylius_checkout_select_payment"] input[name^="sylius_checkout_select_payment[payments]"][type="radio"]:checked');
          paymentMethodSelected.dispatchEvent(new Event('change'));
        }
      } catch(e) {}
    }
  }, false);

  window.addEventListener('message', (event) => {
    if (window.location.origin === event.origin) {
      try {
        if (event.data === 'resize_customer_profile_window') {
          customerProfileIframes.forEach((element) => {
            /**
             * First we force the iframe to reset
             * its inner body to a lower height
             * than the current scroll height
             */
            element.style.height = 'auto';

            /**
             * Next we force the iframe height
             * to its inner body scroll height
             */
            element.style.height = element.contentDocument.documentElement.scrollHeight + 'px';
          });
        }
      } catch(e) {}
    }
  }, false);

  paymentMethods.forEach((element) => {
    const inputField = element.querySelector('.field input[type="radio"]');
    inputField.addEventListener('change', () => {
      const HipayFormIframe = element.querySelector('.hipay-form.hipay-form-iframe');
      if (HipayFormIframe && !customerProfileValid) {
        nextStepButton.setAttribute('disabled', 'disabled');
      }else{
        nextStepButton.removeAttribute('disabled');
      }
      window.postMessage('resize_customer_profile_window');
    });
    if(inputField.checked) {
      inputField.dispatchEvent(new Event('change'));
    }
  });
})();
