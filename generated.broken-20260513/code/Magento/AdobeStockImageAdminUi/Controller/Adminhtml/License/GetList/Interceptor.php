<?php
namespace Magento\AdobeStockImageAdminUi\Controller\Adminhtml\License\GetList;

/**
 * Interceptor class for @see \Magento\AdobeStockImageAdminUi\Controller\Adminhtml\License\GetList
 */
class Interceptor extends \Magento\AdobeStockImageAdminUi\Controller\Adminhtml\License\GetList implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Psr\Log\LoggerInterface $logger, \Magento\AdobeStockClientApi\Api\Client\FilesInterface $files)
    {
        $this->___init();
        parent::__construct($context, $logger, $files);
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
