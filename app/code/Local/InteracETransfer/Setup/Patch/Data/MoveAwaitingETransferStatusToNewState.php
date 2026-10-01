<?php

declare(strict_types=1);

namespace Local\InteracETransfer\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;

/**
 * Orders awaiting an e-Transfer belong in the "new" state, like core offline methods.
 * In "pending_payment" Magento never advances them to processing/complete after invoicing.
 */
class MoveAwaitingETransferStatusToNewState implements DataPatchInterface
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
            $statusStateTable = $this->moduleDataSetup->getTable('sales_order_status_state');
            $orderTable = $this->moduleDataSetup->getTable('sales_order');

            $connection->insertOnDuplicate(
                $statusStateTable,
                [
                    'status' => self::STATUS_CODE,
                    'state' => Order::STATE_NEW,
                    'is_default' => 0,
                    'visible_on_front' => 1,
                ],
                ['visible_on_front']
            );

            $connection->update(
                $orderTable,
                ['state' => Order::STATE_NEW],
                [
                    'state = ?' => Order::STATE_PENDING_PAYMENT,
                    'status = ?' => self::STATUS_CODE,
                ]
            );

            $connection->delete(
                $statusStateTable,
                [
                    'status = ?' => self::STATUS_CODE,
                    'state = ?' => Order::STATE_PENDING_PAYMENT,
                ]
            );
        } finally {
            $connection->endSetup();
        }
    }

    public static function getDependencies(): array
    {
        return [InstallAwaitingETransferStatus::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
