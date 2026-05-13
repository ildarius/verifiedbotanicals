<?php
namespace Magento\Tax\Controller\Adminhtml\Rate\Add;

/**
 * Interceptor class for @see \Magento\Tax\Controller\Adminhtml\Rate\Add
 */
class Interceptor extends \Magento\Tax\Controller\Adminhtml\Rate\Add implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Magento\Tax\Model\Calculation\Rate\Converter $taxRateConverter, \Magento\Tax\Api\TaxRateRepositoryInterface $taxRateRepository)
    {
        $this->___init();
        parent::__construct($context, $coreRegistry, $taxRateConverter, $taxRateRepository);
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
