<?php

declare(strict_types=1);

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ResourceConnection;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var RegionFactory $regionFactory */
$regionFactory = $objectManager->get(RegionFactory::class);

$region = $regionFactory->create();
$region->loadByCode('NS', 'CA');
$regionId = (int)$region->getRegionId();
if ($regionId <= 0) {
    fwrite(STDERR, "Unable to resolve CA region_id for Nova Scotia (NS)\n");
    exit(2);
}

/** @var ResourceConnection $resource */
$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();
$table = $resource->getTableName('tax_calculation_rate');

$rows = $connection->fetchAll(
    sprintf('SELECT tax_calculation_rate_id, code, rate FROM %s WHERE tax_country_id = ? AND tax_region_id = ? AND tax_postcode = ? ORDER BY tax_calculation_rate_id', $table),
    ['CA', $regionId, '*']
);

$amount = (float)($argv[1] ?? 22.99);

fwrite(STDOUT, sprintf("Nova Scotia rates (region_id=%d):\n", $regionId));
foreach ($rows as $row) {
    $rate = (float)$row['rate'];
    $tax = $amount * ($rate / 100.0);
    fwrite(STDOUT, sprintf("- %s: %.4f%% (tax on %.2f => %.2f)\n", (string)$row['code'], $rate, $amount, $tax));
}

exit(0);

