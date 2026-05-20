<?php

declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Service;

class ProductGroupResolver
{
    private RotationConfig $rotationConfig;

    public function __construct(RotationConfig $rotationConfig)
    {
        $this->rotationConfig = $rotationConfig;
    }

    /**
     * @param int[] $categoryIds
     */
    public function resolve(array $categoryIds): ?string
    {
        $categoryIds = array_map('intval', $categoryIds);
        foreach ($this->rotationConfig->getGroupCategoryIds() as $categoryId) {
            if (in_array($categoryId, $categoryIds, true)) {
                return (string)$categoryId;
            }
        }

        return null;
    }
}
