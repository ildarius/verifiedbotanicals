<?php
declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MoveHomepageBannerStripToTemplate implements DataPatchInterface
{
    private const PAGE_IDENTIFIERS = [
        'home',
        'home-demo-37',
    ];

    private const TEMPLATE_DIRECTIVE =
        '{{block class="Magento\Framework\View\Element\Template" template="Magento_Theme::home/home-demo-37-banners.phtml"}}';

    private const START_NEEDLE = '&lt;div class="container"&gt;';

    private const END_NEEDLE = '&lt;div class="service-home block-home-37"&gt;';

    private const MARKERS = [
        'pure_kratom_leaves.png',
        'verified_botanicals_kratom_mylar_bag.png',
        'kratom_shipment_box.png',
        'banner-119.jpg',
        'banner-120.jpg',
        'banner-121.jpg',
    ];

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

            foreach (self::PAGE_IDENTIFIERS as $identifier) {
                $row = $connection->fetchRow(
                    sprintf('SELECT page_id, content FROM %s WHERE identifier = ?', $cmsPageTable),
                    [$identifier]
                );

                if (!$row || !isset($row['page_id'], $row['content'])) {
                    continue;
                }

                $content = (string) $row['content'];
                $updatedContent = $this->replaceBannerStrip($content);

                if ($updatedContent === null || $updatedContent === $content) {
                    continue;
                }

                $connection->update(
                    $cmsPageTable,
                    ['content' => $updatedContent],
                    ['page_id = ?' => (int) $row['page_id']]
                );
            }
        } finally {
            $connection->endSetup();
        }
    }

    private function replaceBannerStrip(string $content): ?string
    {
        if (str_contains($content, self::TEMPLATE_DIRECTIVE)) {
            return null;
        }

        $markerPosition = $this->findMarkerPosition($content);
        if ($markerPosition === null) {
            return null;
        }

        $startPosition = strrpos(substr($content, 0, $markerPosition), self::START_NEEDLE);
        $endPosition = strpos($content, self::END_NEEDLE, $markerPosition);

        if ($startPosition === false || $endPosition === false || $endPosition <= $startPosition) {
            return null;
        }

        return substr($content, 0, $startPosition)
            . self::TEMPLATE_DIRECTIVE
            . "\n  "
            . substr($content, $endPosition);
    }

    private function findMarkerPosition(string $content): ?int
    {
        foreach (self::MARKERS as $marker) {
            $position = strpos($content, $marker);
            if ($position !== false) {
                return $position;
            }
        }

        return null;
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
