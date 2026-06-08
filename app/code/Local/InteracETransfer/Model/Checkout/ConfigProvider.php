<?php

declare(strict_types=1);

namespace Local\InteracETransfer\Model\Checkout;

use Local\InteracETransfer\Model\Config;
use Local\InteracETransfer\Model\Method\InteracETransfer;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class ConfigProvider implements ConfigProviderInterface
{
    private \Magento\Payment\Model\MethodInterface $method;

    public function __construct(
        PaymentHelper $paymentHelper,
        private readonly Config $config,
        private readonly Escaper $escaper
    ) {
        $this->method = $paymentHelper->getMethodInstance(InteracETransfer::PAYMENT_METHOD_CODE);
    }

    public function getConfig(): array
    {
        if (!$this->method->isAvailable()) {
            return [];
        }

        return [
            'payment' => [
                InteracETransfer::PAYMENT_METHOD_CODE => [
                    'shortNotice' => nl2br($this->escaper->escapeHtml($this->config->getShortNotice())),
                ],
            ],
        ];
    }
}
