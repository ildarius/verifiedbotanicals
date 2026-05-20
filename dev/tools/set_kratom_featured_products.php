<?php

declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Cms\Model\BlockFactory;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var State $state */
$state = $objectManager->get(State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (\Throwable $exception) {
}

$targetSkus = ['RB', 'RMD', 'RH', 'GMD', 'GM', 'GH'];

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var Action $productAction */
$productAction = $objectManager->get(Action::class);
/** @var ResourceConnection $resourceConnection */
$resourceConnection = $objectManager->get(ResourceConnection::class);
/** @var BlockFactory $blockFactory */
$blockFactory = $objectManager->get(BlockFactory::class);

$targetIds = [];
foreach ($targetSkus as $sku) {
    try {
        $targetIds[] = (int)$productRepository->get($sku, false, null, true)->getId();
    } catch (NoSuchEntityException $exception) {
        fwrite(STDERR, "Missing target SKU: {$sku}\n");
        exit(1);
    }
}

$connection = $resourceConnection->getConnection();
$featuredAttributeId = (int)$connection->fetchOne(
    "SELECT ea.attribute_id
    FROM eav_attribute ea
    JOIN eav_entity_type eet ON eet.entity_type_id = ea.entity_type_id
    WHERE eet.entity_type_code = 'catalog_product'
      AND ea.attribute_code = 'sm_featured'
    LIMIT 1"
);

if ($featuredAttributeId <= 0) {
    fwrite(STDERR, "Could not resolve catalog_product attribute: sm_featured\n");
    exit(1);
}

$currentlyFeaturedIds = array_map(
    'intval',
    $connection->fetchCol(
        "SELECT entity_id
        FROM catalog_product_entity_int
        WHERE attribute_id = ?
          AND store_id = 0
          AND value = 1",
        [$featuredAttributeId]
    )
);

$idsToClear = array_values(array_diff($currentlyFeaturedIds, $targetIds));

if ($idsToClear !== []) {
    $productAction->updateAttributes($idsToClear, ['sm_featured' => 0], 0);
}

$productAction->updateAttributes($targetIds, ['sm_featured' => 1], 0);

$sidebarBlock = $blockFactory->create()->load('product-sidebar', 'identifier');
if ($sidebarBlock->getId()) {
    $updatedContent = preg_replace(
        '/select_category="[^"]*"/',
        'select_category="377,378,379"',
        (string)$sidebarBlock->getContent(),
        1
    );

    if (is_string($updatedContent) && $updatedContent !== $sidebarBlock->getContent()) {
        $sidebarBlock->setContent($updatedContent);
        $sidebarBlock->save();
    }
}

echo "Updated sm_featured attribute.\n";
echo "Cleared: " . count($idsToClear) . "\n";
echo "Set kratom featured: " . count($targetIds) . "\n";
