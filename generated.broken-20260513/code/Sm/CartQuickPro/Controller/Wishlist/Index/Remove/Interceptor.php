<?php
namespace Sm\CartQuickPro\Controller\Wishlist\Index\Remove;

/**
 * Interceptor class for @see \Sm\CartQuickPro\Controller\Wishlist\Index\Remove
 */
class Interceptor extends \Sm\CartQuickPro\Controller\Wishlist\Index\Remove implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Wishlist\Controller\WishlistProviderInterface $wishlistProvider, \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator, ?\Magento\Wishlist\Model\Product\AttributeValueProvider $attributeValueProvider = null)
    {
        $this->___init();
        parent::__construct($context, $wishlistProvider, $formKeyValidator, $attributeValueProvider);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'dispatch');
        return $pluginInfo ? $this->___callPlugins('dispatch', func_get_args(), $pluginInfo) : parent::dispatch($request);
    }
}
