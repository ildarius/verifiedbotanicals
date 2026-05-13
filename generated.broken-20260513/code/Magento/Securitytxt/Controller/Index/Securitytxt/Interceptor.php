<?php
namespace Magento\Securitytxt\Controller\Index\Securitytxt;

/**
 * Interceptor class for @see \Magento\Securitytxt\Controller\Index\Securitytxt
 */
class Interceptor extends \Magento\Securitytxt\Controller\Index\Securitytxt implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\Controller\ResultFactory $resultPageFactory, \Magento\Securitytxt\Model\Securitytxt $securitytxtModel)
    {
        $this->___init();
        parent::__construct($context, $resultPageFactory, $securitytxtModel);
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
