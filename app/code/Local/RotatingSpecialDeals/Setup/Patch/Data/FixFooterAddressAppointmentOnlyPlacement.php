<?php
declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixFooterAddressAppointmentOnlyPlacement implements DataPatchInterface
{
    private const FOOTER_BLOCK_IDENTIFIER = 'footer-address';

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

            $row = $connection->fetchRow(
                sprintf('SELECT block_id, content FROM %s WHERE identifier = ?', $cmsBlockTable),
                [self::FOOTER_BLOCK_IDENTIFIER]
            );

            if (!$row || !isset($row['block_id'], $row['content'])) {
                return;
            }

            $content = (string)$row['content'];

            $updated = str_replace(
                '&lt;/div&gt; (by appointment only)',
                ' (by appointment only)&lt;/div&gt;',
                $content
            );

            if ($updated === $content) {
                return;
            }

            $connection->update(
                $cmsBlockTable,
                ['content' => $updated],
                ['block_id = ?' => (int)$row['block_id']]
            );
        } finally {
            $connection->endSetup();
        }
    }

    public static function getDependencies(): array
    {
        return [AppendFooterAddressAppointmentOnly::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}

