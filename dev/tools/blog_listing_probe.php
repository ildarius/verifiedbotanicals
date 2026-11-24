<?php
// Read-only. Usage: php dev/tools/blog_listing_probe.php <frontend|crontab> <category_id>
// Prints how many posts each listing collection returns and whether any belongs to the category.
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$area = $argv[1] ?? 'frontend';
$cat = (int)($argv[2] ?? 0);
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode($area);
$om->configure($om->get(\Magento\Framework\App\ObjectManager\ConfigLoader::class)->load($area));
$sm = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$store = $sm->getDefaultStoreView(); $sm->setCurrentStore($store->getId());
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$hidden = array_map('intval', $conn->fetchCol('select post_id from magefan_blog_post_category where category_id=?', [$cat]));
echo "area=$area store={$store->getId()} category=$cat hidden posts=" . count($hidden) . "\n";
$show = function ($name, $ids) use ($hidden) {
    $ids = array_map('intval', array_filter((array)$ids)); $bad = array_intersect($ids, $hidden);
    printf("%-32s %3d posts, hidden shown: %s\n", $name, count($ids), $bad ? implode(',', $bad) : 'none');
};
if ($area === 'crontab') {
    $items = $om->create(\Magefan\Blog\Model\Sitemap\ItemProvider\Post::class)->getItems($store->getId());
    echo 'sitemap blog posts: ' . count($items) . "\n"; foreach ($items as $i) { if (str_contains($i->getUrl(), 'smoke-shop')) echo '  ' . $i->getUrl() . "\n"; } exit;
}
if (isset($argv[3])) { // test a category ID without touching config: this process only
    class ShopProfilesConfigStub extends \Local\BlogApi\Model\ShopProfiles\Config {
        public static int $id = 0; public function __construct() {} public function getCategoryId(): int { return self::$id; }
    }
    ShopProfilesConfigStub::$id = (int)$argv[3];
    $shared = new ReflectionProperty(\Magento\Framework\ObjectManager\ObjectManager::class, '_sharedInstances');
    $all = $shared->getValue($om); $all[\Local\BlogApi\Model\ShopProfiles\Config::class] = new ShopProfilesConfigStub(); $shared->setValue($om, $all);
}
$coll = $om->create(\Magefan\Blog\Model\ResourceModel\Post\Collection::class); echo get_class($coll), " cfg=", $om->get(\Local\BlogApi\Model\ShopProfiles\Config::class)->getCategoryId(), PHP_EOL;
if (getenv('PROBE_DEBUG')) {
    $coll->addActiveFilter(); echo $coll->getSelect(), "\n";
    $pl = $om->get(\Magento\Framework\Interception\PluginListInterface::class);
    var_export($pl->getNext(\Magefan\Blog\Model\ResourceModel\Post\Collection::class, 'addActiveFilter')); echo "\n", get_class($pl), ' scope=', $om->get(\Magento\Framework\Config\ScopeInterface::class)->getCurrentScope(), "\n"; exit;
}
$reg = $om->get(\Magento\Framework\Registry::class);
$req = $om->get(\Magento\Framework\App\RequestInterface::class);
$load = fn($c, $id) => $om->create($c)->load($id);
$post = $load(\Magefan\Blog\Model\Post::class, 20);
foreach ($conn->fetchCol('select category_id from magefan_blog_category') as $id) {
    $reg->unregister('current_blog_category');
    $reg->register('current_blog_category', $load(\Magefan\Blog\Model\Category::class, $id));
    $b = $om->create(\Magefan\Blog\Block\Category\PostList::class); $show('category ' . $id, $b->getPostCollection()->getColumnValues("post_id"));
}
$reg->unregister('current_blog_category');
foreach ($conn->fetchCol('select distinct tag_id from magefan_blog_tag') as $id) {
    $reg->unregister('current_blog_tag'); $reg->register('current_blog_tag', $load(\Magefan\Blog\Model\Tag::class, $id));
    $ids = $om->create(\Magefan\Blog\Block\Tag\PostList::class)->getPostCollection()->getColumnValues("post_id");
    if (array_intersect($ids, $hidden) || in_array($id, [8, 9, 10, 11, 12])) $show('tag ' . $id, $ids);
}
$show('main list', $om->create(\Magefan\Blog\Block\Index::class)->getPostCollection()->getColumnValues("post_id"));
$show('recent sidebar', $om->create(\Magefan\Blog\Block\Sidebar\Recent::class)->getPostCollection()->getColumnValues("post_id"));
$show('featured widget', $om->create(\Magefan\Blog\Block\Widget\Recent::class)->getPostCollection()->getColumnValues("post_id"));
$show('popular sidebar', $om->create(\Magefan\Blog\Block\Sidebar\Popular::class)->getPostCollection()->getColumnValues("post_id"));
$req->setParam('id', 0);
foreach (['2026-07','2026-08','2026-09','2026-10','2026-11'] as $ym) {
    [$y, $m] = explode('-', $ym);
    $reg->unregister('current_blog_archive_year'); $reg->unregister('current_blog_archive_month');
    $reg->register('current_blog_archive_year', (int)$y); $reg->register('current_blog_archive_month', (int)$m);
    $show('archive ' . $ym, $om->create(\Magefan\Blog\Block\Archive\PostList::class)->getPostCollection()->getColumnValues("post_id"));
}
$req->setParam('q', 'kratom'); 
$show('search kratom', $om->create(\Magefan\Blog\Block\Search\PostList::class)->getPostCollection()->getColumnValues("post_id"));
$show('rss', $om->create(\Magefan\Blog\Block\Rss\Feed::class)->getPostCollection()->getColumnValues("post_id"));
foreach ([29, 44, 53, 20, 52] as $pid) {
    $reg->unregister('current_blog_post'); $p = $load(\Magefan\Blog\Model\Post::class, $pid); $reg->register('current_blog_post', $p);
    $np = $om->create(\Magefan\Blog\Block\Post\View\NextPrev::class);
    $ids = []; foreach ([$np->getPrevPost(), $np->getNextPost()] as $x) { if ($x) $ids[] = $x->getId(); }
    $show("next/prev of $pid", $ids);
    $show("related of $pid", $om->create(\Magefan\Blog\Block\Post\View\RelatedPosts::class)->getPostCollection()->getColumnValues("post_id"));
}
$b = $om->create(\Magefan\Blog\Block\Sidebar\Archive::class); echo 'archive sidebar months: ' . implode(' ', array_keys($b->getMonths())) . "\n";
