<?php
declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateFooterShippingHours implements DataPatchInterface
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
            $cmsBlockTable = $this->moduleDataSetup->getTable('cms_block');

            $connection->query(
                sprintf(
                    'UPDATE %s SET content = REPLACE(content, ?, ?) WHERE identifier = ?',
                    $cmsBlockTable
                ),
                [
                    '{{trans "Working Hours"}}',
                    '{{trans "Shipping Hours"}}',
                    'footer-29-content',
                ]
            );

            $saturdayLine = '&lt;li&gt;&lt;a href="#"&gt;{{trans "Saturday - Sunday   (Closed)"}}&lt;/a&gt;&lt;/li&gt;';
            $easternLine = "\n" . '&lt;li&gt;&lt;a href="#"&gt;{{trans "(Eastern Time Zone)"}}&lt;/a&gt;&lt;/li&gt;';

            $connection->query(
                sprintf(
                    'UPDATE %s SET content = REPLACE(content, ?, ?) WHERE identifier = ? AND content NOT LIKE ?',
                    $cmsBlockTable
                ),
                [
                    $saturdayLine,
                    $saturdayLine . $easternLine,
                    'footer-29-content',
                    '%Eastern Time Zone%',
                ]
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
