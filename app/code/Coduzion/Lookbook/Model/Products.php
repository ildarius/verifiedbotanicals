<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Model;

use Magento\Framework\DataObject\IdentityInterface;

class Products extends \Magento\Framework\Model\AbstractModel implements IdentityInterface
{
    // @codingStandardsIgnoreStart
    const CACHE_TAG = 'coduzion_products_grid';
    // @codingStandardsIgnoreEnd
    
    /**
     * Constructor. Initializes the model and sets its resource class.
     */
    protected function _construct()
    {
        $this->_init(\Coduzion\Lookbook\Model\ResourceModel\Products::class);
    }

    /**
     * Retrieve model cache tag for unique identification.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
