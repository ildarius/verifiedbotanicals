<?php

declare(strict_types=1);

namespace Local\InteracETransfer\Cron;

use Local\InteracETransfer\Model\Config;
use Local\InteracETransfer\Model\Method\InteracETransfer;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;

class CancelExpiredOrders
{
    public function __construct(
        private readonly CollectionFactory $orderCollectionFactory,
        private readonly ResourceConnection $resourceConnection,
        private readonly OrderManagementInterface $orderManagement,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $paymentWindowHours = $this->config->getPaymentWindowHours();
        $cutoff = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify(sprintf('-%d hours', $paymentWindowHours))
            ->format('Y-m-d H:i:s');

        $collection = $this->orderCollectionFactory->create();
        $paymentTable = $this->resourceConnection->getTableName('sales_order_payment');

        $collection->addFieldToSelect(['entity_id', 'increment_id', 'status', 'state', 'created_at'])
            ->addFieldToFilter('main_table.state', Order::STATE_PENDING_PAYMENT)
            ->addFieldToFilter('main_table.status', $this->config->getOrderStatus())
            ->addFieldToFilter('main_table.created_at', ['lteq' => $cutoff]);

        $collection->getSelect()->join(
            ['payment' => $paymentTable],
            'main_table.entity_id = payment.parent_id',
            []
        )->where('payment.method = ?', InteracETransfer::PAYMENT_METHOD_CODE);

        foreach ($collection as $order) {
            try {
                if (!$order->canCancel()) {
                    continue;
                }

                $this->orderManagement->cancel((int)$order->getEntityId());

                $savedOrder = $this->orderRepository->get((int)$order->getEntityId());
                $savedOrder->addCommentToStatusHistory(
                    __('Interac e-Transfer payment window expired after %1 hours.', $paymentWindowHours)
                );
                $this->orderRepository->save($savedOrder);
            } catch (\Throwable $exception) {
                $this->logger->error(
                    'Failed to cancel expired Interac e-Transfer order.',
                    [
                        'order_id' => (int)$order->getEntityId(),
                        'increment_id' => (string)$order->getIncrementId(),
                        'exception' => $exception,
                    ]
                );
            }
        }
    }
}
