<?php
declare(strict_types=1);

namespace Local\PartnerProgram\Block\Widget;

use Local\PartnerProgram\Model\PartnerProgramData;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;

/**
 * Partner program explainer. Every price and percentage it prints is computed here from live data
 * (see PartnerProgramData); the template holds only words.
 */
class PartnerPage extends Template implements BlockInterface, IdentityInterface
{
    /** A year of monthly orders: the discounted first order plus this many full-price orders. */
    private const LATER_ORDERS_IN_FIRST_YEAR = 11;

    protected $_template = 'Local_PartnerProgram::widget/partner-page.phtml';

    private ?array $model = null;

    public function __construct(
        Context $context,
        private readonly PartnerProgramData $programData,
        private readonly PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array{
     *   rates: array{discount: float, commission_first: float, commission_other: float, uses_per_customer: int}|null,
     *   first_order_share: float,
     *   holding_days: int,
     *   rows: list<array{size: string, from: bool, price: float, first_pays: float, first_earn: float, later_earn: float}>,
     *   examples: list<array{regulars: int, cells: list<array{per_month: float, first_year: float}>}>,
     *   example_sizes: string[],
     *   later_orders_in_first_year: int
     * }
     */
    public function getModel(): array
    {
        if ($this->model !== null) {
            return $this->model;
        }

        $rates = $this->programData->getRates((int)($this->getData('program_id') ?: 1));
        $sizeAttribute = (string)($this->getData('size_attribute') ?: 'kratom_weight');
        $tableSizes = $this->splitList((string)$this->getData('pack_sizes'));
        $exampleSizes = $this->splitList((string)$this->getData('example_sizes'));
        $regularCounts = array_filter(array_map('intval', $this->splitList((string)$this->getData('example_regulars'))));

        $rows = [];
        $bySize = [];
        if ($rates) {
            $prices = $this->programData->getPackPrices($sizeAttribute, array_unique(array_merge($tableSizes, $exampleSizes)));
            foreach ($prices as $size => $range) {
                $row = $this->computeRow($size, $range['min'], $range['min'] !== $range['max'], $rates);
                $bySize[$size] = $row;
                if (in_array($size, $tableSizes, true)) {
                    $rows[] = $row;
                }
            }
        }

        $exampleSizes = array_values(array_filter($exampleSizes, static fn ($s) => isset($bySize[$s])));
        $examples = [];
        foreach ($exampleSizes ? $regularCounts : [] as $count) {
            $cells = [];
            foreach ($exampleSizes as $size) {
                $row = $bySize[$size];
                $cells[] = [
                    'per_month' => round($count * $row['later_earn'], 2),
                    'first_year' => round(
                        $count * ($row['first_earn'] + self::LATER_ORDERS_IN_FIRST_YEAR * $row['later_earn']),
                        2
                    ),
                ];
            }
            $examples[] = ['regulars' => $count, 'cells' => $cells];
        }

        return $this->model = [
            'rates' => $rates,
            'first_order_share' => $rates ? (100 - $rates['discount']) * $rates['commission_first'] / 100 : 0.0,
            'holding_days' => $this->programData->getHoldingDays(),
            'rows' => $rows,
            'examples' => $examples,
            'example_sizes' => $exampleSizes,
            'later_orders_in_first_year' => self::LATER_ORDERS_IN_FIRST_YEAR,
        ];
    }

    public function formatMoney(float $amount): string
    {
        return $this->priceCurrency->format($amount, false, PriceCurrencyInterface::DEFAULT_PRECISION);
    }

    /** 20.0 -> "20%", 16.5 -> "16.5%" */
    public function formatPercent(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%';
    }

    /** "100g" -> "100 g" */
    public function formatSize(string $label): string
    {
        return (string)preg_replace('/^(\d+(?:\.\d+)?)\s*([a-z]+)$/i', '$1 $2', $label);
    }

    public function getContactName(): string
    {
        return trim((string)$this->getData('contact_name'));
    }

    public function getContactPhone(): string
    {
        return trim((string)$this->getData('contact_phone'));
    }

    public function getContactPhoneHref(): string
    {
        $digits = preg_replace('/\D+/', '', $this->getContactPhone());

        return $digits ? 'tel:+' . (strlen($digits) === 10 ? '1' . $digits : $digits) : '';
    }

    public function getIdentities(): array
    {
        $this->getModel();
        $tags = [PartnerProgramData::CACHE_TAG];
        foreach ($this->programData->getProductIds() as $id) {
            $tags[] = Product::CACHE_TAG . '_' . $id;
        }

        return $tags;
    }

    private function computeRow(string $size, float $price, bool $varies, array $rates): array
    {
        $firstPays = round($price - round($price * $rates['discount'] / 100, 2), 2);

        return [
            'size' => $size,
            'from' => $varies,
            'price' => $price,
            'first_pays' => $firstPays,
            'first_earn' => round($firstPays * $rates['commission_first'] / 100, 2),
            'later_earn' => round($price * $rates['commission_other'] / 100, 2),
        ];
    }

    /** @return string[] */
    private function splitList(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
    }
}
