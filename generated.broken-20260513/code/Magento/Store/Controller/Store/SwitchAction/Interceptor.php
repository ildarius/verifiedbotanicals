<?php
namespace Magento\Store\Controller\Store\SwitchAction;

/**
 * Interceptor class for @see \Magento\Store\Controller\Store\SwitchAction
 */
class Interceptor extends \Magento\Store\Controller\Store\SwitchAction implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Store\Api\StoreCookieManagerInterface $storeCookieManager, \Magento\Framework\App\Http\Context $httpContext, \Magento\Store\Api\StoreRepositoryInterface $storeRepository, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Store\Model\StoreSwitcherInterface $storeSwitcher, \Magento\Store\Controller\Store\SwitchAction\CookieManager $cookieManager)
    {
        $this->___init();
        parent::__construct($context, $storeCookieManager, $httpContext, $storeRepository, $storeManager, $storeSwitcher, $cookieManager);
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
