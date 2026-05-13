<?php
namespace Coduzion\Lookbook\Controller\Adminhtml\Index\MassDelete;

/**
 * Interceptor class for @see \Coduzion\Lookbook\Controller\Adminhtml\Index\MassDelete
 */
class Interceptor extends \Coduzion\Lookbook\Controller\Adminhtml\Index\MassDelete implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Ui\Component\MassAction\Filter $filter, \Coduzion\Lookbook\Model\ResourceModel\Lookbook\CollectionFactory $collectionFactory)
    {
        $this->___init();
        parent::__construct($context, $filter, $collectionFactory);
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
