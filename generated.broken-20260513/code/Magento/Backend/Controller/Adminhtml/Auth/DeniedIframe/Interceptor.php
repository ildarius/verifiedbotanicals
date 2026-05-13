<?php
namespace Magento\Backend\Controller\Adminhtml\Auth\DeniedIframe;

/**
 * Interceptor class for @see \Magento\Backend\Controller\Adminhtml\Auth\DeniedIframe
 */
class Interceptor extends \Magento\Backend\Controller\Adminhtml\Auth\DeniedIframe implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Controller\Result\RawFactory $resultRawFactory)
    {
        $this->___init();
        parent::__construct($context, $resultRawFactory);
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
