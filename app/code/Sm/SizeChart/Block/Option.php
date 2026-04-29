<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Product description block
 *
 * @author     Magentech.com
 */

namespace Sm\SizeChart\Block;

use Magento\Catalog\Model\Product;

/**
 * @api
 * @since 100.0.2
 */
class Option extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Product
     */
    protected $_product = null;
    protected $helper;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Sm\SizeChart\Helper\Data $helper,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        $this->helper        = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = $this->_coreRegistry->registry('product');
        }
        return $this->_product;
    }

    /**
     * @return string
     */

    protected function _toHtml()
    {
        $position            = $this->helper->getConfig('general/size_chart_position');
        $idSizeChartCmsBlock = $this->getProduct()->getData('sm_sizechart');
        if ($position == "options" && $idSizeChartCmsBlock) {
            $template_file = "Sm_SizeChart::popup-sizechart.phtml";
        } else {
            $template_file = "";
        }

        $this->setTemplate($template_file);
        return parent::_toHtml();
    }
}
