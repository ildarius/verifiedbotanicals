<?php

declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Framework\App\ResourceConnection;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var State $state */
$state = $objectManager->get(State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (Throwable $exception) {
}

$csvPath = $argv[1] ?? (BP . '/dev/notes/shipping-matrixrate-ca-weight-rates.csv');
if (!is_string($csvPath) || $csvPath === '' || !is_file($csvPath)) {
    fwrite(STDERR, "MatrixRate import: CSV file not found: {$csvPath}\n");
    exit(1);
}

/** @var ResourceConnection $resource */
$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();

$websiteId = 1;
$conditionName = 'package_weight';

$regionCodeToId = [];
$regionRows = $connection->fetchAll(
    "SELECT region_id, code FROM directory_country_region WHERE country_id = 'CA' AND code IN ('ON','QC')"
);
foreach ($regionRows as $row) {
    $regionCodeToId[(string)$row['code']] = (int)$row['region_id'];
}

$handle = fopen($csvPath, 'r');
if ($handle === false) {
    fwrite(STDERR, "MatrixRate import: failed to open CSV: {$csvPath}\n");
    exit(1);
}

$header = fgetcsv($handle);
if ($header === false || count($header) < 9) {
    fwrite(STDERR, "MatrixRate import: invalid CSV header (expected 9 columns)\n");
    exit(1);
}

$rowsToInsert = [];
$lineNumber = 1;

while (($row = fgetcsv($handle)) !== false) {
    $lineNumber++;

    if ($row === [null] || $row === [] || count(array_filter($row, static fn($v) => (string)$v !== '')) === 0) {
        continue;
    }

    if (count($row) < 9) {
        fclose($handle);
        fwrite(STDERR, "MatrixRate import: invalid row {$lineNumber} (expected 9 columns)\n");
        exit(1);
    }

    $country = trim((string)$row[0]);
    $regionCode = trim((string)$row[1]);
    $city = trim((string)$row[2]);
    $zipFrom = trim((string)$row[3]);
    $zipTo = trim((string)$row[4]);
    $fromValueRaw = trim((string)$row[5]);
    $toValueRaw = trim((string)$row[6]);
    $priceRaw = trim((string)$row[7]);
    $shippingMethod = trim((string)$row[8]);

    if ($country === '' || $country === '*') {
        fclose($handle);
        fwrite(STDERR, "MatrixRate import: row {$lineNumber} has invalid Country (expected CA)\n");
        exit(1);
    }

    $regionId = 0;
    if ($regionCode !== '' && $regionCode !== '*') {
        if (!isset($regionCodeToId[$regionCode])) {
            fclose($handle);
            fwrite(STDERR, "MatrixRate import: row {$lineNumber} has unknown Region/State code: {$regionCode}\n");
            exit(1);
        }
        $regionId = $regionCodeToId[$regionCode];
    }

    $city = ($city === '' || $city === '*') ? '*' : $city;
    $zipFrom = ($zipFrom === '' || $zipFrom === '*') ? '*' : $zipFrom;
    $zipTo = ($zipTo === '' || $zipTo === '*') ? '' : $zipTo;

    if ($fromValueRaw === '' || $fromValueRaw === '*' || (is_numeric($fromValueRaw) && (float)$fromValueRaw <= 0.0)) {
        $fromValue = -1.0;
    } elseif (!is_numeric($fromValueRaw)) {
        fclose($handle);
        fwrite(STDERR, "MatrixRate import: row {$lineNumber} has invalid Weight From: {$fromValueRaw}\n");
        exit(1);
    } else {
        $fromValue = (float)$fromValueRaw;
    }

    if ($toValueRaw === '' || $toValueRaw === '*') {
        $toValue = 10000000.0;
    } elseif (!is_numeric($toValueRaw)) {
        fclose($handle);
        fwrite(STDERR, "MatrixRate import: row {$lineNumber} has invalid Weight To: {$toValueRaw}\n");
        exit(1);
    } else {
        $toValue = (float)$toValueRaw;
    }

    if (!is_numeric($priceRaw)) {
        fclose($handle);
        fwrite(STDERR, "MatrixRate import: row {$lineNumber} has invalid Shipping Price: {$priceRaw}\n");
        exit(1);
    }
    $price = (float)$priceRaw;

    if ($shippingMethod === '' || $shippingMethod === '*') {
        fclose($handle);
        fwrite(STDERR, "MatrixRate import: row {$lineNumber} has invalid Shipping Method\n");
        exit(1);
    }

    $rowsToInsert[] = [
        'website_id' => $websiteId,
        'dest_country_id' => $country,
        'dest_region_id' => $regionId,
        'dest_city' => $city,
        'dest_zip' => $zipFrom,
        'dest_zip_to' => $zipTo,
        'condition_name' => $conditionName,
        'condition_from_value' => $fromValue,
        'condition_to_value' => $toValue,
        'price' => $price,
        'shipping_method' => $shippingMethod,
    ];
}

fclose($handle);

if ($rowsToInsert === []) {
    fwrite(STDERR, "MatrixRate import: no rows found in CSV\n");
    exit(1);
}

$connection->beginTransaction();
try {
    $connection->delete('webshopapps_matrixrate', ['website_id = ?' => $websiteId]);
    $connection->insertMultiple('webshopapps_matrixrate', $rowsToInsert);
    $connection->commit();
} catch (Throwable $exception) {
    $connection->rollBack();
    fwrite(STDERR, "MatrixRate import: failed: {$exception->getMessage()}\n");
    exit(1);
}

$count = (int)$connection->fetchOne(
    'SELECT COUNT(*) FROM webshopapps_matrixrate WHERE website_id = ?',
    [$websiteId]
);

fwrite(STDOUT, "MatrixRate import: inserted {$count} rows for website_id={$websiteId} from {$csvPath}\n");
