<?php
/**
 * Copyright © Coduzion(info@coduzion.com) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Block\Adminhtml\Lookbook;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

class AssignProducts extends \Magento\Backend\Block\Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'catalog/category/edit/assign_products.phtml';

    /**
     * @var \Magento\Catalog\Block\Adminhtml\Category\Tab\Product
     */
    protected $blockGrid;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var \Coduzion\Lookbook\Model\LookbookFactory
     */
    protected $_productCollectionFactory;

    /**
     * AssignProducts constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Coduzion\Lookbook\Model\LookbookFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Coduzion\Lookbook\Model\LookbookFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->jsonEncoder = $jsonEncoder;
        $this->_productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve instance of grid block
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                \Coduzion\Lookbook\Block\Adminhtml\Lookbook\Edit\Tab\Product::class,
                'lookbook.product.grid'
            );
        }
        return $this->blockGrid;
    }

    /**
     * Return HTML of grid block
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * Product Json for Select Products
     *
     * @return string
     */
    public function getProductsJson()
    {
        $lookbookId = $this->getRequest()->getParam('lookbook_id');
        
        $collection = $this->_productCollectionFactory->create()->getCollection()
        ->addFieldToFilter('lookbook_id', $lookbookId)->getFirstItem();
        
        $markers = $collection->getMarkers();

        if ((isset($markers)) && ($markers != 'null')) {
            $jsonarray = json_decode($markers, true);

            $productIds = [];

            foreach ($jsonarray as $outerKey => $outerValue) {
                foreach ($outerValue as $innerKey => $innerArray) {
                    if (isset($innerArray['product_id'])) {
                        $productIds[] = $innerArray['product_id'];
                    }
                }
            }

            if (!empty($productIds)) {
                return $this->jsonEncoder->encode($productIds);
            }
        }
        return '{}';
    }

    /**
     * Get Product
     *
     * @return array
     */
    public function getProduct()
    {
        return $this->registry->registry('lookbook');
    }
}
