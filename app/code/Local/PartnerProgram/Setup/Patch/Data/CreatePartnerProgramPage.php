<?php
declare(strict_types=1);

namespace Local\PartnerProgram\Setup\Patch\Data;

use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page as PageResource;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Creates the CMS page /partner-program. Its content is only the partner page widget, which renders the
 * whole page from Local_PartnerProgram::widget/partner-page.phtml with live prices and rates.
 * Does nothing if a page with that URL key already exists (so admin edits are kept).
 */
class CreatePartnerProgramPage implements DataPatchInterface
{
    public const IDENTIFIER = 'partner-program';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly PageFactory $pageFactory,
        private readonly PageResource $pageResource
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $exists = $connection->fetchOne(
            $connection->select()
                ->from($this->moduleDataSetup->getTable('cms_page'), ['page_id'])
                ->where('identifier = ?', self::IDENTIFIER)
        );
        if ($exists) {
            return $this;
        }

        // Backslashes are doubled: the widget directive parser treats a single backslash as an escape.
        $content = '{{widget type="Local\\\\PartnerProgram\\\\Block\\\\Widget\\\\PartnerPage"'
            . ' program_id="1" size_attribute="kratom_weight" pack_sizes="100g,250g,500g"'
            . ' example_sizes="100g,250g" example_regulars="25,50"'
            . ' contact_name="Ildar" contact_phone="(263) 366-8232"}}';

        $page = $this->pageFactory->create();
        $page->setData([
            'title' => 'Partner Program for Smoke Shops',
            'identifier' => self::IDENTIFIER,
            'page_layout' => '1column',
            'content_heading' => '',
            'content' => $content,
            'meta_title' => 'Partner Program for Smoke Shops and Head Shops | Verified Botanicals',
            'meta_description' => 'How the Verified Botanicals partner program works for smoke shops and head shops:'
                . ' shop cards with a partner code, a discount for customers, a commission for the shop, and how to join.',
            'is_active' => 1,
            'stores' => [0],
        ]);
        $this->pageResource->save($page);

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
}
