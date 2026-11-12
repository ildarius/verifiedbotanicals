<?php

declare(strict_types=1);

namespace Local\CouponLimitByEmail\Observer;

use Local\CouponLimitByEmail\Model\EmailRuleUsage;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Api\RuleRepositoryInterface;

/**
 * Last check before the order is saved (sales_model_service_quote_submit_before): if the order still
 * carries a rule its email has already used up, refuse the order with a message the customer can act on.
 */
class BlockReusedRuleOnSubmit implements ObserverInterface
{
    public function __construct(
        private readonly EmailRuleUsage $usage,
        private readonly RuleRepositoryInterface $ruleRepository
    ) {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getData('order');
        $quote = $observer->getEvent()->getData('quote');
        if (!$order instanceof Order || !$quote instanceof Quote) {
            return;
        }

        foreach ($this->getItemRuleIds($order) as $ruleId) {
            try {
                $limit = (int)$this->ruleRepository->getById($ruleId)->getUsesPerCustomer();
            } catch (NoSuchEntityException) {
                continue;
            }
            if ($this->usage->isLimitReached($quote, $ruleId, $limit, [(string)$order->getCustomerEmail()])) {
                throw new LocalizedException(__(
                    'This discount code can be used only once per customer, and it has already been used '
                    . 'with this email address. Please remove the discount code to place your order.'
                ));
            }
        }
    }

    /**
     * Rule IDs that actually discounted an order line. The order-level applied_rule_ids is copied from the
     * quote, where Magento can leave a rule behind after it stops applying, so it is not used here.
     *
     * @return int[]
     */
    private function getItemRuleIds(Order $order): array
    {
        $ids = [];
        foreach ($order->getAllItems() as $item) {
            foreach (explode(',', (string)$item->getAppliedRuleIds()) as $id) {
                if ((int)$id > 0) {
                    $ids[(int)$id] = (int)$id;
                }
            }
        }

        return array_values($ids);
    }
}
