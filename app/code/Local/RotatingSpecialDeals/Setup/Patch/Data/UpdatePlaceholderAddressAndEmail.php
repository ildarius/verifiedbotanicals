<?php
declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdatePlaceholderAddressAndEmail implements DataPatchInterface
{
    private const NEW_EMAIL = 'robert@verifiedbotanicals.com';
    private const NEW_ADDRESS_HTML = '4950 Queen-Mary suite 100<br>Montreal, Quebec<br>H3W1X2';

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
            $cmsPageTable = $this->moduleDataSetup->getTable('cms_page');

            $replacements = [
                '561 Wellington Road, Street 32, San Francisco' => self::NEW_ADDRESS_HTML,
                '561 Wellington Roads, Street 32, San Francisco' => self::NEW_ADDRESS_HTML,
                'contact@market.com' => self::NEW_EMAIL,
                'support@market.com' => self::NEW_EMAIL,
                'CONTACT@MARKET.COM' => self::NEW_EMAIL,
                'emarket@gmail.com' => self::NEW_EMAIL,
                'Magentech@gmail.com' => self::NEW_EMAIL,
            ];

            foreach ($replacements as $old => $new) {
                $like = '%' . $old . '%';

                $connection->query(
                    sprintf('UPDATE %s SET content = REPLACE(content, ?, ?) WHERE content LIKE ?', $cmsBlockTable),
                    [$old, $new, $like]
                );

                $connection->query(
                    sprintf('UPDATE %s SET content = REPLACE(content, ?, ?) WHERE content LIKE ?', $cmsPageTable),
                    [$old, $new, $like]
                );
            }
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

