<?php
namespace Magento\Catalog\Controller\Adminhtml\Product\CustomOptions;

/**
 * Interceptor class for @see \Magento\Catalog\Controller\Adminhtml\Product\CustomOptions
 */
class Interceptor extends \Magento\Catalog\Controller\Adminhtml\Product\CustomOptions implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder, \Magento\Framework\Registry $registry, \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory)
    {
        $this->___init();
        parent::__construct($context, $productBuilder, $registry, $resultLayoutFactory);
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
