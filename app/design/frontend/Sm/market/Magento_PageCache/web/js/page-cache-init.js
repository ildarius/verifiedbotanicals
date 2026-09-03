define([
    'jquery',
    'pageCache'
], function ($) {
    'use strict';

    return function (config) {
        $('body').pageCache(config || {});
    };
});
