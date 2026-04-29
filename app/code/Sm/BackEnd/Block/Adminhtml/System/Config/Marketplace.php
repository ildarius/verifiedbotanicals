<?php


namespace Sm\BackEnd\Block\Adminhtml\System\Config;

/**
 * Class Marketplace
 * @package Sm\BackEnd\Block\Adminhtml\System\Config
 */
class Marketplace extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';
        if ($element->getComment()) {
            $html .= '<div style="margin: auto; padding: 10px; height: 1500px;">' . $element->getComment() . '</div>';
        }

        return $html;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
