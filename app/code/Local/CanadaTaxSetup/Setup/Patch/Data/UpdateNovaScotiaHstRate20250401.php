<?php
declare(strict_types=1);

namespace Local\CanadaTaxSetup\Setup\Patch\Data;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateNovaScotiaHstRate20250401 implements DataPatchInterface
{
    private const TARGET_REGION_CODE = 'NS';
    private const NEW_RATE = 14.0000;

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly RegionFactory $regionFactory
    ) {
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        try {
            $region = $this->regionFactory->create();
            $region->loadByCode(self::TARGET_REGION_CODE, 'CA');
            $regionId = (int)$region->getRegionId();
            if ($regionId <= 0) {
                throw new \RuntimeException('Unable to resolve CA region_id for Nova Scotia (NS)');
            }

            $table = $this->moduleDataSetup->getTable('tax_calculation_rate');

            $rateIds = $connection->fetchCol(
                sprintf(
                    'SELECT tax_calculation_rate_id FROM %s WHERE tax_country_id = ? AND tax_region_id = ? AND tax_postcode = ?',
                    $table
                ),
                ['CA', $regionId, '*']
            );

            foreach ($rateIds as $rateId) {
                $connection->update(
                    $table,
                    ['rate' => self::NEW_RATE],
                    ['tax_calculation_rate_id = ?' => (int)$rateId]
                );
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
}

