<?php
declare(strict_types=1);

namespace Local\BlogApi\Model\Data;

use Local\BlogApi\Api\Data\BlogPostSyncResultInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class BlogPostSyncResult extends AbstractSimpleObject implements BlogPostSyncResultInterface
{
    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    public function getPostId()
    {
        return $this->_get(self::POST_ID);
    }

    public function getIdentifier()
    {
        return $this->_get(self::IDENTIFIER);
    }

    public function getTitle()
    {
        return $this->_get(self::TITLE);
    }

    public function getPostUrl()
    {
        return $this->_get(self::POST_URL);
    }

    public function getStoreIds()
    {
        return $this->_get(self::STORE_IDS);
    }

    public function getCategoryIds()
    {
        return $this->_get(self::CATEGORY_IDS);
    }

    public function getTagIds()
    {
        return $this->_get(self::TAG_IDS);
    }

    public function getPublishTime()
    {
        return $this->_get(self::PUBLISH_TIME);
    }

    public function getIsActive()
    {
        return $this->_get(self::IS_ACTIVE);
    }

    public function getMetaTitle()
    {
        return $this->_get(self::META_TITLE);
    }

    public function getMetaKeywords()
    {
        return $this->_get(self::META_KEYWORDS);
    }

    public function getMetaDescription()
    {
        return $this->_get(self::META_DESCRIPTION);
    }

    public function getOgTitle()
    {
        return $this->_get(self::OG_TITLE);
    }

    public function getOgDescription()
    {
        return $this->_get(self::OG_DESCRIPTION);
    }

    public function getOgType()
    {
        return $this->_get(self::OG_TYPE);
    }
}
