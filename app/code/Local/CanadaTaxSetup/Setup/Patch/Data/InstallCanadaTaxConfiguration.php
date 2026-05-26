<?php
declare(strict_types=1);

namespace Local\CanadaTaxSetup\Setup\Patch\Data;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\TaxClassInterfaceFactory;
use Magento\Tax\Api\Data\TaxRateInterfaceFactory;
use Magento\Tax\Api\Data\TaxRateTitleInterfaceFactory;
use Magento\Tax\Api\Data\TaxRuleInterfaceFactory;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\Tax\Model\Calculation;

class InstallCanadaTaxConfiguration implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly ResourceConnection $resourceConnection,
        private readonly RegionFactory $regionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly TaxClassRepositoryInterface $taxClassRepository,
        private readonly TaxClassInterfaceFactory $taxClassFactory,
        private readonly TaxRateRepositoryInterface $taxRateRepository,
        private readonly TaxRateInterfaceFactory $taxRateFactory,
        private readonly TaxRateTitleInterfaceFactory $taxRateTitleFactory,
        private readonly TaxRuleRepositoryInterface $taxRuleRepository,
        private readonly TaxRuleInterfaceFactory $taxRuleFactory
    ) {
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        try {
            $activeStoreIds = $this->getActiveStoreIds();

            $retailCustomerClassId = $this->ensureTaxClass('Retail Customer', 'CUSTOMER');
            $taxableGoodsClassId = $this->ensureTaxClass('Taxable Goods', 'PRODUCT');

            $rateIdsByCode = [];
            foreach ($this->getCanadaTaxRates() as $rate) {
                $rateIdsByCode[$rate['code']] = $this->upsertTaxRate(
                    $rate['code'],
                    $rate['region_code'],
                    (float)$rate['rate'],
                    $rate['title'],
                    $activeStoreIds
                );
            }

            foreach ($this->getCanadaTaxRules() as $rule) {
                $ruleRateIds = array_values(array_map(fn (string $code): int => $rateIdsByCode[$code], $rule['rate_codes']));
                $this->upsertTaxRule(
                    $rule['code'],
                    $retailCustomerClassId,
                    $taxableGoodsClassId,
                    $ruleRateIds
                );
            }

            $this->setCoreConfigDefaults($taxableGoodsClassId);
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

    private function getActiveStoreIds(): array
    {
        $ids = [];
        foreach ($this->storeManager->getStores() as $store) {
            if ((int)$store->getId() === 0) {
                continue;
            }
            if ((int)$store->getIsActive() !== 1) {
                continue;
            }
            $ids[] = (int)$store->getId();
        }
        return $ids ?: [0];
    }

    private function ensureTaxClass(string $name, string $type): int
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('tax_class');

        $existing = $connection->fetchOne(
            sprintf('SELECT class_id FROM %s WHERE class_name = ? AND class_type = ? LIMIT 1', $table),
            [$name, $type]
        );
        if ($existing) {
            return (int)$existing;
        }

        $taxClass = $this->taxClassFactory->create();
        $taxClass->setClassName($name);
        $taxClass->setClassType($type);
        $saved = $this->taxClassRepository->save($taxClass);

        return (int)$saved->getClassId();
    }

    private function upsertTaxRate(
        string $code,
        string $regionCode,
        float $rate,
        string $title,
        array $storeIds
    ): int {
        $region = $this->regionFactory->create();
        $region->loadByCode($regionCode, 'CA');
        $regionId = (int)$region->getRegionId();
        if ($regionId <= 0) {
            throw new \RuntimeException(sprintf('Unable to resolve CA region_id for region code "%s"', $regionCode));
        }

        $existingId = $this->findTaxRateIdByCode($code);

        $taxRate = $existingId ? $this->taxRateRepository->get($existingId) : $this->taxRateFactory->create();
        $taxRate->setCode($code);
        $taxRate->setTaxCountryId('CA');
        $taxRate->setTaxRegionId($regionId);
        $taxRate->setTaxPostcode('*');
        $taxRate->setRate($rate);

        $titles = [];
        foreach ($storeIds as $storeId) {
            $rateTitle = $this->taxRateTitleFactory->create();
            $rateTitle->setStoreId((string)$storeId);
            $rateTitle->setValue($title);
            $titles[] = $rateTitle;
        }
        $taxRate->setTitles($titles);

        $saved = $this->taxRateRepository->save($taxRate);
        return (int)$saved->getId();
    }

    private function upsertTaxRule(
        string $code,
        int $customerTaxClassId,
        int $productTaxClassId,
        array $taxRateIds
    ): int {
        $existingId = $this->findTaxRuleIdByCode($code);
        $taxRule = $existingId ? $this->taxRuleRepository->get($existingId) : $this->taxRuleFactory->create();

        $taxRule->setCode($code);
        $taxRule->setPriority(0);
        $taxRule->setPosition(0);
        $taxRule->setCalculateSubtotal(false);
        $taxRule->setCustomerTaxClassIds([$customerTaxClassId]);
        $taxRule->setProductTaxClassIds([$productTaxClassId]);
        $taxRule->setTaxRateIds($taxRateIds);

        $saved = $this->taxRuleRepository->save($taxRule);
        return (int)$saved->getId();
    }

    private function findTaxRateIdByCode(string $code): ?int
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('tax_calculation_rate');

        $existing = $connection->fetchOne(
            sprintf('SELECT tax_calculation_rate_id FROM %s WHERE code = ? LIMIT 1', $table),
            [$code]
        );
        return $existing ? (int)$existing : null;
    }

    private function findTaxRuleIdByCode(string $code): ?int
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('tax_calculation_rule');

        $existing = $connection->fetchOne(
            sprintf('SELECT tax_calculation_rule_id FROM %s WHERE code = ? LIMIT 1', $table),
            [$code]
        );
        return $existing ? (int)$existing : null;
    }

    private function setCoreConfigDefaults(int $shippingTaxClassId): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');

        $quebecRegionId = $this->getQuebecRegionId();

        $values = [
            // Step 4: Tax display settings (plus cross-border trade).
            'tax/calculation/algorithm' => Calculation::CALC_UNIT_BASE,
            'tax/calculation/based_on' => 'shipping',
            'tax/calculation/cross_border_trade_enabled' => '0',
            'tax/defaults/country' => 'CA',
            'tax/defaults/region' => (string)$quebecRegionId,
            'tax/calculation/price_includes_tax' => '0',
            'tax/calculation/shipping_includes_tax' => '0',

            'tax/display/type' => '1', // Excluding Tax
            'tax/display/shipping' => '1', // Excluding Tax

            'tax/cart_display/price' => '1', // Excluding Tax
            'tax/cart_display/subtotal' => '1', // Excluding Tax
            'tax/cart_display/shipping' => '1', // Excluding Tax
            'tax/cart_display/grandtotal' => '0', // Don't additionally show total without tax
            'tax/cart_display/full_summary' => '1',

            'tax/sales_display/price' => '2', // Including Tax
            'tax/sales_display/subtotal' => '2', // Including Tax
            'tax/sales_display/shipping' => '2', // Including Tax
            'tax/sales_display/grandtotal' => '0',
            'tax/sales_display/full_summary' => '1',

            // Step 5: Shipping tax class.
            'tax/classes/shipping_tax_class' => (string)$shippingTaxClassId,
        ];

        foreach ($values as $path => $value) {
            $existing = $connection->fetchOne(
                sprintf('SELECT config_id FROM %s WHERE scope = ? AND scope_id = ? AND path = ? LIMIT 1', $configTable),
                ['default', 0, $path]
            );

            if ($existing) {
                $connection->update(
                    $configTable,
                    ['value' => $value],
                    ['config_id = ?' => (int)$existing]
                );
                continue;
            }

            $connection->insert(
                $configTable,
                [
                    'scope' => 'default',
                    'scope_id' => 0,
                    'path' => $path,
                    'value' => $value,
                ]
            );
        }
    }

    private function getQuebecRegionId(): int
    {
        $region = $this->regionFactory->create();
        $region->loadByCode('QC', 'CA');
        $id = (int)$region->getRegionId();
        if ($id <= 0) {
            throw new \RuntimeException('Unable to resolve CA region_id for Quebec (QC)');
        }
        return $id;
    }

    private function getCanadaTaxRules(): array
    {
        return [
            ['code' => 'CA - Alberta', 'rate_codes' => ['CA-AB-GST']],
            ['code' => 'CA - British Columbia', 'rate_codes' => ['CA-BC-GST', 'CA-BC-PST']],
            ['code' => 'CA - Manitoba', 'rate_codes' => ['CA-MB-GST', 'CA-MB-RST']],
            ['code' => 'CA - New Brunswick', 'rate_codes' => ['CA-NB-HST']],
            ['code' => 'CA - Newfoundland', 'rate_codes' => ['CA-NL-HST']],
            ['code' => 'CA - Northwest Territories', 'rate_codes' => ['CA-NT-GST']],
            ['code' => 'CA - Nova Scotia', 'rate_codes' => ['CA-NS-HST']],
            ['code' => 'CA - Nunavut', 'rate_codes' => ['CA-NU-GST']],
            ['code' => 'CA - Ontario', 'rate_codes' => ['CA-ON-HST']],
            ['code' => 'CA - PEI', 'rate_codes' => ['CA-PE-HST']],
            ['code' => 'CA - Quebec', 'rate_codes' => ['CA-QC-GST', 'CA-QC-QST']],
            ['code' => 'CA - Saskatchewan', 'rate_codes' => ['CA-SK-GST', 'CA-SK-PST']],
            ['code' => 'CA - Yukon', 'rate_codes' => ['CA-YT-GST']],
        ];
    }

    private function getCanadaTaxRates(): array
    {
        return [
            ['code' => 'CA-AB-GST', 'region_code' => 'AB', 'title' => 'GST', 'rate' => 5.0000],
            ['code' => 'CA-BC-GST', 'region_code' => 'BC', 'title' => 'GST', 'rate' => 5.0000],
            ['code' => 'CA-BC-PST', 'region_code' => 'BC', 'title' => 'PST', 'rate' => 7.0000],
            ['code' => 'CA-MB-GST', 'region_code' => 'MB', 'title' => 'GST', 'rate' => 5.0000],
            ['code' => 'CA-MB-RST', 'region_code' => 'MB', 'title' => 'RST', 'rate' => 7.0000],
            ['code' => 'CA-NB-HST', 'region_code' => 'NB', 'title' => 'HST', 'rate' => 15.0000],
            ['code' => 'CA-NL-HST', 'region_code' => 'NL', 'title' => 'HST', 'rate' => 15.0000],
            ['code' => 'CA-NT-GST', 'region_code' => 'NT', 'title' => 'GST', 'rate' => 5.0000],
            ['code' => 'CA-NS-HST', 'region_code' => 'NS', 'title' => 'HST', 'rate' => 15.0000],
            ['code' => 'CA-NU-GST', 'region_code' => 'NU', 'title' => 'GST', 'rate' => 5.0000],
            ['code' => 'CA-ON-HST', 'region_code' => 'ON', 'title' => 'HST', 'rate' => 13.0000],
            ['code' => 'CA-PE-HST', 'region_code' => 'PE', 'title' => 'HST', 'rate' => 15.0000],
            ['code' => 'CA-QC-GST', 'region_code' => 'QC', 'title' => 'GST', 'rate' => 5.0000],
            ['code' => 'CA-QC-QST', 'region_code' => 'QC', 'title' => 'QST', 'rate' => 9.9750],
            ['code' => 'CA-SK-GST', 'region_code' => 'SK', 'title' => 'GST', 'rate' => 5.0000],
            ['code' => 'CA-SK-PST', 'region_code' => 'SK', 'title' => 'PST', 'rate' => 6.0000],
            ['code' => 'CA-YT-GST', 'region_code' => 'YT', 'title' => 'GST', 'rate' => 5.0000],
        ];
    }
}
