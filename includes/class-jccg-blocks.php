<?php
defined( 'ABSPATH' ) || exit;
final class AGJC_Blocks extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
	protected $name = 'jccg';
	public function __construct( string $gateway_id = 'jccg' ) {
		$this->name = $gateway_id === 'jccg_wallet' ? 'jccg_wallet' : 'jccg';
	}
	public function initialize() {
		$this->settings = get_option( 'woocommerce_jccg_settings', array() ); }
	public function is_active() {
		$gateway = WC()->payment_gateways()->payment_gateways()[ $this->name ] ?? null;
		return $gateway && $gateway->is_available();
	}
	public function get_payment_method_script_handles() {
		wp_register_script( 'jccg-blocks', plugins_url( 'assets/blocks.js', AGJC_FILE ), array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ), AGJC_VERSION, true );
		return array( 'jccg-blocks' );
	}
	public function get_payment_method_data() {
		$gateway = WC()->payment_gateways()->payment_gateways()[ $this->name ] ?? null;
		return array(
			'title'       => $gateway ? $gateway->get_title() : __('JazzCash Credit / Debit Card', 'cardraabta-jazzcash-payment-gateway-for-woocommerce'),
			'description' => $gateway ? $gateway->get_description() : '',
			'enabled' => (bool) $this->is_active(),
			'supports'    => array( 'products' ),
		);
	}
}
