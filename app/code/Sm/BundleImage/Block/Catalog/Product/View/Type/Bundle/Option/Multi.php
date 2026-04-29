<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * SM BundleImage
 * Copyright (c) 2017 YouTech Company. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: YouTech Company
 * Websites: https://www.magentech.com
 */

namespace Sm\BundleImage\Block\Catalog\Product\View\Type\Bundle\Option;

/**
 * Bundle option multi select type renderer
 *
 * @api
 * @since 100.0.2
 */
class Multi extends \Sm\BundleImage\Block\Catalog\Product\View\Type\Bundle\Option
{
    /**
     * @var string
     */
    protected $_template = 'Sm_BundleImage::catalog/product/view/type/bundle/option/multi.phtml';

    /**
     * @inheritdoc
     * @since 100.2.0
     */
    protected function assignSelection(\Magento\Bundle\Model\Option $option, $selectionId)
    {
        if (is_array($selectionId)) {
            foreach ($selectionId as $id) {
                if ($id && $option->getSelectionById($id)) {
                    $this->_selectedOptions[] = $id;
                }
            }
        } else {
            parent::assignSelection($option, $selectionId);
        }
    }
}
