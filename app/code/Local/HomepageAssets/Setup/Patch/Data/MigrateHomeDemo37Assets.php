<?php

declare(strict_types=1);

namespace Local\HomepageAssets\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigrateHomeDemo37Assets implements DataPatchInterface
{
    private const PAGE_IDENTIFIER = 'home-demo-37';
    private const BLOCK_IDENTIFIER = 'support-header-37';

    private const PAGE_REPLACEMENTS = [
        '{{media url=wysiwyg/category/green-maeng-da-300x300.png}}'
            => "{{view url='Sm_Market::images/home-demo-37/category/green-maeng-da-300x300.png'}}",
        '{{media url=wysiwyg/category/red-hulu-red-kapuas-300x300.png}}'
            => "{{view url='Sm_Market::images/home-demo-37/category/red-hulu-red-kapuas-300x300.png'}}",
        '{{media url=wysiwyg/category/white-vein-kratom-300x300.png}}'
            => "{{view url='Sm_Market::images/home-demo-37/category/white-vein-kratom-300x300.png'}}",
        '{{media url=wysiwyg/banner/banner-119.jpg}}'
            => "{{view url='Sm_Market::images/home-demo-37/banner/banner-119.jpg'}}",
        '{{media url=wysiwyg/banner/banner-120.jpg}}'
            => "{{view url='Sm_Market::images/home-demo-37/banner/banner-120.jpg'}}",
        '{{media url=wysiwyg/banner/banner-121.jpg}}'
            => "{{view url='Sm_Market::images/home-demo-37/banner/banner-121.jpg'}}",
        '{{media url=wysiwyg/banner/banner-122.png}}'
            => "{{view url='Sm_Market::images/home-demo-37/banner/banner-122.png'}}",
        '{{media url=wysiwyg/icon-image/icon-55-1.png}}'
            => "{{view url='Sm_Market::images/home-demo-37/icon-image/icon-55-1.png'}}",
        '{{media url=wysiwyg/icon-image/icon-55-2.png}}'
            => "{{view url='Sm_Market::images/home-demo-37/icon-image/icon-55-2.png'}}",
        '{{media url=wysiwyg/icon-image/icon-55-3.png}}'
            => "{{view url='Sm_Market::images/home-demo-37/icon-image/icon-55-3.png'}}",
        '{{media url=wysiwyg/icon-image/icon-55-4.png}}'
            => "{{view url='Sm_Market::images/home-demo-37/icon-image/icon-55-4.png'}}",
        '{{media url=wysiwyg/clients/our-1.jpg}}'
            => "{{view url='Sm_Market::images/home-demo-37/clients/our-1.jpg'}}",
        '{{media url=wysiwyg/clients/our-2.jpg}}'
            => "{{view url='Sm_Market::images/home-demo-37/clients/our-2.jpg'}}",
        '{{media url=wysiwyg/clients/our-3.jpg}}'
            => "{{view url='Sm_Market::images/home-demo-37/clients/our-3.jpg'}}",
        '{{media url=wysiwyg/clients/our-4.jpg}}'
            => "{{view url='Sm_Market::images/home-demo-37/clients/our-4.jpg'}}",
    ];

    private const BLOCK_REPLACEMENTS = [
        '{{media url=wysiwyg/support.png}}'
            => "{{view url='Sm_Market::images/shared/support.png'}}",
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $this->moduleDataSetup->startSetup();

        try {
            $this->replaceCmsContent('cms_page', self::PAGE_IDENTIFIER, self::PAGE_REPLACEMENTS);
            $this->replaceCmsContent('cms_block', self::BLOCK_IDENTIFIER, self::BLOCK_REPLACEMENTS);
        } finally {
            $this->moduleDataSetup->endSetup();
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    private function replaceCmsContent(string $tableName, string $identifier, array $replacements): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable($tableName);
        $content = $connection->fetchOne(
            $connection->select()
                ->from($table, ['content'])
                ->where('identifier = ?', $identifier)
                ->limit(1)
        );

        if (!is_string($content) || $content === '') {
            return;
        }

        $updated = str_replace(array_keys($replacements), array_values($replacements), $content);
        if ($updated === $content) {
            return;
        }

        $connection->update(
            $table,
            ['content' => $updated],
            ['identifier = ?' => $identifier]
        );
    }
}
