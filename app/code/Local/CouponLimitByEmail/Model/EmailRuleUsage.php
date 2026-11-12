<?php

declare(strict_types=1);

namespace Local\CouponLimitByEmail\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

/**
 * Counts the orders an email address has already placed with a cart price rule.
 *
 * Magento enforces "Uses per Customer" only by customer ID, so guests (and customers whose earlier
 * order was a guest order) are never limited. This counts by email instead: every order that is not
 * canceled and has the rule in applied_rule_ids. customer_email uses a case-insensitive collation.
 */
class EmailRuleUsage
{
    /** @var array<string,int> */
    private array $cache = [];

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    public function count(string $email, int $ruleId): int
    {
        $email = trim($email);
        if ($email === '' || $ruleId <= 0) {
            return 0;
        }
        $key = $ruleId . '|' . strtolower($email);
        if (!isset($this->cache[$key])) {
            $connection = $this->resource->getConnection('sales');
            $select = $connection->select()
                ->from($this->resource->getTableName('sales_order', 'sales'), ['COUNT(*)'])
                ->where('customer_email = ?', $email)
                ->where('FIND_IN_SET(?, REPLACE(applied_rule_ids, " ", "")) > 0', (string)$ruleId)
                ->where('state <> ?', Order::STATE_CANCELED);
            $this->cache[$key] = (int)$connection->fetchOne($select);
        }

        return $this->cache[$key];
    }

    /**
     * True when any email on the quote has reached the rule's limit.
     */
    public function isLimitReached(Quote $quote, int $ruleId, int $usesPerCustomer, array $extraEmails = []): bool
    {
        if ($usesPerCustomer <= 0) {
            return false;
        }
        foreach ($this->getQuoteEmails($quote, $extraEmails) as $email) {
            if ($this->count($email, $ruleId) >= $usesPerCustomer) {
                return true;
            }
        }

        return false;
    }

    /**
     * Guests type their email on the shipping step (it lands on the shipping address); the quote's own
     * customer_email is only filled from the billing address when the order is placed.
     *
     * @return string[]
     */
    private function getQuoteEmails(Quote $quote, array $extraEmails): array
    {
        $emails = $extraEmails;
        $emails[] = $quote->getCustomerEmail();
        $emails[] = $quote->getShippingAddress()->getEmail();
        $emails[] = $quote->getBillingAddress()->getEmail();

        $unique = [];
        foreach ($emails as $email) {
            $email = trim((string)$email);
            if ($email !== '') {
                $unique[strtolower($email)] = $email;
            }
        }

        return array_values($unique);
    }
}
