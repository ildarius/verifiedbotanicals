<?php
namespace Coduzion\Lookbook\Controller\Adminhtml\Widget\Chooser;

/**
 * Interceptor class for @see \Coduzion\Lookbook\Controller\Adminhtml\Widget\Chooser
 */
class Interceptor extends \Coduzion\Lookbook\Controller\Adminhtml\Widget\Chooser implements \Magento\Framework\Interception\InterceptorInterface
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
