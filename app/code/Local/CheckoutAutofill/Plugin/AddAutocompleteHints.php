<?php

namespace Local\CheckoutAutofill\Plugin;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Tags checkout address fields with HTML autocomplete tokens so browsers
 * offer to autofill saved addresses. Stock Magento leaves them unset.
 * The value is rendered by the theme's Magento_Ui input/select templates.
 */
class AddAutocompleteHints
{
    private const FIELD_TOKENS = [
        'firstname' => 'given-name',
        'middlename' => 'additional-name',
        'lastname' => 'family-name',
        'company' => 'organization',
        'country_id' => 'country',
        'region' => 'address-level1',
        'region_id' => 'address-level1',
        'city' => 'address-level2',
        'postcode' => 'postal-code',
        'telephone' => 'tel',
    ];

    private const STREET_TOKENS = ['address-line1', 'address-line2', 'address-line3'];

    public function afterProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        $steps = &$jsLayout['components']['checkout']['children']['steps']['children'];

        if (isset($steps['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'])) {
            $this->tagFields(
                $steps['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'],
                'shipping'
            );
        }

        $payment = &$steps['billing-step']['children']['payment']['children'];

        // Billing form shown once on the payment page
        if (isset($payment['afterMethods']['children']['billing-address-form']['children']['form-fields']['children'])) {
            $this->tagFields(
                $payment['afterMethods']['children']['billing-address-form']['children']['form-fields']['children'],
                'billing'
            );
        }

        // Billing forms rendered per payment method
        foreach ($payment['payments-list']['children'] ?? [] as $name => $form) {
            if (isset($form['children']['form-fields']['children'])) {
                $this->tagFields(
                    $payment['payments-list']['children'][$name]['children']['form-fields']['children'],
                    'billing'
                );
            }
        }

        return $jsLayout;
    }

    private function tagFields(array &$fields, string $section): void
    {
        foreach (self::FIELD_TOKENS as $code => $token) {
            if (isset($fields[$code])) {
                $fields[$code]['config']['autocomplete'] = $section . ' ' . $token;
            }
        }

        foreach (self::STREET_TOKENS as $line => $token) {
            if (isset($fields['street']['children'][$line])) {
                $fields['street']['children'][$line]['config']['autocomplete'] = $section . ' ' . $token;
            }
        }
    }
}
