<?php
namespace Magento\PaymentServicesPaypal\Controller\SmartButtons\ShippingCallback;

/**
 * Interceptor class for @see \Magento\PaymentServicesPaypal\Controller\SmartButtons\ShippingCallback
 */
class Interceptor extends \Magento\PaymentServicesPaypal\Controller\SmartButtons\ShippingCallback implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\RequestInterface $request, \Magento\Framework\Controller\Result\JsonFactory $jsonFactory, \Magento\PaymentServicesPaypal\Api\ShippingCallbackManagementInterface $shippingCallbackManagement, \Psr\Log\LoggerInterface $logger)
    {
        $this->___init();
        parent::__construct($request, $jsonFactory, $shippingCallbackManagement, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }
}
