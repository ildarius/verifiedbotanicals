<?php
namespace Sm\MegaMenu\Controller\Adminhtml\MenuItems\Duplicate;

/**
 * Interceptor class for @see \Sm\MegaMenu\Controller\Adminhtml\MenuItems\Duplicate
 */
class Interceptor extends \Sm\MegaMenu\Controller\Adminhtml\MenuItems\Duplicate implements \Magento\Framework\Interception\InterceptorInterface
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
    public function execute()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }
}
