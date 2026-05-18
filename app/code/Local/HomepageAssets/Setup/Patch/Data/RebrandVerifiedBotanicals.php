<?php

declare(strict_types=1);

namespace Local\HomepageAssets\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RebrandVerifiedBotanicals implements DataPatchInterface
{
    private const HOMEPAGE_IDENTIFIER = 'home-demo-37';
    private const SOCIAL_BLOCK_IDENTIFIER = 'social-block';

    private const COPYRIGHT_CONTENT = 'Verified Botanicals © 2025. All Rights Reserved.';
    private const DEFAULT_DESCRIPTION = 'Verified Botanicals premium botanical products.';
    private const DEFAULT_KEYWORDS = 'verified botanicals, botanical products, kratom, premium botanicals';

    private const CONTENT_REPLACEMENTS = [
        'SM Market' => 'Verified Botanicals',
        'Sm Market' => 'Verified Botanicals',
        'SM MARKET' => 'VERIFIED BOTANICALS',
        'MagenTech.Com' => 'Verified Botanicals',
        'MagenTech' => 'Verified Botanicals',
        'http://www.facebook.com/MagenTech/' => '#',
        'https://www.facebook.com/MagenTech/' => '#',
        'https://twitter.com/MagenTech' => '#',
        'http://twitter.com/MagenTech' => '#',
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        try {
            $this->updateHomepageMeta();
            $this->updateSocialBlock();
            $this->replaceCmsPageFields();
            $this->replaceCmsBlockContent();
            $this->updateConfigValues();
        } finally {
            $this->moduleDataSetup->endSetup();
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            ReplaceHomepageFarmerBanner::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    private function updateHomepageMeta(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('cms_page');

        $connection->update(
            $table,
            [
                'title' => 'Home - Verified Botanicals Layout 37',
                'meta_title' => 'Verified Botanicals',
                'meta_description' => self::DEFAULT_DESCRIPTION,
            ],
            ['identifier = ?' => self::HOMEPAGE_IDENTIFIER]
        );
    }

    private function updateSocialBlock(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('cms_block');
        $content = $connection->fetchOne(
            $connection->select()
                ->from($table, ['content'])
                ->where('identifier = ?', self::SOCIAL_BLOCK_IDENTIFIER)
                ->limit(1)
        );

        if (!is_string($content) || $content === '') {
            return;
        }

        $updated = str_replace(
            [
                'http://www.facebook.com/MagenTech/',
                'https://www.facebook.com/MagenTech/',
                'https://twitter.com/MagenTech',
                'http://twitter.com/MagenTech',
            ],
            ['#', '#', '#', '#'],
            $content
        );

        if ($updated === $content) {
            return;
        }

        $connection->update(
            $table,
            ['content' => $updated],
            ['identifier = ?' => self::SOCIAL_BLOCK_IDENTIFIER]
        );
    }

    private function replaceCmsPageFields(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('cms_page');
        $rows = $connection->fetchAll(
            $connection->select()
                ->from($table, ['page_id', 'title', 'meta_title', 'meta_description', 'content_heading', 'content'])
        );

        foreach ($rows as $row) {
            $updates = [];
            foreach (['title', 'meta_title', 'meta_description', 'content_heading', 'content'] as $column) {
                $value = $row[$column] ?? null;
                if (!is_string($value) || $value === '') {
                    continue;
                }

                $updated = str_replace(
                    array_keys(self::CONTENT_REPLACEMENTS),
                    array_values(self::CONTENT_REPLACEMENTS),
                    $value
                );

                if ($updated !== $value) {
                    $updates[$column] = $updated;
                }
            }

            if (!$updates) {
                continue;
            }

            $connection->update(
                $table,
                $updates,
                ['page_id = ?' => (int)$row['page_id']]
            );
        }
    }

    private function replaceCmsBlockContent(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('cms_block');
        $rows = $connection->fetchAll(
            $connection->select()
                ->from($table, ['block_id', 'title', 'content'])
        );

        foreach ($rows as $row) {
            $updates = [];
            foreach (['title', 'content'] as $column) {
                $value = $row[$column] ?? null;
                if (!is_string($value) || $value === '') {
                    continue;
                }

                $updated = str_replace(
                    array_keys(self::CONTENT_REPLACEMENTS),
                    array_values(self::CONTENT_REPLACEMENTS),
                    $value
                );

                if ($updated !== $value) {
                    $updates[$column] = $updated;
                }
            }

            if (!$updates) {
                continue;
            }

            $connection->update(
                $table,
                $updates,
                ['block_id = ?' => (int)$row['block_id']]
            );
        }
    }

    private function updateConfigValues(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');

        $connection->update(
            $table,
            ['value' => self::COPYRIGHT_CONTENT],
            ['path = ?' => 'themecore/advanced/copyright_group/copyright_content']
        );

        $connection->update(
            $table,
            ['value' => self::DEFAULT_DESCRIPTION],
            ['path = ?' => 'design/head/default_description']
        );

        $connection->update(
            $table,
            ['value' => self::DEFAULT_KEYWORDS],
            ['path = ?' => 'design/head/default_keywords']
        );

        $connection->update(
            $table,
            ['value' => 'VERIFIED BOTANICALS'],
            ['path = ?' => 'market/product_information/buy_sm_theme/buy_theme_name']
        );
    }
}
