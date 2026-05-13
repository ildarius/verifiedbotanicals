<?php
namespace Sm\ListingTabs\Block\ListingTabs;

/**
 * Interceptor class for @see \Sm\ListingTabs\Block\ListingTabs
 */
class Interceptor extends \Sm\ListingTabs\Block\ListingTabs implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, \Magento\Framework\App\ResourceConnection $resource, \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility, \Magento\Review\Model\Review $review, \Magento\Catalog\Block\Product\Context $context, \Magento\Framework\Serialize\Serializer\Json $jsonSerializer, array $data = [], $attr = null)
    {
        $this->___init();
        parent::__construct($objectManager, $resource, $catalogProductVisibility, $review, $context, $jsonSerializer, $data, $attr);
    }

    /**
     * {@inheritdoc}
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getImage');
        return $pluginInfo ? $this->___callPlugins('getImage', func_get_args(), $pluginInfo) : parent::getImage($product, $imageId, $attributes);
    }
}
