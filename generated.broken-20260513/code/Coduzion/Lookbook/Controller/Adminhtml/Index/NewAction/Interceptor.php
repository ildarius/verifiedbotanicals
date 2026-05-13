<?php
namespace Coduzion\Lookbook\Controller\Adminhtml\Index\NewAction;

/**
 * Interceptor class for @see \Coduzion\Lookbook\Controller\Adminhtml\Index\NewAction
 */
class Interceptor extends \Coduzion\Lookbook\Controller\Adminhtml\Index\NewAction implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory)
    {
        $this->___init();
        parent::__construct($context, $coreRegistry, $resultForwardFactory);
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
