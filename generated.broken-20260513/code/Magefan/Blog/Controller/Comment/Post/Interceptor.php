<?php
namespace Magefan\Blog\Controller\Comment\Post;

/**
 * Interceptor class for @see \Magefan\Blog\Controller\Comment\Post
 */
class Interceptor extends \Magefan\Blog\Controller\Comment\Post implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Customer\Model\Session $customerSession, \Magefan\Blog\Model\CommentFactory $commentFactory, \Magefan\Blog\Model\PostFactory $postFactory, \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator)
    {
        $this->___init();
        parent::__construct($context, $customerSession, $commentFactory, $postFactory, $formKeyValidator);
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
