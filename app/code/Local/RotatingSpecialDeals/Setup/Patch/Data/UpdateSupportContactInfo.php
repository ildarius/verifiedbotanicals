<?php
declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateSupportContactInfo implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        try {
            $cmsBlockTable = $this->moduleDataSetup->getTable('cms_block');
            $cmsPageTable = $this->moduleDataSetup->getTable('cms_page');

            $connection->query(
                sprintf(
                    'UPDATE %s SET content = REPLACE(REPLACE(content, ?, ?), ?, ?) WHERE identifier = ?',
                    $cmsBlockTable
                ),
                [
                    'Online Support 24/7',
                    'Montreal SMS\\Phone',
                    '+84 94344 6000',
                    '(263) 366-8232',
                    'support-header-37',
                ]
            );

            $connection->query(
                sprintf(
                    'UPDATE %s SET content = REPLACE(content, ?, ?) WHERE identifier = ?',
                    $cmsBlockTable
                ),
                [
                    '1800 446 000',
                    '(263) 366-8232',
                    'footer-29-content',
                ]
            );

            $connection->query(
                sprintf(
                    'UPDATE %s SET content = REPLACE(content, ?, ?) WHERE identifier = ?',
                    $cmsPageTable
                ),
                [
                    'Online Support 24/7',
                    'Montreal SMS\\Phone',
                    'home-demo-37',
                ]
            );
        } finally {
            $connection->endSetup();
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
