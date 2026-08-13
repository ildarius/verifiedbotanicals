<?php

declare(strict_types=1);

namespace Local\KratomSearchTweaks\Plugin\Checkout\Cart;

use Magento\Checkout\Block\Cart\Sidebar;
use Magento\Store\Model\StoreManagerInterface;
use PayPal\Braintree\Gateway\Config\PayPal\Config as PayPalConfig;
use PayPal\Braintree\Gateway\Config\PayPalPayLater\Config as PayLaterConfig;
use PayPal\Braintree\Model\Ui\ConfigProvider;

class GuardedPayLaterMessageConfigPlugin
{
    public function __construct(
        private readonly PayPalConfig $config,
        private readonly ConfigProvider $configProvider,
        private readonly PayLaterConfig $payLaterConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function afterGetConfig(Sidebar $subject, array $result): array
    {
        if (!$this->payLaterConfig->isMessageActive('cart')) {
            return $result;
        }

        $result['payPalBraintreeClientToken'] = $this->configProvider->getClientToken();
        $result['payPalBraintreePaylaterMessageConfig'] = $this->config->getMessageStyles('cart');
        $result['paypalBraintreeCurrencyCode'] = $this->storeManager->getStore()->getCurrentCurrencyCode();

        return $result;
    }
}
