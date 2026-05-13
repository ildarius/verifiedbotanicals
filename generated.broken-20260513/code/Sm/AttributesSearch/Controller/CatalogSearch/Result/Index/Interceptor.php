<?php
namespace Sm\AttributesSearch\Controller\CatalogSearch\Result\Index;

/**
 * Interceptor class for @see \Sm\AttributesSearch\Controller\CatalogSearch\Result\Index
 */
class Interceptor extends \Sm\AttributesSearch\Controller\CatalogSearch\Result\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Catalog\Model\Session $catalogSession, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Search\Model\QueryFactory $queryFactory, \Magento\Catalog\Model\Layer\Resolver $layerResolver, \Magento\Framework\View\Result\PageFactory $pageFactory, \Magento\CatalogSearch\Helper\Data $catalogSearchHelper, \Magento\Search\Model\PopularSearchTerms $popularSearchTerms, \Magento\Framework\App\CacheInterface $cacheInterFace, \Magento\Framework\Json\EncoderInterface $jsonEncoder)
    {
        $this->___init();
        parent::__construct($context, $catalogSession, $storeManager, $queryFactory, $layerResolver, $pageFactory, $catalogSearchHelper, $popularSearchTerms, $cacheInterFace, $jsonEncoder);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }
}
