<?php

declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Service;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class CatalogPriceSeeder
{
    private RotationConfig $rotationConfig;

    private CollectionFactory $productCollectionFactory;

    private ProductRepositoryInterface $productRepository;

    private Configurable $configurableType;

    public function __construct(
        RotationConfig $rotationConfig,
        CollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        Configurable $configurableType
    ) {
        $this->rotationConfig = $rotationConfig;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->configurableType = $configurableType;
    }

    public function ensureBasePricing(): void
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price'])
            ->addCategoriesFilter(['in' => $this->rotationConfig->getGroupCategoryIds()])
            ->addFieldToFilter('type_id', ['in' => ['simple', 'configurable']]);

        $simpleIds = [];
        $parentIds = [];
        foreach ($collection as $product) {
            if ($product->getTypeId() === 'simple') {
                $simpleIds[] = (int)$product->getId();
                continue;
            }

            if ($product->getTypeId() === 'configurable') {
                $parentIds[] = (int)$product->getId();
            }
        }

        foreach ($simpleIds as $simpleId) {
            $this->seedSimpleProductPrice($simpleId);
        }

        foreach ($parentIds as $parentId) {
            $this->seedParentProductPrice($parentId);
        }
    }

    private function seedSimpleProductPrice(int $productId): void
    {
        $product = $this->productRepository->getById($productId, true, 0, true);
        $currentPrice = (float)$product->getPrice();
        if ($currentPrice > 0.0001) {
            return;
        }

        $price = $this->derivePriceFromWeight($product->getName() . ' ' . $product->getSku());
        if ($price === null) {
            return;
        }

        $product->setPrice($price);
        $this->productRepository->save($product);
    }

    private function seedParentProductPrice(int $productId): void
    {
        $product = $this->productRepository->getById($productId, true, 0, true);
        $currentPrice = (float)$product->getPrice();
        if ($currentPrice > 0.0001) {
            return;
        }

        $childIds = $this->getChildIds($productId);
        $minPrice = null;
        foreach ($childIds as $childId) {
            $child = $this->productRepository->getById($childId, true, 0, true);
            $childPrice = (float)$child->getPrice();
            if ($childPrice <= 0.0001) {
                continue;
            }

            $minPrice = $minPrice === null ? $childPrice : min($minPrice, $childPrice);
        }

        if ($minPrice === null) {
            return;
        }

        $product->setPrice($minPrice);
        $this->productRepository->save($product);
    }

    private function derivePriceFromWeight(string $text): ?float
    {
        if (!preg_match('/(\d+)g/i', $text, $matches)) {
            return null;
        }

        $grams = (int)$matches[1];
        $map = $this->rotationConfig->getDefaultPrices();

        return $map[$grams] ?? null;
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
