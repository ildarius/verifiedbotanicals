<?php
namespace Magento\Catalog\Controller\Adminhtml\Category\Widget\Chooser;

/**
 * Interceptor class for @see \Magento\Catalog\Controller\Adminhtml\Category\Widget\Chooser
 */
class Interceptor extends \Magento\Catalog\Controller\Adminhtml\Category\Widget\Chooser implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\View\LayoutFactory $layoutFactory, \Magento\Framework\Controller\Result\RawFactory $resultRawFactory)
    {
        $this->___init();
        parent::__construct($context, $layoutFactory, $resultRawFactory);
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
