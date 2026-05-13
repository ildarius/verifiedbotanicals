<?php
namespace Sm\CartQuickPro\Controller\Sidebar\RemoveItem;

/**
 * Interceptor class for @see \Sm\CartQuickPro\Controller\Sidebar\RemoveItem
 */
class Interceptor extends \Sm\CartQuickPro\Controller\Sidebar\RemoveItem implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Checkout\Model\Sidebar $sidebar, \Psr\Log\LoggerInterface $logger, \Magento\Framework\Json\Helper\Data $jsonHelper, \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->___init();
        parent::__construct($context, $sidebar, $logger, $jsonHelper, $resultPageFactory);
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
