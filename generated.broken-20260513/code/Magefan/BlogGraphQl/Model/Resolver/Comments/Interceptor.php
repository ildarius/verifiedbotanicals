<?php
namespace Magefan\BlogGraphQl\Model\Resolver\Comments;

/**
 * Interceptor class for @see \Magefan\BlogGraphQl\Model\Resolver\Comments
 */
class Interceptor extends \Magefan\BlogGraphQl\Model\Resolver\Comments implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder $searchCriteriaBuilder, \Magefan\Blog\Api\CommentRepositoryInterface $commentRepository, \Magefan\BlogGraphQl\Model\Resolver\DataProvider\Comment $commentDataProvider, \Magento\Framework\Api\FilterBuilder $filterBuilder, \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder, \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder)
    {
        $this->___init();
        parent::__construct($searchCriteriaBuilder, $commentRepository, $commentDataProvider, $filterBuilder, $filterGroupBuilder, $sortOrderBuilder);
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
