<?php
namespace Sm\FilterProducts\Block\Widget;

use Magento\Widget\Block\BlockInterface;

class AddFilterProducts extends \Sm\FilterProducts\Block\FilterProducts implements BlockInterface {

    /**
     * AddFilterProducts constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param array $data
     * @param null $attr
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = [],
        $attr = null
    ) {
        parent::__construct($objectManager, $collection, $resource, $catalogProductVisibility, $context, $data, $attr);
    }

    /**
     * @param $attr
     * @param $data
     * @return array|void
     */
    public function _getCfg($attr = null , $data = null)
    {
        $defaults = [];
        $_cfg_xml = $this->_scopeConfig->getValue('filterproducts',\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$this->_storeCode);
        if (empty($_cfg_xml)) {
            return;
        }
        $groups = [];
        foreach ($_cfg_xml as $def_key => $def_cfg) {
            $groups[] = $def_key;
            foreach ($def_cfg as $_def_key => $cfg) {
                $defaults[$_def_key] = $cfg;
            }
        }

        if (empty($groups)) {
            return;
        }
        $cfgs = [];
        foreach ($groups as $group) {
            $_cfgs = $this->_scopeConfig->getValue('filterproducts/'.$group.'',\Magento\Store\Model\ScopeInterface::SCOPE_STORE,$this->_storeCode);
            foreach ($_cfgs as $_key => $_cfg) {
                $cfgs[$_key] = $_cfg;
            }
        }

        if (empty($defaults)) {
            return;
        }
        $configs = [];
        foreach ($defaults as $key => $def) {
            if (isset($defaults[$key])) {
                $configs[$key] = $cfgs[$key];
            } else {
                unset($cfgs[$key]);
            }
        }
        $cf = ($attr != null) ? array_merge($configs, $attr) : $configs;
        $this->_config = ($data != null) ? array_merge($cf, $data) : $cf;
        return $this->_config;
    }

}
