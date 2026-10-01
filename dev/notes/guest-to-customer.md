# Guest to Customer

**Project:** Verified Botanicals  
**Platform:** Magento Open Source 2.4.9  
**Last updated:** 2026-10-01

**Status:** Implemented in `app/code/Local/GuestToCustomer/` and enabled on live.

## Why

Guest checkout stays on (customers are never forced to register), but Magento does not create a
customer record for guest orders. They only showed up under Sales > Orders, never under
Customers > All Customers, unless the shopper clicked "Create an Account" on the success page.

This module turns every guest order into a customer order automatically.

## What Happens When A Guest Places An Order

Runs on `checkout_submit_all_after`, right after the order is saved. This event fires for storefront
checkout, the REST/GraphQL guest-cart API, PayPal/Braintree placement, admin-created orders and
multishipping.

1. Order already has a customer (logged-in shopper) → nothing happens.
2. Guest email has **no account** on the website → a new customer is created:
   - name, email from the order billing address
   - **both** the billing and shipping addresses go into the address book (set as default billing / default shipping)
   - customer group = the store default (`General`)
   - no password; Magento sends its standard **"Welcome — set your password"** email
     (template `customer/create_account/email_no_password_template`)
   - **not** subscribed to the newsletter (no CASL consent was given)
3. Guest email **already has an account** → the order is attached to that account (no duplicate account, no welcome email).
   Controlled by *Attach to Existing Account*.
4. The order is updated: `customer_id` set, `customer_is_guest = 0`, `customer_group_id` = the account's group. The order grid updates too.

Side effects worth knowing:

- The success-page "Create an Account" box disappears for these orders (Magento hides it once the email is registered).
- The guest "Orders and Returns" lookup keeps working for converted orders.
- The order confirmation email is unchanged.
- Any failure (validation, email, DB) is logged and swallowed: the order is already placed and the shopper still
  reaches the success page. The order then simply stays a guest order; convert it later with the CLI below.

## Why Not Magento's Built-In Conversion

Core has `Magento\Sales\Api\OrderCustomerManagementInterface::create()` (what the success-page button calls).
It is not used directly because:

- it drops addresses whose quote `save_in_address_book` flag is `0`, which is the case for most guest
  shipping addresses, so customers would lose their shipping address;
- it throws when the email is already registered instead of attaching the order.

The module reuses the core pieces instead: `OrderCustomerExtractor` (builds the customer + addresses from the
order) and `AccountManagementInterface::createAccount()` (validation, welcome email).

## Admin Settings

Stores > Configuration > Customers > Customer Configuration > **Guest to Customer**

| Setting | Path | Default |
|---|---|---|
| Convert Guest Orders to Customer Accounts | `customer/guest_to_customer/enabled` | Yes |
| Attach to Existing Account | `customer/guest_to_customer/link_existing` | Yes |

Both are website-scoped. Turning the first one off stops the automatic conversion; it does not undo past conversions.

## Converting Older Guest Orders (CLI)

Orders placed before the module was installed (or ones that failed to convert) can be converted with:

```bash
M local:guest-to-customer:convert --dry-run
```

`M` is the `bin/magento` wrapper defined in `~/.bashrc` (cPanel `ea-php83` + `~/php-cli.d`, 2G memory).
The plain `php` on the PATH runs with a 128M limit and a sodium build that is missing constants, so
`php bin/magento ...` can fail silently or fatally for some commands.

Options:

- `--dry-run` — list what would happen, change nothing. **Always run first.**
- `--order=111000000007` — only this increment ID (repeatable).
- `--skip-canceled` — leave canceled orders as guest orders.
- `--no-link` — do not attach orders to accounts that already exist.

The CLI ignores the admin *enabled* flag: running it is the opt-in. Real runs send the welcome email to every
newly created account, so check the dry-run list for test or throwaway emails first.

State on 2026-10-01: five guest orders from before the install (`111000000001`, `…002`, `…004`, `…005`,
`…007`), all test orders by the owner or the Playwright harness. They were left unconverted on purpose. The
dry run showed four would create new accounts and `111000000004` would attach to existing customer 23.

## Files

```
app/code/Local/GuestToCustomer/
├── registration.php
├── etc/module.xml                     sequence: Magento_Customer, Magento_Quote, Magento_Sales
├── etc/config.xml                     defaults (enabled, link_existing)
├── etc/adminhtml/system.xml           admin settings
├── etc/events.xml                     checkout_submit_all_after observer (global area)
├── etc/di.xml                         registers the CLI command
├── Model/Config.php
├── Service/GuestOrderConverter.php    all conversion logic (shared by observer + CLI)
├── Observer/ConvertGuestOrderObserver.php
└── Console/Command/ConvertGuestOrdersCommand.php
```

## Logs

`var/log/system.log`:

```
main.INFO: GuestToCustomer: order #111000000008 created customer 34
main.INFO: GuestToCustomer: order #111000000009 linked to customer 34
```

Failures are logged as `main.ERROR: GuestToCustomer: could not convert order #…` with the exception
(`var/log/system.log` / `var/log/exception.log`).

```bash
grep GuestToCustomer var/log/system.log | tail
```

## Deploy Notes

The store runs in `default` mode **with compiled DI** (`generated/metadata` exists). Any new class or
`di.xml`/`events.xml` change needs `setup:di:compile`; otherwise the compiled object manager builds the new
observer without its constructor arguments and checkout fatals.

Live install sequence used on 2026-10-01 (no downtime):

```bash
# move compiled DI aside so the site runs on runtime DI while the module is enabled
mv generated/metadata var/generated-metadata.pre-guest-to-customer-<ts>
M module:enable Local_GuestToCustomer
M setup:upgrade --keep-generated
M setup:di:compile
M cache:flush
```

To disable: set *Convert Guest Orders to Customer Accounts* = No in admin (no deploy needed), or
`M module:disable Local_GuestToCustomer` followed by `setup:di:compile` and `cache:flush`.

## Verification (2026-10-01, live)

Guest orders were placed through the REST guest-cart API with Interac e-Transfer and a non-deliverable
`guest2customer+…@example.com` email:

- Order `111000000008` (different billing/shipping addresses) → customer 34 created, website 1 / store 111,
  group General, no password, both addresses saved as default billing/shipping, not subscribed,
  `customer_is_guest = 0`, order grid shows the customer, order email still sent.
- Order `111000000009` (same email) → attached to customer 34, no duplicate account.
- Both test orders were then canceled (stock restored) and customer 34 was deleted.

Re-test with the same REST flow (store code `fresh1_en`, SKU e.g. `RB25`, carrier `matrixrate` /
`matrixrate_8`, payment `interac_etransfer`) or `npm run pw:checkout-etransfer`. Use an `@example.com` address
so the welcome email does not reach a real inbox.
