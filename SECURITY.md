# Security

Version 1.1.x is the current maintained source line. No independent penetration-test or provider certification is claimed.

Do not post real credentials, card details, signed callbacks, customer data or exploitable vulnerability details in public issues. Use the repository's **Security → Report a vulnerability** flow once the owner enables GitHub private vulnerability reporting. If that control is unavailable, contact the repository owner privately to arrange a secure reporting channel before sending details.

Include the version, affected component, impact and a reproduction using synthetic data. The maintainer must verify reports, coordinate a fix and notify affected users. No response-time guarantee is offered.

Before public release: enable private vulnerability reporting and secret scanning where available; restrict maintainers; require CI on pull requests; verify provider sandbox and a small live transaction. Never commit wp-config.php, .env files or production payment payloads.

## Trust boundaries

- Settings require manage_woocommerce and WooCommerce's settings nonce.
- Callbacks cannot use a WordPress nonce because JazzCash sends them. They require a valid Integrity Salt HMAC, a known persisted attempt and matching transaction details before order mutation.
- Receipt access requires the WooCommerce order key and a payable order using this gateway.
- GET_LOCK/RELEASE_LOCK deliberately use prepared, uncached SQL. Caching a mutex result would break concurrency protection.
- SOAP output contains a fixed local XML template and XML-escaped dynamic content. HTML escaping must not replace XML escaping.
- Hidden hosted-form pp_Password is visible to the payer under the documented JazzCash redirect protocol. The salt is server-only. Confirm the merchant's profile authorizes this design.
- Logs contain fixed event codes and order IDs, not payloads. Configure WooCommerce log retention and exclude callbacks from any external request-body recording.

## Mobile Wallet boundaries (1.2.0)

The new jccg_wallet method uses hosted redirection and the existing signed return/SOAP routes. Wallet numbers, CNIC digits and MPINs are not collected or persisted. The merchant payment password is still a browser-visible hidden field under the provider protocol; do not confuse it with a payer MPIN. Wallet and card callbacks must match the saved instrument, preventing cross-instrument success reuse. Legacy attempts without an instrument field are treated as card attempts only. Wallet enable/title/description use a distinct settings option; credentials, mode, expiry and approved URLs reuse the encrypted card configuration. Uninstall removes both settings options. No security check is bypassed for wallet responses.
