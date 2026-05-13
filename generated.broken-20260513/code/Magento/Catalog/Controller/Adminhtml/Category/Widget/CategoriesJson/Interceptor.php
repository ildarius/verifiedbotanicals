<?php
namespace Magento\Catalog\Controller\Adminhtml\Category\Widget\CategoriesJson;

/**
 * Interceptor class for @see \Magento\Catalog\Controller\Adminhtml\Category\Widget\CategoriesJson
 */
class Interceptor extends \Magento\Catalog\Controller\Adminhtml\Category\Widget\CategoriesJson implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\View\LayoutFactory $layoutFactory, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\Registry $coreRegistry)
    {
        $this->___init();
        parent::__construct($context, $layoutFactory, $resultJsonFactory, $coreRegistry);
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
