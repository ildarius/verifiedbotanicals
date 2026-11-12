<?php

declare(strict_types=1);

namespace Local\CouponLimitByEmail\Plugin;

use Local\CouponLimitByEmail\Model\EmailRuleUsage;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Utility;

/**
 * Drops a rule from the cart once the customer's email has used it "Uses per Customer" times.
 * Runs on every totals collection; before the guest has typed an email there is nothing to check,
 * so the place-order observer is the backstop.
 */
class LimitUsesPerEmail
{
    public function __construct(
        private readonly EmailRuleUsage $usage
    ) {
    }

    public function afterCanProcessRule(Utility $subject, bool $result, Rule $rule, Address $address): bool
    {
        if (!$result || !(int)$rule->getUsesPerCustomer() || !$address->getQuote()) {
            return $result;
        }
        if ($this->usage->isLimitReached($address->getQuote(), (int)$rule->getId(), (int)$rule->getUsesPerCustomer())) {
            $rule->setIsValidForAddress($address, false);
            return false;
        }

        return $result;
    }
}
