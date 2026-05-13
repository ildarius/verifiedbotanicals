<?php
namespace Magento\Catalog\Controller\Adminhtml\Product\Index;

/**
 * Interceptor class for @see \Magento\Catalog\Controller\Adminhtml\Product\Index
 */
class Interceptor extends \Magento\Catalog\Controller\Adminhtml\Product\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder, \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->___init();
        parent::__construct($context, $productBuilder, $resultPageFactory);
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
