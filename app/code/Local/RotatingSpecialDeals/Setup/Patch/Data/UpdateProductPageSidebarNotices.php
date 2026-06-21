<?php
declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateProductPageSidebarNotices implements DataPatchInterface
{
    private const BLOCK_IDENTIFIER = 'block-top-sidebar-product-page';

    private const OLD_SHIPPING =
        '&lt;span class="service-info"&gt;{{trans "FAST"}} &amp; {{trans "FREE SHIPPING ON ALL ORDERS"}}&lt;/span&gt;';

    private const NEW_SHIPPING =
        '&lt;span class="service-info"&gt;{{trans "LOW-COST SHIPPING ON EVERY ORDER"}}&lt;/span&gt;';

    private const OLD_GUARANTEE =
        '&lt;span class="service-info"&gt;{{trans "30 DAYS MONEY BACK GUARANTEE"}}&lt;/span&gt;';

    private const NEW_GUARANTEE =
        '&lt;span class="service-info"&gt;{{trans "TOP QUALITY. COMPETITIVE PRICES."}}&lt;/span&gt;';

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
                [self::BLOCK_IDENTIFIER]
            );

            if (!$row || !isset($row['block_id'], $row['content'])) {
                return;
            }

            $content = (string) $row['content'];
            $updatedContent = $this->replaceNotices($content);

            if ($updatedContent === null || $updatedContent === $content) {
                return;
            }

            $connection->update(
                $cmsBlockTable,
                ['content' => $updatedContent],
                ['block_id = ?' => (int) $row['block_id']]
            );
        } finally {
            $connection->endSetup();
        }
    }

    private function replaceNotices(string $content): ?string
    {
        if (str_contains($content, self::NEW_SHIPPING) && str_contains($content, self::NEW_GUARANTEE)) {
            return null;
        }

        $updatedContent = str_replace(
            [self::OLD_SHIPPING, self::OLD_GUARANTEE],
            [self::NEW_SHIPPING, self::NEW_GUARANTEE],
            $content
        );

        return $updatedContent !== $content ? $updatedContent : null;
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
