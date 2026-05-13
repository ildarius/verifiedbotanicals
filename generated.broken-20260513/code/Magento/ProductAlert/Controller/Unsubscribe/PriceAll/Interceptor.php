<?php
namespace Magento\ProductAlert\Controller\Unsubscribe\PriceAll;

/**
 * Interceptor class for @see \Magento\ProductAlert\Controller\Unsubscribe\PriceAll
 */
class Interceptor extends \Magento\ProductAlert\Controller\Unsubscribe\PriceAll implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Customer\Model\Session $customerSession)
    {
        $this->___init();
        parent::__construct($context, $customerSession);
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
