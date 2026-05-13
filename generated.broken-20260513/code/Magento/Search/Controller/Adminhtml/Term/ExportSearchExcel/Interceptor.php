<?php
namespace Magento\Search\Controller\Adminhtml\Term\ExportSearchExcel;

/**
 * Interceptor class for @see \Magento\Search\Controller\Adminhtml\Term\ExportSearchExcel
 */
class Interceptor extends \Magento\Search\Controller\Adminhtml\Term\ExportSearchExcel implements \Magento\Framework\Interception\InterceptorInterface
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
