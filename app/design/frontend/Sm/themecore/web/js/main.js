define([
        "jquery",
        "unveil",
        "mage/cookies",
        "fancybox",
        "owlcarousel",
		"swiper",
        "mage/translate",
        "domReady!"
    ],
    function ($) {
        /**
         * Lazy load image
         */

        if ($('.enable-ladyloading').length) {
            function _runLazyLoad() {
                $("img.lazyload").unveil(0, function () {
                    $(this).on('load', function(){
                        this.classList.remove("lazyload");
                    });
                });
            }

            setTimeout(function () {
                _runLazyLoad();
            }, 1000);

            $(document).on("afterAjaxLazyLoad", function () {
                _runLazyLoad();
            });
        }

        /**
         * Back to top
         */
        $(function () {
            $(window).scroll(function () {
                if ($(this).scrollTop() > 500) {
                    $('.back2top').addClass('active');
                } else {
                    $('.back2top').removeClass('active');
                }
            });
            $('.back2top').click(function () {
                $('body,html').animate({
                    scrollTop: 0
                }, 800);
                return false;
            });

        });

        /**
         * Newsletter popup
         */
        if ($('.enable-newsletter-popup').length && $('#newsletter-popup').length) {
            var check_cookie = jQuery.cookie('newsletter_popup');
            if (check_cookie == null || check_cookie == 'shown') {
                popupNewsletter();
            }
            jQuery('#newsletter-popup .subscribe-bottom input').on('click', function () {
                if (jQuery(this).parent().find('input:checked').length) {
                    var check_cookie = jQuery.cookie('newsletter_popup');
                    if (check_cookie == null || check_cookie == 'shown') {
                        jQuery.cookie('newsletter_popup', 'dontshowitagain');
                    } else {
                        jQuery.cookie('newsletter_popup', 'shown');
                        popupNewsletter();
                    }
                } else {
                    jQuery.cookie('newsletter_popup', 'shown');
                }
            });

            function popupNewsletter() {
                $.fancybox.open('#newsletter-popup');
            }
        }
		 /**
         * swiper slider init
         */
		$('.swiper-container').each(function(index) {
			var $this = $(this);
			$this.addClass('swiper-slider-' + index);
			
			var dragSize = $this.data('drag-size') ? $this.data('drag-size') : 200;
			var freeMode = $this.data('free-mode') ? $this.data('free-mode') : false;
			var loop = $this.data('loop') ? $this.data('loop') : false;
			var slidesDesktop = $this.data('slides-desktop') ? $this.data('slides-desktop') : 4;
			var slidesTablet = $this.data('slides-tablet') ? $this.data('slides-tablet') : 3;
			var slidesMobile = $this.data('slides-mobile') ? $this.data('slides-mobile') : 1;
			var spaceBetween = $this.data('space-between') ? $this.data('space-between'): 30;

			var swiper = new Swiper('.swiper-slider-' + index, {
			  direction: 'horizontal',
			  loop: loop,
			  freeMode: freeMode,
			  spaceBetween: spaceBetween,
			  breakpoints: {
				1920: {
				  slidesPerView: slidesDesktop
				},
				992: {
				  slidesPerView: slidesTablet
				},
				768: {
				  slidesPerView: 2
				},
				320: {
				   slidesPerView: slidesMobile
				}
			  },
			  navigation: {
				nextEl: '.swiper-button-next',
				prevEl: '.swiper-button-prev'
			  },
			  scrollbar: {
				el: '.swiper-scrollbar',
				draggable: true,
				dragSize: dragSize
			  }
		   });
		});
        /**
         * Owl slider init
         */

        var owl_data = $('div[data-owl="owl-slider"]');
        owl_data.each(function () {
            var dataOwl = $(this);
            var dotsMobile = dataOwl.data('mobile-dots') == undefined ? false : dataOwl.data('mobile-dots');

            if (dotsMobile) {
                dataOwl.find('.owl-carousel').owlCarousel({
                    autoplay: dataOwl.data('autoplay') == undefined ? false : dataOwl.data('autoplay'),
                    autoplayHoverPause: dataOwl.data('autoplayhoverpause') == undefined ? false : dataOwl.data('autoplayhoverpause'),
                    loop: dataOwl.data('loop') == undefined ? false : dataOwl.data('loop'),
                    center: dataOwl.data('center') == undefined ? false : dataOwl.data('center'),
                    margin: dataOwl.data('margin') == undefined ? 0 : dataOwl.data('margin'),
                    stagePadding: dataOwl.data('stagepadding') == undefined ? 0 : dataOwl.data('stagepadding'),
                    nav: dataOwl.data('nav') == undefined ? false : dataOwl.data('nav'),
                    dots: dataOwl.data('dots') == undefined ? false : dataOwl.data('dots'),
                    mouseDrag: dataOwl.data('mousedrag') == undefined ? false : dataOwl.data('mousedrag'),
                    touchDrag: dataOwl.data('touchdrag') == undefined ? false : dataOwl.data('touchdrag'),
                    navText: ['<span>' + $.mage.__("Prev") + '</span>', '<span>' + $.mage.__("Next") + '</span>'],

                    responsive: {
                        0: {
                            items: dataOwl.data('screen0') == undefined ? 1 : dataOwl.data('screen0'),
                            nav: false,
                            dots: true
                        },
                        481: {
                            items: dataOwl.data('screen481') == undefined ? 1 : dataOwl.data('screen481'),
                            nav: false,
                            dots: true
                        },
                        768: {
                            items: dataOwl.data('screen768') == undefined ? 1 : dataOwl.data('screen768'),
                            nav: false,
                            dots: true
                        },
                        992: {
                            items: dataOwl.data('screen992') == undefined ? 1 : dataOwl.data('screen992'),
                            nav: false,
                            dots: true
                        },
                        1200: {
                            items: dataOwl.data('screen1200') == undefined ? 1 : dataOwl.data('screen1200')
                        },
                        1441: {
                            items: dataOwl.data('screen1441') == undefined ? 1 : dataOwl.data('screen1441')
                        },
                        1681: {
                            items: dataOwl.data('screen1681') == undefined ? 1 : dataOwl.data('screen1681')
                        },
                        1920: {
                            items: dataOwl.data('screen1920') == undefined ? 1 : dataOwl.data('screen1920')
                        },
                    }
                })
            } else {
                dataOwl.find('.owl-carousel').owlCarousel({
                    autoplay: dataOwl.data('autoplay') == undefined ? false : dataOwl.data('autoplay'),
                    autoplayHoverPause: dataOwl.data('autoplayhoverpause') == undefined ? false : dataOwl.data('autoplayhoverpause'),
                    loop: dataOwl.data('loop') == undefined ? false : dataOwl.data('loop'),
                    center: dataOwl.data('center') == undefined ? false : dataOwl.data('center'),
                    margin: dataOwl.data('margin') == undefined ? 0 : dataOwl.data('margin'),
                    stagePadding: dataOwl.data('stagepadding') == undefined ? 0 : dataOwl.data('stagepadding'),
                    nav: dataOwl.data('nav') == undefined ? false : dataOwl.data('nav'),
                    dots: dataOwl.data('dots') == undefined ? false : dataOwl.data('dots'),
                    mouseDrag: dataOwl.data('mousedrag') == undefined ? false : dataOwl.data('mousedrag'),
                    touchDrag: dataOwl.data('touchdrag') == undefined ? false : dataOwl.data('touchdrag'),
                    navText: ['<span>' + $.mage.__("Prev") + '</span>', '<span>' + $.mage.__("Next") + '</span>'],

                    responsive: {
                        0: {
                            items: dataOwl.data('screen0') == undefined ? 1 : dataOwl.data('screen0')
                        },
                        481: {
                            items: dataOwl.data('screen481') == undefined ? 1 : dataOwl.data('screen481')
                        },
                        768: {
                            items: dataOwl.data('screen768') == undefined ? 1 : dataOwl.data('screen768')
                        },
                        992: {
                            items: dataOwl.data('screen992') == undefined ? 1 : dataOwl.data('screen992')
                        },
                        1200: {
                            items: dataOwl.data('screen1200') == undefined ? 1 : dataOwl.data('screen1200')
                        },
                        1441: {
                            items: dataOwl.data('screen1441') == undefined ? 1 : dataOwl.data('screen1441')
                        },
                        1681: {
                            items: dataOwl.data('screen1681') == undefined ? 1 : dataOwl.data('screen1681')
                        },
                        1920: {
                            items: dataOwl.data('screen1920') == undefined ? 1 : dataOwl.data('screen1920')
                        },
                    }
                })
            }


        });


    });
