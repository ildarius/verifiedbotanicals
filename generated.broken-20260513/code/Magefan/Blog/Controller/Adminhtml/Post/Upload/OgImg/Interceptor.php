<?php
namespace Magefan\Blog\Controller\Adminhtml\Post\Upload\OgImg;

/**
 * Interceptor class for @see \Magefan\Blog\Controller\Adminhtml\Post\Upload\OgImg
 */
class Interceptor extends \Magefan\Blog\Controller\Adminhtml\Post\Upload\OgImg implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Catalog\Model\ImageUploader $imageUploader)
    {
        $this->___init();
        parent::__construct($context, $imageUploader);
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
