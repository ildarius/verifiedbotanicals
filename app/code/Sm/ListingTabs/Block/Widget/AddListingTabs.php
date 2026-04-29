<?php

namespace Sm\ListingTabs\Block\Widget;

use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class AddListingTabs extends \Sm\ListingTabs\Block\ListingTabs implements BlockInterface
{

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Review\Model\Review $review
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param SerializerJson $jsonSerializer
     * @param array $data
     * @param $attr
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Review\Model\Review $review,
        \Magento\Catalog\Block\Product\Context $context,
        SerializerJson $jsonSerializer,
        array $data = [],
        $attr = null
    ) {
        parent::__construct($objectManager, $resource, $catalogProductVisibility, $review, $context, $jsonSerializer,
            $data, $attr);
    }

}
