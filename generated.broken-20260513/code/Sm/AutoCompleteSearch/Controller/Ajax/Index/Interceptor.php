<?php
namespace Sm\AutoCompleteSearch\Controller\Ajax\Index;

/**
 * Interceptor class for @see \Sm\AutoCompleteSearch\Controller\Ajax\Index
 */
class Interceptor extends \Sm\AutoCompleteSearch\Controller\Ajax\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Sm\AutoCompleteSearch\Helper\Data $helperData, \Magento\Framework\App\Action\Context $context, \Magento\Search\Model\QueryFactory $queryFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, \Sm\AutoCompleteSearch\Model\Search $searchModel)
    {
        $this->___init();
        parent::__construct($helperData, $context, $queryFactory, $storeManager, $searchModel);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }
}
