# Checkout E-Transfer Flow

**Project:** Verified Botanicals  
**Platform:** Magento Open Source 2.4.7  
**Last updated:** 2026-06-03

**Status:** Implemented in `app/code/Local/InteracETransfer/`. This note keeps the feature rationale and operating model in the permanent notes area.

## Feature Goal

Allow customers to place an order using Interac e-Transfer instructions shown during checkout/success flow, while keeping the order unpaid until the transfer is received and verified.

## Confirmed Decisions

- full transfer instructions appear on the checkout success page only
- the payment step shows only a short warning/summary
- unpaid orders remain open for `72 hours`
- transfer instructions use fixed payee/payment details
- the order confirmation email includes the full instruction set

## Implemented Magento Approach

Do not treat this as a generic `pending` order.

Magento separates **order state** from **order status**:

- **State** is the system workflow bucket.
- **Status** is the business-facing label shown to admins/customers.

For this use case, the clean Magento-native approach is:

- use the Magento order **state** `pending_payment`
- add a custom **status** such as `Awaiting E-Transfer`
- map that custom status to `pending_payment`

Why this is better than plain `pending`:

- `pending_payment` clearly means the order exists but payment has not been received yet
- it is more accurate for operations, reporting, and customer service
- it leaves room for a later automatic cancel rule for unpaid orders
- it avoids overloading Magento’s more ambiguous default `pending` labeling

Recommended lifecycle:

- order placed: `pending_payment` / `Awaiting E-Transfer`
- transfer received and confirmed: invoice created offline, order moves to `processing`
- shipment created: order eventually moves through normal fulfillment flow
- transfer not received by deadline: order canceled

## Current Customer Experience

### At checkout

Customer selects a payment method such as `Interac e-Transfer`.

Checkout should make two things clear before the order is placed:

- the order can be submitted immediately
- the order will not be processed until the transfer is received

### After order placement

On the success page, show a large payment notice/instructions block styled similarly to the reference image, but with original wording.

The same instructions should also be included in:

- the order confirmation email
- the customer account order view
- optionally the order detail in admin for quick support reference

## Copy History

This section captures the original implementation copy direction. Live copy may be revised separately.

### Heading

`Your order has been placed. Payment is still required.`

### Intro

`We have received your order and placed it on hold pending your Interac e-Transfer. We will begin processing once payment has been received and matched to your order.`

### Instruction block

`Please send your Interac e-Transfer using the payment details we provide at checkout. Include your order number in the transfer message so we can match the payment quickly.`

`For privacy and processing reasons, use only the approved payee name shown in the instructions. Do not add extra product-related wording in the recipient name or message beyond your order reference.`

`Once payment is confirmed, you will receive an email update and your order will move into processing. Orders that remain unpaid beyond the payment window may be canceled automatically.`

### Short version for checkout review area

`Orders paid by Interac e-Transfer are submitted immediately but remain pending until payment is received and verified. Please complete the transfer promptly after placing the order.`

## Operational Flow

### 1. Customer places order

- customer chooses `Interac e-Transfer`
- Magento allows normal order placement
- order is created in `pending_payment`
- visible order status label is `Awaiting E-Transfer`

### 2. Success page shows payment instructions

- success page displays:
  - order number
  - order date
  - total
  - payment method
  - e-transfer instruction notice
- copy should explain that fulfillment starts only after payment is received

### 3. Confirmation email repeats instructions

- order confirmation email includes the same payment instructions
- email should tell the customer exactly how to reference the order number

### 4. Admin reviews incoming transfers

- staff confirms the transfer in banking
- staff matches the payment to the Magento order number
- staff creates an invoice offline in Magento
- order status moves to `processing`

### 5. Unpaid orders are handled by rule

- after a defined window, unpaid orders are canceled
- stock is released back normally through Magento cancellation flow

Suggested initial payment window:

- `72 hours`

## Implementation Shape

This likely fits best as a small custom module rather than a theme-only change.

### Module responsibilities

- add or configure an offline payment method for `Interac e-Transfer`
- define custom order status `Awaiting E-Transfer`
- assign that status to `pending_payment`
- show the custom payment notice in:
  - checkout review area
  - success page
  - order email
  - customer order view if desired
- optionally add a cron job to cancel unpaid orders after the deadline

### UI placement

The notice can appear in two places:

- **Before order placement:** short warning under the selected payment method
- **After order placement:** full instruction block on the success page

Confirmed implementation split:

- payment step: short summary only
- success page: full instruction block
- order email: full instruction block

That split is preferable to showing the entire large instruction block before order placement.

## Suggested Design Behavior

The reference image is aggressive and instruction-heavy. For this storefront, the better pattern is:

- keep the message visually prominent
- avoid alarmist competitor-style wording
- state that the order is received but not yet paid
- emphasize the order number reference requirement
- make the next step obvious: send the transfer, then wait for confirmation

## Confirmed Business Rules

1. Full transfer instructions appear on the checkout success page only.
2. The payment method step shows a short warning only.
3. Unpaid orders are canceled after `72 hours`.
4. Payment instructions use fixed recipient details.
5. The order confirmation email repeats the full instruction set.

## Implementation Summary

My recommendation is:

- use a custom offline payment method named `Interac e-Transfer`
- place orders into Magento **state** `pending_payment`
- expose a custom **status** label `Awaiting E-Transfer`
- show a short notice before order placement and a full notice on the success page
- repeat the instructions in the confirmation email
- auto-cancel unpaid orders after `72 hours`

This is the most Magento-native approach and gives you a cleaner operational workflow than using a vague generic `pending` status.
