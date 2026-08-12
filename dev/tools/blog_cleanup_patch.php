<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

const DEMO_CATEGORY_TITLES = [
    'Fashion',
    'Sport Bike',
    'Marketing Tech',
    'eCommerce',
    'Search Parts',
    'Events',
    'Projects',
    'Beauty and Personal Care',
];

$apply = in_array('--apply', $argv, true);

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

try {
    $objectManager->get(State::class)->setAreaCode('adminhtml');
} catch (\Magento\Framework\Exception\LocalizedException $exception) {
    // Area code may already be set for repeated runs.
}

/** @var ResourceConnection $resource */
$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();

$categoryTable = $resource->getTableName('magefan_blog_category');
$categoryStoreTable = $resource->getTableName('magefan_blog_category_store');
$postCategoryTable = $resource->getTableName('magefan_blog_post_category');
$postTable = $resource->getTableName('magefan_blog_post');

$demoCategories = $connection->fetchAll(
    $connection->select()
        ->from($categoryTable, ['category_id', 'title', 'identifier'])
        ->where('title IN (?)', DEMO_CATEGORY_TITLES)
        ->order('title ASC')
);

$montrealPosts = $connection->fetchAll(
    $connection->select()
        ->from($postTable, ['post_id', 'title', 'identifier', 'content', 'short_content'])
        ->where('title LIKE ?', '%Kratom in Montreal%')
);

$updatedPosts = [];
foreach ($montrealPosts as $post) {
    $newContent = removeLoremIpsumParagraphs((string)$post['content']);
    $newShortContent = removeLoremIpsumParagraphs((string)$post['short_content']);

    if ($newContent === (string)$post['content'] && $newShortContent === (string)$post['short_content']) {
        continue;
    }

    $updatedPosts[] = [
        'post_id' => (int)$post['post_id'],
        'title' => $post['title'],
        'identifier' => $post['identifier'],
        'content' => $newContent,
        'short_content' => $newShortContent,
    ];
}

echo 'Blog cleanup patch' . PHP_EOL;
echo 'Mode: ' . ($apply ? 'apply' : 'dry-run') . PHP_EOL;
echo 'Demo categories matched: ' . count($demoCategories) . PHP_EOL;
foreach ($demoCategories as $category) {
    echo sprintf(
        '- category %d: %s (%s)',
        (int)$category['category_id'],
        $category['title'],
        $category['identifier']
    ) . PHP_EOL;
}

echo 'Montreal posts with lorem ipsum cleanup needed: ' . count($updatedPosts) . PHP_EOL;
foreach ($updatedPosts as $post) {
    echo sprintf(
        '- post %d: %s (%s)',
        $post['post_id'],
        $post['title'],
        $post['identifier']
    ) . PHP_EOL;
}

if (!$apply) {
    echo 'Dry run only. Re-run with --apply to execute changes.' . PHP_EOL;
    exit(0);
}

if ($demoCategories) {
    $categoryIds = array_map(static fn(array $row): int => (int)$row['category_id'], $demoCategories);

    $connection->delete($categoryStoreTable, ['category_id IN (?)' => $categoryIds]);
    $connection->delete($postCategoryTable, ['category_id IN (?)' => $categoryIds]);
    $connection->delete($categoryTable, ['category_id IN (?)' => $categoryIds]);
}

foreach ($updatedPosts as $post) {
    $connection->update(
        $postTable,
        [
            'content' => $post['content'],
            'short_content' => $post['short_content'],
            'update_time' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
        ],
        ['post_id = ?' => $post['post_id']]
    );
}

echo 'Applied category cleanup: ' . count($demoCategories) . PHP_EOL;
echo 'Applied post cleanup: ' . count($updatedPosts) . PHP_EOL;

function removeLoremIpsumParagraphs(string $html): string
{
    if ($html === '') {
        return $html;
    }

    $cleaned = preg_replace(
        '/<p\b[^>]*>\s*(?:<[^>]+>\s*)*lorem ipsum\b.*?<\/p>\s*/is',
        '',
        $html
    );

    if ($cleaned === null) {
        return $html;
    }

    return trim($cleaned);
}
