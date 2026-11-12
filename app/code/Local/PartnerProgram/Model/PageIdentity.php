<?php
declare(strict_types=1);

namespace Local\PartnerProgram\Model;

use Magento\Framework\DataObject\IdentityInterface;

/**
 * Passed to the `clean_cache_by_tags` event so the full page cache (built-in or Varnish) drops the partner page.
 */
class PageIdentity implements IdentityInterface
{
    public function getIdentities(): array
    {
        return [PartnerProgramData::CACHE_TAG];
    }
}
