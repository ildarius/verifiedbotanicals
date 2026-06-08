<?php
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Registry;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

const KEEP_REFERENCE_SKUS = [
    'MK-B-58246', // bundle-product.html
    'MK-G-42963', // grouped-product.html
    'MK-DL-01036', // downloadable-product.html
    'MK-CF-4525', // configurable-product.html
    'MK-F-005', // adidas-alliance-ii-sackpack-black.html
    'MK-S-14523', // simple-product.html
];

const REPORT_PATH = BP . '/var/tmp/demo-catalog-cleanup-report-20260606.txt';

$apply = in_array('--apply', $argv, true);

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

try {
    $objectManager->get(State::class)->setAreaCode('adminhtml');
} catch (\Magento\Framework\Exception\LocalizedException $exception) {
    // Area code may already be set when this script is rerun in-process.
}

$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();
$productAction = $objectManager->get(ProductAction::class);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$registry = $objectManager->get(Registry::class);

if ($registry->registry('isSecureArea')) {
    $registry->unregister('isSecureArea');
}
$registry->register('isSecureArea', true);

$kratomSkus = $connection->fetchCol(
    $connection->select()
        ->from(['e' => 'catalog_product_entity'], ['sku'])
        ->join(['aset' => 'eav_attribute_set'], 'aset.attribute_set_id = e.attribute_set_id', [])
        ->where('aset.attribute_set_name = ?', 'Kratom')
);

$protectedDemoSkus = resolveProtectedDemoSkus($connection, KEEP_REFERENCE_SKUS);

$deleteRows = $connection->fetchAll(
    $connection->select()
        ->from(['e' => 'catalog_product_entity'], ['entity_id', 'sku', 'type_id'])
        ->join(['aset' => 'eav_attribute_set'], 'aset.attribute_set_id = e.attribute_set_id', ['attribute_set_name'])
        ->where('aset.attribute_set_name <> ?', 'Kratom')
        ->where('e.sku NOT IN (?)', $protectedDemoSkus)
        ->order('e.sku ASC')
);

$disableRows = $connection->fetchAll(
    $connection->select()
        ->from(['e' => 'catalog_product_entity'], ['entity_id', 'sku', 'type_id'])
        ->where('e.sku IN (?)', $protectedDemoSkus)
        ->order('e.sku ASC')
);

$reportLines = [];
$reportLines[] = 'Demo catalog cleanup';
$reportLines[] = 'Mode: ' . ($apply ? 'apply' : 'dry-run');
$reportLines[] = 'Kratom products preserved: ' . count($kratomSkus);
$reportLines[] = 'Reference/demo products preserved and disabled: ' . count($disableRows);
$reportLines[] = 'Demo products queued for deletion: ' . count($deleteRows);
$reportLines[] = '';
$reportLines[] = 'Preserved demo SKUs:';
foreach ($disableRows as $row) {
    $reportLines[] = sprintf('- %s [%s]', $row['sku'], $row['type_id']);
}
$reportLines[] = '';
$reportLines[] = 'Deleted demo SKUs:';
foreach ($deleteRows as $row) {
    $reportLines[] = sprintf('- %s [%s / %s]', $row['sku'], $row['type_id'], $row['attribute_set_name']);
}
file_put_contents(REPORT_PATH, implode(PHP_EOL, $reportLines) . PHP_EOL);

echo 'Report: ' . REPORT_PATH . PHP_EOL;
echo 'Kratom products preserved: ' . count($kratomSkus) . PHP_EOL;
echo 'Reference/demo products to preserve and disable: ' . count($disableRows) . PHP_EOL;
echo 'Demo products to delete: ' . count($deleteRows) . PHP_EOL;

if (!$apply) {
    echo 'Dry run only. Re-run with --apply to make changes.' . PHP_EOL;
    exit(0);
}

if ($disableRows) {
    $productAction->updateAttributes(
        array_map('intval', array_column($disableRows, 'entity_id')),
        [
            'status' => Status::STATUS_DISABLED,
            'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        ],
        0
    );
    echo 'Disabled preserved demo products: ' . count($disableRows) . PHP_EOL;
}

$deleted = 0;
foreach ($deleteRows as $row) {
    $productRepository->deleteById($row['sku']);
    $deleted++;
    if ($deleted % 25 === 0) {
        echo 'Deleted ' . $deleted . ' products...' . PHP_EOL;
    }
}

echo 'Deleted demo products: ' . $deleted . PHP_EOL;

function resolveProtectedDemoSkus(\Magento\Framework\DB\Adapter\AdapterInterface $connection, array $seedSkus): array
{
    $protected = array_fill_keys($seedSkus, true);

    do {
        $beforeCount = count($protected);
        $parentSkus = array_keys($protected);

        $configurableChildren = $connection->fetchCol(
            $connection->select()
                ->from(['parent' => 'catalog_product_entity'], [])
                ->join(['link' => 'catalog_product_super_link'], 'link.parent_id = parent.entity_id', [])
                ->join(['child' => 'catalog_product_entity'], 'child.entity_id = link.product_id', ['sku'])
                ->where('parent.sku IN (?)', $parentSkus)
        );

        $groupedChildren = $connection->fetchCol(
            $connection->select()
                ->from(['parent' => 'catalog_product_entity'], [])
                ->join(
                    ['link' => 'catalog_product_link'],
                    'link.product_id = parent.entity_id AND link.link_type_id = 3',
                    []
                )
                ->join(['child' => 'catalog_product_entity'], 'child.entity_id = link.linked_product_id', ['sku'])
                ->where('parent.sku IN (?)', $parentSkus)
        );

        $bundleChildren = $connection->fetchCol(
            $connection->select()
                ->from(['parent' => 'catalog_product_entity'], [])
                ->join(['opt' => 'catalog_product_bundle_option'], 'opt.parent_id = parent.entity_id', [])
                ->join(['sel' => 'catalog_product_bundle_selection'], 'sel.option_id = opt.option_id', [])
                ->join(['child' => 'catalog_product_entity'], 'child.entity_id = sel.product_id', ['sku'])
                ->where('parent.sku IN (?)', $parentSkus)
        );

        foreach (array_merge($configurableChildren, $groupedChildren, $bundleChildren) as $sku) {
            $protected[$sku] = true;
        }
    } while (count($protected) > $beforeCount);

    ksort($protected);
    return array_keys($protected);
}
