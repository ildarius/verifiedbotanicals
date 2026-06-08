define([
    'jquery',
    'Magento_Checkout/js/checkout-data'
], function ($, checkoutData) {
    'use strict';

    return function (checkoutDataResolver) {
        var defaultShippingAddress = {
            country_id: 'CA'
        };

        return $.extend({}, checkoutDataResolver, {
            resolveShippingAddress: function () {
                var shippingAddressData = checkoutData.getShippingAddressFromData();

                if (!shippingAddressData || !shippingAddressData.country_id) {
                    checkoutData.setShippingAddressFromData(
                        $.extend({}, defaultShippingAddress, shippingAddressData || {})
                    );
                }

                return checkoutDataResolver.resolveShippingAddress.apply(this, arguments);
            }
        });
    };
});
