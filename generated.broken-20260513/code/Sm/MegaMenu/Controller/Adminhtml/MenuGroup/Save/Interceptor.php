<?php
namespace Sm\MegaMenu\Controller\Adminhtml\MenuGroup\Save;

/**
 * Interceptor class for @see \Sm\MegaMenu\Controller\Adminhtml\MenuGroup\Save
 */
class Interceptor extends \Sm\MegaMenu\Controller\Adminhtml\MenuGroup\Save implements \Magento\Framework\Interception\InterceptorInterface
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
