<?php
namespace Magento\Sitemap\Controller\Adminhtml\Sitemap\Index;

/**
 * Interceptor class for @see \Magento\Sitemap\Controller\Adminhtml\Sitemap\Index
 */
class Interceptor extends \Magento\Sitemap\Controller\Adminhtml\Sitemap\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context)
    {
        $this->___init();
        parent::__construct($context);
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
