<?php

declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Service;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class EligibleProductProvider
{
    private RotationConfig $rotationConfig;

    private CollectionFactory $productCollectionFactory;

    private Visibility $catalogProductVisibility;

    private ProductGroupResolver $productGroupResolver;

    public function __construct(
        RotationConfig $rotationConfig,
        CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        ProductGroupResolver $productGroupResolver
    ) {
        $this->rotationConfig = $rotationConfig;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->productGroupResolver = $productGroupResolver;
    }

    /**
     * @param int[] $excludedProductIds
     * @return array<string,array<int,array<string,int|string|float>>>
     */
    public function getPools(array $excludedProductIds = []): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId(1)
            ->addAttributeToSelect(['name', 'price', 'special_price'])
            ->addCategoriesFilter(['in' => $this->rotationConfig->getGroupCategoryIds()])
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('is_saleable', ['eq' => 1], 'left')
            ->addFieldToFilter('type_id', Configurable::TYPE_CODE);
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());

        if ($excludedProductIds) {
            $collection->addFieldToFilter('entity_id', ['nin' => $excludedProductIds]);
        }

        $pools = [];
        foreach ($collection as $product) {
            $groupKey = $this->productGroupResolver->resolve($product->getCategoryIds());
            if ($groupKey === null) {
                continue;
            }

            $pools[$groupKey][] = [
                'product_id' => (int)$product->getId(),
                'sku' => (string)$product->getSku(),
                'name' => (string)$product->getName(),
                'group_key' => $groupKey,
                'price' => (float)$product->getPrice(),
            ];
        }

        return $pools;
    }
}
