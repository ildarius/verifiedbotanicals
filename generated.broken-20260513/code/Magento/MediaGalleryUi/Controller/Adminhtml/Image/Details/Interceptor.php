<?php
namespace Magento\MediaGalleryUi\Controller\Adminhtml\Image\Details;

/**
 * Interceptor class for @see \Magento\MediaGalleryUi\Controller\Adminhtml\Image\Details
 */
class Interceptor extends \Magento\MediaGalleryUi\Controller\Adminhtml\Image\Details implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\MediaGalleryUi\Model\GetDetailsByAssetId $getDetailsByAssetId, \Psr\Log\LoggerInterface $logger)
    {
        $this->___init();
        parent::__construct($context, $getDetailsByAssetId, $logger);
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
