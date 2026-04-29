define([
        "jquery",
        "unveil",
        "owlcarousel",
		"swiper",
        "slick",
		"flipdown",
        "domReady!"
    ],
    function ($) {
        /**
         * Theme custom javascript
         */

        /**
         * Fix hover on IOS
         */
        $('body').bind('touchstart', function () {
        });

        /**
         * Click show filter on mobile
         */

        $("body").on('click', '#btn-filter, .filter-overlay, #close-filter', function () {
            $('html').toggleClass('show-filter-sidebar');
        });

        /**
         * Fix height item slider
         */

        function _maxHeightItem() {
            setTimeout(function () {
                var grid = $('div[data-max-height="max-height-grid-items"]');

                grid.each(function () {
                    var dataGrid = $(this);
                    var hightest = 0;

                    dataGrid.find('.product-item').each(function () {
                        var hight = $(this).height();
                        console.log(hight);
                        if (hight > hightest) {
                            hightest = hight;
                        }
                    });

                    dataGrid.find('.product-item-info').css({'height': hightest})
                });
            }, 3000);
        }

        _maxHeightItem();

        $(document).on("setMaxheightItem", function (event) {
            _maxHeightItem();
        });


        /**
         * Add parent class to megamenu item
         */

        $('.sm_megamenu_menu > li > div').parent().addClass('parent-item');

        /**
         * Menu ontop
         */

        if ($('.enable-stickymenu').length) {
            var wd = $(window);
            if ($('.ontop-element').length) {
                var menu_offset_top = $('.ontop-element').offset().top;

                function processScroll() {
                    var scrollTop = wd.scrollTop();
                    if (scrollTop >= menu_offset_top) {
                        $('.ontop-element').addClass('menu-on-top');
                        $('body').addClass('body-on-top');
                    } else if (scrollTop <= menu_offset_top) {
                        $('.ontop-element').removeClass('menu-on-top');
                        $('body').removeClass('body-on-top');
                    }
                }

                processScroll();
                wd.scroll(function () {
                    processScroll();
                });
            }
        }

        /**
         * Menu sidebar mobile
         */

        $('.mobile-menu #btn-nav-mobile, .nav-overlay').click(function () {
            $('body').toggleClass('show-sidebar-nav');
        });

        $('div[data-move="customer-mobile"]  .header.links').clone().appendTo('#customer-mobile');

        var menuType = $('#sm-header-mobile').data('menutype');

        if (menuType == 'megamenu') {
            $('.btn-submobile').click(function () {
                $(this).prev().slideToggle(200);
                $(this).toggleClass('btnsub-active');
                $(this).parent().toggleClass('parent-active');
                $(".sm-megamenu-child img").trigger("unveil");
            });

            function cloneMegaMenu() {
                var breakpoints = $('#sm-header-mobile').data('breakpoint');
                var doc_width = $(window).width();
                if (doc_width <= breakpoints) {
                    var horizontalMegamenu = $('.sm_megamenu_wrapper_horizontal_menu .horizontal-type');
                    var verticalMegamenu = $('.sm_megamenu_wrapper_vertical_menu .vertical-type');
                    $('#navigation-mobile').append(horizontalMegamenu);
                    $('#navigation-mobile').append(verticalMegamenu);
                } else {
                    var horizontalMegamenu = $('#navigation-mobile .horizontal-type');
                    var verticalMegamenu = $('#navigation-mobile .vertical-type');
                    $('.sm_megamenu_wrapper_horizontal_menu .sambar-inner .mega-content').append(horizontalMegamenu);
                    $('.sm_megamenu_wrapper_vertical_menu .sambar-inner .mega-content').append(verticalMegamenu);
                }
            }

            cloneMegaMenu();

            $(window).resize(function () {
                cloneMegaMenu();
            });
        } else {
            $('.navigation-mobile > ul li').has('ul').append('<span class="touch-button"><span>open</span></span>');

            $('.touch-button').click(function () {
                $(this).prev().slideToggle(200);
                $(this).toggleClass('active');
                $(this).parent().toggleClass('parent-active');
            });
        }

        /**
         * Clone minicart mobile
         */

        function cloneCart() {
            var breakpoints = $('#sm-header-mobile').data('breakpoint');
            var doc_width = $(window).width();
            if (doc_width <= breakpoints) {
                var cartDesktop = $('div[data-move="minicart-mobile"] > .minicart-wrapper');
                $('#minicart-mobile').append(cartDesktop);
            } else {
                var cartMobile = $('#minicart-mobile > .minicart-wrapper');
                $('div[data-move="minicart-mobile"]').append(cartMobile);
            }
        }

        cloneCart();

        $(window).resize(function () {
            cloneCart();
        });

        /**
         * Show vertical menu by class in cms page: enable-vertical-menu
         */

        if ($('.enable-vertical-menu').length) {
            $('body').addClass('show-vertical-menu');
        }

        /**
         * Focus input search
         */

        $('.header-container .block-search .input-text,.header-container .block-search .searchbox-cat').focus(function () {
            $('body').addClass('search-active');
        });

        $('.header-container .block-search .input-text,.header-container .block-search .searchbox-cat').blur(function () {
            $('body').removeClass('search-active');
        });

        /**
         * Hover product style 1
         */

        if ($('.product-1-style').length) {
            $("body").on('mouseenter touchstart', '.products-grid .product-item-info', function () {
                var imageHeight = $(this).find('.product-image-container').height();
                $(this).find('.product-item-details .quickview-handler').css({'top': imageHeight - 36});
            });
        }

        /**
         * Hover product style 14 , Hover product style 15
         */

        if ($('.product-14-style,.product-15-style,.product-17-style').length) {
            $("body").on('mouseenter touchstart', '.products-grid .product-item-info', function () {
                var imageHeight = $(this).find('.product-image-container').height();
                $(this).find('.product-item-details .actions-secondary').css({'top': -1 * (imageHeight / 2 + 20)});
            });
        }
		if ($('.product-18-style').length) {
            $("body").on('mouseenter touchstart', '.products-grid .product-item-info', function () {
                var imageHeight = $(this).find('.product-image-container').height();
                $(this).find('.product-item-details .actions-secondary').css({'top': -1 * (imageHeight - 70)});
            });
        }
        if ($('.product-19-style').length) {
            $("body").on('mouseenter touchstart', '.products-grid .product-item-info', function () {
                var imageHeight = $(this).find('.product-image-container').height();
                $(this).find('.product-item-details .actions-secondary').css({'top': -1 * (imageHeight - 70)});
            });
        }
		if ($('.product-22-style').length) {
            $("body").on('mouseenter touchstart', '.products-grid .product-item-info', function () {
                var imageHeight = $(this).find('.product-image-container').height();
                $(this).find('.product-item-details .actions-secondary').css({'top': -1 * (imageHeight - 80)}); 
            });
        }
		if ($('.product-23-style').length) {
            $("body").on('mouseenter touchstart', '.products-grid .product-item-info', function () {
                var imageHeight = $(this).find('.product-image-container').height();
                $(this).find('.product-item-details .actions-secondary').css({'top': -1 * (imageHeight - 10)}); 
            });
        }

        /**
         * Button Qty
         */
        $('.qty-plus').click(function () { 
            var qty = $(this).parent().prev(".tf-qty");
            qty.val(Number(qty.val()) + 1);
            return;
        });

        $('.qty-minus').click(function () {
            var qty = $(this).parent().prev(".tf-qty");
            var value = Number(qty.val()) - 1;
            if (value > 0) {
                $(qty).val(value);
            }
            return;
        });

        /**
         * Countdown static
         */

        function _countDownStatic(date, id) {
            var dateNow = new Date();
            var amount = date.getTime() - dateNow.getTime();
            delete dateNow;
            if (amount < 0) {
                id.html("Now!");
            } else {
                var days = 0;
                hours = 0;
                mins = 0;
                secs = 0;
                out = "";
                amount = Math.floor(amount / 1000);
                days = Math.floor(amount / 86400);
                amount = amount % 86400;
                hours = Math.floor(amount / 3600);
                amount = amount % 3600;
                mins = Math.floor(amount / 60);
                amount = amount % 60;
                secs = Math.floor(amount);
                $(".time-day .num-time", id).text(days);
                $(".time-day .title-time", id).text(((days <= 1) ? "Day" : "Days"));
                $(".time-hours .num-time", id).text(hours);
                $(".time-hours .title-time", id).text(((hours <= 1) ? "Hour" : "Hours"));
                $(".time-mins .num-time", id).text(mins);
                $(".time-mins .title-time", id).text(((mins <= 1) ? "Min" : "Mins"));
                $(".time-secs .num-time", id).text(secs);
                $(".time-secs .title-time", id).text(((secs <= 1) ? "Sec" : "Secs"));
                setTimeout(function () {
                    _countDownStatic(date, id)
                }, 1000);
            }
        }

        $(".countdown-static").each(function () {
            var timer = $(this).data('timer');
            var data = new Date(timer);
            _countDownStatic(data, $(this));
        });

        /**
         * Hover item menu init lazyload image
         */

        $(".sm_megamenu_menu > li").hover(function () {
            $(document).trigger("afterAjaxLazyLoad");
        });

        /**
         * Menu feature
         */

        $(".layout-prev li").mouseenter(function () {
            $(this).addClass("active");
            $(this).find("img").attr("src", $(this).find(".prev-layout").data("src-prev"));
        }).mouseleave(function () {
            $(this).removeClass("active");
        });

        /**
         * Slick slider client
         */

        $('.slider-for-client').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: false,
            fade: true,
            asNavFor: '.slider-nav-client'
        });
        $('.slider-nav-client').slick({
            slidesToShow: 3,
            slidesToScroll: 1,
            asNavFor: '.slider-for-client',
            dots: true,
            centerMode: true,
            focusOnSelect: true
        });

        /**
         * Gallery image
         */

        $(".img-gallery").fancybox({
            openEffect: 'fade',
            closeEffect: 'fade'
        });

        $('.play-video').fancybox({
            openEffect: 'none',
            closeEffect: 'none',
            helpers: {
                media: {}
            }
        });

        /**
         * Store slider
         */

        $('.slider-store .owl-carousel').owlCarousel({
            nav: true,
            dots: false,
            center: true,
            items: 1,
            loop: true,
            margin: 165,
        });
		 //  Click search v30
        $('.header-style-30 .header-content .customer-actions .search-container .icon-search1').on('click', function() {
            $(this).parent().toggleClass('show-block');
        });
		 //  Click search v31
        $('.header-style-31 .header-content .customer-actions .search-container .icon-search1').on('click', function() {
            $(this).parent().toggleClass('show-block');
        });
		//  Click search v40
        $('.header-style-40 .header-content .customer-actions .search-container .icon-search1').on('click', function() {
            $(this).parent().toggleClass('show-block');
        });
         //  Click search v35
        $('.header-style-35 .customer-header .search-container .icon-search1').on('click', function() {
            $(this).parent().toggleClass('show-block');
        });
        $('.header-style-35 .header-top .btn-close').click(function () {
            $('.header-style-35 .header-top .hd-top-content').slideToggle(200);
             $(this).toggleClass('active');

        });
		
		$('.block-marquee .button-close').click(function() {
          $(this).toggleClass('active'); 
          $('.block-marquee').slideToggle(300); 
        });

		
    });
