<?php
declare(strict_types=1);

namespace Local\BlogApi\Api\Data;

interface BlogPostSyncResultInterface
{
    public const STATUS = 'status';
    public const POST_ID = 'post_id';
    public const IDENTIFIER = 'identifier';
    public const TITLE = 'title';
    public const POST_URL = 'post_url';
    public const STORE_IDS = 'store_ids';
    public const CATEGORY_IDS = 'category_ids';
    public const TAG_IDS = 'tag_ids';
    public const PUBLISH_TIME = 'publish_time';
    public const IS_ACTIVE = 'is_active';
    public const META_TITLE = 'meta_title';
    public const META_KEYWORDS = 'meta_keywords';
    public const META_DESCRIPTION = 'meta_description';
    public const OG_TITLE = 'og_title';
    public const OG_DESCRIPTION = 'og_description';
    public const OG_TYPE = 'og_type';

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @return int
     */
    public function getPostId();

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string
     */
    public function getPostUrl();

    /**
     * @return int[]
     */
    public function getStoreIds();

    /**
     * @return int[]
     */
    public function getCategoryIds();

    /**
     * @return int[]
     */
    public function getTagIds();

    /**
     * @return string
     */
    public function getPublishTime();

    /**
     * @return int
     */
    public function getIsActive();

    /**
     * @return string
     */
    public function getMetaTitle();

    /**
     * @return string
     */
    public function getMetaKeywords();

    /**
     * @return string
     */
    public function getMetaDescription();

    /**
     * @return string
     */
    public function getOgTitle();

    /**
     * @return string
     */
    public function getOgDescription();

    /**
     * @return string
     */
    public function getOgType();
}
