# Release and security review — 1.1.1

Security review performed 13 October 2026 on 1.1.0. Branding and author-metadata revision prepared 14 October 2026 as 1.1.1. Scope: the supplied plugin source and generated runtime ZIP. Result: no exploitable injection, arbitrary execution, file-upload or unauthorized account-access path was identified in this review. This is a bounded code review and local test result, **not** a guarantee against compromise, an independent penetration test, antivirus certification or provider approval.

## Version 1.1.1 verification

The 14 October revision changes display naming, description, author URI, plugin folder/text domain, translations and previous-build activation protection. The payment protocol is unchanged. The 48 baseline and 20 security probes were rerun, 77 real WordPress/WooCommerce integration checks passed, and two bootstrap collision checks passed. Earlier HTTP test evidence below is retained from 1.1.0; it is not relabeled as a new provider test.

## Executed evidence

| Check | Observed result |
| --- | --- |
| Protocol/regression tests | 48 passed; includes an independent published JazzCash HMAC example |
| Additional adversarial tests | 20 passed; `tests/security.php` also reruns the 48 baseline checks |
| Running WordPress/WooCommerce integration | 77 passed, both mode configurations and both HPOS/legacy order storage |
| Actual local HTTP callbacks | 22 passed, signed synthetic return and SOAP messages in both modes |
| Official WordPress Plugin Check 2.1.0 | Static and runtime checks: no errors found on extracted release ZIP |
| Runtime source tripwire | All 6 runtime PHP/JavaScript files passed the listed dangerous-sink scan |
| Syntax | PHP lint and JavaScript syntax passed |

Local environment: PHP 8.3.6, WordPress 7.1.3, WooCommerce 11.2.1 and MariaDB 10.11.14. The CI matrix for PHP 8.1–8.4 is configured, not executed on GitHub. Minimum supported versions are requirements, not a claim that every combination was tested. Test evidence is in `docs/evidence/`.

HTTP tests exercised WordPress routing, HTTP status, redirects, SOAP XML, persisted paid state and duplicate callbacks. They used local transport and a test router indicating HTTPS, rather than a public TLS connection. Database lock contention was verified with two real database connections. Full simultaneous provider return/IPN delivery was not tested.

## Final focused security review

The reviewed executable files are the main bootstrap, gateway, protocol and Blocks PHP classes, uninstall handler and local Blocks JavaScript. The WSDL is a fixed local XML template. The runtime bundle contains no test harness, development credential fixture, WordPress installation, database, private key, dependency archive or executable binary.

| Threat | Control and verification |
| --- | --- |
| Virus injection, backdoor or arbitrary commands | No runtime upload/write facility, dynamic PHP evaluation, shell execution, user creation, password reset or remote code updater. All include paths are fixed local paths. Manual source inspection plus the narrow source scanner found no listed dangerous sink. No antivirus signature engine was run. |
| SQL injection | Incoming reference is strictly matched before deriving an integer order ID. The only direct database operations are prepared named-lock queries; order access uses WooCommerce CRUD. SQL-like reference probes were rejected. |
| Cross-site scripting | Attributes, URLs and displayed text are escaped. Blocks uses React text children. Provider response text is neither rendered nor placed in notes. A real receipt with executable-looking credential text emitted escaped attributes; signed script-like retrieval references were rejected. |
| Settings takeover / CSRF | Settings require `manage_woocommerce` plus the WooCommerce settings nonce. Anonymous and ordinary customer writes and an invalid nonce were rejected. Ordinary customer admin rendering returned no content. |
| Unauthorized order access | A payable receipt requires the matching WooCommerce order bearer key. Missing, wrong and traversal-like keys could not create an attempt or expose its form. Keep order-key links private. |
| Forged payment | Mandatory constant-time HMAC verification plus persisted transaction, date and original mode. Return additionally binds amount, currency, merchant and card type. SOAP checks its documented fields, password and stored original amount. Missing, modified and forged signatures failed. |
| Replay / double fulfilment | A connection-scoped order mutex serializes mutation; paid, refunded and cancelled orders are protected. Duplicate success did not reduce real product stock twice. Expired attempts cannot automatically generate new charge attempts. |
| XML entity / parser abuse | Size caps, external-network-disabled XML parsing and explicit DTD/entity rejection. Duplicate/nested fields and unexpected SOAP operations rejected. Form array coercion and duplicate keys rejected; JSON values must be strings before order handling. |
| Secret leakage | Settings use authenticated AES-GCM encryption and blank-on-render secret fields. Tampered ciphertext failed authentication. Integrity Salt absent from receipt and Blocks data. Logs use fixed events and order IDs. Public failures are generic. |
| Redirect / remote request abuse | Payment form destinations require the exact mode-specific JazzCash HTTPS host; lookalikes, credentials in URL and unexpected ports rejected. Callback redirects use WordPress safe redirects. There is no server-side arbitrary URL fetch. |

Residual boundaries: the JazzCash redirect protocol intentionally places the dedicated `pp_Password` in a browser-visible hidden form; it is **not** a server-only secret. The Integrity Salt must remain private. Encryption cannot protect a compromised WordPress runtime or stolen server salts. A privileged store manager can configure the gateway as part of their role. Standard host traffic controls remain necessary for volumetric denial of service; rejection-log throttling is not a WAF. This plugin review does not audit other plugins, themes, administrators or hosting.

## WordPress.org guideline review

The following entries record the package's disposition against each numbered guideline, not directory approval or legal advice. The publisher must maintain ongoing obligations after release.

| Guideline | Package disposition |
| --- | --- |
| 1 | GPL-2.0-or-later license included; no bundled commercial libraries or copied branding assets. |
| 2 | Maintainer identified; security/reporting guidance included. Ongoing responsibility remains with publisher. |
| 3 | Full stable runtime ZIP prepared; directory upload and SVN maintenance pending. |
| 4 | PHP/JS are readable, unminified source; development/build source supplied separately. |
| 5 | Neither mode is license-locked; no trial expiration or plugin upsell. |
| 6 | JazzCash service, merchant account dependency and service terms disclosed. |
| 7 | Data flow documented; no telemetry, ads or unrelated external assets. |
| 8 | No external executable delivery or custom updater. |
| 9 | Independent branding and factual claims; no compliance or approval guarantee. |
| 10 | No forced public credit or promotional backlink. |
| 11 | Gateway-specific settings and notices; no dashboard takeover. |
| 12 | Descriptive readme, five relevant tags, no keyword stuffing. |
| 13 | Uses WordPress/WooCommerce-provided browser libraries. |
| 14 | Release discipline documented; future commit behavior is a maintainer obligation. |
| 15 | Version/header/stable tag aligned at 1.1.1. |
| 16 | Runtime implementation present; provider acceptance remains an explicit release gate. |
| 17 | Generic-leading slug payment-gateway-for-jazzcash; user-selected display title and non-endorsement statement. Title/slug approval remains subject to reviewer discretion. |
| 18 | Directory review discretion acknowledged. |

Privacy/merchant obligations are disclosed rather than certified. Read the actual merchant agreement for the account; obtain legal/PCI advice if required for the store's operation. No real merchant contract or WordPress.org account ownership was supplied for verification.

## Remaining gates

1. Real JazzCash sandbox card authorization and 3DS, provider-sent SOAP IPN and acknowledgement acceptance for the merchant profile.
2. Real classic and block checkout browser flow, return/IPN overlap, public HTTPS, hosting/cache/firewall and theme compatibility.
3. Approved live merchant endpoint and credentials; one small real transaction reconciled in JazzCash.
4. WordPress.org account/slug and reviewer approval. Plugin Check is not directory approval.
5. Confirm GitHub Actions succeeds for the published commit and configure ongoing repository maintenance.

## Primary references

Consulted 13 October 2026:

- https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/
- https://github.com/WordPress/plugin-check
- https://sandbox.jazzcash.com.pk/SandboxDocumentation/v4.2/index.html
- https://sandbox.jazzcash.com.pk/SandboxDocumentation/v4.2/features.html
- https://sandbox.jazzcash.com.pk/SandboxDocumentation/Content/documentation/Payment%20Gateway%20Integration%20Guide%20for%20Merchants-v4.2.pdf
- https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/checkout-payment-methods/payment-method-integration

JazzCash current service, terms, privacy notice and merchant agreement links are in `readme.txt`. The provider's private onboarding profile must resolve any mismatch with the public protocol documents.
