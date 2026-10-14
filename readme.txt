=== Payment Gateway for JazzCash ===
Contributors: malikarslan
Tags: woocommerce, payment gateway, credit card, jazzcash, pakistan
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept debit and credit card payments through JazzCash in WooCommerce. Includes Sandbox/Live modes, signed callbacks and 72-hour expiry.

== Description ==

Accept online debit and credit card payments in your WooCommerce store with the JazzCash Payment Gateway. Customers pay directly through JazzCash hosted checkout in PKR. Customers enter their card details on JazzCash. This is an independent integration by Malik Arslan (https://malikarslan.com), not an official or endorsed JazzCash, WordPress or WooCommerce product.

Features:

* Separate Sandbox and Live merchant credentials and approved HTTPS payment URLs.
* Classic checkout and Checkout Blocks integration, with HPOS order storage support.
* Default 72-hour transaction expiry calculated in Asia/Karachi.
* HMAC-SHA256 request signing and mandatory signature verification for responses.
* Order, reference, timestamp and amount validation; duplicate notification protection.
* SOAP 1.1 IPN endpoint and WSDL for merchant provisioning with JazzCash.
* Encrypted settings, permission-protected administration and minimal debug logging.

You need an approved JazzCash payment-gateway merchant account, dedicated payment credentials and the page-redirection endpoint provided by JazzCash. Sandbox and Live functionality are included without a plugin fee or activation license. JazzCash may charge service fees under your merchant agreement.

Requires WooCommerce 8.3+, PKR currency, HTTPS, PHP OpenSSL/iconv/DOM and MySQL/MariaDB connection-scoped named locks. Gateway responses must be signed. Use on a staging store and complete your merchant acceptance testing before taking real payments.

= External service and privacy =

The plugin uses JazzCash to process the payment selected by the customer. When the customer presses "Continue to JazzCash", their browser submits order/payment references, amount, PKR currency, creation/expiry timestamps, return URL, merchant ID, payment password and request signature to the configured JazzCash endpoint. The Integrity Salt is never submitted. This version does not submit billing names, email addresses, telephone numbers or addresses. JazzCash receives the customer's browser/IP information during that visit and collects card details on its own page.

Service domains are sandbox.jazzcash.com.pk and payments.jazzcash.com.pk. The plugin receives authenticated status callbacks to update WooCommerce orders. It stores payment-attempt metadata, status and transaction references with the order. Debug logs contain fixed event codes and order IDs only, and use WooCommerce's log-retention settings. It has no telemetry, advertising, tracking pixels, third-party JavaScript or external updater.

JazzCash service: https://www.jazzcash.com.pk/accept-payments/payment-gateway
JazzCash terms: https://www.jazzcash.com.pk/tc
JazzCash privacy notice: https://www.jazzcash.com.pk/assets/documents/JazzCash-Privacy-Notice-sep-2026.pdf
Merchant service agreement: https://www.jazzcash.com.pk/assets/uploads/2026/04/SERVICE-ORDER-FORM-COMMERCIAL-TERMS-FOR-ONLINE-PAYMENT-COLLECTION-SERVICES-MW-and-CARDS.pdf

Your signed merchant agreement may contain additional obligations. The plugin does not certify PCI, privacy-law or other legal compliance. Update your store privacy policy and retention practices accordingly; suggested text is added to WordPress's privacy-policy guide.

= Hosted-form credential behavior =

JazzCash's documented page-redirection protocol includes pp_Password in the browser's hidden form. Customers can inspect that value. Use only dedicated payment credentials approved for this integration, never your account-login password. The Integrity Salt remains server-side. See README.md for credential rotation and limitations.

== Installation ==

1. Back up your site and deactivate the earlier 1.0.0 or 1.1.0 build if installed. Do not run both builds together.
2. Upload and activate this plugin with WooCommerce active.
3. Open WooCommerce > Settings > Payments > JazzCash Credit / Debit Card.
4. Choose Sandbox, enter sandbox credentials and the merchant-issued HTTPS page-redirection URL, and retain the default 72-hour expiry if suitable.
5. Register the displayed Return URL with JazzCash and enable signed responses.
6. To use SOAP IPN, provide the IPN URL and WSDL to JazzCash and have the service contract and signed acknowledgement approved for your merchant profile.
7. Run the acceptance checklist in README.md. Enter Live credentials and URL only after merchant approval, then switch mode and reconcile a small live payment.

== Frequently Asked Questions ==

= Does it support Sandbox and Live? =

Yes. Each mode uses separate credentials and endpoint settings. A successful sandbox response marks a test order paid, so use a staging store.

= Is this approved by JazzCash or WordPress.org? =

No approval or certification is implied. Directory admission is determined by WordPress's reviewers. Payment availability depends on your merchant account and completed provider testing.

= Why does a non-success payment show On hold? =

A non-success response is not treated as proof that no funds moved. Reconcile it in JazzCash before cancelling or retrying. A verified later success can complete the held order. Cancelled, refunded and already-paid orders are protected from automatic reopening or duplicate fulfilment.

= Does 72-hour expiry hold WooCommerce stock for 72 hours? =

No. WooCommerce's stock-hold setting is separate. Review that setting; 4320 minutes equals 72 hours. An expired attempt is not automatically replaced with a new charge attempt. Contact the store and reconcile the old payment first.

= Are refunds or saved cards supported? =

Not in this version. Process refunds through JazzCash and record them in WooCommerce. Recurring payments, wallet/OTC and tokenization are not included.

= What happens on deletion? =

Plugin deletion removes credentials/settings for the current site but retains order accounting records. On multisite, clear settings on each site before network deletion. Deactivation alone retains settings. Constants in wp-config.php must be removed separately.

== Changelog ==

= 1.1.2 =
* Align display name with the generic-leading WordPress.org slug.
* Add confirmed WordPress.org contributor malikarslan.
* Refresh submission documentation.

= 1.1.1 =
* Rename the plugin and improve payment-focused title and description.
* Add Malik Arslan author website and upgrade collision protection.

= 1.1.0 =
* Public distribution naming, service/privacy documentation and GPL license.
* Hardened settings authorization, callback parsing and receipt caching headers.
* Reject duplicate fields, unsupported content types and unsafe endpoint origins.
* Protect against automatic retry of unresolved expired attempts.
* Add translation support, uninstall cleanup, expanded tests and release automation.

= 1.0.0 =
* Initial private build.
