define([
    'jquery',
    'quickView',
    'domReady!'
], function ($) {
    'use strict';

    return function (config) {
        var quickViewConfig = config || {};

        $(quickViewConfig.product_container).cartQuickView(quickViewConfig);
        $(document)
            .off('afterAjaxProductsLoaded.smCartQuickProQuickview')
            .on('afterAjaxProductsLoaded.smCartQuickProQuickview', function () {
                $(quickViewConfig.product_container).cartQuickView(quickViewConfig);
            });
    };
});
