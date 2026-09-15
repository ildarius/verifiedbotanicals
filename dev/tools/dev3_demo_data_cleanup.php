#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Explicit, dependency-aware cleanup for the dev3 demo inventory reviewed on
 * 2026-09-08. Defaults to a dry run; pass --apply only after a backup.
 *
 * Run inside the web container:
 *   php dev/tools/dev3_demo_data_cleanup.php
 *   php dev/tools/dev3_demo_data_cleanup.php --apply
 */

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ResourceConnection;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

$apply = in_array('--apply', $argv, true);
$productIds = [42, 43, 46, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60,
    140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154,
    155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169,
    170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184,
    185, 186, 187, 258, 280, 281, 282, 283];
$reviewIds = [41, 43, 47, 50, 51, 52, 53, 54, 192];
$orderIds = [1, 2, 3, 4, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25];
$customerIds = range(1, 19);
$pageIds = [5, 6, 7];
$attributeIds = [140, 141, 142];
$groupIds = array_merge([1], range(5, 40), range(42, 44));
$storeIds = array_merge([1], range(39, 110), range(112, 118));

if (count($productIds) !== 67 || count($reviewIds) !== 9 || count($orderIds) !== 19
    || count($customerIds) !== 19 || count($pageIds) !== 3 || count($groupIds) !== 40
    || count($storeIds) !== 80) {
    throw new RuntimeException('The explicit cleanup inventory has been altered. Refusing to run.');
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
/** @var ResourceConnection $resource */
$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();
$in = static fn(array $ids): string => implode(',', array_map('intval', $ids));

$checks = [
    'demo products' => ['catalog_product_entity', 'entity_id', $productIds, 67],
    'demo reviews' => ['review', 'review_id', $reviewIds, 9],
    'live Kratom reviews retained' => ['review', 'review_id', range(239, 262), 24],
    'demo orders' => ['sales_order', 'entity_id', $orderIds, 19],
    'demo customers' => ['customer_entity', 'entity_id', $customerIds, 19],
    'demo CMS pages remaining' => ['cms_page', 'page_id', $pageIds, 3],
    'demo attributes' => ['eav_attribute', 'attribute_id', $attributeIds, 3],
    'live storefront' => ['store', 'store_id', [111], 1],
    'demo store views' => ['store', 'store_id', $storeIds, 80],
    'demo store groups' => ['store_group', 'group_id', $groupIds, 40],
];

foreach ($checks as $label => [$table, $column, $ids, $expected]) {
    $actual = (int) $connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s WHERE %s IN (%s)', $table, $column, $in($ids)));
    printf("%-36s %d%s\n", $label . ':', $actual, $actual === $expected ? '' : " (expected {$expected})");
    if ($actual !== $expected) {
        throw new RuntimeException("Preflight failed for {$label}.");
    }
}

if (in_array(470, $productIds, true)) {
    throw new RuntimeException('Protected product 470 is in the deletion inventory. Refusing to run.');
}
printf("%-36s %s\n", 'protected product 470:', $connection->fetchOne('SELECT COUNT(*) FROM catalog_product_entity WHERE entity_id = 470') ? 'present; excluded' : 'absent; excluded');
printf("%-36s %s\n", 'protected about-us page:', $connection->fetchOne('SELECT COUNT(*) FROM cms_page WHERE page_id = 8') ? 'present; excluded' : 'absent; excluded');

$autoSetId = (int) $connection->fetchOne("SELECT attribute_set_id FROM eav_attribute_set WHERE attribute_set_name = 'Auto'");
if (!$autoSetId || (int) $connection->fetchOne("SELECT COUNT(*) FROM catalog_product_entity WHERE attribute_set_id = {$autoSetId}") !== 0) {
    throw new RuntimeException('Auto attribute set is missing or still has products.');
}

$details = [
    'product URL rewrites' => "SELECT COUNT(*) FROM url_rewrite WHERE entity_type = 'product' AND entity_id IN ({$in($productIds)})",
    'store-scoped configuration rows' => "SELECT COUNT(*) FROM core_config_data WHERE scope = 'stores' AND scope_id IN ({$in($storeIds)})",
    'CMS block assignments affected' => "SELECT COUNT(*) FROM cms_block_store WHERE store_id IN ({$in($storeIds)})",
];
foreach ($details as $label => $sql) {
    printf("%-36s %d\n", $label . ':', (int) $connection->fetchOne($sql));
}

if (!$apply) {
    echo "Dry run complete. No data was changed. Re-run with --apply after confirming current backups.\n";
    exit(0);
}

$connection->beginTransaction();
try {
    // Orphaned grid/index and URL-rewrite rows are not all protected by FKs.
    $connection->query("DELETE FROM sales_order_grid WHERE entity_id IN ({$in($orderIds)})");
    $connection->query("DELETE FROM sales_invoice_grid WHERE order_id IN ({$in($orderIds)})");
    $connection->query("DELETE FROM sales_shipment_grid WHERE order_id IN ({$in($orderIds)})");
    $connection->query("DELETE FROM sales_creditmemo_grid WHERE order_id IN ({$in($orderIds)})");
    $connection->query("DELETE FROM sales_order WHERE entity_id IN ({$in($orderIds)})");
    $connection->query("DELETE FROM review WHERE review_id IN ({$in($reviewIds)})");
    $connection->query("DELETE FROM review_entity_summary WHERE entity_pk_value IN ({$in($productIds)})");
    $connection->query("DELETE FROM url_rewrite WHERE entity_type = 'product' AND entity_id IN ({$in($productIds)})");
    $connection->query("DELETE FROM catalog_product_entity WHERE entity_id IN ({$in($productIds)})");
    $connection->query("DELETE FROM customer_entity WHERE entity_id IN ({$in($customerIds)})");

    // Remove configuration at deleted scopes. No cms_block or cms_block_store row is touched.
    $connection->query("DELETE FROM core_config_data WHERE scope = 'stores' AND scope_id IN ({$in($storeIds)})");
    $connection->query("DELETE FROM core_config_data WHERE scope = 'groups' AND scope_id IN ({$in($groupIds)})");
    $connection->query("DELETE FROM store WHERE store_id IN ({$in($storeIds)})");
    $connection->query("DELETE FROM store_group WHERE group_id IN ({$in($groupIds)})");

    $connection->query("DELETE FROM cms_page WHERE page_id IN ({$in($pageIds)})");
    $connection->query("DELETE FROM eav_attribute_set WHERE attribute_set_id = {$autoSetId}");
    $connection->query("DELETE FROM eav_attribute WHERE attribute_id IN ({$in($attributeIds)})");
    $connection->commit();
} catch (Throwable $exception) {
    $connection->rollBack();
    throw $exception;
}

echo "Cleanup applied successfully. Reindex and clear Magento caches before storefront validation.\n";
