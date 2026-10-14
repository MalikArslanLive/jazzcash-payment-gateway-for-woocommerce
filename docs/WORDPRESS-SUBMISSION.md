# WordPress.org submission preparation

Plugin: Payment Gateway for JazzCash
Version: 1.1.2
Expected slug/text domain: payment-gateway-for-jazzcash
Account supplied by user: malikarslan
Author: Malik Arslan
Author URI: https://malikarslan.com

## Overview for reviewers

An independent WooCommerce integration for JazzCash hosted debit and credit card payments in PKR. It includes separate Sandbox/Live merchant settings, signed return and SOAP IPN validation, a configurable 72-hour default expiry, encrypted settings, duplicate-payment protection, Checkout Blocks and HPOS support. Customers enter card data on JazzCash. The readme documents the service, data flow, terms, privacy notice, browser-visible hosted-form payment password and unsupported features.

## Public requirements reviewed 14 October 2026

- https://wordpress.org/plugins/developers/add/
- https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/
- https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/

Runtime ZIP: installable WordPress structure, below 10 MB, GPL-compatible readable source and no development tools or nested archives. Display name starts with a generic term and matches the expected slug. Readme includes service disclosure, installation, limitations and the contributor username supplied by the user. Terms or ownership declarations in the final upload form must be reviewed with the account owner before acceptance.

## Status

Not submitted. Secure browser login was not completed; signed-in upload requirements and account eligibility remain uninspected. Do not claim real JazzCash merchant testing or directory approval. Confirm public TLS, real sandbox authorization and provider-sent IPN acceptance before production use.
