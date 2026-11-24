<?php
declare(strict_types=1);

namespace Local\BlogApi\Plugin;

use Local\BlogApi\Model\ShopProfiles\Config;
use Magefan\Blog\Model\ResourceModel\Post\Collection;

/**
 * Leaves posts in the "Shop profiles" category out of every storefront post listing.
 *
 * Every Magefan listing (index, tag, category, archive, author, search, RSS, sidebar and widgets, related posts,
 * next/previous links) builds its collection through addActiveFilter(); the post page itself does not use a
 * collection. Registered in etc/frontend/di.xml only, so the admin grid, cron (sitemap) and REST are unaffected.
 */
class ExcludeShopProfilesFromListings
{
    private const FLAG = 'local_blogapi_shop_profiles_excluded';

    public function __construct(
        private readonly Config $config
    ) {
    }

    public function afterAddActiveFilter(Collection $subject, Collection $result): Collection
    {
        $categoryId = $this->config->getCategoryId();
        if ($categoryId <= 0 || $subject->getFlag(self::FLAG)) {
            return $result;
        }

        $subject->setFlag(self::FLAG, true);
        $hiddenPosts = $subject->getConnection()->select()
            ->from($subject->getTable('magefan_blog_post_category'), ['post_id'])
            ->where('category_id = ?', $categoryId);
        $subject->getSelect()->where('main_table.post_id NOT IN ?', $hiddenPosts);

        return $result;
    }
}
