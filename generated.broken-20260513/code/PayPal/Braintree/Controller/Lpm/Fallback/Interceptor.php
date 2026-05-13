<?php
namespace PayPal\Braintree\Controller\Lpm\Fallback;

/**
 * Interceptor class for @see \PayPal\Braintree\Controller\Lpm\Fallback
 */
class Interceptor extends \PayPal\Braintree\Controller\Lpm\Fallback implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\App\Request\Http $httpRequest)
    {
        $this->___init();
        parent::__construct($context, $httpRequest);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }
}
