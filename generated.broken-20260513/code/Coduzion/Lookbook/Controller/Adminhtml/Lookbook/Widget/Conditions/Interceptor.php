<?php
namespace Coduzion\Lookbook\Controller\Adminhtml\Lookbook\Widget\Conditions;

/**
 * Interceptor class for @see \Coduzion\Lookbook\Controller\Adminhtml\Lookbook\Widget\Conditions
 */
class Interceptor extends \Coduzion\Lookbook\Controller\Adminhtml\Lookbook\Widget\Conditions implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Coduzion\Lookbook\Model\Rule $rule)
    {
        $this->___init();
        parent::__construct($context, $rule);
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
