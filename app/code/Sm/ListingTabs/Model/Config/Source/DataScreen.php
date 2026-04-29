<?php
/**
 *
 * SM Listing Tabs - Version 2.8.0
 * Copyright (c) 2017 YouTech Company. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: YouTech Company
 * Websites: http://www.magentech.com
 */

namespace Sm\ListingTabs\Model\Config\Source;

class DataScreen implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('1')],
            ['value' => '2', 'label' => __('2')],
            ['value' => '3', 'label' => __('3')],
            ['value' => '4', 'label' => __('4')],
            ['value' => '5', 'label' => __('5')],
            ['value' => '6', 'label' => __('6')]

        ];
    }
}