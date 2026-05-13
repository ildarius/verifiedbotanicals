<?php
namespace Magento\Indexer\Controller\Adminhtml\Indexer\MassInvalidate;

/**
 * Interceptor class for @see \Magento\Indexer\Controller\Adminhtml\Indexer\MassInvalidate
 */
class Interceptor extends \Magento\Indexer\Controller\Adminhtml\Indexer\MassInvalidate implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry)
    {
        $this->___init();
        parent::__construct($context, $indexerRegistry);
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
