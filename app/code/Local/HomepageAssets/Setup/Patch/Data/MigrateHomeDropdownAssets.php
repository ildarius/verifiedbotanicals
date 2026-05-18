<?php

declare(strict_types=1);

namespace Local\HomepageAssets\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigrateHomeDropdownAssets implements DataPatchInterface
{
    private const BLOCK_IDENTIFIER = 'home-dropdown';

    private const REPLACEMENTS = [
        '{{media url=wysiwyg/layout-demo/layout-1.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-1.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-2.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-2.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-3.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-3.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-4.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-4.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-5.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-5.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-6.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-6.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-7.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-7.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-8.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-8.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-9.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-9.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-10.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-10.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-11.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-11.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-12.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-12.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-13.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-13.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-14.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-14.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-15.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-15.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-16.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-16.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-17.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-17.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-18.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-18.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-19.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-19.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-20.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-20.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-21.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-21.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-22.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-22.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-23.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-23.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-24.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-24.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-25.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-25.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-26.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-26.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-27.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-27.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-28.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-28.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-29.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-29.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-30.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-30.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-31.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-31.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-32.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-32.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-33.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-33.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-34.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-34.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-36.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-36.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-37.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-37.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-38.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-38.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-39.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-39.jpg'}}",
        '{{media url=wysiwyg/layout-demo/layout-40.jpg}}'
            => "{{view url='images/shared/layout-demo/layout-40.jpg'}}",
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('cms_block');

        $this->moduleDataSetup->startSetup();

        try {
            $content = $connection->fetchOne(
                $connection->select()
                    ->from($table, ['content'])
                    ->where('identifier = ?', self::BLOCK_IDENTIFIER)
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
                ['identifier = ?' => self::BLOCK_IDENTIFIER]
            );
        } finally {
            $this->moduleDataSetup->endSetup();
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            MigrateHomeDemo37Assets::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
