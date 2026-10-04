<?php

declare(strict_types=1);

namespace Local\GuestToCustomer\Observer;

use Local\GuestToCustomer\Model\Config;
use Local\GuestToCustomer\Model\NewAccountSession;
use Local\GuestToCustomer\Service\GuestOrderConverter;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

/**
 * Runs after the order is placed (checkout_submit_all_after). Failures are logged and never
 * bubble up: the order already exists, so the customer must still land on the success page.
 */
class ConvertGuestOrderObserver implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly GuestOrderConverter $converter,
        private readonly NewAccountSession $newAccountSession,
        private readonly State $appState,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        // Onepage/API checkout passes "order"; multishipping passes "orders".
        $orders = $observer->getEvent()->getData('orders') ?: [$observer->getEvent()->getData('order')];

        foreach ($orders as $order) {
            if (!$order instanceof OrderInterface || !$this->config->isEnabled((int)$order->getStoreId())) {
                continue;
            }

            try {
                $result = $this->converter->convert(
                    $order,
                    $this->config->isLinkExisting((int)$order->getStoreId())
                );

                // Lets the success page offer "Set your password" to this shopper (storefront only, not admin orders).
                if ($result['result'] === GuestOrderConverter::RESULT_CREATED
                    && $this->appState->getAreaCode() !== Area::AREA_ADMINHTML
                ) {
                    $this->newAccountSession->remember($order, (int)$result['customer_id']);
                }
            } catch (\Throwable $exception) {
                $this->logger->error(
                    sprintf('GuestToCustomer: could not convert order #%s', $order->getIncrementId()),
                    ['exception' => $exception]
                );
                continue;
            }

            if ($result['result'] !== GuestOrderConverter::RESULT_SKIPPED) {
                $this->logger->info(
                    sprintf(
                        'GuestToCustomer: order #%s %s customer %d',
                        $order->getIncrementId(),
                        $result['result'] === GuestOrderConverter::RESULT_CREATED ? 'created' : 'linked to',
                        $result['customer_id']
                    )
                );
            }
        }
    }
}
