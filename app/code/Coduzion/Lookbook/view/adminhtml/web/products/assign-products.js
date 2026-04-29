define([
    'mage/adminhtml/grid'
], function () {
    'use strict';

    return function (config) {
        var selectedProducts = config.selectedProducts,
            categoryProducts = $H(selectedProducts),
            gridJsObject = window[config.gridJsObjectName],
            tabIndex = 1000,
            lastSelectedRadio = null;

        console.log(Object.toJSON(categoryProducts));
        jQuery(document).on('click', '.easy-marker', function (e) {
            var dataIndex = jQuery(this).attr('data-index');
            var selectedProductId = categoryProducts.get(dataIndex);
            $('in_category_products').value = selectedProductId;
            jQuery('input[type="radio"]').prop('checked', false);
            jQuery('input[type="radio"][value="' + selectedProductId + '"]').prop('checked', true);
            lastSelectedRadio = jQuery('input[type="radio"][value="' + selectedProductId + '"]')[0];
            updateSelectProductValue(selectedProductId);

        });

        /**
         * Register Category Product
         *
         * @param {Object} grid
         * @param {Object} element
         * @param {Boolean} checked
         */
        function registerCategoryProduct(grid, element, checked) {
            if (checked) {
                categoryProducts.set(element.value, element.value);
                if (lastSelectedRadio && lastSelectedRadio !== element) {
                    lastSelectedRadio.checked = false;
                }
                lastSelectedRadio = element;
                $('in_category_products').value = element.value;
                updateSelectProductValue(element.value); // Call the function to update .select_product
            }
            grid.reloadParams = {
                'selected_products[]': categoryProducts.keys()
            };
        }

        /**
         * Click on product row
         *
         * @param {Object} grid
         * @param {String} event
         */
        function categoryProductRowClick(grid, event) {
            var trElement = Event.findElement(event, 'tr'),
                radioElement = Element.getElementsBySelector(trElement, 'input[type="radio"]')[0];

            if (trElement && radioElement) {
                radioElement.checked = true;
                registerCategoryProduct(grid, radioElement, true);
            }
        }

        /**
         * Initialize category product row
         *
         * @param {Object} grid
         * @param {String} row
         */
        function categoryProductRowInit(grid, row) {
            var radio = $(row).getElementsBySelector('input[type="radio"]')[0];
            if (radio) {
                Event.observe(radio, 'change', function () {
                    registerCategoryProduct(grid, radio, radio.checked);
                });
            }
        }

        /**
         * Update .select_product value
         *
         * @param {String} value
         */
        function updateSelectProductValue(value) {
            jQuery('.select_product').val(value);
        }

        gridJsObject.rowClickCallback = categoryProductRowClick;
        gridJsObject.initRowCallback = categoryProductRowInit;

        if (gridJsObject.rows) {
            gridJsObject.rows.each(function (row) {
                categoryProductRowInit(gridJsObject, row);
            });
        }
    };
});
