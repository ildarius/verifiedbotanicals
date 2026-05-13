<?php
namespace Magento\PaymentServicesBase\Controller\GetSdk\Index;

/**
 * Interceptor class for @see \Magento\PaymentServicesBase\Controller\GetSdk\Index
 */
class Interceptor extends \Magento\PaymentServicesBase\Controller\GetSdk\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Controller\ResultFactory $resultFactory, \Magento\PaymentServicesBase\Model\SdkContent\Provider $sdkContentProvider, \Psr\Log\LoggerInterface $logger)
    {
        $this->___init();
        parent::__construct($resultFactory, $sdkContentProvider, $logger);
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
