<?php

declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Service;

class RotationConfig
{
    private const CYCLE_DAYS = 14;
    private const DISCOUNT_FACTOR = 0.70;
    private const HOMEPAGE_IDENTIFIER = 'home-demo-37';
    private const HOMEPAGE_WIDGET_TYPE = 'Sm\FilterProducts\Block\Widget\AddFilterProducts';
    private const HOMEPAGE_WIDGET_TEMPLATE = 'Sm_FilterProducts::grid-slider-deal2.phtml';
    private const HOMEPAGE_PRODUCT_LIMIT = 2;
    private const GROUP_CATEGORY_IDS = [377, 378, 379];
    private const DEFAULT_PRICES = [
        25 => 12.99,
        50 => 21.99,
        100 => 39.99,
        250 => 84.99,
        500 => 149.99,
    ];

    /**
     * @return int[]
     */
    public function getGroupCategoryIds(): array
    {
        return self::GROUP_CATEGORY_IDS;
    }

    /**
     * @return array<int,float>
     */
    public function getDefaultPrices(): array
    {
        return self::DEFAULT_PRICES;
    }

    public function getCycleDays(): int
    {
        return self::CYCLE_DAYS;
    }

    public function getDiscountFactor(): float
    {
        return self::DISCOUNT_FACTOR;
    }

    public function getHomepageIdentifier(): string
    {
        return self::HOMEPAGE_IDENTIFIER;
    }

    public function getHomepageWidgetType(): string
    {
        return self::HOMEPAGE_WIDGET_TYPE;
    }

    public function getHomepageWidgetTemplate(): string
    {
        return self::HOMEPAGE_WIDGET_TEMPLATE;
    }

    public function getHomepageProductLimit(): int
    {
        return self::HOMEPAGE_PRODUCT_LIMIT;
    }
}
