<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Model\Rule\Condition;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Coduzion\Lookbook\Model\LookbookFactory;
use Magento\Rule\Model\Condition\Context;
use Magento\Backend\Helper\Data;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Rule Lookbook condition data model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Lookbook extends AbstractLookbook
{
    /**
     * @var string
     */
    protected $elementName = 'parameters';

    /**
     * @var array
     */
    protected $joinedAttributes = [];

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Lookbook
     *
     * @var LookbookFactory
     */
    protected $lookbookFactory;

    /**
     * @param Context $context
     * @param Data $backendData
     * @param FormatInterface $localeFormat
     * @param StoreManagerInterface $storeManager
     * @param LookbookFactory $lookbookFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendData,
        FormatInterface $localeFormat,
        StoreManagerInterface $storeManager,
        LookbookFactory $lookbookFactory,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->lookbookFactory = $lookbookFactory;
        parent::__construct(
            $context,
            $backendData,
            $localeFormat,
            $data
        );
    }

    /**
     * Get Lookbooks Collection
     *
     * @return AbstractCollection
     */
    public function getLookbooksCollection()
    {
        $lookbooksCollection = $this->lookbookFactory->create()->getCollection();
        return $lookbooksCollection;
    }

    /**
     * @inheritdoc
     */
    public function loadAttributeOptions()
    {
        $attributes['lookbook_id'] = 'Lookbook ID';

        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @param array &$attributes
     * @return void
     */
    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);
        $attributes['lookbook_id'] = __('Lookbook ID');
    }

    /**
     * Add condition to collection
     *
     * @param Collection $collection
     * @return $this
     */
    public function addToCollection($collection)
    {
        $attribute = $this->getAttributeObject();
        return $this;
    }

    /**
     * Retrieve after element HTML
     *
     * @return string
     */
    public function getValueAfterElementHtml()
    {
        $html = '';

        switch ($this->getAttribute()) {
            case 'lookbook_id':
                $image = $this->_assetRepo->getUrl('images/rule_chooser_trigger.gif');
                break;
        }

        if (!empty($image)) {
            $html = '<a href="javascript:void(0)" class="rule-chooser-trigger"><img src="' .
                $image .
                '" alt="" class="v-middle rule-chooser-trigger" title="' .
                __(
                    'Open Chooser'
                ) . '" /></a>';
        }
        return $html;
    }

    /**
     * Adds Attributes that belong to Global Scope
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param Collection $collection
     * @return $this
     */
    protected function addGlobalAttribute(
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute,
        Collection $collection
    ) {
        return $this;
    }

    /**
     * Adds Attributes that don't belong to Global Scope
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param Collection $collection
     * @return $this
     */
    protected function addNotGlobalAttribute(
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute,
        Collection $collection
    ) {
        $storeId = $this->storeManager->getStore()->getId();
        $values = $collection->getAllAttributeValues($attribute);
        $validEntities = [];
        if ($values) {
            foreach ($values as $entityId => $storeValues) {
                if (isset($storeValues[$storeId])) {
                    if ($this->validateAttribute($storeValues[$storeId])) {
                        $validEntities[] = $entityId;
                    }
                } else {
                    if (isset($storeValues[Store::DEFAULT_STORE_ID]) &&
                        $this->validateAttribute($storeValues[Store::DEFAULT_STORE_ID])
                    ) {
                        $validEntities[] = $entityId;
                    }
                }
            }
        }
        $this->setOperator('()');
        $this->unsetData('value_parsed');
        if ($validEntities) {
            $this->setData('value', implode(',', $validEntities));
        } else {
            $this->unsetData('value');
        }

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getMappedSqlField()
    {
        return '';
    }

    /**
     * @inheritdoc
     *
     * @param Collection $productCollection
     * @return $this
     */
    public function collectValidatedAttributes($productCollection)
    {
        return $this->addToCollection($productCollection);
    }

    /**
     * Add attribute to collection based on scope
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param Collection $collection
     * @return void
     */
    private function addAttributeToCollection($attribute, $collection): void
    {
        if ($attribute->getBackend() && $attribute->isScopeGlobal()) {
            $this->addGlobalAttribute($attribute, $collection);
        } else {
            $this->addNotGlobalAttribute($attribute, $collection);
        }
    }
}
