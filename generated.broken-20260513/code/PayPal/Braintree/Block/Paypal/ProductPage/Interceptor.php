<?php
namespace PayPal\Braintree\Block\Paypal\ProductPage;

/**
 * Interceptor class for @see \PayPal\Braintree\Block\Paypal\ProductPage
 */
class Interceptor extends \PayPal\Braintree\Block\Paypal\ProductPage implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context, \Magento\Framework\Locale\ResolverInterface $localeResolver, \Magento\Checkout\Model\Session $checkoutSession, \PayPal\Braintree\Gateway\Config\PayPal\Config $config, \PayPal\Braintree\Gateway\Config\PayPalCredit\Config $payPalCreditConfig, \PayPal\Braintree\Gateway\Config\PayPalPayLater\Config $payPalPayLaterConfig, \PayPal\Braintree\Gateway\Config\Config $braintreeConfig, \PayPal\Braintree\Model\Ui\ConfigProvider $configProvider, \Magento\Payment\Model\MethodInterface $payment, \Magento\Checkout\Model\DefaultConfigProvider $defaultConfigProvider, \Magento\Customer\Model\Session $customerSession, \Magento\Framework\Registry $registry, \Magento\Directory\Model\Currency $currency, \Magento\Tax\Helper\Data $taxHelper, \Magento\Framework\App\RequestInterface $request, array $data = [])
    {
        $this->___init();
        parent::__construct($context, $localeResolver, $checkoutSession, $config, $payPalCreditConfig, $payPalPayLaterConfig, $braintreeConfig, $configProvider, $payment, $defaultConfigProvider, $customerSession, $registry, $currency, $taxHelper, $request, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getAmount(): float
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getAmount');
        return $pluginInfo ? $this->___callPlugins('getAmount', func_get_args(), $pluginInfo) : parent::getAmount();
    }
}
