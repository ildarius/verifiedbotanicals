<?php
namespace Magento\AdobeStockImageAdminUi\Controller\Adminhtml\License\SaveLicensed;

/**
 * Interceptor class for @see \Magento\AdobeStockImageAdminUi\Controller\Adminhtml\License\SaveLicensed
 */
class Interceptor extends \Magento\AdobeStockImageAdminUi\Controller\Adminhtml\License\SaveLicensed implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\AdobeStockImageApi\Api\SaveLicensedImageInterface $saveLicensed, \Psr\Log\LoggerInterface $logger)
    {
        $this->___init();
        parent::__construct($context, $saveLicensed, $logger);
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
