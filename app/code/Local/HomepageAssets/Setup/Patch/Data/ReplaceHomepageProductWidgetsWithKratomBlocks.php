<?php

declare(strict_types=1);

namespace Local\HomepageAssets\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ReplaceHomepageProductWidgetsWithKratomBlocks implements DataPatchInterface
{
    private const PAGE_IDENTIFIER = 'home-demo-37';
    private const DEALS_WIDGET_PATTERN =
        '/\{\{widget type="Sm\\\\FilterProducts\\\\Block\\\\Widget\\\\AddFilterProducts"[^}]*template="Sm_FilterProducts::grid-slider-deal2\.phtml"[^}]*\}\}/s';
    private const NEW_ARRIVALS_WIDGET_PATTERN =
        '/\{\{widget type="Sm\\\\FilterProducts\\\\Block\\\\Widget\\\\AddFilterProducts"[^}]*template="Sm_FilterProducts::grid-slider\.phtml"[^}]*title_module="NEW PRODUCTS"[^}]*\}\}/s';
    private const DEALS_BLOCK_DIRECTIVE =
        '{{block class="Local\HomepageAssets\Block\HomepageKratomProducts" template="Local_HomepageAssets::homepage/kratom-products.phtml" limit="3"}}';
    private const NEW_ARRIVALS_BLOCK_DIRECTIVE =
        '{{block class="Local\HomepageAssets\Block\HomepageKratomProducts" template="Local_HomepageAssets::homepage/kratom-products.phtml" limit="6"}}';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('cms_page');

        $this->moduleDataSetup->startSetup();

        try {
            $content = $connection->fetchOne(
                $connection->select()
                    ->from($table, ['content'])
                    ->where('identifier = ?', self::PAGE_IDENTIFIER)
                    ->limit(1)
            );

            if (!is_string($content) || $content === '') {
                return $this;
            }

            $updated = preg_replace(self::DEALS_WIDGET_PATTERN, self::DEALS_BLOCK_DIRECTIVE, $content);
            $updated = is_string($updated)
                ? preg_replace(self::NEW_ARRIVALS_WIDGET_PATTERN, self::NEW_ARRIVALS_BLOCK_DIRECTIVE, $updated)
                : null;

            if (!is_string($updated)) {
                return $this;
            }

            if ($updated === $content) {
                return $this;
            }

            $connection->update(
                $table,
                ['content' => $updated],
                ['identifier = ?' => self::PAGE_IDENTIFIER]
            );
        } finally {
            $this->moduleDataSetup->endSetup();
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            SwapHomepageDealsWidgetToLatestKratom::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
