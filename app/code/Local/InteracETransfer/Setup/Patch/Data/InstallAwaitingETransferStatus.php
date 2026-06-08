<?php

declare(strict_types=1);

namespace Local\InteracETransfer\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;

class InstallAwaitingETransferStatus implements DataPatchInterface
{
    private const STATUS_CODE = 'awaiting_etransfer';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        try {
            $statusTable = $this->moduleDataSetup->getTable('sales_order_status');
            $statusStateTable = $this->moduleDataSetup->getTable('sales_order_status_state');

            $connection->insertOnDuplicate(
                $statusTable,
                [
                    'status' => self::STATUS_CODE,
                    'label' => 'Awaiting E-Transfer',
                ],
                ['label']
            );

            $stateRow = $connection->fetchOne(
                sprintf(
                    'SELECT status FROM %s WHERE status = ? AND state = ?',
                    $statusStateTable
                ),
                [self::STATUS_CODE, Order::STATE_PENDING_PAYMENT]
            );

            if (!$stateRow) {
                $connection->insert(
                    $statusStateTable,
                    [
                        'status' => self::STATUS_CODE,
                        'state' => Order::STATE_PENDING_PAYMENT,
                        'is_default' => 0,
                        'visible_on_front' => 1,
                    ]
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
