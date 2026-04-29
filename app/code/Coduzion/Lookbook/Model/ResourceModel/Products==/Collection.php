<?php

namespace Coduzion\Lookbook\Model\ResourceModel\Products;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'lookbook_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Coduzion\Lookbook\Model\Products::class,
            \Coduzion\Lookbook\Model\ResourceModel\Products::class
        );
    }
}
