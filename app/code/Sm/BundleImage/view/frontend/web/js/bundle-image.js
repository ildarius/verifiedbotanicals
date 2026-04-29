define([
        "jquery",
        "Magento_Ui/js/modal/modal",
        "printThis",
        "mage/translate",
        "mage/validation",
        "domReady!"
    ],
    function ($, modal) {
        var imgNotSelected = $("#bundle-attr-data").data("no-selected");
        var quickviewlabel = $("#bundle-attr-data").data("quickview-label");
        var quickViewEnable = $("#bundle-attr-data").data("check-quickview");
        var printFile = $("#bundle-attr-data").data("style");

        /**
         * Select
         */

        /** Set image default **/
        $(".fieldset-bundle-options .bundle-option-select").each(function () {
            var imgUrl = $(this).find(':selected').data('image');
            var productUrl = $(this).find(':selected').data('url');
            $(this).closest(".control").children(".child-bundle-items").find("img").attr("src", imgUrl);
            if (productUrl) {
                $(this).closest(".control").children(".child-bundle-items").find(".bundle-image").attr("href", productUrl);
            }

            $(this).change(function () {
                var value = $(this).val();
                var quickViewUrl = "";
                if (value) {
                    imgUrl = this.selectedOptions[0].getAttribute("data-image");
                    productUrl = this.selectedOptions[0].getAttribute("data-url");
                    quickViewUrl = this.selectedOptions[0].getAttribute("data-quickview-url");

                    if (imgUrl != null) {
                        $(this).closest(".control").children(".child-bundle-items").find(".item").removeClass("no-select");
                        $(this).closest(".control").children(".child-bundle-items").find("img").attr("src", imgUrl);
                    }

                    if (productUrl != null) {
                        $(this).closest(".control").children(".child-bundle-items").find(".bundle-image").attr("href", productUrl);
                        $(this).closest(".control").children(".child-bundle-items").removeClass("no-quickview");
                        $(this).closest(".control").children(".child-bundle-items").find(".bundle-quickview").attr("href", quickViewUrl).addClass("quickview-handler sm_quickview_handler");
                    } else {
                        $(this).closest(".control").children(".child-bundle-items").find(".bundle-image").attr("href", productUrl);
                        $(this).closest(".control").children(".child-bundle-items").addClass("no-quickview");
                    }
                } else {
                    $(this).closest(".control").children(".child-bundle-items").find(".item").addClass("no-select");
                    $(this).closest(".control").children(".child-bundle-items").find("img").attr("src", imgNotSelected);
                    $(this).closest(".control").children(".child-bundle-items").find(".bundle-image").attr("href", "javascript:void(0)");
                }
            });
        });

        /**
         * Multi select
         */

        $(".fieldset-bundle-options .multiselect").each(function () {
            var imgUrl;
            var itemHtml;

            $(this).change(function () {
                itemHtml = "";
                $(this).find(":selected").each(function () {
                    imgUrl = $(this).data("image");
                    var productUrl = $(this).data("url");
                    var quickViewUrl = $(this).data("quickview-url");
                    var quickViewHtml = "";

                    if (typeof productUrl === "undefined") {
                        itemHtml = itemHtml + '<div class="item">' +
                            '<a class="bundle-image" target="_blank" href="javascript:void(0)"><img src="' + imgUrl + '" /></a>' +
                            '</div>'
                    } else {
                        if (quickViewEnable == "enable-quickview") {
                            quickViewHtml = '<a class="bundle-quickview action quickview-handler sm_quickview_handler" href="' + quickViewUrl + '">' + quickviewlabel + '</a>';
                        } else {
                            quickViewHtml = "";
                        }

                        itemHtml = itemHtml + '<div class="item">' +
                            '<a class="bundle-image" target="_blank" href="' + productUrl + '"><img src="' + imgUrl + '" /></a>' + quickViewHtml + '</div>'
                    }
                });

                if (itemHtml) {
                    $(this).closest(".control").find(".child-bundle-items .item").remove();
                    $(this).closest(".control").find(".child-bundle-items").append(itemHtml);
                } else {
                    $(this).closest(".control").find(".child-bundle-items .item").remove();
                    $(this).closest(".control").find(".child-bundle-items").append(
                        '<div class="item no-select"><a class="bundle-image" href="javascript:void(0)"><img src="' + imgNotSelected + '" /></a></div>'
                    );
                }

            })
        });

        /** Set image default **/
        $(".fieldset-bundle-options .multiselect").each(function () {
            var imgUrl;
            var itemHtml = "";
            var productUrl = "";
            var quickViewUrl = "";
            var quickViewHtml = "";
            $(this).find(":selected").each(function () {
                imgUrl = $(this).data("image");
                productUrl = $(this).data("url");
                quickViewUrl = $(this).data("quickview-url");

                if (typeof productUrl === "undefined") {
                    itemHtml = itemHtml + '<div class="item">' +
                        '<a class="bundle-image" target="_blank" href="javascript:void(0)"><img src="' + imgUrl + '" /></a>' +
                        '</div>'
                } else {
                    if (quickViewEnable == "enable-quickview") {
                        quickViewHtml = '<a class="bundle-quickview action quickview-handler sm_quickview_handler" href="' + quickViewUrl + '">' + quickviewlabel + '</a>';
                    } else {
                        quickViewHtml = "";
                    }

                    itemHtml = itemHtml + '<div class="item">' +
                        '<a class="bundle-image" target="_blank" href="' + productUrl + '"><img src="' + imgUrl + '" /></a>' + quickViewHtml + '</div>'
                }
            });
            if (itemHtml) {
                $(this).closest(".control").find(".child-bundle-items .item").remove();
                $(this).closest(".control").find(".child-bundle-items").append(itemHtml);
            }
        });

        /**
         * Modal view config
         */

        var popupBundle;

        if ($("#popup-modal-bundle").length) {
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $.mage.__('Your Configuration'),
                modalClass: 'sm-bundle-modal',
                buttons: []
            };

            popupBundle = modal(options, $('#popup-modal-bundle'));

            $("#button-view-config").on('click', function (e) {
                e.preventDefault();
                /** Append data **/
                $(".block-bundle-summary .price-as-configured").clone().appendTo('#bundle-custom-price');
                $("#bundle-summary .bundle.items").clone().appendTo('#bundle-product-config');
                $("#config-bundle-container .page.messages .messages").find(".message").remove();

                $("#popup-modal-bundle").modal("openModal");
                $("#message-bundle").css({"display": "none"});
            });

            /** Remove data after closed modal **/

            $('#popup-modal-bundle').on('modalclosed', function () {
                $("#bundle-custom-price,#bundle-product-config").html("");
            });
        }


        /**
         * Bundle trigger add to cart
         */

        var mageError = $.mage.__('Please select required option.');
        var defaultText = $.mage.__('Add to Cart');
        var addingText = $.mage.__('Adding...');
        var addedText = $.mage.__('Added');
        var checkButton;
        var checkSuccessMes;
        var dataForm = $('#product_addtocart_form');

        $("#bundle-trigger").on('click', function (e) {
            e.preventDefault();
            var validForm = dataForm.validation('isValid');

            if (validForm) {
                $("#bundleSummary .tocart").trigger("click");
                $("#bundle-trigger").addClass("loading").find("span").html(addingText);
                clearInterval(checkButton);
                checkSuccessMes = setInterval(checkSuccess, 100);
            } else {
                $("#message-bundle .error.message span").html(mageError);
                $("#message-bundle").addClass("msg-error").removeClass("msg-success").css({"display": "block"});
            }
        });

        function checkSuccess() {
            if ($("#config-bundle-container .page.messages .success.message").length) {
                $("#bundle-trigger").removeClass("loading").find("span").html(addedText);
                clearInterval(checkSuccessMes);
                setTimeout(function () {
                    $("#bundle-trigger").removeClass("loading").find("span").html(defaultText);
                }, 500);
            }
        }

        /**
         * Trigger rating
         */

        $("#config-bundle-container .product-reviews-summary .action.add").attr("href", "javascript:void(0)");
        $("#config-bundle-container .product-reviews-summary .action.view").attr("href", "javascript:void(0)");

        $("#config-bundle-container .product-reviews-summary .action.add").on('click', function () {
            $(".sm-bundle-modal .modal-header .action-close").trigger("click");
            setTimeout(function () {
                $(".product-info-main .product-reviews-summary .action.add").trigger("click");
            }, 500);
        });

        $("#config-bundle-container .product-reviews-summary .action.view").on('click', function () {
            $(".sm-bundle-modal .modal-header .action-close").trigger("click");
            setTimeout(function () {
                $(".product-info-main .product-reviews-summary .action.view").trigger("click");
            }, 500);
        });

        /**
         * Print
         */

        $("#bundle-print").on('click', function () {
            $("#config-bundle-container").printThis({
                importCSS: false,
                loadCSS: printFile
            });
        });
    });