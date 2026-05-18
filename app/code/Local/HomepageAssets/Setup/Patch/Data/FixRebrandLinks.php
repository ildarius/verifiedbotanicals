<?php

declare(strict_types=1);

namespace Local\HomepageAssets\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixRebrandLinks implements DataPatchInterface
{
    private const URL_REPLACEMENTS = [
        'http://www.facebook.com/MagenTech/' => '#',
        'https://www.facebook.com/MagenTech/' => '#',
        'https://twitter.com/MagenTech' => '#',
        'http://twitter.com/MagenTech' => '#',
        'http://www.facebook.com/Verified Botanicals/' => '#',
        'https://www.facebook.com/Verified Botanicals/' => '#',
        'https://twitter.com/Verified Botanicals' => '#',
        'http://twitter.com/Verified Botanicals' => '#',
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        try {
            $this->replaceCmsBlockContent();
        } finally {
            $this->moduleDataSetup->endSetup();
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            RebrandVerifiedBotanicals::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    private function replaceCmsBlockContent(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('cms_block');
        $rows = $connection->fetchAll(
            $connection->select()->from($table, ['block_id', 'content'])
        );

        foreach ($rows as $row) {
            $content = $row['content'] ?? null;
            if (!is_string($content) || $content === '') {
                continue;
            }

            $updated = str_replace(
                array_keys(self::URL_REPLACEMENTS),
                array_values(self::URL_REPLACEMENTS),
                $content
            );

            if ($updated === $content) {
                continue;
            }

            $connection->update(
                $table,
                ['content' => $updated],
                ['block_id = ?' => (int)$row['block_id']]
            );
        }
    }
}
