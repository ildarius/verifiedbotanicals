<?php
namespace Magento\Catalog\Controller\Adminhtml\Product\Duplicate;

/**
 * Interceptor class for @see \Magento\Catalog\Controller\Adminhtml\Product\Duplicate
 */
class Interceptor extends \Magento\Catalog\Controller\Adminhtml\Product\Duplicate implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder, \Magento\Catalog\Model\Product\Copier $productCopier, ?\Psr\Log\LoggerInterface $logger = null)
    {
        $this->___init();
        parent::__construct($context, $productBuilder, $productCopier, $logger);
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
