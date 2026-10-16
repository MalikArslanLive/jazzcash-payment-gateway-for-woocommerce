# WordPress.org review update — 1.2.1

Display name: CardRaabta Payment Gateway for JazzCash and WooCommerce
Requested slug/text domain: cardraabta-payment-gateway-for-jazzcash-and-woocommerce
Current assigned submission slug: cardraabta-jazzcash-payment-gateway-for-woocommerce
Account/contributor: malikarslan
Author: Malik Arslan
Author URI: https://malikarslan.com

## Review status and required owner actions

Version 1.2.0 was submitted on 14 October 2026 and passed the submission scan. A 16 October review pended it for naming and external-service disclosure. This 1.2.1 package addresses both findings. It is not approved. Upload this update to the existing submission; do not create another submission. Reply to the review email thread requesting the exact new slug above. The plugin team must allocate it; updating code alone does not change the reserved slug. A text-domain warning against the old assigned slug may appear until allocation.

## Naming

The distinctive CardRaabta project identifier remains first. Payment Gateway explains the purpose. Both third-party brands follow 'for', consistent with the reviewer's suggested display-name structure. The main plugin header, readme heading, translations, build, folder and text domain use the new identity. Public name searches returned no exact independently owned CardRaabta match; this is not legal trademark clearance or an availability guarantee. No third-party logos, badges or copied banners are bundled. Author/contributor identities remain Malik Arslan / malikarslan, not a provider identity. GitHub remains a public development repository under MalikArslanLive, with an explicit independent-integration disclaimer.

## External services

A dedicated top-level External services readme section describes JazzCash, the merchant opt-in, what data is transmitted, when/why, Sandbox and Live hosts, hosted credential visibility, callbacks and signed SOAP acknowledgement, storage/logs, official service/terms/merchant terms/privacy links and absence of other services. README repeats the disclosure. The official terms page and linked merchant terms PDF and September 2026 privacy PDF were opened and their contents checked on 16 October. The merchant PDF includes Online Payment Gateway Service Commercial Terms. The privacy notice covers JazzCash/MMBL branchless banking and other services.

The URLs cited by the reviewer in registerNamespace, SOAP envelopes and DoUpdatePaymentStatusResponse are XML namespace identifiers. They are not fetched. Do not change their http spelling: that would change the protocol vocabulary. XML parsing uses LIBXML_NONET and rejects DTD/entities. The bundled WSDL is local. This context should be stated briefly in the reply.

## Payment compatibility

Card/wallet gateway IDs, shared encrypted credential option, separate wallet controls, transaction metadata, signing, trusted-host checks and jccg_return/jccg_ipn routes are retained. Existing 1.2.0 activation is rejected until that build is deactivated; do not uninstall it, since deletion removes credentials. Pending orders and existing settings remain usable after the rename. The owner reported merchant testing successful on 14 October; no additional real merchant transaction is performed by this revision's automated tests.
