<?php

declare(strict_types=1);

namespace Local\InteracETransfer\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    public const XML_PATH_ACTIVE = 'payment/interac_etransfer/active';
    public const XML_PATH_TITLE = 'payment/interac_etransfer/title';
    public const XML_PATH_SHORT_NOTICE = 'payment/interac_etransfer/short_notice';
    public const XML_PATH_RECIPIENT_NAME = 'payment/interac_etransfer/recipient_name';
    public const XML_PATH_RECIPIENT_EMAIL = 'payment/interac_etransfer/recipient_email';
    public const XML_PATH_PAYMENT_WINDOW_HOURS = 'payment/interac_etransfer/payment_window_hours';
    public const XML_PATH_ORDER_STATUS = 'payment/interac_etransfer/order_status';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isActive(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getTitle(?int $storeId = null): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_TITLE, ScopeInterface::SCOPE_STORE, $storeId));
    }

    public function getShortNotice(?int $storeId = null): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_SHORT_NOTICE, ScopeInterface::SCOPE_STORE, $storeId));
    }

    public function getRecipientName(?int $storeId = null): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_RECIPIENT_NAME, ScopeInterface::SCOPE_STORE, $storeId));
    }

    public function getRecipientEmail(?int $storeId = null): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_RECIPIENT_EMAIL, ScopeInterface::SCOPE_STORE, $storeId));
    }

    public function getPaymentWindowHours(?int $storeId = null): int
    {
        $hours = (int)$this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_WINDOW_HOURS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return max(1, $hours);
    }

    public function getOrderStatus(?int $storeId = null): string
    {
        return trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_ORDER_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }
}
