<?php
namespace Magento\PaymentServicesPaypalGraphQl\Model\Resolver\CompleteOrderResolver;

/**
 * Interceptor class for @see \Magento\PaymentServicesPaypalGraphQl\Model\Resolver\CompleteOrderResolver
 */
class Interceptor extends \Magento\PaymentServicesPaypalGraphQl\Model\Resolver\CompleteOrderResolver implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\PaymentServicesPaypal\Api\CompleteOrderInterface $completeOrder, \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId, \Magento\Sales\Api\OrderRepositoryInterface $orderRepository, \Magento\SalesGraphQl\Model\Formatter\Order $orderFormatter)
    {
        $this->___init();
        parent::__construct($completeOrder, $maskedQuoteIdToQuoteId, $orderRepository, $orderFormatter);
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
