<?php

namespace Sm\Themecore\Block\System\Config\Form\Field;

use Magento\Framework\Registry;

class Color extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_coreRegistry;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    )
    {
        $this->_coreRegistry = $coreRegistry;
        $this->_storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $html     = $element->getElementHtml();
        $cpPath   = $mediaUrl . 'sm/color/';
        if (!$this->_coreRegistry->registry('colorpicker_loaded')) {
            $html .= '<script type="text/javascript" src="' . $cpPath . 'jscolor.js"></script>
			<style type="text/css">input.jscolor { background-image: url(' . $cpPath . 'color.png) !important; background-position: calc(100% - 10px) center; background-repeat: no-repeat; padding-right: 44px !important; } input.jscolor.disabled,input.jscolor[disabled] { pointer-events: none; }</style>';
            $this->_coreRegistry->registry('colorpicker_loaded', 1);
        }
        $html .= '<script type="text/javascript">
                var el = document.getElementById("' . $element->getHtmlId() . '");
                el.className = el.className + " jscolor {required:false}";
            </script>';
        return $html;
    }
}