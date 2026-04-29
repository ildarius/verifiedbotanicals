define([
    'jquery',
    'mage/translate',
    'jquery/ui',
    'mage/validation'
], function($, $t) {
    "use strict";

    $.widget('mage.catalogBuyNow', {

        options: {
            buyNowButtonDisabledClass: 'disabled'
        },

        _create: function() {
                this._bindEvent();
        },

        _bindEvent: function() {
            var self = this;
            this.element.on('click', function(e) {
                self.submitForm($(this));
            });
        },

        /**
         * Handler for the form 'submit' event
         *
         * @param {Object} form
         */
        submitForm: function (buyNowButton) {
            var form = $(this.options.form);
            var ignore = null;

            form.mage('validation', {
                ignore: ignore ? ':hidden:not(' + ignore + ')' : ':hidden'
            }).find('input:text').attr('autocomplete', 'off');

            /*Validate form*/
            if(form.validation('isValid')) {
                form.off('submit');
                // disable 'Buy Now' button
                buyNowButton.prop('disabled', true);
                buyNowButton.addClass(this.options.buyNowButtonDisabledClass);
                //form.attr('action', form.attr('action')+"return_url/"+this.options.redirectUrl);
                form.append('<input type="hidden" name="return_url" value="'+this.options.redirectUrl+'"/>');
                form.submit();
            }
        }
    });

    return $.mage.catalogBuyNow;
});