<?php
namespace Magento\Widget\Controller\Adminhtml\Widget\LoadOptions;

/**
 * Interceptor class for @see \Magento\Widget\Controller\Adminhtml\Widget\LoadOptions
 */
class Interceptor extends \Magento\Widget\Controller\Adminhtml\Widget\LoadOptions implements \Magento\Framework\Interception\InterceptorInterface
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
