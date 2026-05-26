<?php
declare(strict_types=1);

namespace Local\CanadaTaxSetup\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Tax\Api\Data\TaxRuleInterfaceFactory;
use Magento\Tax\Api\TaxRuleRepositoryInterface;

class SplitCanadaStackedTaxRules implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly ResourceConnection $resourceConnection,
        private readonly TaxRuleRepositoryInterface $taxRuleRepository,
        private readonly TaxRuleInterfaceFactory $taxRuleFactory
    ) {
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        try {
            $retailCustomerClassId = (int)$this->fetchOne(
                'SELECT class_id FROM %s WHERE class_name = ? AND class_type = ? LIMIT 1',
                'tax_class',
                ['Retail Customer', 'CUSTOMER']
            );
            $taxableGoodsClassId = (int)$this->fetchOne(
                'SELECT class_id FROM %s WHERE class_name = ? AND class_type = ? LIMIT 1',
                'tax_class',
                ['Taxable Goods', 'PRODUCT']
            );

            if ($retailCustomerClassId <= 0 || $taxableGoodsClassId <= 0) {
                throw new \RuntimeException('Expected tax classes not found (Retail Customer / Taxable Goods).');
            }

            $stacked = [
                'CA - British Columbia' => [
                    ['code' => 'CA - British Columbia - GST', 'rate_code' => 'CA-BC-GST'],
                    ['code' => 'CA - British Columbia - PST', 'rate_code' => 'CA-BC-PST'],
                ],
                'CA - Manitoba' => [
                    ['code' => 'CA - Manitoba - GST', 'rate_code' => 'CA-MB-GST'],
                    ['code' => 'CA - Manitoba - RST', 'rate_code' => 'CA-MB-RST'],
                ],
                'CA - Quebec' => [
                    ['code' => 'CA - Quebec - GST', 'rate_code' => 'CA-QC-GST'],
                    ['code' => 'CA - Quebec - QST', 'rate_code' => 'CA-QC-QST'],
                ],
                'CA - Saskatchewan' => [
                    ['code' => 'CA - Saskatchewan - GST', 'rate_code' => 'CA-SK-GST'],
                    ['code' => 'CA - Saskatchewan - PST', 'rate_code' => 'CA-SK-PST'],
                ],
            ];

            foreach ($stacked as $oldRuleCode => $newRules) {
                $oldRuleId = $this->findTaxRuleIdByCode($oldRuleCode);
                if ($oldRuleId) {
                    $this->taxRuleRepository->deleteById($oldRuleId);
                }

                foreach ($newRules as $newRule) {
                    $rateId = $this->findTaxRateIdByCode($newRule['rate_code']);
                    if (!$rateId) {
                        throw new \RuntimeException(sprintf('Missing tax rate code "%s"', $newRule['rate_code']));
                    }
                    $this->upsertSingleRateRule(
                        $newRule['code'],
                        $retailCustomerClassId,
                        $taxableGoodsClassId,
                        $rateId
                    );
                }
            }
        } finally {
            $connection->endSetup();
        }
    }

    public static function getDependencies(): array
    {
        return [
            InstallCanadaTaxConfiguration::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    private function fetchOne(string $query, string $table, array $bind = []): ?string
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName($table);
        $value = $connection->fetchOne(sprintf($query, $tableName), $bind);
        if ($value === false) {
            return null;
        }
        return (string)$value;
    }

    private function findTaxRateIdByCode(string $code): ?int
    {
        $id = $this->fetchOne(
            'SELECT tax_calculation_rate_id FROM %s WHERE code = ? LIMIT 1',
            'tax_calculation_rate',
            [$code]
        );
        return $id ? (int)$id : null;
    }

    private function findTaxRuleIdByCode(string $code): ?int
    {
        $id = $this->fetchOne(
            'SELECT tax_calculation_rule_id FROM %s WHERE code = ? LIMIT 1',
            'tax_calculation_rule',
            [$code]
        );
        return $id ? (int)$id : null;
    }

    private function upsertSingleRateRule(
        string $code,
        int $customerTaxClassId,
        int $productTaxClassId,
        int $taxRateId
    ): int {
        $existingId = $this->findTaxRuleIdByCode($code);
        $taxRule = $existingId ? $this->taxRuleRepository->get($existingId) : $this->taxRuleFactory->create();

        $taxRule->setCode($code);
        $taxRule->setPriority(0);
        $taxRule->setPosition(0);
        $taxRule->setCalculateSubtotal(false);
        $taxRule->setCustomerTaxClassIds([$customerTaxClassId]);
        $taxRule->setProductTaxClassIds([$productTaxClassId]);
        $taxRule->setTaxRateIds([$taxRateId]);

        $saved = $this->taxRuleRepository->save($taxRule);
        return (int)$saved->getId();
    }
}
