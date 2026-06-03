<?php
declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveEmDashesFromHomeDemo37WhyChooseUs implements DataPatchInterface
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
            $cmsPageTable = $this->moduleDataSetup->getTable('cms_page');

            $row = $connection->fetchRow(
                sprintf('SELECT page_id, content FROM %s WHERE identifier = ?', $cmsPageTable),
                ['home-demo-37']
            );

            if (!$row || !isset($row['page_id'], $row['content'])) {
                return;
            }

            $content = (string) $row['content'];
            if (!str_contains($content, 'Why Buyers Choose Verified Botanicals')) {
                return;
            }

            if (!str_contains($content, '—')) {
                return;
            }

            $updated = str_replace('—', '-', $content);

            $connection->update(
                $cmsPageTable,
                ['content' => $updated],
                ['page_id = ?' => (int) $row['page_id']]
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

