<?php
namespace Magento\Bundle\Controller\Adminhtml\Bundle\Product\Edit\Edit;

/**
 * Interceptor class for @see \Magento\Bundle\Controller\Adminhtml\Bundle\Product\Edit\Edit
 */
class Interceptor extends \Magento\Bundle\Controller\Adminhtml\Bundle\Product\Edit\Edit implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder, \Magento\Framework\View\Result\PageFactory $resultPageFactory, ?\Magento\Store\Model\StoreManagerInterface $storeManager = null)
    {
        $this->___init();
        parent::__construct($context, $productBuilder, $resultPageFactory, $storeManager);
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
