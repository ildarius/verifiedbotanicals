<?php
namespace Magento\TaxImportExport\Controller\Adminhtml\Rate\ExportCsv;

/**
 * Interceptor class for @see \Magento\TaxImportExport\Controller\Adminhtml\Rate\ExportCsv
 */
class Interceptor extends \Magento\TaxImportExport\Controller\Adminhtml\Rate\ExportCsv implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\App\Response\Http\FileFactory $fileFactory)
    {
        $this->___init();
        parent::__construct($context, $fileFactory);
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
