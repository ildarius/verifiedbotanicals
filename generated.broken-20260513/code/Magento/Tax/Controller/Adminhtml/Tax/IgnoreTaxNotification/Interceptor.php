<?php
namespace Magento\Tax\Controller\Adminhtml\Tax\IgnoreTaxNotification;

/**
 * Interceptor class for @see \Magento\Tax\Controller\Adminhtml\Tax\IgnoreTaxNotification
 */
class Interceptor extends \Magento\Tax\Controller\Adminhtml\Tax\IgnoreTaxNotification implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassService, \Magento\Tax\Api\Data\TaxClassInterfaceFactory $taxClassDataObjectFactory, \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList)
    {
        $this->___init();
        parent::__construct($context, $taxClassService, $taxClassDataObjectFactory, $cacheTypeList);
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
