define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_ReCaptchaWebapiUi/js/webapiReCaptchaRegistry'
], function ($, wrapper, recaptchaRegistry) {
    'use strict';

    function isBraintreePayment(payload) {
        return payload
            && payload.paymentMethod
            && typeof payload.paymentMethod.method === 'string'
            && payload.paymentMethod.method.indexOf('braintree') === 0;
    }

    return function (placeOrder) {
        return wrapper.wrap(placeOrder, function (originalAction, serviceUrl, payload, messageContainer) {
            var recaptchaDeferred;

            if (!isBraintreePayment(payload)) {
                return originalAction(serviceUrl, payload, messageContainer);
            }

            if (recaptchaRegistry.triggers.hasOwnProperty('recaptcha-checkout-braintree')) {
                recaptchaDeferred = $.Deferred();
                recaptchaRegistry.addListener('recaptcha-checkout-braintree', function (token) {
                    payload.xReCaptchaValue = token;
                    originalAction(serviceUrl, payload, messageContainer).done(function () {
                        recaptchaDeferred.resolve.apply(recaptchaDeferred, arguments);
                    }).fail(function () {
                        recaptchaDeferred.reject.apply(recaptchaDeferred, arguments);
                    });
                });
                recaptchaRegistry.triggers['recaptcha-checkout-braintree']();

                if (!recaptchaRegistry._isInvisibleType.hasOwnProperty('recaptcha-checkout-braintree') ||
                    recaptchaRegistry._isInvisibleType['recaptcha-checkout-braintree'] === false
                ) {
                    recaptchaRegistry.removeListener('recaptcha-checkout-braintree');
                }

                return recaptchaDeferred;
            }

            return originalAction(serviceUrl, payload, messageContainer);
        });
    };
});
