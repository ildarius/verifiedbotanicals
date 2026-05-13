<?php
namespace Coduzion\Lookbook\Controller\Adminhtml\AssignProducts\Index;

/**
 * Interceptor class for @see \Coduzion\Lookbook\Controller\Adminhtml\AssignProducts\Index
 */
class Interceptor extends \Coduzion\Lookbook\Controller\Adminhtml\AssignProducts\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Coduzion\Lookbook\Model\LookbookFactory $lookbookFactory)
    {
        $this->___init();
        parent::__construct($context, $resultPageFactory, $resultJsonFactory, $lookbookFactory);
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
