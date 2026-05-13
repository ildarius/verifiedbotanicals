<?php
namespace Magefan\Blog\Controller\Post\Viewscount;

/**
 * Interceptor class for @see \Magefan\Blog\Controller\Post\Viewscount
 */
class Interceptor extends \Magefan\Blog\Controller\Post\Viewscount implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magefan\Blog\Model\Url $url)
    {
        $this->___init();
        parent::__construct($context, $storeManager, $url);
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
