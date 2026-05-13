<?php
namespace PayPal\Braintree\Controller\Adminhtml\Virtual\Index;

/**
 * Interceptor class for @see \PayPal\Braintree\Controller\Adminhtml\Virtual\Index
 */
class Interceptor extends \PayPal\Braintree\Controller\Adminhtml\Virtual\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->___init();
        parent::__construct($context, $resultPageFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): \Magento\Backend\Model\View\Result\Page
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }
}
