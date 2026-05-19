<?php

declare(strict_types=1);

namespace Local\HomepageAssets\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RestoreHomepageKratomWidgets implements DataPatchInterface
{
    private const PAGE_IDENTIFIER = 'home-demo-37';

    private const DEAL_SECTION_PATTERN =
        '#&lt;div class="block-home-37 block-deal-full-37 hover-to-show"&gt;.*?&lt;/div&gt;\s*&lt;/div&gt;\s*&lt;/div&gt;#s';

    private const NEW_ARRIVALS_SECTION_PATTERN =
        '#&lt;div class="container"&gt;\s*&lt;div class="block-home-37"&gt;\s*&lt;div class="block-title"&gt;\s*&lt;span&gt;\{\{trans "Today[^"]*"\}\}&lt;/span&gt;\s*&lt;strong&gt;\{\{trans "New Arrivals"\}\}&lt;/strong&gt;\s*&lt;/div&gt;.*?&lt;/div&gt;\s*&lt;/div&gt;\s*&lt;/div&gt;#s';

    private const DEAL_SECTION_REPLACEMENT = <<<'HTML'
&lt;div class="block-home-37 block-deal-full-37 hover-to-show"&gt;
  &lt;div class="container"&gt;
    &lt;div data-owl="owl-slider" data-autoplay="false" data-nav="true" data-dots="false" data-screen0="1" data-screen481="2" data-screen768="2" data-screen992="2" data-screen1200="2" data-screen1441="2" data-screen1681="2" data-screen1920="2" data-margin="30" data-autoplayhoverpause="true" data-loop="false" data-center="false" data-stagepadding="0" data-mousedrag="true" data-touchdrag="true"&gt;
      {{widget type="Sm\FilterProducts\Block\Widget\AddFilterProducts" template="Sm_FilterProducts::grid-slider-deal2.phtml" product_source="lastest_products" select_category="377,378,379" product_limitation="3" date_to="06/07/2026" title_text="Deals Of The Day" title_module="Grab The Best Offer &lt;br&gt;of This Week!" view_link="#" display_countdown="0"}}
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;
HTML;

    private const NEW_ARRIVALS_SECTION_REPLACEMENT = <<<'HTML'
&lt;div class="container"&gt;
  &lt;div class="block-home-37"&gt;
    &lt;div class="block-title"&gt;
      &lt;span&gt;{{trans "Today’s Fresh"}}&lt;/span&gt;
      &lt;strong&gt;{{trans "New Arrivals"}}&lt;/strong&gt;
    &lt;/div&gt;
    &lt;div class="block-bestseller block-home hover-to-show hidden-title-block"&gt;
      &lt;div data-owl="owl-slider" data-autoplay="false" data-nav="true" data-dots="false" data-screen0="1" data-screen481="2" data-screen768="3" data-screen992="4" data-screen1200="5" data-screen1441="5" data-screen1681="5" data-screen1920="5" data-margin="30" data-autoplayhoverpause="true" data-loop="false" data-center="false" data-stagepadding="0" data-mousedrag="true" data-touchdrag="true"&gt;
        {{widget type="Sm\FilterProducts\Block\Widget\AddFilterProducts" template="Sm_FilterProducts::grid-slider.phtml" title_module="NEW PRODUCTS" product_source="lastest_products" select_category="377,378,379" product_limitation="8" display_countdown="0"}}
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;
HTML;

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('cms_page');

        $this->moduleDataSetup->startSetup();

        try {
            $content = $connection->fetchOne(
                $connection->select()
                    ->from($table, ['content'])
                    ->where('identifier = ?', self::PAGE_IDENTIFIER)
                    ->limit(1)
            );

            if (!is_string($content) || $content === '') {
                return $this;
            }

            $updated = preg_replace(
                self::DEAL_SECTION_PATTERN,
                self::DEAL_SECTION_REPLACEMENT,
                $content,
                1
            );

            if (!is_string($updated)) {
                return $this;
            }

            $updated = preg_replace(
                self::NEW_ARRIVALS_SECTION_PATTERN,
                self::NEW_ARRIVALS_SECTION_REPLACEMENT,
                $updated,
                1
            );

            if (!is_string($updated) || $updated === $content) {
                return $this;
            }

            $connection->update(
                $table,
                ['content' => $updated],
                ['identifier = ?' => self::PAGE_IDENTIFIER]
            );
        } finally {
            $this->moduleDataSetup->endSetup();
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            ReplaceHomepageProductWidgetsWithKratomBlocks::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
