<?php
/**
 * Remove credentials on explicit plugin deletion; retain accounting order records.
 *
 * @package ArslanCardGatewayJazzCash
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
// Per-site settings only. A network administrator must remove settings on each site.
delete_option( 'woocommerce_jccg_settings' );
delete_option( 'woocommerce_jccg_wallet_settings' );
delete_transient( 'agjc_rejection_logged' );
