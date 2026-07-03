<?php
use Magento\Framework\App\Bootstrap;
require 'app/bootstrap.php';

/**
 * Harness to import products from var/tmp/products.csv
 * Usage: docker exec -u 1000 ddev-magento-web php import_products.php
 */

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);

try {
    $state->setAreaCode('adminhtml');
} catch (\Exception $e) {}

$csvCandidates = ['var/tmp/products.csv', 'data/products.csv'];
$csvFile = null;
foreach ($csvCandidates as $candidate) {
    if (file_exists($candidate)) {
        $csvFile = $candidate;
        break;
    }
}

if ($csvFile === null) {
    die("File not found. Checked: " . implode(', ', $csvCandidates) . "\n");
}

echo "Using CSV: $csvFile\n";

$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
$attributeSetFactory = $objectManager->get(\Magento\Eav\Model\Entity\Attribute\SetFactory::class);
$attributeSetManagement = $objectManager->get(\Magento\Eav\Api\AttributeSetManagementInterface::class);
$attributeManagement = $objectManager->get(\Magento\Eav\Api\AttributeManagementInterface::class);
$entityType = $eavConfig->getEntityType('catalog_product');
$defaultAttributeSetId = $entityType->getDefaultAttributeSetId();

// 1. Create "Kratom" Attribute Set if it doesn't exist
$attributeSetName = 'Kratom';
$attributeSet = $attributeSetFactory->create();
$attributeSetCollection = $attributeSet->getCollection()
    ->addFieldToFilter('attribute_set_name', $attributeSetName)
    ->setEntityTypeFilter($entityType->getId())
    ->getFirstItem();

if (!$attributeSetCollection->getId()) {
    echo "Creating Attribute Set: $attributeSetName\n";
    $attributeSet->setData([
        'attribute_set_name' => $attributeSetName,
        'entity_type_id' => $entityType->getId(),
        'sort_order' => 2,
    ]);
    $attributeSetManagement->create('catalog_product', $attributeSet, $defaultAttributeSetId);
    $attributeSetId = $attributeSet->getId();
} else {
    echo "Attribute Set '$attributeSetName' already exists.\n";
    $attributeSetId = $attributeSetCollection->getId();
}

// 2. Create "kratom_weight" attribute if it doesn't exist
$attributeCode = 'kratom_weight';
$attribute = $eavConfig->getAttribute('catalog_product', $attributeCode);

if (!$attribute->getId()) {
    echo "Creating Attribute: $attributeCode\n";
    /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
    $attribute = $objectManager->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
    $attribute->setData([
        'attribute_code' => $attributeCode,
        'entity_type_id' => $entityType->getId(),
        'frontend_label' => ['Kratom Weight'],
        'frontend_input' => 'select',
        'is_required' => 0,
        'is_user_defined' => 1,
        'is_global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
        'is_visible' => 1,
        'is_searchable' => 1,
        'is_filterable' => 1,
        'is_comparable' => 0,
        'is_visible_on_front' => 1,
        'used_in_product_listing' => 1,
        'backend_type' => 'int',
    ]);
    $attribute->save();
} else {
    echo "Attribute '$attributeCode' already exists.\n";
}

// Ensure attribute is in the "Kratom" set
try {
    // Check if attribute is already in the set
    $attributeManagement->assign(
        'catalog_product',
        $attributeSetId,
        $attributeSet->getDefaultGroupId($attributeSetId),
        $attributeCode,
        999
    );
} catch (\Exception $e) {
    // Already assigned or other error
}

// 3. Define and ensure options for kratom_weight
$weights = ['25g', '50g', '100g', '250g', '500g'];
$optionManagement = $objectManager->get(\Magento\Eav\Api\AttributeOptionManagementInterface::class);
$attributeOptionLabelFactory = $objectManager->get(\Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory::class);
$attributeOptionFactory = $objectManager->get(\Magento\Eav\Api\Data\AttributeOptionInterfaceFactory::class);

$existingOptions = $attribute->getSource()->getAllOptions(false);
$optionMap = [];
foreach ($existingOptions as $opt) {
    $optionMap[$opt['label']] = $opt['value'];
}

foreach ($weights as $weight) {
    if (!isset($optionMap[$weight])) {
        echo "Adding option: $weight\n";
        $optionLabel = $attributeOptionLabelFactory->create();
        $optionLabel->setStoreId(0);
        $optionLabel->setLabel($weight);

        $option = $attributeOptionFactory->create();
        $option->setLabel($weight);
        $option->setStoreLabels([$optionLabel]);
        // Keep weight options stable + ordered (small -> big)
        $option->setSortOrder(((int)array_search($weight, $weights, true) + 1) * 10);
        $option->setIsDefault(false);

        $optionManagement->add('catalog_product', $attributeCode, $option);
        
        // Refresh attribute to get new option ID
        $attribute = $eavConfig->getAttribute('catalog_product', $attributeCode, true);
        $newOptions = $attribute->getSource()->getAllOptions(false);
        foreach ($newOptions as $opt) {
            if ($opt['label'] == $weight) {
                $optionMap[$weight] = $opt['value'];
            }
        }
    }
}

// Ensure existing option sort order is correct (small -> big)
try {
    $resourceConnectionForOptionSort = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
    $connectionForOptionSort = $resourceConnectionForOptionSort->getConnection();
    $optionTable = $resourceConnectionForOptionSort->getTableName('eav_attribute_option');

    foreach ($weights as $idx => $weight) {
        if (!isset($optionMap[$weight])) {
            continue;
        }
        $optionId = (int)$optionMap[$weight];
        if ($optionId <= 0) {
            continue;
        }

        $connectionForOptionSort->update(
            $optionTable,
            ['sort_order' => ($idx + 1) * 10],
            ['option_id = ?' => $optionId]
        );
    }
} catch (\Throwable $e) {
    echo "Warning: Failed to enforce kratom_weight option sort order: " . $e->getMessage() . "\n";
}

// Ensure kratom_weight renders as text swatches (like demo "size" swatches)
try {
    $resourceConnectionForSwatches = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
    $connectionForSwatches = $resourceConnectionForSwatches->getConnection();
    $catalogEavAttributeTable = $resourceConnectionForSwatches->getTableName('catalog_eav_attribute');
    $swatchTable = $resourceConnectionForSwatches->getTableName('eav_attribute_option_swatch');

    $attributeId = (int)$attribute->getId();
    $existingAdditionalDataRaw = $connectionForSwatches->fetchOne(
        "SELECT additional_data FROM {$catalogEavAttributeTable} WHERE attribute_id = ?",
        [$attributeId]
    );

    $existingAdditionalData = [];
    if (is_string($existingAdditionalDataRaw) && trim($existingAdditionalDataRaw) !== '') {
        $decoded = json_decode($existingAdditionalDataRaw, true);
        if (is_array($decoded)) {
            $existingAdditionalData = $decoded;
        }
    }

    $updatedAdditionalData = array_merge($existingAdditionalData, [
        'swatch_input_type' => 'text',
        'update_product_preview_image' => '1',
        'use_product_image_for_swatch' => 0,
    ]);

    $connectionForSwatches->update(
        $catalogEavAttributeTable,
        [
            'additional_data' => json_encode($updatedAdditionalData, JSON_UNESCAPED_SLASHES),
            'is_filterable_in_search' => 1,
        ],
        ['attribute_id = ?' => $attributeId]
    );

    // Seed text swatches for the weight options for admin(0) + default store(1)
    $swatchRows = [];
    foreach ($weights as $weight) {
        if (!isset($optionMap[$weight])) {
            continue;
        }
        $optionId = (int)$optionMap[$weight];
        if ($optionId <= 0) {
            continue;
        }

        foreach ([0, 1] as $storeId) {
            $swatchRows[] = [
                'option_id' => $optionId,
                'store_id' => $storeId,
                'type' => 0, // 0 = text
                'value' => $weight,
            ];
        }
    }

    if ($swatchRows !== []) {
        $connectionForSwatches->insertOnDuplicate($swatchTable, $swatchRows, ['type', 'value']);
    }

    // Magento caches the swatch attribute list; clean relevant caches so listing swatches appear immediately.
    try {
        /** @var \Magento\Framework\App\Cache\TypeListInterface $cacheTypeListForSwatches */
        $cacheTypeListForSwatches = $objectManager->get(\Magento\Framework\App\Cache\TypeListInterface::class);
        foreach (['config', 'eav', 'block_html', 'full_page'] as $cacheType) {
            $cacheTypeListForSwatches->cleanType($cacheType);
        }
    } catch (\Throwable $e) {
        echo "Warning: Failed to clean caches after swatch setup: " . $e->getMessage() . "\n";
    }
} catch (\Throwable $e) {
    // Non-fatal: products/import should still work even if swatch setup fails.
    echo "Warning: Failed to configure kratom_weight swatches: " . $e->getMessage() . "\n";
}

// 4. Parse CSV and Import
$productsData = [];
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $header = fgetcsv($handle, 1000, ",");
    
    // Strip BOM from first column name if present
    if (isset($header[0])) {
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    }
    
    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        if (count($header) == count($data)) {
            $productsData[] = array_combine($header, $data);
        }
    }
    fclose($handle);
}

$categoryFactory = $objectManager->get(\Magento\Catalog\Model\CategoryFactory::class);
$productFactory = $objectManager->get(\Magento\Catalog\Model\ProductFactory::class);
$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$categoryCollectionFactory = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class);
$sourceItemFactory = $objectManager->get(\Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory::class);
$sourceItemsSave = $objectManager->get(\Magento\InventoryApi\Api\SourceItemsSaveInterface::class);
$cycleStorage = $objectManager->get(\Local\RotatingSpecialDeals\Service\CycleStorage::class);
$rotationConfig = $objectManager->get(\Local\RotatingSpecialDeals\Service\RotationConfig::class);
$resourceConnection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$indexerRegistry = $objectManager->get(\Magento\Framework\Indexer\IndexerRegistry::class);
$cacheTypeList = $objectManager->get(\Magento\Framework\App\Cache\TypeListInterface::class);

function getFirstRowValue(array $row, array $keys, string $default = ''): string {
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && trim((string)$row[$key]) !== '') {
            return trim((string)$row[$key]);
        }
    }

    return $default;
}

function getCategoryIds($categoryNames, $categoryCollectionFactory) {
    if (trim((string)$categoryNames) === '') {
        return [];
    }

    $names = array_map('trim', explode(',', $categoryNames));
    $collection = $categoryCollectionFactory->create()
        ->addAttributeToFilter('name', ['in' => $names]);
    return $collection->getAllIds();
}

function isRowInStock(array $row): bool {
    $value = strtolower(getFirstRowValue($row, ['In stock?', 'is_in_stock'], '0'));
    return $value === 'yes' || $value === '1';
}

function getRowQty(array $row): float {
    $rawQty = getFirstRowValue($row, ['Stock', 'qty']);
    if ($rawQty !== '' && is_numeric($rawQty)) {
        return (float)$rawQty;
    }

    return isRowInStock($row) ? 1.0 : 0.0;
}

function saveDefaultSourceItem(
    string $sku,
    float $qty,
    bool $isInStock,
    $sourceItemFactory,
    $sourceItemsSave
): void {
    $sourceItem = $sourceItemFactory->create();
    $sourceItem->setSourceCode('default');
    $sourceItem->setSku($sku);
    $sourceItem->setQuantity($qty);
    $sourceItem->setStatus($isInStock ? 1 : 0);
    $sourceItemsSave->execute([$sourceItem]);
}

function getRowType(array $row): string {
    return strtolower(getFirstRowValue($row, ['Type', 'product_type']));
}

function isSimpleRow(array $row): bool {
    return in_array(getRowType($row), ['variation', 'simple'], true);
}

function isConfigurableRow(array $row): bool {
    return in_array(getRowType($row), ['variable', 'configurable'], true);
}

function getRowSku(array $row): string {
    return getFirstRowValue($row, ['SKU', 'sku']);
}

function getRowName(array $row): string {
    return getFirstRowValue($row, ['Name', 'name']);
}

function getRowCategories(array $row): string {
    return getFirstRowValue($row, ['Categories', 'categories']);
}

function getRowPrice(array $row): float {
    $value = getFirstRowValue($row, ['Regular price', 'price'], '0');
    return is_numeric($value) ? (float)$value : 0.0;
}

function getRowDescription(array $row): string {
    return getFirstRowValue($row, ['Description', 'description']);
}

function getRowShortDescription(array $row): string {
    return getFirstRowValue($row, ['Short Description', 'short_description']);
}

function getRowWeightValue(array $row): float {
    $value = getFirstRowValue($row, ['Weight (kg)', 'weight'], '0');
    return is_numeric($value) ? (float)$value : 0.0;
}

function getRowParentSku(array $row): string {
    return getFirstRowValue($row, ['Parent', '_internal_parent_sku']);
}

function getRowWeightLabel(array $row): string {
    $label = getFirstRowValue($row, ['_internal_size_label']);
    if ($label !== '') {
        return $label;
    }

    if (preg_match('/(\d+g)/i', getRowName($row), $matches)) {
        return $matches[1];
    }

    return '';
}

function getBundledWhiteKratomRows(): array {
    $definitions = [
        [
            'sku' => 'WMD',
            'name' => 'White Maeng Da',
            'categories' => 'White Vein Kratom',
            'description' => '<p><strong>White Maeng Da Kratom Powder Product Details</strong></p><p>White Maeng Da is a well-known white-vein kratom powder name used in the botanical trade. Our lots are sourced through established Indonesian farm and collection networks with a focus on mature-leaf harvesting, clean handling, controlled drying, and batch traceability.</p><p>After harvest, material is cleaned, dried, milled, microground, filtered, and verified through standard quality-control checks including contaminant, heavy-metals, and microbial screening. This listing is provided for botanical reference and research catalog purposes only. <strong>Not intended for human consumption.</strong></p>',
            'short_description' => '<p><strong>White Maeng Da</strong> is a white-vein kratom powder name commonly associated with carefully dried mature leaves sourced through established Indonesian farm and collection networks. Lots are selected for consistency, batch traceability, clean handling, and a uniform finished powder. Not intended for human consumption.</p>',
        ],
        [
            'sku' => 'WM',
            'name' => 'White Malay',
            'categories' => 'White Vein Kratom',
            'description' => '<p><strong>White Malay Kratom Powder Product Details</strong></p><p>White Malay is a white-vein kratom powder name used in the botanical marketplace for lots prepared from mature leaves and handled through established regional sourcing networks. The focus is on consistent leaf selection, careful drying, batch traceability, and a clean finished powder.</p><p>Each batch is processed through inspection, drying, milling, microgrinding, contaminant filtration, and routine quality-control checks. This product is offered for botanical reference and research catalog use only. <strong>Not intended for human consumption.</strong></p>',
            'short_description' => '<p><strong>White Malay</strong> is a white-vein kratom powder name linked with mature-leaf sourcing, careful drying, and consistent batch handling for a clean, uniform botanical powder. Not intended for human consumption.</p>',
        ],
        [
            'sku' => 'WH',
            'name' => 'White Hulu / White Kapuas',
            'categories' => 'White Vein Kratom',
            'description' => '<p><strong>White Hulu / White Kapuas Kratom Powder Product Details</strong></p><p>White Hulu / White Kapuas is a regional white-vein kratom powder name associated with Indonesian sourcing networks and mature-leaf selection. Lots are handled with attention to clean processing, traceability, and consistent post-harvest drying.</p><p>After harvest, material is cleaned, dried, milled, microground, filtered, and verified through routine quality-control checks before release. This listing is for botanical reference and research catalog purposes only. <strong>Not intended for human consumption.</strong></p>',
            'short_description' => '<p><strong>White Hulu / White Kapuas</strong> is a white-vein kratom powder name associated with Indonesian regional sourcing and consistent post-harvest handling for a smooth, uniform botanical powder. Not intended for human consumption.</p>',
        ],
    ];

    $weights = [
        ['label' => '25g', 'suffix' => '25', 'price' => 9.25, 'weight' => 0.025],
        ['label' => '50g', 'suffix' => '50', 'price' => 17.00, 'weight' => 0.05],
        ['label' => '100g', 'suffix' => '100', 'price' => 27.75, 'weight' => 0.1],
        ['label' => '250g', 'suffix' => '250', 'price' => 55.50, 'weight' => 0.25],
        ['label' => '500g', 'suffix' => '500', 'price' => 101.75, 'weight' => 0.5],
    ];

    $rows = [];
    foreach ($definitions as $definition) {
        $rows[] = [
            'sku' => $definition['sku'],
            'product_type' => 'configurable',
            'categories' => $definition['categories'],
            'name' => $definition['name'],
            'description' => $definition['description'],
            'short_description' => $definition['short_description'],
            'is_in_stock' => '1',
        ];

        foreach ($weights as $weight) {
            $rows[] = [
                'sku' => $definition['sku'] . $weight['suffix'],
                'product_type' => 'simple',
                'name' => $definition['name'] . ' – ' . $weight['label'],
                'price' => (string)$weight['price'],
                'qty' => '999',
                'is_in_stock' => '1',
                'weight' => (string)$weight['weight'],
                '_internal_size_label' => $weight['label'],
                '_internal_parent_sku' => $definition['sku'],
            ];
        }
    }

    return $rows;
}

function mergeMissingRowsBySku(array $productsData, array $additionalRows): array {
    $existingSkus = [];
    foreach ($productsData as $row) {
        $sku = getRowSku($row);
        if ($sku !== '') {
            $existingSkus[$sku] = true;
        }
    }

    foreach ($additionalRows as $row) {
        $sku = getRowSku($row);
        if ($sku === '' || isset($existingSkus[$sku])) {
            continue;
        }

        $productsData[] = $row;
        $existingSkus[$sku] = true;
    }

    return $productsData;
}

function getStoredPriceBySku(string $sku, $resourceConnection): float {
    $connection = $resourceConnection->getConnection();
    $attributeId = (int)$connection->fetchOne(
        "SELECT attribute_id
        FROM eav_attribute
        WHERE attribute_code = 'price'
          AND entity_type_id = (
              SELECT entity_type_id
              FROM eav_entity_type
              WHERE entity_type_code = 'catalog_product'
          )"
    );

    if ($attributeId <= 0) {
        return 0.0;
    }

    $value = $connection->fetchOne(
        "SELECT d.value
        FROM catalog_product_entity e
        JOIN catalog_product_entity_decimal d
          ON d.entity_id = e.entity_id
        WHERE e.sku = ?
          AND d.attribute_id = ?
          AND d.store_id = 0
        LIMIT 1",
        [$sku, $attributeId]
    );

    return $value === false ? 0.0 : round((float)$value, 2);
}

$productsData = mergeMissingRowsBySku($productsData, getBundledWhiteKratomRows());

$simplesByParent = [];
$simplePricesByParent = [];
$updatedProductIds = [];

// PASS 1: Create Simple Products (variations)
echo "Pass 1: Creating Simple Products\n";
foreach ($productsData as $row) {
    if (!isSimpleRow($row)) continue;

    $sku = getRowSku($row);
    echo "Processing variation: " . $sku . "\n";
    $isInStock = isRowInStock($row);
    $qty = getRowQty($row);
    
    try {
        $product = $productRepository->get($sku);
        echo "Product " . $sku . " already exists, updating.\n";
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        $product = $productFactory->create();
        $product->setSku($sku);
    }

    $product->setName(getRowName($row));
    $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
    $product->setAttributeSetId($attributeSetId);
    $product->setPrice(getRowPrice($row));
    $product->setWeight(getRowWeightValue($row));
    $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);
    $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
    $product->setStockData([
        'use_config_manage_stock' => 0,
        'manage_stock' => 1,
        'is_in_stock' => $isInStock ? 1 : 0,
        'qty' => $qty
    ]);

    $weightLabel = getRowWeightLabel($row);
    if ($weightLabel !== '' && isset($optionMap[$weightLabel])) {
        $product->setData($attributeCode, $optionMap[$weightLabel]);
    }

    $categoryIds = getCategoryIds(getRowCategories($row), $categoryCollectionFactory);
    if ($categoryIds !== []) {
        $product->setCategoryIds($categoryIds);
    }

    $productRepository->save($product);
    $updatedProductIds[] = (int)$product->getId();
    saveDefaultSourceItem($sku, $qty, $isInStock, $sourceItemFactory, $sourceItemsSave);
    echo "Created/Updated simple product: " . $sku . "\n";
    
    $parentSku = getRowParentSku($row);
    if ($parentSku !== '') {
        $simplesByParent[$parentSku][] = $sku;
        $simplePricesByParent[$parentSku][] = getRowPrice($row);
    }
}

// PASS 2: Create Configurable Products (variables)
echo "Pass 2: Creating Configurable Products\n";
foreach ($productsData as $row) {
    if (!isConfigurableRow($row)) continue;

    $sku = getRowSku($row);
    echo "Processing variable product: " . $sku . "\n";
    
    try {
        $product = $productRepository->get($sku);
        echo "Product " . $sku . " already exists, updating links.\n";
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        $product = $productFactory->create();
        $product->setSku($sku);
    }

    $product->setName(getRowName($row));
    $product->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE);
    $product->setAttributeSetId($attributeSetId);
    $parentPrices = $simplePricesByParent[$sku] ?? [];
    $product->setPrice($parentPrices !== [] ? min($parentPrices) : getRowPrice($row));
    $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
    $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
    $product->setDescription(getRowDescription($row));
    $product->setShortDescription(getRowShortDescription($row));
    $product->setWeight(getRowWeightValue($row));
    $product->setStockData([
        'use_config_manage_stock' => 0,
        'manage_stock' => 1,
        'is_in_stock' => 1,
        'qty' => 1
    ]);
    $product->setCategoryIds(getCategoryIds(getRowCategories($row), $categoryCollectionFactory));

    // Link simples to configurable
    if (isset($simplesByParent[$sku])) {
        $associatedIds = [];
        $attributeValues = [];
        foreach ($simplesByParent[$sku] as $simpleSku) {
            $simpleProduct = $productRepository->get($simpleSku);
            $associatedIds[] = $simpleProduct->getId();
            $attributeValues[] = $simpleProduct->getData($attributeCode);
        }

        $product->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE);
        
        $configurableOptionFactory = $objectManager->get(\Magento\ConfigurableProduct\Api\Data\OptionInterfaceFactory::class);
        $configurableOptionValueFactory = $objectManager->get(\Magento\ConfigurableProduct\Api\Data\OptionValueInterfaceFactory::class);
        $configurableOptionRepository = $objectManager->get(\Magento\ConfigurableProduct\Api\OptionRepositoryInterface::class);

        $values = [];
        foreach (array_unique($attributeValues) as $val) {
            $value = $configurableOptionValueFactory->create();
            $value->setValueIndex($val);
            $values[] = $value;
        }

        $option = $configurableOptionFactory->create();
        $option->setAttributeId($attribute->getId());
        $option->setLabel('Kratom Weight');
        $option->setPosition(0);
        $option->setValues($values);

        $extensionAttributes = $product->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $objectManager->get(\Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory::class)->create();
        }
        $extensionAttributes->setConfigurableProductOptions([$option]);
        $extensionAttributes->setConfigurableProductLinks($associatedIds);
        $product->setExtensionAttributes($extensionAttributes);
    }

    $productRepository->save($product);
    $updatedProductIds[] = (int)$product->getId();
    echo "Created/Updated configurable product: " . $sku . "\n";
}

// Keep the active rotating cycle at the configured discount against fresh base prices.
$activeCycle = $cycleStorage->getActiveCycle();
if ($activeCycle) {
    echo "Refreshing active rotating cycle {$activeCycle['cycle_id']}\n";
    foreach ($activeCycle['items'] as $item) {
        $sku = (string)$item['sku'];
        $product = $productRepository->getById((int)$item['product_id'], true, 0, true);
        $basePrice = getStoredPriceBySku($sku, $resourceConnection);
        if ($basePrice <= 0.0) {
            echo "Skipped special refresh for {$sku}; base price is 0\n";
            continue;
        }

        $specialPrice = round($basePrice * $rotationConfig->getDiscountFactor(), 2);
        $product->setSpecialPrice($specialPrice);
        $product->setSpecialFromDate((string)$activeCycle['started_at']);
        $product->setSpecialToDate((string)$activeCycle['ends_at']);
        $productRepository->save($product);
        $updatedProductIds[] = (int)$product->getId();
        echo "Refreshed active rotating special for {$sku} to {$specialPrice}\n";
    }
}

$updatedProductIds = array_values(array_unique(array_filter($updatedProductIds)));
if ($updatedProductIds !== []) {
    try {
        $indexerRegistry->get('catalog_product_price')->reindexList($updatedProductIds);
    } catch (\Throwable $e) {
        echo "Warning: Failed partial catalog_product_price reindex: " . $e->getMessage() . "\n";
    }

    foreach (['catalog_category_product', 'catalog_product_category', 'cataloginventory_stock', 'inventory'] as $indexerId) {
        try {
            $indexerRegistry->get($indexerId)->reindexList($updatedProductIds);
        } catch (\Throwable $e) {
            echo "Warning: Failed partial {$indexerId} reindex: " . $e->getMessage() . "\n";
        }
    }

    try {
        $indexerRegistry->get('catalogsearch_fulltext')->reindexList($updatedProductIds);
    } catch (\Throwable $e) {
        echo "Warning: Failed partial catalogsearch_fulltext reindex: " . $e->getMessage() . "\n";
    }
}
$cacheTypeList->cleanType('block_html');
$cacheTypeList->cleanType('full_page');

echo "Import finished.\n";
