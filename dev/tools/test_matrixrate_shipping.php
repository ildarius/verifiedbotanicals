<?php

declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Store\Model\StoreManagerInterface;
use WebShopApps\MatrixRate\Model\Carrier\Matrixrate;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var State $state */
$state = $objectManager->get(State::class);
try {
    $state->setAreaCode('frontend');
} catch (Throwable $exception) {
}

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$storeId = (int)($argv[1] ?? 111);

/** @var Matrixrate $carrier */
$carrier = $objectManager->create(Matrixrate::class);

/**
 * @return array<string, float> methodTitle => price
 */
function estimate(ObjectManagerInterface $objectManager, Matrixrate $carrier, int $storeId, int $websiteId, string $countryId, int $regionId, float $weightKg): array
{
    /** @var RateRequest $request */
    $request = $objectManager->create(RateRequest::class);

    $request->setStoreId($storeId);
    $request->setWebsiteId($websiteId);
    $request->setDestCountryId($countryId);
    $request->setDestRegionId($regionId);
    $request->setDestCity('Toronto');
    $request->setDestPostcode('M5V1A1');

    $request->setPackageWeight($weightKg);
    $request->setFreeMethodWeight($weightKg);
    $request->setPackageQty(1);
    $request->setPackageValue(100.00);

    $result = $carrier->collectRates($request);
    if ($result === false) {
        return [];
    }

    $out = [];
    foreach ($result->getAllRates() as $rate) {
        if (method_exists($rate, 'getErrorMessage') && $rate->getErrorMessage()) {
            continue;
        }
        $title = (string)$rate->getMethodTitle();
        $out[$title] = (float)$rate->getPrice();
    }
    ksort($out);
    return $out;
}

$websiteId = (int)$storeManager->getStore($storeId)->getWebsiteId();

// Region IDs: CA-ON=74, CA-QC=76, CA-BC=67
$regions = [
    'ON' => 74,
    'QC' => 76,
    'BC' => 67,
];

$scenarios = [
    ['weight' => 0.025, 'expected' => ['QC' => ['Express Shipping' => 17.35, 'Regular Shipping' => 3.80], 'ON' => ['Express Shipping' => 17.35, 'Regular Shipping' => 3.80], 'BC' => ['Express Shipping' => 26.80, 'Regular Shipping' => 3.80]]],
    ['weight' => 0.050, 'expected' => ['QC' => ['Express Shipping' => 19.60, 'Regular Shipping' => 6.41], 'ON' => ['Express Shipping' => 19.60, 'Regular Shipping' => 6.41], 'BC' => ['Express Shipping' => 28.10, 'Regular Shipping' => 6.41]]],
    ['weight' => 0.125, 'expected' => ['QC' => ['Express Shipping' => 19.60, 'Regular Shipping' => 6.41], 'ON' => ['Express Shipping' => 19.60, 'Regular Shipping' => 6.41], 'BC' => ['Express Shipping' => 28.10, 'Regular Shipping' => 6.41]]],
    ['weight' => 0.126, 'expected' => ['QC' => ['Express Shipping' => 21.99], 'ON' => ['Express Shipping' => 21.99], 'BC' => ['Express Shipping' => 21.99]]],
    ['weight' => 0.250, 'expected' => ['QC' => ['Express Shipping' => 21.99], 'ON' => ['Express Shipping' => 21.99], 'BC' => ['Express Shipping' => 21.99]]],
    ['weight' => 0.500, 'expected' => ['QC' => ['Express Shipping' => 21.99], 'ON' => ['Express Shipping' => 21.99], 'BC' => ['Express Shipping' => 21.99]]],
    ['weight' => 1.000, 'expected' => ['QC' => ['Express Shipping' => 21.99], 'ON' => ['Express Shipping' => 21.99], 'BC' => ['Express Shipping' => 21.99]]],
    ['weight' => 1.001, 'expected' => ['QC' => ['Express Shipping' => 24.99], 'ON' => ['Express Shipping' => 24.99], 'BC' => ['Express Shipping' => 24.99]]],
    ['weight' => 2.000, 'expected' => ['QC' => ['Express Shipping' => 24.99], 'ON' => ['Express Shipping' => 24.99], 'BC' => ['Express Shipping' => 24.99]]],
    ['weight' => 2.001, 'expected' => ['QC' => ['Express Shipping' => 32.99], 'ON' => ['Express Shipping' => 32.99], 'BC' => ['Express Shipping' => 32.99]]],
    ['weight' => 5.000, 'expected' => ['QC' => ['Express Shipping' => 32.99], 'ON' => ['Express Shipping' => 32.99], 'BC' => ['Express Shipping' => 32.99]]],
];

$hadFailure = false;

foreach ($scenarios as $scenario) {
    $weightKg = (float)$scenario['weight'];
    /** @var array<string, array<string, float>> $expectedByRegion */
    $expectedByRegion = $scenario['expected'];

    foreach ($expectedByRegion as $regionCode => $expected) {
        $actual = estimate($objectManager, $carrier, $storeId, $websiteId, 'CA', $regions[$regionCode], $weightKg);

        $ok = $actual === $expected;
        if (!$ok) {
            $hadFailure = true;
        }

        $status = $ok ? 'OK' : 'FAIL';
        fwrite(STDOUT, sprintf("[%s] %s weight=%.3fkg expected=%s actual=%s\n", $status, $regionCode, $weightKg, json_encode($expected), json_encode($actual)));
    }
}

exit($hadFailure ? 1 : 0);
