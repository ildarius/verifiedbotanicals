<?php
namespace Magento\MediaGalleryUi\Controller\Adminhtml\Image\Upload;

/**
 * Interceptor class for @see \Magento\MediaGalleryUi\Controller\Adminhtml\Image\Upload
 */
class Interceptor extends \Magento\MediaGalleryUi\Controller\Adminhtml\Image\Upload implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\MediaGalleryUi\Model\UploadImage $upload, \Psr\Log\LoggerInterface $logger)
    {
        $this->___init();
        parent::__construct($context, $upload, $logger);
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
