<?php

declare(strict_types=1);

namespace Local\HomepageAssets\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ReplaceHomepageFarmerBanner implements DataPatchInterface
{
    private const PAGE_IDENTIFIER = 'home-demo-37';

    private const REPLACEMENTS = [
        '{{media url=wysiwyg/banner/banner-122.png}}'
            => "{{view url='images/home-demo-37/banner/kratom_farmer_indonesia.png'}}",
        "{{view url='images/home-demo-37/banner/banner-122.png'}}"
            => "{{view url='images/home-demo-37/banner/kratom_farmer_indonesia.png'}}",
    ];

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

            $updated = str_replace(array_keys(self::REPLACEMENTS), array_values(self::REPLACEMENTS), $content);
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
            FixThemeAssetViewUrls::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
