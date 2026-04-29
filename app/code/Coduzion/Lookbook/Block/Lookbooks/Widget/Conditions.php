<?php
/**
 * Copyright © Coduzion, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Coduzion\Lookbook\Block\Lookbooks\Widget;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Backend\Block\Template\Context;
use Magento\Rule\Block\Conditions as RuleConditions;
use Coduzion\Lookbook\Model\Rule;
use Magento\Framework\Registry;

class Conditions extends Template implements RendererInterface
{
    /**
     * @var RuleConditions
     */
    protected $conditions;

    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var Factory
     */
    protected $elementFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $_template = 'Coduzion_Lookbook::lookbook/widget/conditions.phtml';

    /**
     * Construct method
     *
     * @param Context $context
     * @param Factory $elementFactory
     * @param RuleConditions $conditions
     * @param Rule $rule
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        RuleConditions $conditions,
        Rule $rule,
        Registry $registry,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->conditions = $conditions;
        $this->rule = $rule;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $widgetParameters = [];
        $widget = $this->registry->registry('current_widget_instance');
        if ($widget) {
            $widgetParameters = $widget->getWidgetParameters();
        } elseif ($widgetOptions = $this->getLayout()->getBlock('wysiwyg_widget.options')) {
            $widgetParameters = $widgetOptions->getWidgetValues();
        }

        if (isset($widgetParameters['conditions'])) {
            $this->rule->loadPost($widgetParameters);
        }
    }

    /**
     * Get block cache life time
     *
     * @return int
     * @since 100.1.0
     */
    protected function getCacheLifetime()
    {
        return 3600;
    }
    
    /**
     * @inheritdoc
     */
    public function render(AbstractElement $element)
    {
        $this->element = $element;
        $this->rule->getConditions()->setJsFormObject($this->getHtmlId());
        return $this->toHtml();
    }

    /**
     * Get new child url
     *
     * @return string
     */
    public function getNewChildUrl()
    {
        return $this->getUrl(
            'lookbook/lookbook_widget/conditions/form/' . $this->getElement()->getContainer()->getHtmlId()
        );
    }

    /**
     * Get element
     *
     * @return AbstractElement
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * Get html id
     *
     * @return string
     */
    public function getHtmlId()
    {
        return $this->getElement()->getContainer()->getHtmlId();
    }

    /**
     * Get input html
     *
     * @return string
     */
    public function getInputHtml()
    {
        $this->input = $this->elementFactory->create('text');
        $this->input->setRule($this->rule)->setRenderer($this->conditions);
        return $this->input->toHtml();
    }
}
