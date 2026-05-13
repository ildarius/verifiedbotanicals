<?php
namespace Magefan\BlogGraphQl\Model\Resolver\Post;

/**
 * Interceptor class for @see \Magefan\BlogGraphQl\Model\Resolver\Post
 */
class Interceptor extends \Magefan\BlogGraphQl\Model\Resolver\Post implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magefan\BlogGraphQl\Model\Resolver\DataProvider\Post $postDataProvider)
    {
        $this->___init();
        parent::__construct($postDataProvider);
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
