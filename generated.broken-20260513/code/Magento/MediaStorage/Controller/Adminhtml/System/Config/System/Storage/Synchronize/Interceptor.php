<?php
namespace Magento\MediaStorage\Controller\Adminhtml\System\Config\System\Storage\Synchronize;

/**
 * Interceptor class for @see \Magento\MediaStorage\Controller\Adminhtml\System\Config\System\Storage\Synchronize
 */
class Interceptor extends \Magento\MediaStorage\Controller\Adminhtml\System\Config\System\Storage\Synchronize implements \Magento\Framework\Interception\InterceptorInterface
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
