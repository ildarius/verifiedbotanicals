define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'domReady!'
], function ($, modal) {
    'use strict';

    return function (config, element) {
        var $popup = $(element);
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            modalClass: 'sm_popup_login',
            buttons: []
        };

        function bindWishlistPopup() {
            $('.action.towishlist').removeAttr('data-post');
        }

        bindWishlistPopup();

        $(document)
            .off('click.smPopupLogin', '.action.towishlist')
            .on('click.smPopupLogin', '.action.towishlist', function (event) {
                event.preventDefault();
                bindWishlistPopup();
                $popup.modal(options).modal('openModal');
            })
            .off('afterAjaxLazyLoad.smPopupLogin')
            .on('afterAjaxLazyLoad.smPopupLogin', function () {
                bindWishlistPopup();
            });

        if (config.recaptchaSelector && $(config.recaptchaSelector).length && $.fn.applyBindings !== undefined) {
            $(config.recaptchaSelector).applyBindings();
        }
    };
});
