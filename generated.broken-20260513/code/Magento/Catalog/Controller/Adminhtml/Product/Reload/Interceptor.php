<?php
namespace Magento\Catalog\Controller\Adminhtml\Product\Reload;

/**
 * Interceptor class for @see \Magento\Catalog\Controller\Adminhtml\Product\Reload
 */
class Interceptor extends \Magento\Catalog\Controller\Adminhtml\Product\Reload implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder)
    {
        $this->___init();
        parent::__construct($context, $productBuilder);
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
