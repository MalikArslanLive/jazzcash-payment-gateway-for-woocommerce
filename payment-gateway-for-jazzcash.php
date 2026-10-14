<?php
/**
 * Plugin Name: JazzCash Payment Gateway for WooCommerce
 * Description: Accept debit and credit card payments through JazzCash in WooCommerce, with Sandbox/Live modes, signed callbacks and configurable expiry.
 * Version: 1.1.1
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * WC requires at least: 8.3
 * WC tested up to: 11.2.1
 * Requires Plugins: woocommerce
 * Author: Malik Arslan
 * Author URI: https://malikarslan.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: payment-gateway-for-jazzcash
 */
defined( 'ABSPATH' ) || exit;
register_activation_hook(
	__FILE__,
	static function () {
		if ( is_plugin_active( 'jazzcash-card-gateway/jazzcash-card-gateway.php' ) || is_plugin_active( 'arslan-card-gateway-jazzcash/arslan-card-gateway-jazzcash.php' ) ) {
			wp_die( esc_html__( 'Deactivate the previous JazzCash Card Gateway plugin before activating this release. Settings and order records are retained.', 'payment-gateway-for-jazzcash' ) );
		}
	}
);
// A previous private build may be active while WordPress validates this upgrade.
if ( defined( 'AGJC_FILE' ) ) {
	return;
}
define( 'AGJC_FILE', __FILE__ );
define( 'AGJC_VERSION', '1.1.1' );
add_action(
	'template_redirect',
	static function () {
		if ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
			nocache_headers();
			header( 'Referrer-Policy: no-referrer' );
			header( 'X-Content-Type-Options: nosniff' );
		}
	}
);
add_action(
	'admin_init',
	static function () {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content(
				'JazzCash Payment Gateway for WooCommerce',
				'<p>' . esc_html__( 'When you choose JazzCash, the order reference, amount, currency, payment timestamps and return address are sent to JazzCash to process your payment. Card information is entered on JazzCash. Payment references and status are retained with your order. This plugin does not add analytics or advertising trackers. Contact the store regarding order retention and privacy requests.', 'payment-gateway-for-jazzcash' ) . '</p>'
			);
		}
	}
);
require_once __DIR__ . '/includes/class-jccg-protocol.php';
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( 'Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return; }
		require_once __DIR__ . '/includes/class-jccg-gateway.php';
		add_filter(
			'woocommerce_payment_gateways',
			static function ( $gateways ) {
				$gateways[] = 'AGJC_Gateway';
				return $gateways;
			}
		);
		// Instantiate on callbacks too, even when this gateway has since been disabled.
		add_action(
			'woocommerce_api_jccg_return',
			static function () {
				( new AGJC_Gateway() )->callback( false );
			}
		);
		add_action(
			'woocommerce_api_jccg_ipn',
			static function () {
				( new AGJC_Gateway() )->callback( true );
			}
		);
	}
);
add_action(
	'woocommerce_blocks_loaded',
	static function () {
		if ( class_exists( 'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
			require_once __DIR__ . '/includes/class-jccg-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				static function ( $registry ) {
					$registry->register( new AGJC_Blocks() );
				}
			);
		}
	}
);
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=jccg' ) ) . '">' . esc_html__('Settings', 'payment-gateway-for-jazzcash') . '</a>' );
		return $links;
	}
);
