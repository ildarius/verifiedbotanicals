define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element);
        var $menu = $root.closest('.horizontal-megamenu-block').find('.sm_megamenu_wrapper_horizontal_menu').first();

        if (!$menu.length) {
            return;
        }

        $menu.find('.sm_megamenu_menu > li > div').each(function () {
            var $item = $(this);
            var position = $item.position();

            if (!position) {
                return;
            }

            if ($item.outerWidth() + position.left > $menu.width()) {
                $item.css({ right: '0' });
            }
        });

        $menu.find('div.sm_megamenu_actived').each(function () {
            var $self = $(this);
            var $level1 = $self.parents('.sm_megamenu_lv1').first();

            $self.parents('.sm_megamenu_title').each(function () {
                var $title = $(this);

                if (!$title.hasClass('sm_megamenu_actived')) {
                    $title.addClass('sm_megamenu_actived');
                }
            });

            if ($level1.length && !$level1.hasClass('sm_megamenu_actived')) {
                $level1.addClass('sm_megamenu_actived');
            }
        });
    };
});
