<?php
namespace Sm\FilterProducts\Block\Widget\AddFilterProducts;

/**
 * Interceptor class for @see \Sm\FilterProducts\Block\Widget\AddFilterProducts
 */
class Interceptor extends \Sm\FilterProducts\Block\Widget\AddFilterProducts implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, \Magento\Catalog\Model\ResourceModel\Product\Collection $collection, \Magento\Framework\App\ResourceConnection $resource, \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility, \Magento\Catalog\Block\Product\Context $context, array $data = [], $attr = null)
    {
        $this->___init();
        parent::__construct($objectManager, $collection, $resource, $catalogProductVisibility, $context, $data, $attr);
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
