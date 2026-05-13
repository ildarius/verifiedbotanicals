<?php
namespace Magento\Wishlist\Controller\Shared\Cart;

/**
 * Interceptor class for @see \Magento\Wishlist\Controller\Shared\Cart
 */
class Interceptor extends \Magento\Wishlist\Controller\Shared\Cart implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Checkout\Model\Cart $cart, \Magento\Wishlist\Model\Item\OptionFactory $optionFactory, \Magento\Wishlist\Model\ItemFactory $itemFactory, \Magento\Checkout\Helper\Cart $cartHelper, \Magento\Framework\Escaper $escaper)
    {
        $this->___init();
        parent::__construct($context, $cart, $optionFactory, $itemFactory, $cartHelper, $escaper);
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
