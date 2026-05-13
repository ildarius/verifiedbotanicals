<?php
namespace Sm\Market\Controller\Cms\Index;

/**
 * Interceptor class for @see \Sm\Market\Controller\Cms\Index
 */
class Interceptor extends \Sm\Market\Controller\Cms\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory, ?\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig = null, ?\Magento\Cms\Helper\Page $page = null)
    {
        $this->___init();
        parent::__construct($context, $resultForwardFactory, $scopeConfig, $page);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($coreRoute = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute($coreRoute);
    }
}
