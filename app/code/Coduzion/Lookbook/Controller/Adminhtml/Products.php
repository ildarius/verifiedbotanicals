<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Controller\Adminhtml;

abstract class Products extends \Magento\Backend\App\Action
{
    /**
     * Initialize requested category and put it into registry.
     *
     * @param bool $getRootInstead
     * @return \Magento\Catalog\Model\Category|false
     */
    protected function _initItem()
    {
        $id = (int) $this->getRequest()->getParam('lookbook_id', false);
        $product = $this->_objectManager->create(\Coduzion\Lookbook\Model\Products::class);
        if ($id) {
            $product->load($id);
        }
        $this->_objectManager->get(\Magento\Framework\Registry::class)->register('product', $product);
        $this->_objectManager->get(\Magento\Framework\Registry::class)->register('lookbook', $product);
        $this->_objectManager->get(\Magento\Cms\Model\Wysiwyg\Config::class);
        return $product;
    }
}
