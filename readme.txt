=== CardRaabta Payment Gateway for JazzCash and WooCommerce ===
Contributors: malikarslan
Tags: woocommerce, payment gateway, credit card, jazzcash, pakistan
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept JazzCash wallet and debit/credit card payments in WooCommerce, with Sandbox/Live modes, signed callbacks and 72-hour expiry.

== Description ==

Accept JazzCash Mobile Wallet and online debit/credit card payments in your WooCommerce store. Customers pay directly through JazzCash hosted checkout in PKR. Customers enter card or wallet details on JazzCash and approve wallet payments on their phone. This is an independent integration by Malik Arslan (https://malikarslan.com), not an official or endorsed JazzCash, WordPress or WooCommerce product.

Features:

* Separate Sandbox and Live merchant credentials and approved HTTPS payment URLs.
* Separate card and mobile-wallet methods on classic checkout and Checkout Blocks, with HPOS order storage support.
* Default 72-hour transaction expiry calculated in Asia/Karachi.
* HMAC-SHA256 request signing and mandatory signature verification for responses.
* Order, reference, timestamp and amount validation; duplicate notification protection.
* SOAP 1.1 IPN endpoint and WSDL for merchant provisioning with JazzCash.
* Encrypted settings, permission-protected administration and minimal debug logging.

You need an approved JazzCash payment-gateway merchant account, dedicated payment credentials and the page-redirection endpoint provided by JazzCash. Sandbox and Live functionality are included without a plugin fee or activation license. JazzCash may charge service fees under your merchant agreement.

Requires WooCommerce 8.3+, PKR currency, HTTPS, PHP OpenSSL/iconv/DOM and MySQL/MariaDB connection-scoped named locks. Gateway responses must be signed. Use on a staging store and complete your merchant acceptance testing before taking real payments.

== External services ==

= JazzCash payment processing =

This plugin relies on JazzCash, an external payment service, to process debit/credit card and Mobile Wallet payments. A store owner must obtain an approved merchant account, configure dedicated credentials and enable the chosen payment method. Both Sandbox and Live integrations are included; JazzCash service charges and merchant terms apply separately.

When and why data is sent: only when a customer chooses a JazzCash method, opens the order payment page and presses "Continue to JazzCash", their browser POSTs a signed payment request to the merchant-configured JazzCash hosted payment URL. The purpose is to authenticate the merchant, identify the order and process the selected payment. Activation, ordinary browsing and saving settings do not send payment data to JazzCash.

Where data is sent: Sandbox requests go to https://sandbox.jazzcash.com.pk/ and Live requests go to https://payments.jazzcash.com.pk/. The exact page-redirection path is supplied by JazzCash for the merchant. Other hosts, insecure HTTP endpoints and unexpected ports are rejected.

Data sent: merchant ID and dedicated payment password; transaction type/version/language; unique transaction reference; order ID as bill reference, description and merchant-defined field; amount in paisa and PKR currency; creation and expiry timestamps; return URL; and SecureHash signature. Wallet requests also include the bank/product identifiers TBANK/RETL; card requests include the registration flag. The Integrity Salt is never sent. The plugin does not send billing name, email, telephone or postal address, and does not collect card numbers, wallet numbers, CNIC, OTP or MPIN. JazzCash receives browser/IP information and collects the payment details directly on its own hosted page.

Return and IPN: JazzCash sends payment references, response codes, timestamps and authentication fields to this store's public Return URL or SOAP IPN URL. Signed callbacks are verified before updating the related WooCommerce order. The store replies to a valid SOAP notification with a signed acknowledgement containing a response code and padded response message. Invalid notifications receive a generic error. Payment-attempt metadata, status and transaction references are retained with the WooCommerce order. Optional logs contain fixed event codes and order IDs, using WooCommerce log retention. Full payloads and credentials are not logged.

Service and policies (official JazzCash links):
* Payment gateway service: https://www.jazzcash.com.pk/business/accept-payments/payment-gateway
* Terms and conditions: https://www.jazzcash.com.pk/tc/
* Online payment gateway merchant terms (PDF; includes Online Payment Gateway Service Commercial Terms): https://www.jazzcash.com.pk/assets/uploads/2026/04/Overall-Banking-Service-Agreement-BNPL-QR-PGW-MP-standard-format-with-all-service-order-forms.pdf
* Customer privacy notice (PDF): https://www.jazzcash.com.pk/assets/documents/JazzCash-Privacy-Notice-sep-2026.pdf

Your signed merchant agreement may contain additional obligations. Update your store privacy policy and retention practices accordingly. Suggested customer-facing text is added to WordPress's privacy-policy guide. The plugin does not certify PCI, privacy-law or other legal compliance.

= No other external services =

This plugin adds no telemetry, advertising, tracking pixels, externally hosted JavaScript or custom updater. WordPress and WooCommerce are local dependencies. GitHub and documentation links are references for users/developers; the plugin does not fetch code or data from them.

The strings http://schemas.xmlsoap.org/soap/envelope/, http://schemas.xmlsoap.org/wsdl/, http://schemas.xmlsoap.org/wsdl/soap/, http://www.w3.org/2001/XMLSchema and http://tempuri.org/ in PHP/WSDL are XML namespace or SOAP-action identifiers, not external service requests. They must retain their protocol-defined spelling, including http. XML parsing disables network access with LIBXML_NONET and rejects DTD/entity declarations. The WSDL is a bundled local template, with this store's HTTPS IPN address substituted when served.

= Hosted-form credential behavior =

JazzCash's documented page-redirection protocol includes pp_Password in the browser's hidden form. Customers can inspect that value. Use only dedicated payment credentials approved for this integration, never your account-login password. The Integrity Salt remains server-side. See README.md for credential rotation and limitations.

== Installation ==

1. Back up your site and deactivate the earlier 1.0.0–1.2.0 build if installed. Do not run both builds together.
2. Upload and activate this plugin with WooCommerce active.
3. Open WooCommerce > Settings > Payments > JazzCash Credit / Debit Card.
4. Choose Sandbox, enter sandbox credentials and the merchant-issued HTTPS page-redirection URL, and retain the default 72-hour expiry if suitable.
5. Register the displayed Return URL with JazzCash and enable signed responses.
6. To use SOAP IPN, provide the IPN URL and WSDL to JazzCash and have the service contract and signed acknowledgement approved for your merchant profile.
7. Run the acceptance checklist in README.md. Enter Live credentials and URL only after merchant approval, then switch mode and reconcile a small live payment.

== Frequently Asked Questions ==

= How do I enable JazzCash wallet payments? =

After JazzCash approves MWALLET for your merchant account, enable JazzCash Mobile Wallet under WooCommerce > Settings > Payments. Shared credentials, mode, expiry and wallet URLs are configured under the card gateway settings. Configure separate wallet redirect URLs if issued by JazzCash; a blank wallet URL uses the existing mode URL. Both checkout methods share that mode's encrypted merchant credentials, expiry and debug controls. Wallet can be enabled while card payments are disabled. Phone/CNIC and MPIN are handled by JazzCash, not collected or stored by this plugin. Register the same signed return and SOAP IPN routes for the approved profile.


= Does it support Sandbox and Live? =

Yes. Each mode uses separate credentials and endpoint settings. A successful sandbox response marks a test order paid, so use a staging store.

= Is this approved by JazzCash or WordPress.org? =

No approval or certification is implied. Directory admission is determined by WordPress's reviewers. Payment availability depends on your merchant account and completed provider testing.

= Why does a non-success payment show On hold? =

A non-success response is not treated as proof that no funds moved. Reconcile it in JazzCash before cancelling or retrying. A verified later success can complete the held order. Cancelled, refunded and already-paid orders are protected from automatic reopening or duplicate fulfilment.

= Does 72-hour expiry hold WooCommerce stock for 72 hours? =

No. WooCommerce's stock-hold setting is separate. Review that setting; 4320 minutes equals 72 hours. An expired attempt is not automatically replaced with a new charge attempt. Contact the store and reconcile the old payment first.

= Are refunds or saved cards supported? =

Not in this version. Process refunds through JazzCash and record them in WooCommerce. Recurring payments, OTC vouchers and tokenization are not included.

= What happens on deletion? =

Plugin deletion removes credentials/settings for the current site but retains order accounting records. On multisite, clear settings on each site before network deletion. Deactivation alone retains settings. Constants in wp-config.php must be removed separately.

== Changelog ==

= 1.2.1 =
* Use a distinctive third-party integration name, matching folder and text domain.
* Expand external-service disclosure and verified official terms/privacy links.
* Clarify local SOAP/XML namespace identifiers and retain the existing payment/security controls.

= 1.2.0 =
* Add hosted JazzCash Mobile Wallet as a separate opt-in checkout method.
* Bind each saved payment attempt to its card or wallet instrument.
* Apply existing signature, order, replay, expiry and reconciliation checks to wallet callbacks.
* Support wallet payments in Checkout Blocks and HPOS.
* Align the WooCommerce-focused name and slug, and expand wallet tests.

= 1.1.3 =
* Add the distinctive CardRaabta identifier required by the signed-in submission form.
* Align plugin folder, text domain and translations with the proposed directory slug.
* Preserve previous gateway settings and block simultaneous activation of older builds.

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
