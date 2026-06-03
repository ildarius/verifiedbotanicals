<?php
declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateHomeDemo37VerifiedFeedback implements DataPatchInterface
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

            $content = $connection->fetchOne(
                sprintf('SELECT content FROM %s WHERE identifier = ?', $cmsPageTable),
                ['home-demo-37']
            );
            if (!is_string($content) || $content === '') {
                return;
            }

            $replacement = $this->loadReplacementSectionFromThemeImport();
            if ($replacement === null) {
                return;
            }

            $sectionStart = '&lt;div class="client-farmer block-home-37 hover-to-show"&gt;';
            $startPos = strpos($content, $sectionStart);
            if ($startPos === false) {
                return;
            }

            $sectionEnd = '&lt;/div&gt;</div>';
            $endPos = strpos($content, $sectionEnd, $startPos);
            if ($endPos === false) {
                return;
            }

            $endPos += strlen($sectionEnd);
            $updatedContent = substr($content, 0, $startPos) . $replacement . substr($content, $endPos);

            $connection->update(
                $cmsPageTable,
                ['content' => $updatedContent],
                ['identifier = ?' => 'home-demo-37']
            );
        } finally {
            $connection->endSetup();
        }
    }

    private function loadReplacementSectionFromThemeImport(): ?string
    {
        $pagesXmlPath = BP . '/app/code/Sm/Market/etc/import/pages.xml';
        $xml = @file_get_contents($pagesXmlPath);
        if (!is_string($xml) || $xml === '') {
            return null;
        }

        if (
            !preg_match(
                '~(&lt;div class="client-farmer block-home-37 hover-to-show"&gt;.*?&lt;/div&gt;</div>)~s',
                $xml,
                $matches
            )
        ) {
            return null;
        }

        return $matches[1];
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

