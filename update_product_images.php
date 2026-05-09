<?php
declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\Data\ImageContentInterfaceFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;

require __DIR__ . '/app/bootstrap.php';

/**
 * Resize and attach product images for configurable kratom products.
 *
 * Usage:
 *   docker exec -u 1000 ddev-magento-web php update_product_images.php
 */

const SOURCE_DIR = __DIR__ . '/var/tmp';
const ORIGINALS_DIR = __DIR__ . '/var/import/product_images/originals';
const LEGACY_DIR = __DIR__ . '/var/import/product_images/legacy';
const OPTIMIZED_DIR = __DIR__ . '/var/import/product_images/optimized';

const TARGET_SIZE = 1200;
const JPEG_QUALITY = 82;

$productImages = [
    [
        'sku' => 'RB',
        'name' => 'Red Bali',
        'source' => 'Red Bali.png',
        'slug' => 'red-bali-canada',
        'label' => 'Red Bali Canada',
    ],
    [
        'sku' => 'RMD',
        'name' => 'Red Maeng Da',
        'source' => 'Red Maeng Da.png',
        'slug' => 'red-maeng-da-canada',
        'label' => 'Red Maeng Da Canada',
    ],
    [
        'sku' => 'RH',
        'name' => 'Red Hulu / Red Kapuas',
        'source' => 'Red Hulu  Red Kapuas.png',
        'slug' => 'red-hulu-red-kapuas-canada',
        'label' => 'Red Hulu Red Kapuas Canada',
    ],
    [
        'sku' => 'GMD',
        'name' => 'Green Maeng Da',
        'source' => 'Green Maeng Da.png',
        'slug' => 'green-maeng-da-canada',
        'label' => 'Green Maeng Da Canada',
    ],
    [
        'sku' => 'GM',
        'name' => 'Green Malay',
        'source' => 'Green Malay.png',
        'slug' => 'green-malay-canada',
        'label' => 'Green Malay Canada',
    ],
    [
        'sku' => 'GH',
        'name' => 'Green Hulu / Green Kapuas',
        'source' => 'Green Hulu Green Kapuas.png',
        'slug' => 'green-hulu-green-kapuas-canada',
        'label' => 'Green Hulu Green Kapuas Canada',
    ],
];

$legacyFiles = [
    'Red-Bali-1x1.png',
    'Red-Bali-300x300.png',
    'Red-Maeng-Da-1x1.png',
    'Red-Maeng-Da-300x300.png',
    'Red-Hulu--Red-Kapuas-1x1.png',
    'Red-Hulu--Red-Kapuas-300x300.png',
    'Green-Maeng-Da-1x1.png',
    'Green-Maeng-Da-300x300.png',
    'Green-Malay-1x1.png',
    'Green-Malay-300x300.png',
    'Green-Hulu-Green-Kapuas-1x1.png',
    'Green-Hulu-Green-Kapuas-300x300.png',
];

$zoneIdentifierFiles = [
    'Red Bali.png:Zone.Identifier',
    'Red Maeng Da.png:Zone.Identifier',
    'Red Hulu  Red Kapuas.png:Zone.Identifier',
    'Green Maeng Da.png:Zone.Identifier',
    'Green Malay.png:Zone.Identifier',
    'Green Hulu Green Kapuas.png:Zone.Identifier',
];

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(State::class);

try {
    $state->setAreaCode('adminhtml');
} catch (\Throwable $exception) {
}

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var ProductAttributeMediaGalleryManagementInterface $galleryManagement */
$galleryManagement = $objectManager->get(ProductAttributeMediaGalleryManagementInterface::class);
/** @var ProductAttributeMediaGalleryEntryInterfaceFactory $entryFactory */
$entryFactory = $objectManager->get(ProductAttributeMediaGalleryEntryInterfaceFactory::class);
/** @var ImageContentInterfaceFactory $contentFactory */
$contentFactory = $objectManager->get(ImageContentInterfaceFactory::class);

ensureDirectory(ORIGINALS_DIR);
ensureDirectory(LEGACY_DIR);
ensureDirectory(OPTIMIZED_DIR);

moveAuxiliaryFiles($legacyFiles, LEGACY_DIR);
moveAuxiliaryFiles($zoneIdentifierFiles, LEGACY_DIR);

$failures = [];

foreach ($productImages as $productImage) {
    try {
        $originalPath = moveOriginalToPermanentStorage($productImage['source'], $productImage['slug']);
        $optimizedPath = OPTIMIZED_DIR . '/' . $productImage['slug'] . '.jpg';

        optimizeImage($originalPath, $optimizedPath);
        attachImageToProduct(
            $productRepository,
            $galleryManagement,
            $entryFactory,
            $contentFactory,
            $productImage['sku'],
            $productImage['label'],
            $optimizedPath
        );

        echo sprintf(
            "Updated %s (%s) using %s\n",
            $productImage['sku'],
            $productImage['name'],
            basename($optimizedPath)
        );
    } catch (\Throwable $exception) {
        $failures[] = sprintf('%s: %s', $productImage['sku'], $exception->getMessage());
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Completed with failures:\n" . implode("\n", $failures) . "\n");
    exit(1);
}

echo "All product images updated successfully.\n";

function ensureDirectory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Unable to create directory: ' . $path);
    }
}

function moveAuxiliaryFiles(array $filenames, string $destinationDir): void
{
    foreach ($filenames as $filename) {
        $source = SOURCE_DIR . '/' . $filename;
        $destination = $destinationDir . '/' . $filename;

        if (!file_exists($source) || file_exists($destination)) {
            continue;
        }

        if (!rename($source, $destination)) {
            throw new RuntimeException('Unable to move auxiliary file: ' . $filename);
        }
    }
}

function moveOriginalToPermanentStorage(string $sourceFilename, string $slug): string
{
    $permanentPath = ORIGINALS_DIR . '/' . $slug . '.png';
    $tempPath = SOURCE_DIR . '/' . $sourceFilename;

    if (file_exists($tempPath) && !file_exists($permanentPath)) {
        if (!rename($tempPath, $permanentPath)) {
            throw new RuntimeException('Unable to move source image: ' . $sourceFilename);
        }
    }

    if (!file_exists($permanentPath)) {
        throw new RuntimeException('Source image not found in temp or permanent storage: ' . $sourceFilename);
    }

    return $permanentPath;
}

function optimizeImage(string $inputPath, string $outputPath): void
{
    $command = sprintf(
        'magick %3$s -gravity center -crop 1536x1536+0+0 +repage -resize %1$dx%1$d -background white -alpha remove -alpha off -colorspace sRGB -strip -interlace Plane -sampling-factor 4:2:0 -quality %2$d %4$s',
        TARGET_SIZE,
        JPEG_QUALITY,
        escapeshellarg($inputPath),
        escapeshellarg($outputPath)
    );

    exec($command . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        throw new RuntimeException("Image optimization failed for {$inputPath}: " . implode("\n", $output));
    }
}

function attachImageToProduct(
    ProductRepositoryInterface $productRepository,
    ProductAttributeMediaGalleryManagementInterface $galleryManagement,
    ProductAttributeMediaGalleryEntryInterfaceFactory $entryFactory,
    ImageContentInterfaceFactory $contentFactory,
    string $sku,
    string $label,
    string $imagePath
): void {
    $product = $productRepository->get($sku, true, 0, true);

    foreach ($galleryManagement->getList($sku) as $entry) {
        $galleryManagement->remove($sku, (int) $entry->getId());
    }

    $content = $contentFactory->create();
    $content->setBase64EncodedData(base64_encode((string) file_get_contents($imagePath)));
    $content->setType('image/jpeg');
    $content->setName(basename($imagePath));

    $entry = $entryFactory->create();
    $entry->setMediaType('image');
    $entry->setLabel($label);
    $entry->setPosition(1);
    $entry->setDisabled(false);
    $entry->setTypes(['image', 'small_image', 'thumbnail']);
    $entry->setContent($content);

    $galleryManagement->create($sku, $entry);

    $product = $productRepository->get($sku, true, 0, true);
    $product->setData('image_label', $label);
    $product->setData('small_image_label', $label);
    $product->setData('thumbnail_label', $label);
    $productRepository->save($product);
}
