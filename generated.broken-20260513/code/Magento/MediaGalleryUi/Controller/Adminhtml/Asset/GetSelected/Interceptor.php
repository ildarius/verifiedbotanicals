<?php
namespace Magento\MediaGalleryUi\Controller\Adminhtml\Asset\GetSelected;

/**
 * Interceptor class for @see \Magento\MediaGalleryUi\Controller\Adminhtml\Asset\GetSelected
 */
class Interceptor extends \Magento\MediaGalleryUi\Controller\Adminhtml\Asset\GetSelected implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Controller\Result\JsonFactory $resultFactory, \Magento\MediaGalleryApi\Api\GetAssetsByIdsInterface $getAssetById, \Magento\Backend\App\Action\Context $context, \Magento\Cms\Helper\Wysiwyg\Images $images, \Magento\Cms\Model\Wysiwyg\Images\Storage $storage)
    {
        $this->___init();
        parent::__construct($resultFactory, $getAssetById, $context, $images, $storage);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }
}
