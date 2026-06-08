define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'interac_etransfer',
        component: 'Local_InteracETransfer/js/view/payment/method-renderer/interac-etransfer-method'
    });

    return Component.extend({});
});
