<?php
/*------------------------------------------------------------------------
# SM Market - Version 1.0.0
# Copyright (c) 2016 YouTech Company. All Rights Reserved.
# @license - Copyrighted Commercial Software
# Author: YouTech Company
# Websites: http://www.magentech.com
-------------------------------------------------------------------------*/

namespace Sm\DegreeView\Model\Config\Source;

class Input implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'drag', 'label' => __('Mouse Drag')],
            ['value' => 'move', 'label' => __('Mouse Move')],
            ['value' => 'wheel', 'label' => __('Mouse Wheel')],
        ];
    }
}