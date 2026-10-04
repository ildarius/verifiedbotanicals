<?php

declare(strict_types=1);

namespace Local\GuestToCustomer\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Remembers, in the shopper's checkout session, which account this checkout just created.
 *
 * Setting a password straight from the success page is only safe for an account that the
 * same browser created moments ago. It must never be offered for an account that already
 * existed: anyone can place an order with someone else's email, and would then be able to
 * take that account over. Hence: same session, same order, account still has no password,
 * short time window.
 */
class NewAccountSession
{
    private const SESSION_KEY = 'local_guest_to_customer_new_account';
    private const WINDOW_SECONDS = 3600;

    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly CustomerRegistry $customerRegistry
    ) {
    }

    public function remember(OrderInterface $order, int $customerId): void
    {
        $this->checkoutSession->setData(self::SESSION_KEY, [
            'order_id' => (int)$order->getEntityId(),
            'customer_id' => $customerId,
            'created_at' => time(),
        ]);
    }

    /**
     * Customer ID the shopper may set a password for on the success page, or null.
     */
    public function getEligibleCustomerId(OrderInterface $order): ?int
    {
        $entry = $this->checkoutSession->getData(self::SESSION_KEY);
        if (!is_array($entry)
            || (int)($entry['order_id'] ?? 0) !== (int)$order->getEntityId()
            || (int)($entry['customer_id'] ?? 0) !== (int)$order->getCustomerId()
            || time() - (int)($entry['created_at'] ?? 0) > self::WINDOW_SECONDS
        ) {
            return null;
        }

        $customerId = (int)$entry['customer_id'];
        try {
            $passwordHash = (string)$this->customerRegistry->retrieveSecureData($customerId)->getPasswordHash();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            return null;
        }

        return $passwordHash === '' ? $customerId : null;
    }

    public function forget(): void
    {
        $this->checkoutSession->unsetData(self::SESSION_KEY);
    }
}
