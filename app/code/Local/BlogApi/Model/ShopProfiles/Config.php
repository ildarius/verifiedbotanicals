<?php
declare(strict_types=1);

namespace Local\BlogApi\Model\ShopProfiles;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * The blog category whose posts stay live at their own URLs but are left out of every storefront listing.
 * The ID is kept in one place: the config value below (default in etc/config.xml, overridable in core_config_data).
 */
class Config
{
    public const XML_PATH_CATEGORY_ID = 'local_blogapi/shop_profiles/category_id';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getCategoryId(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_CATEGORY_ID);
    }
}
