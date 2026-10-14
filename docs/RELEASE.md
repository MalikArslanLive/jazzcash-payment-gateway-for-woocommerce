# Release and merchant acceptance

Release 1.1.2 is prepared for merchant acceptance and WordPress.org submission review. It is not provider-certified. Do not accept live payments until the checklist below is complete.

## Merchant setup

Follow README.md. Use staging with Sandbox; enter the merchant-issued redirect URL, Merchant ID, dedicated payment password and Integrity Salt. Never send credentials to GitHub or public support. Default expiry is 72 hours; stock reservation is configured separately in WooCommerce.

Record evidence from real JazzCash for classic and block checkouts: success, decline, abandoned browser, cancellation, expiry, delayed success, duplicate notification and return/IPN overlap. Confirm card/3DS authorization, matching settled amount, single fulfilment and stock reduction. Have JazzCash approve the supplied SOAP WSDL and fixed-width signed acknowledgement for the specific merchant profile. Confirm unsigned notifications are not enabled. Switch to Live only after these pass, then reconcile a small real transaction in the provider portal.

## GitHub

Source repository: `MalikArslanLive/jazzcash-payment-gateway-for-woocommerce`.

The source kit includes all readable code, GPL license, tests, build script, contribution/security guidance and pinned GitHub Actions. The repository contains the source at its root. CI needs no payment secrets. Enable private vulnerability reporting, branch protection and secret scanning where available. Require checks before merging; tag the reviewed commit `v1.1.2` and attach the installable ZIP plus checksums to a release after acceptance. GitHub Actions runs are separate from the recorded local checks; inspect the repository Actions page for the status of each commit.

## WordPress.org

Use the maintainer's real WordPress.org account and monitored contact email. Confirm the proposed name/slug is available. The user supplied WordPress.org username malikarslan; it is now listed in Contributors. Confirm the signed-in account matches before upload. Submit the runtime ZIP, not the source kit. Respond to reviewers and complete requested changes. After approval, keep the full runtime source and matching version in the assigned SVN repository and increment versions for later changes. No automatic directory approval is claimed.

## Reproduce checks

With PHP 8.1+ and required extensions:

```sh
php tests/run.php
php tests/security.php
php tests/bootstrap.php
node --check assets/blocks.js
python3 tools/build.py
```

On a **disposable local** WordPress install with WooCommerce and the release plugin activated, set `WP_ENVIRONMENT_TYPE` to `local` before running `wp eval-file tests/integration.php`. This test deliberately changes gateway settings, currency, inventory and order-storage mode and creates synthetic products/orders. Never run it on a production store. Use Plugin Check against the extracted runtime ZIP, with its CLI bootstrap for runtime checks. The development tree deliberately includes non-runtime tests that must not be submitted in the runtime ZIP.

The HTTP test evidence covers real local HTTP requests to WordPress with HTTPS indicated by a trusted-proxy test router. It does not establish public TLS, hosting/firewall behavior or successful provider delivery. Verify those on staging.
