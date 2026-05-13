<?php
namespace Sm\MegaMenu\Controller\Adminhtml\MenuGroup\MassStatus;

/**
 * Interceptor class for @see \Sm\MegaMenu\Controller\Adminhtml\MenuGroup\MassStatus
 */
class Interceptor extends \Sm\MegaMenu\Controller\Adminhtml\MenuGroup\MassStatus implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $registry)
    {
        $this->___init();
        parent::__construct($context, $registry);
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
