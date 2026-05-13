<?php
namespace Sm\MegaMenu\Controller\Adminhtml\MenuItems\GetChildItems;

/**
 * Interceptor class for @see \Sm\MegaMenu\Controller\Adminhtml\MenuItems\GetChildItems
 */
class Interceptor extends \Sm\MegaMenu\Controller\Adminhtml\MenuItems\GetChildItems implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Sm\MegaMenu\Model\MenuItems $menuItems, array $data = [])
    {
        $this->___init();
        parent::__construct($context, $menuItems, $data);
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
