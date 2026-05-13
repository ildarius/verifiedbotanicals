<?php
namespace Magefan\Blog\Controller\Adminhtml\Block\Featured\Grid\Chooser;

/**
 * Interceptor class for @see \Magefan\Blog\Controller\Adminhtml\Block\Featured\Grid\Chooser
 */
class Interceptor extends \Magefan\Blog\Controller\Adminhtml\Block\Featured\Grid\Chooser implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\View\LayoutFactory $layoutFactory, \Magento\Framework\Controller\Result\RawFactory $resultRawFactory)
    {
        $this->___init();
        parent::__construct($context, $layoutFactory, $resultRawFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): \Magento\Framework\Controller\Result\Raw
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }
}
