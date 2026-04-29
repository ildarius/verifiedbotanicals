<?php

namespace Sm\AttributesSearch\Block\Widget;

use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;

class AddAttributesSearch extends \Sm\AttributesSearch\Block\AttributesSearch implements BlockInterface
{

    /**
     * AddAttributesSearch constructor.
     * @param Template\Context $context
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param SerializerJson $jsonSerializer
     * @param \Sm\AttributesSearch\Helper\Data $helperData
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        SerializerJson $jsonSerializer,
        \Sm\AttributesSearch\Helper\Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $eavConfig, $storeManager, $moduleManager, $jsonSerializer, $helperData, $data);
    }

}
