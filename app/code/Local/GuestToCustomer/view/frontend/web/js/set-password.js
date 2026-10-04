define([
    'jquery',
    'mage/translate',
    'mage/validation'
], function ($, $t) {
    'use strict';

    return function (config, element) {
        var $root = $(element),
            $form = $root.find('form'),
            $error = $root.find('[data-role="error"]');

        function showError(message) {
            $error.find('div').text(message || $t('Something went wrong. Please try again.'));
            $error.prop('hidden', false);
        }

        $form.validation();

        $form.on('submit', function (event) {
            var $button = $form.find('button[type="submit"]');

            event.preventDefault();
            if (!$form.validation('isValid')) {
                return;
            }

            $error.prop('hidden', true);
            $button.prop('disabled', true);

            // jQuery ajax (not fetch) so customer-data picks up sections.xml and refreshes the header.
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                dataType: 'json',
                data: $form.serialize()
            }).done(function (response) {
                if (response && response.success) {
                    $root.find('[data-role="form-state"]').prop('hidden', true);
                    $root.find('[data-role="done-state"]').prop('hidden', false);
                    return;
                }
                showError(response && response.message);
            }).fail(function () {
                showError();
            }).always(function () {
                $button.prop('disabled', false);
            });
        });
    };
});
