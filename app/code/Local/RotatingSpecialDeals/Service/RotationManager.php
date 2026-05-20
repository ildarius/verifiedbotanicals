<?php

declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;

class RotationManager
{
    private RotationConfig $rotationConfig;

    private CycleStorage $cycleStorage;

    private CatalogPriceSeeder $catalogPriceSeeder;

    private EligibleProductProvider $eligibleProductProvider;

    private HomepageWidgetSynchronizer $homepageWidgetSynchronizer;

    private ProductRepositoryInterface $productRepository;

    private ProductAction $productAction;

    private Configurable $configurableType;

    private IndexerRegistry $indexerRegistry;

    private TypeListInterface $cacheTypeList;

    public function __construct(
        RotationConfig $rotationConfig,
        CycleStorage $cycleStorage,
        CatalogPriceSeeder $catalogPriceSeeder,
        EligibleProductProvider $eligibleProductProvider,
        HomepageWidgetSynchronizer $homepageWidgetSynchronizer,
        ProductRepositoryInterface $productRepository,
        ProductAction $productAction,
        Configurable $configurableType,
        IndexerRegistry $indexerRegistry,
        TypeListInterface $cacheTypeList
    ) {
        $this->rotationConfig = $rotationConfig;
        $this->cycleStorage = $cycleStorage;
        $this->catalogPriceSeeder = $catalogPriceSeeder;
        $this->eligibleProductProvider = $eligibleProductProvider;
        $this->homepageWidgetSynchronizer = $homepageWidgetSynchronizer;
        $this->productRepository = $productRepository;
        $this->productAction = $productAction;
        $this->configurableType = $configurableType;
        $this->indexerRegistry = $indexerRegistry;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @return array<string,mixed>
     */
    public function rotateIfDue(bool $force = false): array
    {
        $this->catalogPriceSeeder->ensureBasePricing();

        $activeCycle = $this->cycleStorage->getActiveCycle();
        $latestCycle = $activeCycle ?? $this->cycleStorage->getLatestResolvedCycle();
        $now = new \DateTimeImmutable('now');

        if ($activeCycle !== null && !$force) {
            $endsAt = new \DateTimeImmutable((string)$activeCycle['ends_at']);
            if ($endsAt > $now) {
                return [
                    'skipped' => true,
                    'message' => sprintf(
                        'Active cycle %d remains valid until %s.',
                        (int)$activeCycle['cycle_id'],
                        $activeCycle['ends_at']
                    ),
                ];
            }
        }

        $excludedProductIds = [];
        if ($latestCycle !== null) {
            $excludedProductIds = array_map(
                'intval',
                array_column($latestCycle['items'] ?? [], 'product_id')
            );
        }

        $selectedItems = $this->selectProducts($excludedProductIds);
        $endsAt = $now->add(new \DateInterval('P' . $this->rotationConfig->getCycleDays() . 'D'));
        $cycleId = $this->cycleStorage->createCycle(
            $now,
            $endsAt,
            $selectedItems,
            $this->rotationConfig->getHomepageIdentifier(),
            CycleStorage::STATUS_PENDING
        );

        try {
            $affectedIds = [];

            if ($activeCycle !== null) {
                $affectedIds = array_merge($affectedIds, $this->clearSpecialPricing($activeCycle));
                $this->cycleStorage->updateStatus((int)$activeCycle['cycle_id'], CycleStorage::STATUS_ROTATED);
            }

            $affectedIds = array_merge($affectedIds, $this->applySpecialPricing($selectedItems, $now, $endsAt));
            $this->homepageWidgetSynchronizer->sync($endsAt);
            $this->refreshIndexesAndCaches($affectedIds);
            $this->cycleStorage->updateStatus($cycleId, CycleStorage::STATUS_ACTIVE);
        } catch (\Throwable $exception) {
            $this->cycleStorage->updateStatus($cycleId, CycleStorage::STATUS_FAILED);
            throw $exception;
        }

        return [
            'skipped' => false,
            'cycle_id' => $cycleId,
            'selected_skus' => array_column($selectedItems, 'sku'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param int[] $excludedProductIds
     * @return array<int,array<string,int|string|float>>
     */
    private function selectProducts(array $excludedProductIds): array
    {
        $pools = array_filter(
            $this->eligibleProductProvider->getPools($excludedProductIds),
            static function (array $items): bool {
                return $items !== [];
            }
        );

        if (count($pools) < 2) {
            throw new LocalizedException(
                __('At least two eligible product groups are required to rotate special deals.')
            );
        }

        $groupKeys = array_keys($pools);
        shuffle($groupKeys);
        $selectedGroupKeys = array_slice($groupKeys, 0, 2);

        $selectedItems = [];
        foreach ($selectedGroupKeys as $groupKey) {
            $selectedItems[] = $this->pickRandomItem($pools[$groupKey]);
        }

        return $selectedItems;
    }

    /**
     * @param array<int,array<string,int|string|float>> $items
     * @return array<string,int|string|float>
     */
    private function pickRandomItem(array $items): array
    {
        $index = random_int(0, count($items) - 1);
        return $items[$index];
    }

    /**
     * @param array<string,mixed> $cycle
     * @return int[]
     */
    private function clearSpecialPricing(array $cycle): array
    {
        $affectedIds = [];
        foreach ($cycle['items'] ?? [] as $item) {
            $productId = (int)$item['product_id'];
            $productIds = array_merge([$productId], $this->getChildIds($productId));
            foreach ($productIds as $id) {
                $this->productAction->updateAttributes(
                    [$id],
                    [
                        'special_price' => null,
                        'special_from_date' => null,
                        'special_to_date' => null,
                    ],
                    0
                );
                $affectedIds[] = $id;
            }
        }

        return array_values(array_unique($affectedIds));
    }

    /**
     * @param array<int,array<string,int|string|float>> $selectedItems
     * @return int[]
     */
    private function applySpecialPricing(
        array $selectedItems,
        \DateTimeImmutable $startedAt,
        \DateTimeImmutable $endsAt
    ): array {
        $affectedIds = [];

        foreach ($selectedItems as $item) {
            $productId = (int)$item['product_id'];
            $productIds = array_merge([$productId], $this->getChildIds($productId));

            foreach ($productIds as $id) {
                $price = $this->resolveBasePrice($id);
                if ($price <= 0.0) {
                    continue;
                }

                $this->productAction->updateAttributes(
                    [$id],
                    [
                        'special_price' => round($price * $this->rotationConfig->getDiscountFactor(), 2),
                        'special_from_date' => $startedAt->format('Y-m-d H:i:s'),
                        'special_to_date' => $endsAt->format('Y-m-d H:i:s'),
                    ],
                    0
                );
                $affectedIds[] = $id;
            }
        }

        return array_values(array_unique($affectedIds));
    }

    /**
     * @param int[] $affectedIds
     */
    private function refreshIndexesAndCaches(array $affectedIds): void
    {
        $affectedIds = array_values(array_unique(array_map('intval', $affectedIds)));
        if ($affectedIds !== []) {
            $this->indexerRegistry->get('catalog_product_price')->reindexList($affectedIds);
        }

        $this->cacheTypeList->cleanType('block_html');
        $this->cacheTypeList->cleanType('full_page');
    }

    private function resolveBasePrice(int $productId): float
    {
        $product = $this->productRepository->getById($productId, true, 0, true);
        $price = round((float)$product->getPrice(), 2);
        if ($price > 0.0) {
            return $price;
        }

        $childIds = $this->getChildIds($productId);
        if ($childIds === []) {
            return 0.0;
        }

        $childPrices = [];
        foreach ($childIds as $childId) {
            $child = $this->productRepository->getById($childId, true, 0, true);
            $childPrice = round((float)$child->getPrice(), 2);
            if ($childPrice > 0.0) {
                $childPrices[] = $childPrice;
            }
        }

        if ($childPrices === []) {
            return 0.0;
        }

        return min($childPrices);
    }

    /**
     * @return int[]
     */
    private function getChildIds(int $productId): array
    {
        $childMatrix = $this->configurableType->getChildrenIds($productId);
        $childIds = [];
        foreach ($childMatrix as $ids) {
            foreach ($ids as $childId) {
                $childIds[] = (int)$childId;
            }
        }

        return array_values(array_unique($childIds));
    }
}
