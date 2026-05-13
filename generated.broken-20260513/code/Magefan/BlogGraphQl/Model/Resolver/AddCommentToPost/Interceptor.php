<?php
namespace Magefan\BlogGraphQl\Model\Resolver\AddCommentToPost;

/**
 * Interceptor class for @see \Magefan\BlogGraphQl\Model\Resolver\AddCommentToPost
 */
class Interceptor extends \Magefan\BlogGraphQl\Model\Resolver\AddCommentToPost implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magefan\Blog\Api\CommentRepositoryInterface $commentRepository, \Magefan\Blog\Api\PostRepositoryInterface $postRepository, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository)
    {
        $this->___init();
        parent::__construct($scopeConfig, $commentRepository, $postRepository, $customerRepository);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(\Magento\Framework\GraphQl\Config\Element\Field $field, $context, \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'resolve');
        return $pluginInfo ? $this->___callPlugins('resolve', func_get_args(), $pluginInfo) : parent::resolve($field, $context, $info, $value, $args);
    }
}
