<?php
namespace Coduzion\Lookbook\Controller\Adminhtml\Index\Save;

/**
 * Interceptor class for @see \Coduzion\Lookbook\Controller\Adminhtml\Index\Save
 */
class Interceptor extends \Coduzion\Lookbook\Controller\Adminhtml\Index\Save implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\MediaStorage\Model\File\UploaderFactory $uploader, \Magento\Framework\Filesystem $filesystem, \Coduzion\Lookbook\Model\ImageUploader $imageUploader)
    {
        $this->___init();
        parent::__construct($context, $dataPersistor, $storeManager, $uploader, $filesystem, $imageUploader);
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
