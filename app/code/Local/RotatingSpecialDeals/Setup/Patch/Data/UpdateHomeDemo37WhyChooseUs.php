<?php
declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateHomeDemo37WhyChooseUs implements DataPatchInterface
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
            if (str_contains($content, 'Why Buyers Choose Verified Botanicals')) {
                return;
            }

            $startNeedle = '&lt;div class="service-home block-home-37"&gt;';
            $endNeedle = '&lt;div class="client-farmer block-home-37';

            $start = strpos($content, $startNeedle);
            $end = strpos($content, $endNeedle, $start === false ? 0 : $start);

            if ($start === false || $end === false || $end <= $start) {
                return;
            }

            $replacement = <<<'HTML'
&lt;div class="service-home block-home-37"&gt;
    &lt;div class="row"&gt;
      &lt;div class="col-lg-6"&gt;
        &lt;a href="#" class="banner-service"&gt;
          &lt;img class="mark-lazy" src="{{view url='images/home-demo-37/banner/kratom_farmer_indonesia.png'}}" alt="Banner" width="750" height="722" /&gt;
        &lt;/a&gt;
      &lt;/div&gt;
      &lt;div class="col-lg-6"&gt;
        &lt;div class="content"&gt;
          &lt;div class="block-title"&gt;
            &lt;span&gt;Transparent. Canadian. In stock.&lt;/span&gt;
            &lt;strong&gt;Why Buyers Choose Verified Botanicals&lt;/strong&gt;
          &lt;/div&gt;
          &lt;ul&gt;
            &lt;li class="item"&gt;
              &lt;div class="image"&gt;
                &lt;img class="mark-lazy" src="{{view url='images/home-demo-37/icon-image/icon-55-1.png'}}" alt="Banner" width="90" height="90" /&gt;
              &lt;/div&gt;
              &lt;div class="info"&gt;
                &lt;h3&gt;Lab-Tested Every Batch&lt;/h3&gt;
                &lt;p&gt;Every strain we carry is third-party tested for purity and authenticity. Certificates of analysis are available on request. No guesswork, no blind trust.&lt;/p&gt;
              &lt;/div&gt;
            &lt;/li&gt;
            &lt;li class="item"&gt;
              &lt;div class="image"&gt;
                &lt;img class="mark-lazy" src="{{view url='images/home-demo-37/icon-image/icon-55-2.png'}}" alt="Banner" width="90" height="90" /&gt;
              &lt;/div&gt;
              &lt;div class="info"&gt;
                &lt;h3&gt;Always In Stock&lt;/h3&gt;
                &lt;p&gt;We maintain inventory on all six strains year-round. No backorders, no &quot;temporarily unavailable&quot;. Your order ships the same or next business day.&lt;/p&gt;
              &lt;/div&gt;
            &lt;/li&gt;
            &lt;li class="item"&gt;
              &lt;div class="image"&gt;
                &lt;img class="mark-lazy" src="{{view url='images/home-demo-37/icon-image/icon-55-3.png'}}" alt="Banner" width="90" height="90" /&gt;
              &lt;/div&gt;
              &lt;div class="info"&gt;
                &lt;h3&gt;Safe &amp; Secure Checkout&lt;/h3&gt;
                &lt;p&gt;All transactions are encrypted and processed through trusted Canadian payment systems. Your personal and payment data is never stored on our servers.&lt;/p&gt;
              &lt;/div&gt;
            &lt;/li&gt;
            &lt;li class="item"&gt;
              &lt;div class="image"&gt;
                &lt;img class="mark-lazy" src="{{view url='images/home-demo-37/icon-image/icon-55-4.png'}}" alt="Banner" width="90" height="90" /&gt;
              &lt;/div&gt;
              &lt;div class="info"&gt;
                &lt;h3&gt;Real Person, Real Support&lt;/h3&gt;
                &lt;p&gt;Questions before you order? Text or call us directly at our Montreal number. No ticket system, no chatbot. A real person responds.&lt;/p&gt;
              &lt;/div&gt;
            &lt;/li&gt;
          &lt;/ul&gt;
        &lt;/div&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;
HTML;

            $updated = substr($content, 0, $start) . $replacement . substr($content, $end);

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
