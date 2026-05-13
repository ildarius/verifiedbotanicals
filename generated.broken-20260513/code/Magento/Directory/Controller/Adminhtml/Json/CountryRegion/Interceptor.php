<?php
namespace Magento\Directory\Controller\Adminhtml\Json\CountryRegion;

/**
 * Interceptor class for @see \Magento\Directory\Controller\Adminhtml\Json\CountryRegion
 */
class Interceptor extends \Magento\Directory\Controller\Adminhtml\Json\CountryRegion implements \Magento\Framework\Interception\InterceptorInterface
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
