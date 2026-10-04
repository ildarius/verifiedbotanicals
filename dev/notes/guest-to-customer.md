# Guest to Customer

**Project:** Verified Botanicals  
**Platform:** Magento Open Source 2.4.9  
**Last updated:** 2026-10-04

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

- The core success-page "Create an Account" box is replaced by this module's account notice (next section).
- The guest "Orders and Returns" lookup keeps working for converted orders.
- The order confirmation email is unchanged.
- Any failure (validation, email, DB) is logged and swallowed: the order is already placed and the shopper still
  reaches the success page. The order then simply stays a guest order; convert it later with the CLI below.

## Success Page: "Set Your Password"

### The problem it fixes (found 2026-10-04)

Magento's success page still showed its "Create an Account" box (`checkout.registration`) after the module had
already created the account. Clicking it led to "There is already an account with this email address".
Magento only hides that box when the email is taken **if** *Enable Guest Checkout Login*
(`checkout/options/enable_guest_checkout_login`) is on. That setting is off by default since 2.4.7 (it stops
email enumeration), and when it's off `AccountManagement::isEmailAvailable()` always returns `true`.

### What the shopper sees now

The core box is moved into the module's block (`local.guest_to_customer.success`), which sits in
`order.success.additional.info`, directly under "Your order # is …" and above the Interac e-Transfer
instructions. The block shows one of these:

| Situation | Shown |
|---|---|
| Account was **just created by this checkout, in this browser session** (and still has no password, within 1 hour) | "Your order is saved to your account": read-only email, Password + Confirm Password, **Set your password** button. On success: "You're all set", the shopper is signed in, "View your order" button. |
| Email **already had an account** (order attached), or the 1-hour window passed, or a password is already set | "Your order is saved to your account": **Sign in** button + "Haven't set a password yet? Use Forgot Your Password?" |
| Order is still a guest order (module disabled or conversion failed) | Magento's original "Create an Account" box |
| Shopper was signed in when ordering | nothing |

The form posts with AJAX to `guesttocustomer/account/setPassword` (form key checked by Magento's CSRF
validation). The Interac payment instructions stay on screen. `etc/frontend/sections.xml` makes the header
pick up the signed-in state.

### Why the inline password is safe

Anyone can type someone else's email at checkout, so setting a password from the success page could be used
to take over an account. It is only offered when **all** of these hold (`Model/NewAccountSession.php`):

- the observer created the account during this checkout and recorded it in **this browser's checkout session**
  (`local_guest_to_customer_new_account`: order id, customer id, time). Admin-created orders are never recorded.
- the success page's last order is that same order, and it still belongs to that customer;
- the account still has **no password**;
- less than **1 hour** has passed.

The controller re-checks all of this server-side. Existing accounts never get the form; that was tested
by planting the session marker for an account that has a password, and both the page and a direct POST
refused. After success the marker is cleared, so the form works once.

The password is set through Magento's own reset path: issue a token (`changeResetPasswordLinkToken`) and
redeem it right away (`resetPassword`). That enforces the store's password rules (8+ characters, 3 character
classes, not equal to the email) and **invalidates the link in the welcome email**.

Trade-off: the inline form appears only when the email was new, so someone could learn that an email had no
account by placing an order with it. That's accepted: it takes a real order, and guest-checkout login (off)
would leak far more.

Note on the welcome email: its "set your password" link uses Magento's reset-link lifetime,
`customer/password/reset_link_expiration_period`, which is **2 hours** by default. After that the customer
has to use "Forgot Your Password?". This is the main reason the inline form exists.

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
├── etc/module.xml                     sequence: Magento_Checkout, Magento_Customer, Magento_Quote, Magento_Sales
├── etc/config.xml                     defaults (enabled, link_existing)
├── etc/adminhtml/system.xml           admin settings
├── etc/events.xml                     checkout_submit_all_after observer (global area)
├── etc/di.xml                         CLI command; lazy checkout-session proxy for NewAccountSession
├── etc/frontend/routes.xml            guesttocustomer/*
├── etc/frontend/sections.xml          refresh customer-data after set-password (shopper gets signed in)
├── Model/Config.php
├── Model/NewAccountSession.php        "account created by this checkout" marker + eligibility rules
├── Service/GuestOrderConverter.php    all conversion logic (shared by observer + CLI)
├── Observer/ConvertGuestOrderObserver.php
├── Block/Success/Account.php          success-page notice: picks the mode
├── Controller/Account/SetPassword.php AJAX: set password + sign in
├── Console/Command/ConvertGuestOrdersCommand.php
└── view/frontend/
    ├── layout/checkout_onepage_success.xml   block in order.success.additional.info; moves checkout.registration into it
    ├── templates/success/account.phtml
    ├── web/js/set-password.js                validation (Magento rules) + AJAX submit
    └── web/css/success.css                   plain CSS, not LESS: no theme recompile needed
```

Test helpers live in `dev/tools/guest-to-customer/` (see Verification).

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

**Static files.** This server does not generate missing static files on demand (requests for
`pub/static/...` that aren't on disk return 404 instead of going through `static.php`), and the last full
`setup:static-content:deploy` predates the success-page files. They were published by copying them in, then
the static version was bumped (no trailing newline!) so ea-nginx doesn't serve its cached 404:

```bash
D=pub/static/frontend/Sm/market/en_US/Local_GuestToCustomer
mkdir -p $D/js $D/css
cp app/code/Local/GuestToCustomer/view/frontend/web/js/set-password.js $D/js/
cp app/code/Local/GuestToCustomer/view/frontend/web/css/success.css $D/css/
printf '%s' "$(date +%s)" > pub/static/deployed_version.txt
M cache:flush
```

Repeat that after editing either file. A future full static deploy includes them automatically.

**Constructor changes.** If a constructor changes in a class that compiled DI already knows (e.g. the
observer), the live site uses the stale compiled argument list until `setup:di:compile` finishes. Move
`generated/metadata` aside **before** putting the changed file in place, then upgrade, compile, and flush as
above.

To disable: set *Convert Guest Orders to Customer Accounts* = No in admin (no deploy needed), or
`M module:disable Local_GuestToCustomer` followed by `setup:di:compile` and `cache:flush`.

## Verification

### 2026-10-01: conversion (live)

Guest orders were placed through the REST guest-cart API with Interac e-Transfer and a non-deliverable
`guest2customer+…@example.com` email:

- Order `111000000008` (different billing/shipping addresses) → customer 34 created, website 1 / store 111,
  group General, no password, both addresses saved as default billing/shipping, not subscribed,
  `customer_is_guest = 0`, order grid shows the customer, order email still sent.
- Order `111000000009` (same email) → attached to customer 34, no duplicate account.
- Both test orders were then canceled (stock restored) and customer 34 was deleted.

### 2026-10-04: success page (live, headless Chrome)

Placing orders through the REST API is now blocked by invisible reCAPTCHA (`recaptcha_frontend/type_for/place_order`),
even without a session. The test therefore uses `dev/tools/guest-to-customer/`:

- `place-test-order.php <email>`: places a guest e-Transfer order through the same checkout service classes the
  REST endpoints call (runs the observer, skips the reCAPTCHA webapi check).
- `seed-checkout-session.php <PHPSESSID> <order id> <0|1>`: writes the last-order IDs (and optionally the
  new-account marker) into the browser's session, which is what checkout does.
- `success-page-check.js <email> [password]`: does both, opens the success page, sets the password,
  checks My Orders, and saves screenshots to `var/tmp/guest-to-customer-*.png`. `MARK=0` / `ATTACK=1` variants
  are described in the file.

```bash
node dev/tools/guest-to-customer/success-page-check.js "guest2customer+$(date +%s)@example.com" 'Some-Pass-2026!'
```

Results:
- New account: the form showed under the order number. Mismatched and too-short passwords were rejected
  in the browser. "Set your password" returned `{"success":true}`, the shopper was signed in, My Orders listed
  the order, and the account's reset token was cleared.
- Same email again (account exists, has a password), even with the marker planted: the "Sign in" version
  showed, and a direct POST to the endpoint returned "This form has expired…" without changing the account.
- Test orders `111000000015`–`111000000019` were canceled and the test customers deleted.

Always use an `@example.com` address so the welcome email doesn't reach a real inbox. Cancel the test orders
and delete the test customers afterwards.
