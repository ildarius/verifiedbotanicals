<?php
use Magento\Framework\App\Bootstrap;
require 'app/bootstrap.php';

/**
 * Harness to import categories from var/tmp/products.csv
 * Usage: docker exec -u 1000 ddev-magento-web php import_categories.php
 */

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);

// Setting area code to 'adminhtml' is required for many repository operations
try {
    $state->setAreaCode('adminhtml');
} catch (\Magento\Framework\Exception\LocalizedException $e) {
    // Area code might already be set
}

$csvFile = 'data/products.csv';
if (!file_exists($csvFile)) {
    die("File not found: $csvFile\n");
}

$categories = [];
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $header = fgetcsv($handle, 1000, ",");
    $catIdx = array_search('Categories', $header);
    if ($catIdx === false) {
        die("Categories column not found in CSV header\n");
    }

    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        if (isset($data[$catIdx]) && !empty($data[$catIdx])) {
            // Split by comma in case of multiple categories per product
            $rowCats = explode(',', $data[$catIdx]);
            foreach ($rowCats as $cat) {
                $categories[] = trim($cat);
            }
        }
    }
    fclose($handle);
}

$uniqueCategories = array_unique($categories);
sort($uniqueCategories);

echo "Found " . count($uniqueCategories) . " unique categories in CSV:\n";
foreach ($uniqueCategories as $cat) {
    echo "- $cat\n";
}

$categoryFactory = $objectManager->get(\Magento\Catalog\Model\CategoryFactory::class);
$categoryRepository = $objectManager->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
$storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
$rootCategoryId = $storeManager->getStore()->getRootCategoryId();

foreach ($uniqueCategories as $categoryName) {
    $collection = $categoryFactory->create()->getCollection()
        ->addAttributeToFilter('name', $categoryName)
        ->setPageSize(1);
    
    if ($collection->getSize() > 0) {
        echo "Category '$categoryName' already exists.\n";
        continue;
    }

    try {
        $category = $categoryFactory->create();
        $category->setName($categoryName);
        $category->setIsActive(true);
        $category->setParentId($rootCategoryId);
        // Path should include the root category (ID 1) and the store root category
        $category->setPath('1/' . $rootCategoryId);
        $categoryRepository->save($category);
        echo "Created category: $categoryName\n";
    } catch (\Exception $e) {
        echo "Error creating category '$categoryName': " . $e->getMessage() . "\n";
    }
}
