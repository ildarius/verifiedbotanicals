<?php
namespace Sm\MegaMenu\Controller\Adminhtml\MenuItems\Edit;

/**
 * Interceptor class for @see \Sm\MegaMenu\Controller\Adminhtml\MenuItems\Edit
 */
class Interceptor extends \Sm\MegaMenu\Controller\Adminhtml\MenuItems\Edit implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Framework\Registry $registry, \Magento\Framework\Data\Collection $collection, \Magento\Framework\DataObject $dataObject, \Sm\MegaMenu\Helper\Defaults $defaults)
    {
        $this->___init();
        parent::__construct($context, $resultPageFactory, $registry, $collection, $dataObject, $defaults);
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
