<?php
namespace PayPal\Braintree\Controller\Payment\UpdatePaymentMethod;

/**
 * Interceptor class for @see \PayPal\Braintree\Controller\Payment\UpdatePaymentMethod
 */
class Interceptor extends \PayPal\Braintree\Controller\Payment\UpdatePaymentMethod implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \PayPal\Braintree\Model\Adapter\BraintreeAdapter $adapter, \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement, \Magento\Framework\Session\SessionManagerInterface $session, \Magento\Checkout\Model\Session $checkoutSession)
    {
        $this->___init();
        parent::__construct($context, $adapter, $tokenManagement, $session, $checkoutSession);
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
