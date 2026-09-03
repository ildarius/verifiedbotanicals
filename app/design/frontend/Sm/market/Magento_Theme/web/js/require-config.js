define([], function () {
    'use strict';

    return function (config) {
        require.config(config || {});
    };
});
