<?php
namespace Magefan\Blog\Controller\Adminhtml\Import\Form;

/**
 * Interceptor class for @see \Magefan\Blog\Controller\Adminhtml\Import\Form
 */
class Interceptor extends \Magefan\Blog\Controller\Adminhtml\Import\Form implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context)
    {
        $this->___init();
        parent::__construct($context);
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
