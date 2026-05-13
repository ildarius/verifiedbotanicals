<?php
namespace Magento\Bundle\Controller\Adminhtml\Bundle\Product\Edit\ShowUpdateResult;

/**
 * Interceptor class for @see \Magento\Bundle\Controller\Adminhtml\Bundle\Product\Edit\ShowUpdateResult
 */
class Interceptor extends \Magento\Bundle\Controller\Adminhtml\Bundle\Product\Edit\ShowUpdateResult implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder, \Magento\Catalog\Helper\Product\Composite $productCompositeHelper)
    {
        $this->___init();
        parent::__construct($context, $productBuilder, $productCompositeHelper);
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
