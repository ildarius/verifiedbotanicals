<?php
namespace Magento\Reports\Controller\Adminhtml\Report\Statistics\Index;

/**
 * Interceptor class for @see \Magento\Reports\Controller\Adminhtml\Report\Statistics\Index
 */
class Interceptor extends \Magento\Reports\Controller\Adminhtml\Report\Statistics\Index implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter, array $reportTypes)
    {
        $this->___init();
        parent::__construct($context, $dateFilter, $reportTypes);
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
