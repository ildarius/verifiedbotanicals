<?php
declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AppendFooterAddressAppointmentOnly implements DataPatchInterface
{
    private const FOOTER_BLOCK_IDENTIFIER = 'footer-address';
    private const APPOINTMENT_ONLY_TEXT = ' (by appointment only)';

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
            if (stripos($content, 'by appointment only') !== false) {
                return;
            }

            $pos = strrpos($content, '</div>');
            if ($pos === false) {
                $updated = $content . self::APPOINTMENT_ONLY_TEXT;
            } else {
                $updated = substr($content, 0, $pos) . self::APPOINTMENT_ONLY_TEXT . substr($content, $pos);
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
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}

