<?php
namespace Magento\Newsletter\Controller\Adminhtml\Template\Drop;

/**
 * Interceptor class for @see \Magento\Newsletter\Controller\Adminhtml\Template\Drop
 */
class Interceptor extends \Magento\Newsletter\Controller\Adminhtml\Template\Drop implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context)
    {
        $this->___init();
        parent::__construct($context);
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
