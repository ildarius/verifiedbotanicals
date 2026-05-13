<?php
namespace Magento\Paypal\Controller\Adminhtml\Billing\Agreement\View;

/**
 * Interceptor class for @see \Magento\Paypal\Controller\Adminhtml\Billing\Agreement\View
 */
class Interceptor extends \Magento\Paypal\Controller\Adminhtml\Billing\Agreement\View implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry)
    {
        $this->___init();
        parent::__construct($context, $coreRegistry);
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
