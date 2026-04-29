define([
        'jquery',
        'Magento_Ui/js/modal/modal',
        "domReady!"
    ],
    function ($, modal) {
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            modalClass: 'sm-sizechart',
            buttons: []
        };

        if ($('#modal-sizechart').length) {
            var popup = modal(options, $('#modal-sizechart'));
            var appendButon = setInterval(setButton, 1000);
            var sizeCode = "." + $("#sm-sizechart-container").data('size-code');
        }

        function setButton() {
            $(".product-info-main .product-options-wrapper " + sizeCode).addClass("has-sizechart").append($("#button-sizechart-append"));

            if ($(".product-info-main .product-options-wrapper " + sizeCode + " #button-sizechart-append").length) {
                clearInterval(appendButon);
            }
        }

        $(document).on('click', '#sizechart-btn', function () {
            $("#modal-sizechart").modal("openModal");
        });
    });