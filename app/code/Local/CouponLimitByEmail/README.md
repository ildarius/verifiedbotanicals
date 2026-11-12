# Local_CouponLimitByEmail

Makes a cart price rule's **Uses per Customer** limit count by email address, so guests are limited too.
Magento on its own counts uses only by customer ID, so a guest (or a customer whose earlier order was a guest
order) could use a "once per customer" code again and again.

## Quickstart

Nothing to configure. The module applies to every cart price rule whose **Uses per Customer** is above 0
(today only rule 1, "Affiliate coupons - 20% off", set to 1).

- **Change the limit:** Marketing > Cart Price Rules > the rule > Uses per Customer. 0 turns the limit off
  (for logged-in customers and guests alike).
- **Let someone use the code again:** cancel their earlier order (canceled orders don't count), or raise
  Uses per Customer.
- **See who has used rule 1:**

  ```sql
  SELECT increment_id, state, customer_email FROM sales_order
  WHERE FIND_IN_SET(1, applied_rule_ids) AND state <> 'canceled';
  ```

## What the customer sees

1. Code entered on the cart page before checkout: it applies (no email yet).
2. Email typed on the shipping step: if that email already has an order with the rule, the discount
   drops off the totals.
3. Code entered after the email is known: "The coupon code isn't valid. Verify the code and try again."
4. Place order while the discount is still on the cart (backstop): the order is refused with "This discount
   code can be used only once per customer, and it has already been used with this email address. Please
   remove the discount code to place your order."

## How it works

- `Model/EmailRuleUsage.php`: counts orders for an email (case-insensitive) with the rule in
  `applied_rule_ids`, ignoring canceled orders. Checks every email on the cart: the cart's own email and the
  shipping and billing address emails (a guest's email lands on the addresses first).
- `Plugin/LimitUsesPerEmail.php`: `afterCanProcessRule` on `Magento\SalesRule\Model\Utility`; rejects the rule
  during totals collection once the limit is reached.
- `Observer/BlockReusedRuleOnSubmit.php`: `sales_model_service_quote_submit_before`; refuses the order if an
  order line still carries a used-up rule. It reads the rule IDs from the order lines, not the order's own
  `applied_rule_ids`, because Magento can leave a rule there after it stops applying.

## Limits

- Different email, different customer: `name+1@gmail.com` or a second address gets the discount again.
- Admin-created orders go through the same checks.
