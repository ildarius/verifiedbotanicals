var config = {
    map: {
        '*': {
            'fancybox': 'js/jquery.fancybox/jquery.fancybox.pack',
            'owlcarousel': 'js/owl.carousel',
			'swiper': 'js/swiper-bundle.min',
            'unveil': 'js/jquery.unveil',
            'catalogBuyNow': 'js/catalog-buynow'
        }
    },
    deps: [
        "js/jquery.fancybox/jquery.fancybox.pack",
        "js/jquery.fancybox/jquery.fancybox-media",
        "js/owl.carousel",
		'js/swiper-bundle.min',
        "js/main"
    ],
    config: {
        mixins: {
            'Magento_Swatches/js/swatch-renderer': {
                'js/swatch-renderer-mixin': true
            }
        }
    }
};