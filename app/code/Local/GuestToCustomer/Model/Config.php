<?php

declare(strict_types=1);

namespace Local\GuestToCustomer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLED = 'customer/guest_to_customer/enabled';
    public const XML_PATH_LINK_EXISTING = 'customer/guest_to_customer/link_existing';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isLinkExisting(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_LINK_EXISTING, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
