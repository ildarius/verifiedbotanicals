<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Coduzion\Lookbook\Model\Rule\Condition;

use Coduzion\Lookbook\Model\Rule\Condition\LookbookFactory;
use Magento\Rule\Model\Condition\Context;

/**
 * Combination of lookbook conditions
 */
class Combine extends \Magento\Rule\Model\Condition\Combine
{
    /**
     * @var LookbookFactory
     */
    protected $lookbookFactory;

    /**
     * @var string
     */
    protected $elementName = 'parameters';

    /**
     * @var array
     */
    private $excludedAttributes;

    /**
     * @param Context $context
     * @param LookbookFactory $conditionFactory
     * @param array $data
     * @param array $excludedAttributes
     */
    public function __construct(
        Context $context,
        LookbookFactory $conditionFactory,
        array $data = [],
        array $excludedAttributes = []
    ) {
        $this->lookbookFactory = $conditionFactory;
        parent::__construct($context, $data);
        $this->setType(\Coduzion\Lookbook\Model\Rule\Condition\Combine::class);
        $this->excludedAttributes = $excludedAttributes;
    }

    /**
     * Get new child select options
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $lookbookAttributes = $this->lookbookFactory->create()->loadAttributeOptions()->getAttributeOption();
        $attributes = [];
        foreach ($lookbookAttributes as $code => $label) {
            if (!in_array($code, $this->excludedAttributes)) {
                $attributes[] = [
                    'value' => Lookbook::class . '|' . $code,
                    'label' => $label,
                ];
            }
        }
        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions,
            [
                [
                    'value' => \Coduzion\Lookbook\Model\Rule\Condition\Combine::class,
                    'label' => __('Conditions Combination'),
                ],
                ['label' => __('Lookbook Attribute'), 'value' => $attributes]
            ]
        );

        return $conditions;
    }

    /**
     * Collect validated attributes for lookbook Collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @return $this
     */
    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }
        return $this;
    }
}
