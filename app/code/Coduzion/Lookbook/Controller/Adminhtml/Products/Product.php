<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Coduzion\Lookbook\Controller\Adminhtml\Products;

abstract class Product extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    // @codingStandardsIgnoreStart
    const ADMIN_RESOURCE = 'Coduzion_Lookbook::item_list';
    // @codingStandardsIgnoreEnd
}
