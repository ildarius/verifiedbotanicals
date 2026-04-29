<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Product description block
 *
 * @author     Magentech.com
 */

namespace Sm\SizeChart\Model\Config\Source;

class Position implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'options', 'label' => __('Product Options')],
            ['value' => 'tabs', 'label' => __('Product Tabs')],
        ];
    }
}