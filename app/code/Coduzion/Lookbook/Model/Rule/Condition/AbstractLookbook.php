<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Model\Rule\Condition;

use Magento\Framework\App\ObjectManager;
use Magento\Rule\Model\Condition\Context;
use Magento\Backend\Helper\Data;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Model\AbstractModel;
use Coduzion\Lookbook\Model\ResourceModel\Lookbook\Collection;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Abstract Rule lookbook condition data model
 *
 * phpcs:disable Magento2.Classes.AbstractApi
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
abstract class AbstractLookbook extends \Magento\Rule\Model\Condition\AbstractCondition
{
    /**
     * All attribute values as array in form:
     * array(
     *   [entity_id_1] => array(
     *          [store_id_1] => store_value_1,
     *          [store_id_2] => store_value_2,
     *          ...
     *          [store_id_n] => store_value_n
     *   ),
     *   ...
     * )
     *
     * Will be set only for not global scope attribute
     *
     * @var array
     */
    protected $_entityAttributeValues = null;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $_localeFormat;

    /**
     * @param Context $context
     * @param Data $backendData
     * @param FormatInterface $localeFormat
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendData,
        FormatInterface $localeFormat,
        array $data = []
    ) {
        $this->_backendData = $backendData;
        $this->_localeFormat = $localeFormat;
        parent::__construct($context, $data);
    }

    /**
     * Customize default operator input by type mapper for some types
     *
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        return $this->_defaultOperatorInputByType;
    }

    /**
     * Retrieve attribute object
     *
     * @return null
     */
    public function getAttributeObject()
    {
        return null;
    }

    /**
     * Add special attributes
     *
     * @param array &$attributes
     * @return void
     */
    protected function _addSpecialAttributes(array &$attributes)
    {
        $attributes['lookbook_id'] = __('Lookbook ID');
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
     * Prepares values options to be used as select options or hashed array
     * Result is stored in following keys:
     *  'value_select_options' - normal select array: array(array('value' => $value, 'label' => $label), ...)
     *  'value_option' - hashed array: array($value => $label, ...),
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _prepareValueOptions()
    {
        // Check that both keys exist. Maybe somehow only one was set not in this routine, but externally.
        $selectReady = $this->getData('value_select_options');
        $hashedReady = $this->getData('value_option');
        if ($selectReady && $hashedReady) {
            return $this;
        }

        // Get array of select options. It will be used as source for hashed options
        $selectOptions = null;
        if (is_object($this->getAttributeObject())) {
            $attributeObject = $this->getAttributeObject();
            if ($attributeObject->usesSource()) {
                if ($attributeObject->getFrontendInput() == 'multiselect') {
                    $addEmptyOption = false;
                } else {
                    $addEmptyOption = true;
                }
                $selectOptions = $attributeObject->getSource()->getAllOptions($addEmptyOption);
            }
        }

        $this->_setSelectOptions($selectOptions, $selectReady, $hashedReady);

        return $this;
    }

    /**
     * Set new values only if we really got them
     *
     * @param array $selectOptions
     * @param array $selectReady
     * @param array $hashedReady
     * @return $this
     */
    protected function _setSelectOptions($selectOptions, $selectReady, $hashedReady)
    {
        if ($selectOptions !== null) {
            // Overwrite only not already existing values
            if (!$selectReady) {
                $this->setData('value_select_options', $selectOptions);
            }
            if (!$hashedReady) {
                $hashedOptions = [];
                foreach ($selectOptions as $option) {
                    if (is_array($option['value'])) {
                        continue; // We cannot use array as index
                    }
                    $hashedOptions[$option['value']] = $option['label'];
                }
                $this->setData('value_option', $hashedOptions);
            }
        }
        return $this;
    }

    /**
     * Retrieve value by option
     *
     * @param string|null $option
     * @return string
     */
    public function getValueOption($option = null)
    {
        $this->_prepareValueOptions();
        return $this->getData('value_option' . ($option !== null ? '/' . $option : ''));
    }

    /**
     * Retrieve select option values
     *
     * @return array
     */
    public function getValueSelectOptions()
    {
        $this->_prepareValueOptions();
        return $this->getData('value_select_options');
    }

    /**
     * Retrieve after element HTML
     *
     * @return string
     */
    public function getValueAfterElementHtml()
    {
        $html = '';

        if ($this->getAttribute() == 'lookbook_id') {
            $image = $this->_assetRepo->getUrl('images/rule_chooser_trigger.gif');
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
     * Retrieve attribute element
     *
     * @return AbstractElement
     */
    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);
        return $element;
    }

    /**
     * Collect validated attributes
     *
     * @param Collection $lookbookCollection
     * @return $this
     */
    public function collectValidatedAttributes($lookbookCollection)
    {
        $attribute = $this->getAttribute();
        
        return $this;
    }

    /**
     * Retrieve input type
     *
     * @return string
     */
    public function getInputType()
    {
        return 'text';
    }

    /**
     * Retrieve value element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * Retrieve value element chooser URL
     *
     * @return string
     */
    public function getValueElementChooserUrl()
    {
        $url = false;
        if ($this->getAttribute() == 'lookbook_id') {
            $url = 'lookbook/widget/chooser/attribute/' . $this->getAttribute();
            if ($this->getJsFormObject()) {
                $url .= '/form/' . $this->getJsFormObject();
            }
        }
        return $url !== false ? $this->_backendData->getUrl($url) : '';
    }

    /**
     * Retrieve Explicit Apply
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getExplicitApply()
    {
        if ($this->getAttribute() == 'lookbook_id') {
            return true;
        }
        return false;
    }

    /**
     * Load array
     *
     * @param array $arr
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function loadArray($arr)
    {
        $this->setAttribute(isset($arr['attribute']) ? $arr['attribute'] : false);

        if (!empty($arr['operator']) && $arr['operator'] == '()') {
            if (isset($arr['value'])) {
                $arr['value'] = preg_replace('/\s*,\s*/', ',', $arr['value']);
            }
        }

        return parent::loadArray($arr);
    }

    /**
     * Validate lookbook attribute value for condition
     *
     * @param AbstractModel $model
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        return true;
    }

    /**
     * Get mapped sql field
     *
     * @return string
     */
    public function getMappedSqlField()
    {
        $mappedSqlField = parent::getMappedSqlField();
        return $mappedSqlField;
    }

    /**
     * Validate product by entity ID
     *
     * @param int $productId
     * @return bool
     */
    public function validateByEntityId($productId)
    {
        return true;
    }

    /**
     * Correct '==' and '!=' operators
     *
     * Categories can't be equal because product is included categories selected by administrator and in their parents
     *
     * @return string
     */
    public function getOperatorForValidate()
    {
        $operator = $this->getOperator();
        
        return $operator;
    }
}
