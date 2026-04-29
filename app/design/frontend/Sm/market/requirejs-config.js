var config = {
    map: {
        '*': {
            'bootstrap.bundle.min': 'js/bootstrap/bootstrap.bundle.min',
            'slick': 'js/slick',
			'flipdown': 'js/flipdown' 
        }
    },
    shim: {
        'bootstrap.bundle.min': {
            'deps': ['jquery']
        }
    },
    deps: [
        "js/bootstrap/bootstrap.bundle.min",
        "js/theme-js"
    ]
};