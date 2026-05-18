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

$csvFile = 'data/products.csv';
if (!file_exists($csvFile)) {
    die("File not found: $csvFile\n");
}

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
        $option->setSortOrder(0);
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

function getCategoryIds($categoryNames, $categoryCollectionFactory) {
    $names = array_map('trim', explode(',', $categoryNames));
    $collection = $categoryCollectionFactory->create()
        ->addAttributeToFilter('name', ['in' => $names]);
    return $collection->getAllIds();
}

function isRowInStock(array $row): bool {
    $value = strtolower(trim((string)($row['In stock?'] ?? '')));
    return $value === 'yes' || $value === '1';
}

function getRowQty(array $row): float {
    $rawQty = trim((string)($row['Stock'] ?? ''));
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

$simplesByParent = [];

// PASS 1: Create Simple Products (variations)
echo "Pass 1: Creating Simple Products\n";
foreach ($productsData as $row) {
    if ($row['Type'] !== 'variation') continue;

    echo "Processing variation: " . $row['SKU'] . "\n";
    $isInStock = isRowInStock($row);
    $qty = getRowQty($row);
    
    try {
        $product = $productRepository->get($row['SKU']);
        echo "Product " . $row['SKU'] . " already exists, updating.\n";
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        $product = $productFactory->create();
        $product->setSku($row['SKU']);
    }

    $product->setName($row['Name']);
    $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
    $product->setAttributeSetId($attributeSetId);
    $product->setPrice($row['Regular price'] ?: 0);
    $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);
    $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
    $product->setStockData([
        'use_config_manage_stock' => 0,
        'manage_stock' => 1,
        'is_in_stock' => $isInStock ? 1 : 0,
        'qty' => $qty
    ]);

    // Extract weight from name (e.g., "Red Bali - 25g")
    if (preg_match('/(\d+g)/', $row['Name'], $matches)) {
        $weightLabel = $matches[1];
        if (isset($optionMap[$weightLabel])) {
            $product->setData($attributeCode, $optionMap[$weightLabel]);
        }
    }

    $product->setCategoryIds(getCategoryIds($row['Categories'], $categoryCollectionFactory));
    $productRepository->save($product);
    saveDefaultSourceItem($row['SKU'], $qty, $isInStock, $sourceItemFactory, $sourceItemsSave);
    echo "Created/Updated simple product: " . $row['SKU'] . "\n";
    
    $simplesByParent[$row['Parent']][] = $row['SKU'];
}

// PASS 2: Create Configurable Products (variables)
echo "Pass 2: Creating Configurable Products\n";
foreach ($productsData as $row) {
    if ($row['Type'] !== 'variable') continue;

    echo "Processing variable product: " . $row['SKU'] . "\n";
    
    try {
        $product = $productRepository->get($row['SKU']);
        echo "Product " . $row['SKU'] . " already exists, updating links.\n";
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        $product = $productFactory->create();
        $product->setSku($row['SKU']);
    }

    $product->setName($row['Name']);
    $product->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE);
    $product->setAttributeSetId($attributeSetId);
    $product->setPrice(0);
    $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
    $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
    $product->setDescription($row['Description']);
    $product->setShortDescription($row['Short Description']);
    $product->setWeight($row['Weight (kg)'] ?: 0);
    $product->setStockData([
        'use_config_manage_stock' => 0,
        'manage_stock' => 1,
        'is_in_stock' => 1,
        'qty' => 1
    ]);
    $product->setCategoryIds(getCategoryIds($row['Categories'], $categoryCollectionFactory));

    // Link simples to configurable
    if (isset($simplesByParent[$row['SKU']])) {
        $associatedIds = [];
        $attributeValues = [];
        foreach ($simplesByParent[$row['SKU']] as $simpleSku) {
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
    echo "Created/Updated configurable product: " . $row['SKU'] . "\n";
}

echo "Import finished.\n";
