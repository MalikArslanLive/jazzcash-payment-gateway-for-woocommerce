# Contributing

Use PHP 8.1+ with DOM, iconv and OpenSSL. Run `php tests/run.php` and `python3 tools/build.py`.

Install the ZIP in a disposable HTTPS WordPress/WooCommerce store. Run `wp plugin check cardraabta-payment-gateway-for-jazzcash-and-woocommerce` and the runtime invocation documented in docs/RELEASE-CHECKLIST.md. Run `wp eval-file tests/integration.php` only in a disposable local environment; it creates orders/products and changes store settings. Never run it against production.

Use synthetic credentials and data. Preserve amount precision, callback idempotency and signature verification. Add regression tests for changes to payment state or protocol behavior. Do not loosen verification to accommodate an undocumented provider response. Obtain the merchant-approved protocol sample first.

All contributed runtime code is GPL-2.0-or-later. CI/tooling is not shipped to WordPress users. Each release must increment the main header, AGJC_VERSION, readme stable tag and changelog together. Package from the allowlist builder; do not upload a GitHub source ZIP as the WordPress release.
