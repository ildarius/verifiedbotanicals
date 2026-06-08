define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Local_InteracETransfer/payment/interac-etransfer'
        },

        getShortNotice: function () {
            var config = window.checkoutConfig.payment.interac_etransfer || {};

            return config.shortNotice || '';
        }
    });
});
