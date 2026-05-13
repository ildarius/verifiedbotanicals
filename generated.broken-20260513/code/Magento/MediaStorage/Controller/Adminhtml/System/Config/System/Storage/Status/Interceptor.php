<?php
namespace Magento\MediaStorage\Controller\Adminhtml\System\Config\System\Storage\Status;

/**
 * Interceptor class for @see \Magento\MediaStorage\Controller\Adminhtml\System\Config\System\Storage\Status
 */
class Interceptor extends \Magento\MediaStorage\Controller\Adminhtml\System\Config\System\Storage\Status implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory)
    {
        $this->___init();
        parent::__construct($context, $resultJsonFactory);
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
