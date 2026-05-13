<?php
namespace Magento\PaymentServicesPaypalGraphQl\Model\Resolver\AddProductsToNewCart;

/**
 * Interceptor class for @see \Magento\PaymentServicesPaypalGraphQl\Model\Resolver\AddProductsToNewCart
 */
class Interceptor extends \Magento\PaymentServicesPaypalGraphQl\Model\Resolver\AddProductsToNewCart implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\QuoteGraphQl\Model\Cart\CreateEmptyCartForGuest $cartManagement, \Magento\Quote\Model\Cart\AddProductsToCart $addProductsToCart, \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskQuoteIdConverter, \Magento\Quote\Api\CartRepositoryInterface $cartRepository, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, \Psr\Log\LoggerInterface $logger)
    {
        $this->___init();
        parent::__construct($cartManagement, $addProductsToCart, $maskQuoteIdConverter, $cartRepository, $customerRepository, $logger);
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
