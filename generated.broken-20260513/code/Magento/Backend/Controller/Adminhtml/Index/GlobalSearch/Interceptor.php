<?php
namespace Magento\Backend\Controller\Adminhtml\Index\GlobalSearch;

/**
 * Interceptor class for @see \Magento\Backend\Controller\Adminhtml\Index\GlobalSearch
 */
class Interceptor extends \Magento\Backend\Controller\Adminhtml\Index\GlobalSearch implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, array $searchModules = [])
    {
        $this->___init();
        parent::__construct($context, $resultJsonFactory, $searchModules);
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
