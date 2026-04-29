<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Block\Adminhtml\Widget\Chooser;

use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Coduzion\Lookbook\Model\ResourceModel\Lookbook\CollectionFactory;
use Coduzion\Lookbook\Block\Adminhtml\Widget\Chooser\Renderer\Image;
use Coduzion\Lookbook\Block\Adminhtml\Widget\Chooser\Renderer\Status;

class Lookbook extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Coduzion\Lookbook\Model\ResourceModel\Lookbook\CollectionFactory
     */
    protected $lookbookCollectionFactory;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $lookbookCollectionFactory
     * @param StoreManagerInterface $storemanager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $lookbookCollectionFactory,
        StoreManagerInterface $storemanager,
        array $data = []
    ) {
        $this->lookbookCollectionFactory = $lookbookCollectionFactory;
        $this->_storeManager = $storemanager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Set grid id and other values
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        if ($this->getRequest()->getParam('current_grid_id')) {
            $this->setId($this->getRequest()->getParam('current_grid_id'));
        } else {
            $this->setId('lookbookChooserGrid_' . $this->getId());
        }

        $form = $this->getJsFormObject();
        $this->setRowClickCallback("{$form}.chooserGridRowClick.bind({$form})");
        $this->setCheckboxCheckCallback("{$form}.chooserGridCheckboxCheck.bind({$form})");
        $this->setRowInitCallback("{$form}.chooserGridRowInit.bind({$form})");
        $this->setDefaultSort('lookbook_id');
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Add column filer to collection
     *
     * @param Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_ids') {
            $selected = $this->_getSelectedLookbooks();
            if (empty($selected)) {
                $selected = '';
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('lookbook_id', ['in' => $selected]);
            } else {
                $this->getCollection()->addFieldToFilter('lookbook_id', ['nin' => $selected]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Prepare Catalog Product Collection for attribute SKU in Promo Conditions SKU chooser
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->lookbookCollectionFactory->create()
            ->addFieldToSelect('*');
        $collection->addFieldToFilter('status', 1);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Define Chooser Grid Columns and filters
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_ids',
            [
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_ids',
                'values' => $this->_getSelectedLookbooks(),
                'align' => 'center',
                'index' => 'lookbook_id',
                'use_index' => true
            ]
        );

        $this->addColumn(
            'title',
            [
                'header' => __('Title'),
                'name' => 'title',
                'width' => '80px',
                'index' => 'title'
            ]
        );

        $this->addColumn(
            'lookbook_image',
            [
                'header' => __('Image'),
                'name' => 'lookbook_image',
                'width' => '80px',
                'index' => 'lookbook_image',
                'renderer'  => Image::class
            ]
        );

        $this->addColumn(
            'image_resolution',
            [
                'header' => __('Image Resolution'),
                'name' => 'image_resolution',
                'width' => '100px',
                'index' => 'image_resolution'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'name' => 'status',
                'width' => '80px',
                'index' => 'status',
                'renderer'  => Status::class
            ]
        );

        $this->addColumn(
            'description',
            [
                'header' => __('Description'),
                'name' => 'description',
                'index' => 'description'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Ger grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            '*/*/chooser',
            ['_current' => true, 'current_grid_id' => $this->getId(), 'collapse' => null]
        );
    }

    /**
     * Get selected lookbook ids array
     *
     * @return mixed
     */
    protected function _getSelectedLookbooks()
    {
        $lookbooks = $this->getRequest()->getPost('selected', []);

        return $lookbooks;
    }
}
