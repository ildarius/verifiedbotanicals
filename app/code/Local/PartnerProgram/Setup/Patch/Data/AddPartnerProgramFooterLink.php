<?php
declare(strict_types=1);

namespace Local\PartnerProgram\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Adds "Partner Program" after "About Us" in the footer-links-bottom CMS block. The block is a Page Builder
 * HTML element, so its markup is stored HTML-escaped. Skips if the link is already there.
 */
class AddPartnerProgramFooterLink implements DataPatchInterface
{
    private const BLOCK = 'footer-links-bottom';
    private const ANCHOR = '&lt;li&gt;&lt;a href="{{store url="about-us"}}"&gt;{{trans "About Us"}}&lt;/a&gt;&lt;/li&gt;';
    private const LINK = '&lt;li&gt;&lt;a href="{{store url="partner-program"}}"&gt;{{trans "Partner Program"}}&lt;/a&gt;&lt;/li&gt;';

    public function __construct(private readonly ModuleDataSetupInterface $moduleDataSetup)
    {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('cms_block');
        $content = (string)$connection->fetchOne(
            $connection->select()->from($table, ['content'])->where('identifier = ?', self::BLOCK)
        );
        if ($content === '' || str_contains($content, 'partner-program') || !str_contains($content, self::ANCHOR)) {
            return $this;
        }

        $connection->update(
            $table,
            ['content' => str_replace(self::ANCHOR, self::ANCHOR . "\n                            " . self::LINK, $content)],
            ['identifier = ?' => self::BLOCK]
        );

        return $this;
    }

    public static function getDependencies(): array
    {
        return [CreatePartnerProgramPage::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
