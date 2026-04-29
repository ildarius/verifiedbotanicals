var config = {
    map: {
        '*': {
            'printThis': 'Sm_BundleImage/js/printThis'
        }
    },
    shim: {
        'printThis': {
            'deps': ['jquery']
        }
    },
    deps: [
        'Sm_BundleImage/js/bundle-image'
    ]
};