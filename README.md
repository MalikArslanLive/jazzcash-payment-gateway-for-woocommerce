# CardRaabta – Payment Gateway for JazzCash — 1.1.3

Accept debit and credit card payments directly through JazzCash in your WooCommerce store. Includes separate Sandbox and Live settings, 72-hour default expiry, signed payment callbacks, Checkout Blocks and HPOS support.

Developed by [Malik Arslan](https://malikarslan.com). Not an official or endorsed JazzCash plugin. Before upgrading the earlier 1.0.0–1.1.2 build, deactivate that plugin; the gateway ID and encrypted settings remain compatible. Do not run both builds together.

## Download

[Download the installable plugin ZIP](downloads/cardraabta-payment-gateway-for-jazzcash-1.1.3.zip?raw=true) · [Security review](docs/AUDIT.md) · [Merchant acceptance checklist](docs/RELEASE.md)

Use the installable ZIP for WordPress. GitHub’s source-code ZIP includes development files. Real JazzCash merchant acceptance remains required before live use.

Source and issue tracking: https://github.com/MalikArslanLive/jazzcash-payment-gateway-for-woocommerce

## Install

1. In WordPress, open Plugins → Add New → Upload Plugin. Upload this ZIP and activate.
2. Open WooCommerce → Settings → Payments → JazzCash Credit / Debit Card.
3. Select Sandbox. Enter the sandbox Merchant ID, Password and Integrity Salt. Paste the **HTTPS page-redirection payment URL supplied in your JazzCash merchant onboarding profile**. Do not paste a JSON API URL. Live credentials and URL are stored separately.
4. Copy the exact Return URL from settings into your JazzCash merchant profile. Enable response hashing with JazzCash.
5. For asynchronous notifications, ask JazzCash to activate signed SOAP IPN, register the IPN URL and its WSDL shown in settings, and confirm its acknowledgement format. This plugin exposes SOAP 1.1 `DoUpdatePaymentStatus` in `http://tempuri.org/`. An arbitrary JSON webhook is not a substitute for this SOAP service.
6. Leave transaction expiry at **72 hours**, or set a whole number from 1–2160. Both timestamps use Asia/Karachi independently of your WordPress timezone.
7. Enable the gateway, save settings, and complete the acceptance checks below. After approval, enter Live credentials and URL, switch to Live and perform a small real payment.

Requirements: WordPress 6.5+, WooCommerce 8.3+, PHP 8.1+, OpenSSL, iconv, DOM/XML, MySQL/MariaDB GET_LOCK support, HTTPS and PKR store currency. The callback routes must be public over HTTPS and excluded from caching, CAPTCHA, maintenance-mode and login requirements. HTTPS detection must work correctly behind any reverse proxy.

## Included

- Credit/debit card option on classic checkout and Checkout Blocks; card entry takes place on JazzCash.
- Separate sandbox/live credentials and merchant-issued URLs; endpoints restricted to the matching JazzCash domain.
- Password-style, blank-on-render credential controls. Blank retains an existing value; `__CLEAR__` removes it. Values stored in settings are AES-256-GCM encrypted using WordPress salts.
- HMAC-SHA256 request/response checking, constant-time signature comparison, unique transaction references and exact decimal-to-paisa conversion.
- Return URL and SOAP IPN validate a persisted attempt before updating its order. Return responses require matching merchant, amount, currency, type and creation timestamp. The documented SOAP input has no amount/currency; its signature, password and transaction timestamp bind it to the saved original request, whose amount must still match the order.
- Duplicate callback protection, database serialization of updates, and paid/refunded/cancelled order protection. Paid orders cannot be downgraded by later error notifications.
- WooCommerce order CRUD for HPOS. Minimal logs under WooCommerce → Status → Logs → `cardraabta-payment-gateway-for-jazzcash`.

## Payment and stock behavior

An order stays unpaid until a valid success response (`000`) arrives. Successful sandbox payments update test orders too; use a staging store. Successful payments use WooCommerce `payment_complete()`, which selects Processing or Completed as appropriate and lets WooCommerce manage stock.

For a non-success response the plugin conservatively puts an unpaid order On hold with the provider response code. It does not assume every error means the customer was not charged. Check JazzCash before retrying, cancelling or refunding. No provider message or card data is copied into order notes.

The payment-page expiry is **not** a 72-hour WooCommerce inventory reservation. WooCommerce’s Hold stock (minutes) setting can cancel an unpaid order sooner. If you need a matching hold, configure it to 4320 minutes after considering stock impact. A verified payment for a cancelled/refunded or already-paid order is recorded for manual reconciliation; it will not automatically reopen or fulfil the order. Late authenticated notifications are deliberately not rejected solely because they arrive after the payment expiry.

Reloading an unexpired payment attempt reuses its reference. After its expiry, the plugin blocks another automatic payment attempt to reduce double-charge risk. Expired attempts require manual reconciliation before a new order/payment is created. Changing the mode, amount or merchant for an existing attempt requires review; use a new order after reconciling the old one. Do not rotate credentials while payments are in flight; this version validates with the credentials currently saved for the attempt's original mode.

## Credential security and hosted redirect

JazzCash’s documented card redirect form contains `pp_Password`. It is sent in a hidden HTML field and **can be viewed by the customer in the browser source**. A hidden field is not secret storage. The Integrity Salt never leaves the server. Use JazzCash payment credentials only, and confirm this page-redirection profile is approved for your merchant. This plugin does not claim the password is server-only. Server-only credential transmission would require a different JazzCash-approved session/API integration.

Do not enable request-body capture in hosting/APM/security tools on these routes: JazzCash responses can include payment/customer fields. This plugin never logs full payloads, passwords, salts, hashes, card details, customer contact details or callback URLs. Encrypting settings protects database copies; it does not protect against a compromised WordPress server. Keep salts backed up; changing them makes saved encrypted credentials unreadable until re-entered.

Optional wp-config.php constants override stored settings:

```php
define('AGJC_SANDBOX_MERCHANT_ID', 'your-sandbox-id');
define('AGJC_SANDBOX_PASSWORD', 'your-sandbox-payment-password');
define('AGJC_SANDBOX_SALT', 'your-sandbox-integrity-salt');
// Equivalent live names: AGJC_LIVE_MERCHANT_ID, AGJC_LIVE_PASSWORD, AGJC_LIVE_SALT.
```

## Acceptance testing before Live

Local verification includes standalone protocol/security tests, a disposable running WordPress/WooCommerce installation using both legacy order storage and HPOS, and HTTP return/SOAP callback tests with synthetic signed messages. Official Plugin Check passed static and runtime checks. These results do **not** verify an actual JazzCash merchant profile, bank authorization or 3DS. See `docs/AUDIT.md` in the source package for evidence and remaining release gates.

- Complete one sandbox success on both classic and block checkout; confirm one stock reduction and correct order status.
- Compare a generated request hash with JazzCash’s merchant hash calculator; confirm empty-field and character encoding handling for your profile.
- Test decline, cancellation, expiry, abandoned redirect and a return with a missing/altered signature. A forged callback must never mark an order paid.
- Send a real signed SOAP IPN from JazzCash with the browser closed. Confirm its fixed-width 3+200+64 character acknowledgement and SOAP action are accepted. Resend it and confirm no duplicate fulfilment/email.
- Test return plus IPN concurrently, a changed amount/currency, and a delayed success after cancellation.
- Check HTTPS/caching, WooCommerce stock-hold timing and safe logs. Confirm no real customer orders are created in sandbox.
- Validate the live endpoint and approved card/3DS configuration with JazzCash; make and reconcile a small live payment.

The supplied WSDL defines the merchant service contract; it must be registered with JazzCash. The public documentation does not establish the provisioning and response-hash byte conventions of every merchant profile. If your onboarding sample differs, obtain that sample and adapt the SOAP adapter before enabling IPN in production. Unsigned notifications are rejected, even if your profile makes hashing optional.

This version does not implement automatic refunds, recurring payments, saved cards, wallet/OTC payments or automatic status inquiry. Reconcile unresolved orders and issue refunds in the JazzCash portal. Deactivation retains settings. Plugin deletion removes settings and credentials for the current site while retaining order accounting records. On multisite, remove saved credentials on every site before network deletion. Remove wp-config.php constants separately.

## Developer checks

The GitHub/source package includes `tests/`, `tools/`, CI and audit documentation. The installable plugin ZIP excludes development files. Run `php tests/run.php` from the source checkout. The CLI test uses fake credentials and makes no charges. Build the installable ZIP with `python3 tools/build.py`.

Reference documentation consulted 13 October 2026:

- https://sandbox.jazzcash.com.pk/SandboxDocumentation/v4.2/index.html
- https://sandbox.jazzcash.com.pk/SandboxDocumentation/v4.2/features.html
- https://sandbox.jazzcash.com.pk/SandboxDocumentation/Content/documentation/Payment%20Gateway%20Integration%20Guide%20for%20Merchants-v4.2.pdf (payment response and status-update sections)
- https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/checkout-payment-methods/payment-method-integration
