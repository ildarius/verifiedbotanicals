<?php

declare(strict_types=1);

namespace Local\HomepageAssets\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixThemeAssetViewUrls implements DataPatchInterface
{
    private const REPLACEMENTS = [
        "{{view url='Sm_Market::images/" => "{{view url='images/",
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        try {
            $this->replaceCmsContent('cms_page');
            $this->replaceCmsContent('cms_block');
        } finally {
            $this->moduleDataSetup->endSetup();
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            MigrateHomeDemo37Assets::class,
            MigrateHomeDropdownAssets::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    private function replaceCmsContent(string $tableName): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable($tableName);

        $rows = $connection->fetchPairs(
            $connection->select()
                ->from($table, ['identifier', 'content'])
                ->where('content LIKE ?', "%{{view url='Sm_Market::images/%")
        );

        foreach ($rows as $identifier => $content) {
            if (!is_string($content) || $content === '') {
                continue;
            }

            $updated = str_replace(array_keys(self::REPLACEMENTS), array_values(self::REPLACEMENTS), $content);
            if ($updated === $content) {
                continue;
            }

            $connection->update(
                $table,
                ['content' => $updated],
                ['identifier = ?' => $identifier]
            );
        }
    }
}
