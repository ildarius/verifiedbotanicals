<?php

declare(strict_types=1);

namespace Local\GuestToCustomer\Service;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\OrderCustomerExtractor;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Turns a guest order into a customer order.
 *
 * Same idea as the core "Create an Account" button on the checkout success page
 * (Magento\Sales\Model\Order\CustomerManagement), but it keeps every order address
 * (core drops addresses whose "save in address book" flag is off, which is the case
 * for most guest shipping addresses) and attaches the order to an existing account
 * when the email is already registered.
 */
class GuestOrderConverter
{
    public const RESULT_CREATED = 'created';
    public const RESULT_LINKED = 'linked';
    public const RESULT_SKIPPED = 'skipped';

    public function __construct(
        private readonly AccountManagementInterface $accountManagement,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderCustomerExtractor $orderCustomerExtractor,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Find the account the order would be attached to, or null if a new one would be created.
     */
    public function findExistingCustomer(OrderInterface $order): ?CustomerInterface
    {
        try {
            return $this->customerRepository->get(
                trim((string)$order->getCustomerEmail()),
                $this->getWebsiteId($order)
            );
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }

    public function canConvert(OrderInterface $order): bool
    {
        return !$order->getCustomerId() && trim((string)$order->getCustomerEmail()) !== '';
    }

    /**
     * Saves the passed order instance so callers that still hold it (checkout) keep the customer link.
     *
     * @return array{result: string, customer_id: int|null}
     */
    public function convert(OrderInterface $order, bool $linkExisting = true): array
    {
        if (!$this->canConvert($order)) {
            return ['result' => self::RESULT_SKIPPED, 'customer_id' => null];
        }

        $customer = $this->findExistingCustomer($order);
        if ($customer !== null) {
            if (!$linkExisting) {
                return ['result' => self::RESULT_SKIPPED, 'customer_id' => (int)$customer->getId()];
            }
            $this->linkOrder($order, $customer);

            return ['result' => self::RESULT_LINKED, 'customer_id' => (int)$customer->getId()];
        }

        $customer = $this->orderCustomerExtractor->extract((int)$order->getEntityId());
        // Set explicitly so CLI runs (admin store) create the account on the order's website.
        $customer->setStoreId((int)$order->getStoreId());
        $customer->setWebsiteId($this->getWebsiteId($order));

        // No password: Magento sends the "registered_no_password" welcome email with a set-password link.
        $customer = $this->accountManagement->createAccount($customer);
        $this->linkOrder($order, $customer);

        return ['result' => self::RESULT_CREATED, 'customer_id' => (int)$customer->getId()];
    }

    private function linkOrder(OrderInterface $order, CustomerInterface $customer): void
    {
        $order->setCustomerId((int)$customer->getId());
        $order->setCustomerIsGuest(0);
        $order->setCustomerGroupId((int)$customer->getGroupId());
        $this->orderRepository->save($order);
    }

    private function getWebsiteId(OrderInterface $order): int
    {
        return (int)$this->storeManager->getStore((int)$order->getStoreId())->getWebsiteId();
    }
}
