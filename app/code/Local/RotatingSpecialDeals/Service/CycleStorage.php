<?php

declare(strict_types=1);

namespace Local\RotatingSpecialDeals\Service;

use Magento\Framework\App\ResourceConnection;

class CycleStorage
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ROTATED = 'rotated';
    public const STATUS_FAILED = 'failed';

    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function getActiveCycle(): ?array
    {
        return $this->loadSingleCycleByStatus(self::STATUS_ACTIVE);
    }

    public function getLatestResolvedCycle(): ?array
    {
        $connection = $this->resourceConnection->getConnection();
        $cycleTable = $this->resourceConnection->getTableName('local_rotating_special_deal_cycle');
        $select = $connection->select()
            ->from($cycleTable)
            ->where('status IN (?)', [self::STATUS_ACTIVE, self::STATUS_ROTATED])
            ->order('started_at DESC')
            ->limit(1);

        $cycle = $connection->fetchRow($select);
        if (!$cycle) {
            return null;
        }

        $cycle['items'] = $this->getCycleItems((int)$cycle['cycle_id']);

        return $cycle;
    }

    /**
     * @param array<int,array<string,int|string>> $items
     */
    public function createCycle(
        \DateTimeImmutable $startedAt,
        \DateTimeImmutable $endsAt,
        array $items,
        string $homepageIdentifier,
        string $status = self::STATUS_PENDING
    ): int {
        $connection = $this->resourceConnection->getConnection();
        $cycleTable = $this->resourceConnection->getTableName('local_rotating_special_deal_cycle');
        $itemTable = $this->resourceConnection->getTableName('local_rotating_special_deal_item');

        $connection->beginTransaction();
        try {
            $connection->insert($cycleTable, [
                'started_at' => $startedAt->format('Y-m-d H:i:s'),
                'ends_at' => $endsAt->format('Y-m-d H:i:s'),
                'status' => $status,
                'homepage_identifier' => $homepageIdentifier,
            ]);

            $cycleId = (int)$connection->lastInsertId($cycleTable);

            foreach ($items as $item) {
                $connection->insert($itemTable, [
                    'cycle_id' => $cycleId,
                    'product_id' => (int)$item['product_id'],
                    'sku' => (string)$item['sku'],
                    'group_key' => (string)$item['group_key'],
                ]);
            }

            $connection->commit();

            return $cycleId;
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    public function updateStatus(int $cycleId, string $status): void
    {
        $connection = $this->resourceConnection->getConnection();
        $cycleTable = $this->resourceConnection->getTableName('local_rotating_special_deal_cycle');

        $connection->update(
            $cycleTable,
            ['status' => $status],
            ['cycle_id = ?' => $cycleId]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getCycleItems(int $cycleId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $itemTable = $this->resourceConnection->getTableName('local_rotating_special_deal_item');
        $select = $connection->select()
            ->from($itemTable)
            ->where('cycle_id = ?', $cycleId)
            ->order('item_id ASC');

        return $connection->fetchAll($select);
    }

    private function loadSingleCycleByStatus(string $status): ?array
    {
        $connection = $this->resourceConnection->getConnection();
        $cycleTable = $this->resourceConnection->getTableName('local_rotating_special_deal_cycle');
        $select = $connection->select()
            ->from($cycleTable)
            ->where('status = ?', $status)
            ->order('started_at DESC')
            ->limit(1);

        $cycle = $connection->fetchRow($select);
        if (!$cycle) {
            return null;
        }

        $cycle['items'] = $this->getCycleItems((int)$cycle['cycle_id']);

        return $cycle;
    }
}
