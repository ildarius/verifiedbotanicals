<?php
namespace Magento\User\Controller\Adminhtml\User\RoleGrid;

/**
 * Interceptor class for @see \Magento\User\Controller\Adminhtml\User\RoleGrid
 */
class Interceptor extends \Magento\User\Controller\Adminhtml\User\RoleGrid implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Magento\User\Model\UserFactory $userFactory)
    {
        $this->___init();
        parent::__construct($context, $coreRegistry, $userFactory);
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
