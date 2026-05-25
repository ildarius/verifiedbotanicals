<?php

declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;

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
/** @var QuoteFactory $quoteFactory */
$quoteFactory = $objectManager->get(QuoteFactory::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

$storeId = (int)($argv[1] ?? 111);
$store = $storeManager->getStore($storeId);

$regions = [
    'QC' => ['region_id' => 76, 'region_code' => 'QC', 'city' => 'Montreal', 'postcode' => 'H2Y1C6'],
    'ON' => ['region_id' => 74, 'region_code' => 'ON', 'city' => 'Toronto', 'postcode' => 'M5V1A1'],
    'BC' => ['region_id' => 67, 'region_code' => 'BC', 'city' => 'Vancouver', 'postcode' => 'V5K0A1'],
];

/**
 * @param array<string,int> $items sku => qty
 * @return array{weight: float, rates: array<string,float>} weightKg + methodTitle=>price
 */
function quoteRates(
    QuoteFactory $quoteFactory,
    ProductRepositoryInterface $productRepository,
    \Magento\Store\Api\Data\StoreInterface $store,
    array $address,
    array $items
): array {
    /** @var Quote $quote */
    $quote = $quoteFactory->create();
    $quote->setStore($store);
    $quote->setIsMultiShipping(false);

    foreach ($items as $sku => $qty) {
        $product = $productRepository->get($sku, false, (int)$store->getId(), true);
        $quote->addProduct($product, (int)$qty);
    }

    $quote->getBillingAddress()->addData($address);
    $shipping = $quote->getShippingAddress();
    $shipping->addData($address);
    $shipping->setCollectShippingRates(true);

    $quote->collectTotals();

    $rates = [];
    foreach ($shipping->getAllShippingRates() as $rate) {
        if ($rate->getCarrier() !== 'matrixrate') {
            continue;
        }
        $rates[(string)$rate->getMethodTitle()] = (float)$rate->getPrice();
    }
    ksort($rates);

    return [
        'weight' => (float)$shipping->getWeight(),
        'rates' => $rates,
    ];
}

$scenarios = [
    [
        'label' => '1×RB25 (25g)',
        'items' => ['RB25' => 1],
        'expectedWeight' => 0.025,
        'expectedRates' => [
            'QC' => ['Express Shipping' => 17.35, 'Regular Shipping' => 3.80],
            'ON' => ['Express Shipping' => 17.35, 'Regular Shipping' => 3.80],
            'BC' => ['Express Shipping' => 26.80, 'Regular Shipping' => 3.80],
        ],
    ],
    [
        'label' => '1×RB50 (50g)',
        'items' => ['RB50' => 1],
        'expectedWeight' => 0.050,
        'expectedRates' => [
            'QC' => ['Express Shipping' => 19.60, 'Regular Shipping' => 6.41],
            'ON' => ['Express Shipping' => 19.60, 'Regular Shipping' => 6.41],
            'BC' => ['Express Shipping' => 28.10, 'Regular Shipping' => 6.41],
        ],
    ],
    [
        'label' => '1×RB25 + 1×RB100 (125g)',
        'items' => ['RB25' => 1, 'RB100' => 1],
        'expectedWeight' => 0.125,
        'expectedRates' => [
            'QC' => ['Express Shipping' => 19.60, 'Regular Shipping' => 6.41],
            'ON' => ['Express Shipping' => 19.60, 'Regular Shipping' => 6.41],
            'BC' => ['Express Shipping' => 28.10, 'Regular Shipping' => 6.41],
        ],
    ],
    [
        'label' => '1×RB250 (250g)',
        'items' => ['RB250' => 1],
        'expectedWeight' => 0.250,
        'expectedRates' => [
            'QC' => ['Express Shipping' => 21.99],
            'ON' => ['Express Shipping' => 21.99],
            'BC' => ['Express Shipping' => 21.99],
        ],
    ],
    [
        'label' => '2×RB500 (1kg)',
        'items' => ['RB500' => 2],
        'expectedWeight' => 1.000,
        'expectedRates' => [
            'QC' => ['Express Shipping' => 21.99],
            'ON' => ['Express Shipping' => 21.99],
            'BC' => ['Express Shipping' => 21.99],
        ],
    ],
    [
        'label' => '3×RB500 (1.5kg)',
        'items' => ['RB500' => 3],
        'expectedWeight' => 1.500,
        'expectedRates' => [
            'QC' => ['Express Shipping' => 24.99],
            'ON' => ['Express Shipping' => 24.99],
            'BC' => ['Express Shipping' => 24.99],
        ],
    ],
    [
        'label' => '5×RB500 (2.5kg)',
        'items' => ['RB500' => 5],
        'expectedWeight' => 2.500,
        'expectedRates' => [
            'QC' => ['Express Shipping' => 32.99],
            'ON' => ['Express Shipping' => 32.99],
            'BC' => ['Express Shipping' => 32.99],
        ],
    ],
];

$hadFailure = false;

foreach ($scenarios as $scenario) {
    foreach (['QC', 'ON', 'BC'] as $regionCode) {
        $address = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'street' => ['1 King St'],
            'city' => $regions[$regionCode]['city'],
            'postcode' => $regions[$regionCode]['postcode'],
            'telephone' => '0000000000',
            'country_id' => 'CA',
            'region_id' => $regions[$regionCode]['region_id'],
            'region_code' => $regions[$regionCode]['region_code'],
        ];

        $result = quoteRates($quoteFactory, $productRepository, $store, $address, $scenario['items']);
        $expectedRates = $scenario['expectedRates'][$regionCode];

        $weightOk = abs($result['weight'] - (float)$scenario['expectedWeight']) < 0.0001;
        $ratesOk = $result['rates'] === $expectedRates;
        $ok = $weightOk && $ratesOk;

        if (!$ok) {
            $hadFailure = true;
        }

        $status = $ok ? 'OK' : 'FAIL';
        fwrite(
            STDOUT,
            sprintf(
                "[%s] %s %s weight=%.4fkg rates=%s\n",
                $status,
                $regionCode,
                $scenario['label'],
                $result['weight'],
                json_encode($result['rates'])
            )
        );
    }
}

exit($hadFailure ? 1 : 0);
