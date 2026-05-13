<?php
namespace Magento\Marketplace\Controller\Adminhtml\Partners\Index;

/**
 * Interceptor class for @see \Magento\Marketplace\Controller\Adminhtml\Partners\Index
 */
class Interceptor extends \Magento\Marketplace\Controller\Adminhtml\Partners\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\View\LayoutFactory $layoutFactory)
    {
        $this->___init();
        parent::__construct($context, $layoutFactory);
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
