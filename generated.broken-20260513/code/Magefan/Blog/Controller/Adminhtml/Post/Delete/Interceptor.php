<?php
namespace Magefan\Blog\Controller\Adminhtml\Post\Delete;

/**
 * Interceptor class for @see \Magefan\Blog\Controller\Adminhtml\Post\Delete
 */
class Interceptor extends \Magefan\Blog\Controller\Adminhtml\Post\Delete implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor)
    {
        $this->___init();
        parent::__construct($context, $dataPersistor);
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
