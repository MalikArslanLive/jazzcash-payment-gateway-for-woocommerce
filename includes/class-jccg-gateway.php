<?php
defined( 'ABSPATH' ) || exit;

class AGJC_Gateway extends WC_Payment_Gateway {
	public function __construct() {
		$this->id                 = 'jccg';
		$this->method_title       = __( 'JazzCash Credit / Debit Card', 'payment-gateway-for-jazzcash' );
		$this->method_description = __('JazzCash hosted card checkout (PKR). Configure credentials and the merchant-approved payment URL. IPN requires JazzCash activation. See README.md.', 'payment-gateway-for-jazzcash');
		$this->has_fields         = false;
		$this->supports           = array( 'products' );
		$this->init_form_fields();
		$this->init_settings();
		$this->title = $this->get_option( 'title', __( 'JazzCash Credit / Debit Card', 'payment-gateway-for-jazzcash' ) );
		if ( $this->mode() === 'sandbox' ) {
			$this->title .= ' ' . __( '(Sandbox test)', 'payment-gateway-for-jazzcash' ); }
		$this->description = $this->get_option( 'description', __( 'Pay securely by credit or debit card on JazzCash.', 'payment-gateway-for-jazzcash' ) );
		$this->enabled     = $this->get_option( 'enabled', 'no' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable', 'payment-gateway-for-jazzcash' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable JazzCash card payments', 'payment-gateway-for-jazzcash' ),
				'default' => 'no',
			),
			'title'        => array(
				'title'   => __( 'Checkout title', 'payment-gateway-for-jazzcash' ),
				'type'    => 'text',
				'default' => __( 'JazzCash Credit / Debit Card', 'payment-gateway-for-jazzcash' ),
			),
			'description'  => array(
				'title'   => __( 'Checkout description', 'payment-gateway-for-jazzcash' ),
				'type'    => 'textarea',
				'default' => __( 'Pay securely by credit or debit card on JazzCash.', 'payment-gateway-for-jazzcash' ),
			),
			'mode'         => array(
				'title'   => __( 'Mode', 'payment-gateway-for-jazzcash' ),
				'type'    => 'select',
				'default' => 'sandbox',
				'options' => array(
					'sandbox' => __( 'Sandbox (test)', 'payment-gateway-for-jazzcash' ),
					'live'    => __( 'Live', 'payment-gateway-for-jazzcash' ),
				),
			),
			'expiry_hours' => array(
				'title'             => __( 'Transaction expiry (hours)', 'payment-gateway-for-jazzcash' ),
				'type'              => 'number',
				'default'           => '72',
				'custom_attributes' => array(
					'min'  => '1',
					'max'  => '2160',
					'step' => '1',
				),
				'description'       => __( 'Default: 72 hours. Calculated in Asia/Karachi. This does not change WooCommerce stock hold settings.', 'payment-gateway-for-jazzcash' ),
			),
			'debug'        => array(
				'title'   => __( 'Debug logging', 'payment-gateway-for-jazzcash' ),
				'type'    => 'checkbox',
				'label'   => __( 'Log safe event codes and order IDs only', 'payment-gateway-for-jazzcash' ),
				'default' => 'no',
			),
		);
		foreach ( array(
			'sandbox' => __( 'Sandbox', 'payment-gateway-for-jazzcash' ),
			'live'    => __( 'Live', 'payment-gateway-for-jazzcash' ),
		) as $mode => $label ) {
			$this->form_fields[ $mode . '_heading' ] = array(
				'title' => $label . ' ' . __('credentials', 'payment-gateway-for-jazzcash'),
				'type'  => 'title',
			);
			foreach ( array(
				'merchant_id' => __( 'Merchant ID', 'payment-gateway-for-jazzcash' ),
				'password'    => __( 'Password', 'payment-gateway-for-jazzcash' ),
				'salt'        => __( 'Integrity Salt', 'payment-gateway-for-jazzcash' ),
			) as $field => $name ) {
				$this->form_fields[ $mode . '_' . $field ] = array(
					'title'       => $label . ' ' . $name,
					'type'        => 'secret',
					'description' => __( 'Leave blank to retain. Enter __CLEAR__ to remove. Encrypted using this WordPress installation’s salts.', 'payment-gateway-for-jazzcash' ),
				);
			}
			$this->form_fields[ $mode . '_url' ] = array(
				'title'       => $label . ' ' . __('payment URL', 'payment-gateway-for-jazzcash'),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Paste the HTTPS page-redirection URL issued by JazzCash. Sandbox accepts sandbox.jazzcash.com.pk; Live accepts payments.jazzcash.com.pk.', 'payment-gateway-for-jazzcash' ),
			);
		}
	}

	public function admin_options() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		parent::admin_options();
		echo '<h3>' . esc_html__('Callback URLs', 'payment-gateway-for-jazzcash') . '</h3><p>' . esc_html__('Return URL:', 'payment-gateway-for-jazzcash') . ' <code>' . esc_html( WC()->api_request_url( 'jccg_return' ) ) . '</code></p>';
		echo '<p>' . esc_html__('SOAP IPN URL:', 'payment-gateway-for-jazzcash') . ' <code>' . esc_html( WC()->api_request_url( 'jccg_ipn' ) ) . '</code></p>';
		echo '<p>' . esc_html__('WSDL:', 'payment-gateway-for-jazzcash') . ' <code>' . esc_html( add_query_arg( 'wsdl', '1', WC()->api_request_url( 'jccg_ipn' ) ) ) . '</code></p>';
		echo '<p>' . esc_html__( 'Enable signed responses and IPN in your JazzCash merchant profile. Register the WSDL with JazzCash and validate the acknowledgement in sandbox before Live mode.', 'payment-gateway-for-jazzcash' ) . '</p>';
		echo '<p>' . esc_html__('Hosted checkout: the JazzCash redirect form includes pp_Password, visible in the browser source. The Integrity Salt stays on your server. Use dedicated payment credentials, never an account-login password.', 'payment-gateway-for-jazzcash') . '</p>';
		if ( ! $this->ready() ) {
			echo '<p>' . esc_html__('Gateway unavailable: check HTTPS, PKR currency, required PHP extensions, credentials and payment URL.', 'payment-gateway-for-jazzcash') . '</p>'; }
	}

	public function generate_secret_html( $key, $data ) {
		$field = $this->get_field_key( $key );
		return '<tr valign="top"><th scope="row"><label for="' . esc_attr( $field ) . '">' . esc_html( $data['title'] ) . '</label></th><td><input class="input-text regular-input" type="password" autocomplete="new-password" value="" id="' . esc_attr( $field ) . '" name="' . esc_attr( $field ) . '" placeholder="' . ( $this->get_option( $key ) ? esc_attr__( 'Saved — leave blank to retain', 'payment-gateway-for-jazzcash' ) : esc_attr__( 'Not configured', 'payment-gateway-for-jazzcash' ) ) . '"><p class="description">' . esc_html( $data['description'] ) . '</p></td></tr>';
	}

	public function process_admin_options() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false; }
		check_admin_referer( 'woocommerce-settings' );
		$result = parent::process_admin_options();
		// Payment credentials must not be loaded on unrelated requests.
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( $this->get_option_key(), false ); }
		return $result;
	}

	private function encryption_key(): string {
		return hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true ); }

	public function validate_secret_field( $key, $value ) {
		if ( $value === null ) {
			$value = ''; }
		if ( ! is_string( $value ) ) {
			throw new Exception( esc_html__( 'Invalid credential.', 'payment-gateway-for-jazzcash' ) ); }
		$value = wp_unslash( $value );
		if ( $value === '' ) {
			return $this->get_option( $key, '' ); }
		if ( $value === '__CLEAR__' ) {
			return ''; }
		if ( ! preg_match( '/^[\x21-\x7E]{1,256}$/D', $value ) ) {
			throw new Exception( esc_html__( 'Credentials must be printable ASCII without spaces.', 'payment-gateway-for-jazzcash' ) ); }
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			throw new Exception( esc_html__( 'OpenSSL is required.', 'payment-gateway-for-jazzcash' ) ); }
		$iv        = random_bytes( 12 );
		$tag       = '';
		$encrypted = openssl_encrypt( $value, 'aes-256-gcm', $this->encryption_key(), OPENSSL_RAW_DATA, $iv, $tag );
		if ( $encrypted === false ) {
			throw new Exception( esc_html__( 'Unable to save credential.', 'payment-gateway-for-jazzcash' ) ); }
		return 'enc1:' . base64_encode( $iv . $tag . $encrypted );
	}

	private function secret( string $mode, string $name ): string {
		$constant = 'AGJC_' . strtoupper( $mode . '_' . $name );
		if ( defined( $constant ) ) {
			return (string) constant( $constant ); }
		$legacy_constant = 'JCCG_' . strtoupper( $mode . '_' . $name );
		if ( defined( $legacy_constant ) ) {
			return (string) constant( $legacy_constant ); }
		$value = $this->get_option( $mode . '_' . $name, '' );
		if ( ! str_starts_with( $value, 'enc1:' ) || ! function_exists( 'openssl_decrypt' ) ) {
			return ''; }
		$raw = base64_decode( substr( $value, 5 ), true );
		if ( $raw === false || strlen( $raw ) < 29 ) {
			return ''; }
		$plain = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', $this->encryption_key(), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ) );
		return $plain === false ? '' : $plain;
	}

	public function validate_expiry_hours_field( $key, $value ) {
		if ( ! preg_match( '/^[0-9]+$/D', (string) $value ) || (int) $value < 1 || (int) $value > 2160 ) {
			throw new Exception( esc_html__( 'Expiry must be between 1 and 2160 whole hours.', 'payment-gateway-for-jazzcash' ) );
		}
		return (string) (int) $value;
	}

	private function mode(): string {
		return $this->get_option( 'mode' ) === 'live' ? 'live' : 'sandbox'; }
	private function endpoint( string $mode ): string {
		$url   = trim( $this->get_option( $mode . '_url', '' ) );
		$parts = wp_parse_url( $url );
		$host  = $mode === 'live' ? 'payments.jazzcash.com.pk' : 'sandbox.jazzcash.com.pk';
		if ( ! $parts || ( $parts['scheme'] ?? '' ) !== 'https' || strtolower( $parts['host'] ?? '' ) !== $host
			|| isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] )
			|| ( isset( $parts['port'] ) && $parts['port'] !== 443 ) ) {
			return ''; }
		return $url;
	}

	public function ready(): bool {
		$mode = $this->mode();
		return is_ssl() && get_woocommerce_currency() === 'PKR' && function_exists( 'iconv' )
			&& function_exists( 'openssl_encrypt' ) && class_exists( 'DOMDocument' )
			&& $this->endpoint( $mode ) !== '' && $this->secret( $mode, 'merchant_id' ) !== ''
			&& $this->secret( $mode, 'password' ) !== '' && $this->secret( $mode, 'salt' ) !== '';
	}
	public function is_available() {
		return parent::is_available() && $this->ready(); }

	private function log_event( string $event, int $order_id = 0 ): void {
		if ( $this->get_option( 'debug' ) === 'yes' ) {
			// Deliberately never log request/response bodies, hashes, URLs or provider messages.
			wc_get_logger()->info(
				$event,
				array(
					'source'   => 'payment-gateway-for-jazzcash',
					'order_id' => $order_id,
				)
			);
		}
	}

	private function lock( int $id ): void {
		global $wpdb;
		$name = 'jccg_' . md5( $wpdb->prefix . ':' . $id );
		if ( (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $name ) ) !== '1' ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Connection-scoped database mutex; caching would break concurrency safety.
			throw new RuntimeException( esc_html__( 'Payment is being updated. Please try again.', 'payment-gateway-for-jazzcash' ) );
		}
	}
	private function unlock( int $id ): void {
		global $wpdb;
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'jccg_' . md5( $wpdb->prefix . ':' . $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Connection-scoped database mutex; caching would break concurrency safety.
	}

	public function process_payment( $order_id ) {
		try {
			if ( ! $this->is_available() ) {
				throw new RuntimeException( esc_html__( 'JazzCash is unavailable. Please contact the store.', 'payment-gateway-for-jazzcash' ) ); }
			$order = wc_get_order( $order_id );
			if ( ! $order || ! $order->needs_payment() || $order->get_currency() !== 'PKR' ) {
				throw new RuntimeException( esc_html__( 'This order cannot be paid using JazzCash.', 'payment-gateway-for-jazzcash' ) );
			}
			// No payment is completed until the signed callback is verified.
			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true ),
			);
		} catch ( Throwable $e ) {
			wc_add_notice( esc_html( $e->getMessage() ), 'error' );
			return array( 'result' => 'failure' );
		}
	}

	public function receipt( $order_id ) {
		$locked = false;
		try {
			$order = wc_get_order( $order_id );
			$key   = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce order-key bearer link; verified below before any mutation.
			if ( ! $order || $key === '' || ! hash_equals( $order->get_order_key(), $key )
				|| $order->get_payment_method() !== $this->id || ! $order->needs_payment()
				|| ! $this->ready() || $this->enabled !== 'yes' ) {
				throw new RuntimeException( esc_html__( 'Payment link is unavailable.', 'payment-gateway-for-jazzcash' ) ); }
			$this->lock( (int) $order_id );
			$locked = true;
			$order  = wc_get_order( $order_id );
			if ( ! $order->needs_payment() || $order->get_currency() !== 'PKR' ) {
				throw new RuntimeException( esc_html__( 'Order is no longer payable.', 'payment-gateway-for-jazzcash' ) ); }
			$attempts = (array) $order->get_meta( '_jccg_attempts' );
			$mode     = $this->mode();
			$amount   = AGJC_Protocol::amount( (string) $order->get_total() );
			$active   = (string) $order->get_meta( '_jccg_active' );
			$a        = $attempts[ $active ] ?? null;
			if ( $a && ( $a['amount'] !== $amount || $a['mode'] !== $mode ) ) {
				throw new RuntimeException( esc_html__( 'An existing payment attempt needs store review before this order can be changed.', 'payment-gateway-for-jazzcash' ) );
			}
			if ( $a && $a['expires'] <= time() ) {
				throw new RuntimeException( esc_html__( 'This payment attempt has expired. Contact the store before making another payment.', 'payment-gateway-for-jazzcash' ) );
			}
			if ( ! $a ) {
				if ( count( $attempts ) >= 20 ) {
					throw new RuntimeException( esc_html__( 'Please contact the store to review this payment.', 'payment-gateway-for-jazzcash' ) ); }
				[$date, $expiry, $expires] = AGJC_Protocol::dates( (int) $this->get_option( 'expiry_hours', '72' ) );
				$active                    = 'T' . $order_id . 'R' . strtoupper( bin2hex( random_bytes( 4 ) ) );
				if ( strlen( $active ) > 20 ) {
					throw new RuntimeException( esc_html__( 'Order reference is too long.', 'payment-gateway-for-jazzcash' ) ); }
				$a                   = array(
					'mode'     => $mode,
					'amount'   => $amount,
					'merchant' => $this->secret( $mode, 'merchant_id' ),
					'date'     => $date,
					'expiry'   => $expiry,
					'expires'  => $expires,
					'status'   => 'pending',
				);
				$attempts[ $active ] = $a;
				$order->update_meta_data( '_jccg_active', $active );
				$order->update_meta_data( '_jccg_attempts', $attempts );
				$order->save();
				$this->log_event( 'payment_attempt_created', (int) $order_id );
			}
			if ( ! hash_equals( $a['merchant'], $this->secret( $mode, 'merchant_id' ) ) ) {
				throw new RuntimeException( esc_html__( 'Merchant credentials changed. Contact the store.', 'payment-gateway-for-jazzcash' ) ); }
			$fields                  = array(
				'pp_Version'              => '2.0',
				'pp_TxnType'              => 'MPAY',
				'pp_Language'             => 'EN',
				'pp_MerchantID'           => $a['merchant'],
				'pp_Password'             => $this->secret( $mode, 'password' ),
				'pp_TxnRefNo'             => $active,
				'pp_Amount'               => $amount,
				'pp_TxnCurrency'          => 'PKR',
				'pp_TxnDateTime'          => $a['date'],
				'pp_TxnExpiryDateTime'    => $a['expiry'],
				'pp_BillReference'        => (string) $order_id,
				'pp_Description'          => 'Order ' . $order_id,
				'pp_ReturnURL'            => WC()->api_request_url( 'jccg_return' ),
				'pp_IsRegisteredCustomer' => 'No',
				'ppmpf_1'                 => (string) $order_id,
			);
			$fields['pp_SecureHash'] = AGJC_Protocol::hash( $fields, $this->secret( $mode, 'salt' ) );
			echo '<p>' . esc_html__( 'You will enter your card details on JazzCash.', 'payment-gateway-for-jazzcash' ) . '</p>';
			if ( $mode === 'sandbox' ) {
				echo '<p><strong>' . esc_html__( 'Sandbox test payment — no live payment.', 'payment-gateway-for-jazzcash' ) . '</strong></p>'; }
			echo '<form method="post" action="' . esc_url( $this->endpoint( $mode ) ) . '" autocomplete="off">';
			foreach ( $fields as $name => $value ) {
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">'; }
			echo '<button type="submit" class="button alt">' . esc_html__( 'Continue to JazzCash', 'payment-gateway-for-jazzcash' ) . '</button></form>';
		} catch ( Throwable $e ) {
			$this->log_event( 'payment_form_unavailable', (int) $order_id );
			wc_print_notice( esc_html( $e->getMessage() ), 'error' );
		} finally {
			if ( $locked ) {
				$this->unlock( (int) $order_id ); }
		}
	}

	/** Verify original bytes first; never sanitize signed values before authentication. */
	public function handle_notification( array $data, bool $soap = false ): array {
		if ( count( $data ) > 80 ) {
			throw new RuntimeException( esc_html__( 'Too many fields.', 'payment-gateway-for-jazzcash' ) ); }
		foreach ( $data as $key => $value ) {
			if ( ! is_string( $key ) || ! preg_match( '/^pp[A-Za-z0-9_]+$/D', $key ) || ! is_string( $value ) || strlen( $value ) > 4096 ) {
				throw new RuntimeException( esc_html__( 'Invalid fields.', 'payment-gateway-for-jazzcash' ) ); }
		}
		$ref = $data['pp_TxnRefNo'] ?? '';
		if ( ! preg_match( '/^T([1-9][0-9]*)R[A-F0-9]{8}$/D', $ref ) || strlen( $ref ) > 20 ) {
			throw new RuntimeException( esc_html__( 'Unknown transaction.', 'payment-gateway-for-jazzcash' ) ); }
		$id = (int) preg_replace( '/^T([0-9]+)R.*$/', '$1', $ref );
		$this->lock( $id );
		try {
			$order = wc_get_order( $id );
			if ( ! $order || $order->get_payment_method() !== $this->id ) {
				throw new RuntimeException( esc_html__( 'Unknown order.', 'payment-gateway-for-jazzcash' ) ); }
			$attempts = (array) $order->get_meta( '_jccg_attempts' );
			$a        = $attempts[ $ref ] ?? null;
			if ( ! $a || ! in_array( $a['mode'], array( 'sandbox', 'live' ), true ) ) {
				throw new RuntimeException( esc_html__( 'Unknown attempt.', 'payment-gateway-for-jazzcash' ) ); }
			$salt = $this->secret( $a['mode'], 'salt' );
			if ( ! AGJC_Protocol::verify( $data, $salt ) ) {
				throw new RuntimeException( esc_html__( 'Signature failed.', 'payment-gateway-for-jazzcash' ) ); }
			if ( ( $data['pp_TxnDateTime'] ?? '' ) !== $a['date'] ) {
				throw new RuntimeException( esc_html__( 'Transaction date mismatch.', 'payment-gateway-for-jazzcash' ) ); }
			$code = $data['pp_ResponseCode'] ?? '';
			if ( ! preg_match( '/^[A-Za-z0-9]{3}$/D', $code ) ) {
				throw new RuntimeException( esc_html__( 'Missing status.', 'payment-gateway-for-jazzcash' ) ); }
			if ( $soap ) {
				if ( ! in_array( $data['pp_TxnType'] ?? '', array( 'MPAY', 'MIGS' ), true )
					|| ! hash_equals( $this->secret( $a['mode'], 'password' ), $data['pp_Password'] ?? '' ) ) {
					throw new RuntimeException( esc_html__( 'IPN credentials mismatch.', 'payment-gateway-for-jazzcash' ) );
				}
			} else {
				foreach ( array(
					'pp_MerchantID'  => $a['merchant'],
					'pp_Amount'      => $a['amount'],
					'pp_TxnCurrency' => 'PKR',
					'pp_TxnType'     => 'MPAY',
				) as $key => $expected ) {
					if ( ( $data[ $key ] ?? '' ) !== $expected ) {
						throw new RuntimeException( esc_html__( 'Payment details mismatch.', 'payment-gateway-for-jazzcash' ) ); }
				}
			}
			// SOAP 4.2 omits amount/currency: bind it to the previously stored signed request.
			if ( $order->get_currency() !== 'PKR' || AGJC_Protocol::amount( (string) $order->get_total() ) !== $a['amount'] ) {
				throw new RuntimeException( esc_html__( 'Order total changed; manual reconciliation required.', 'payment-gateway-for-jazzcash' ) );
			}
			foreach ( array(
				'pp_Amount'      => $a['amount'],
				'pp_TxnCurrency' => 'PKR',
				'pp_MerchantID'  => $a['merchant'],
			) as $key => $expected ) {
				if ( isset( $data[ $key ] ) && $data[ $key ] !== $expected ) {
					throw new RuntimeException( esc_html__( 'Payment details mismatch.', 'payment-gateway-for-jazzcash' ) ); }
			}
			if ( $a['status'] === 'paid' ) {
				return array( $order, $salt ); }
			if ( $code === '000' ) {
				$rrn = $data['pp_RetreivalReferenceNo'] ?? '';
				if ( ! preg_match( '/^[A-Za-z0-9]{1,32}$/D', $rrn ) ) {
					throw new RuntimeException( esc_html__( 'Missing payment reference.', 'payment-gateway-for-jazzcash' ) ); }
				if ( $order->is_paid() || $order->has_status( array( 'cancelled', 'refunded' ) ) || $order->get_date_paid() ) {
					if ( ( $a['status'] ?? '' ) !== 'review' ) {
						$order->add_order_note( 'JazzCash: verified payment requires manual reconciliation (order already paid, cancelled or refunded). Reference: ' . $ref );
						$attempts[ $ref ]['status'] = 'review';
					}
				} elseif ( $order->has_status( array( 'pending', 'failed', 'on-hold' ) ) ) {
					// Complete first: on a crash before metadata saves, a replay sees the paid order.
					$order->payment_complete( $rrn );
					$attempts[ $ref ]['status'] = 'paid';
					$order->add_order_note( 'JazzCash verified card payment (' . $a['mode'] . '). Reference: ' . $ref );
					$this->log_event( 'payment_verified', $id );
				} else {
					throw new RuntimeException( esc_html__( 'Order status requires review.', 'payment-gateway-for-jazzcash' ) ); }
			} elseif ( ( $a['last_code'] ?? '' ) !== $code && ! $order->is_paid() && ! $order->get_date_paid() && ! $order->has_status( array( 'cancelled', 'refunded' ) ) ) {
				// Unknown/pending/error codes cannot safely prove that no charge occurred.
				$order->update_status( 'on-hold', 'JazzCash returned code ' . $code . '. Check the merchant portal before retrying or cancelling.' );
				$attempts[ $ref ]['last_code'] = $code;
				$this->log_event( 'payment_needs_review', $id );
			}
			$order->update_meta_data( '_jccg_attempts', $attempts );
			$order->save();
			return array( $order, $salt );
		} finally {
			$this->unlock( $id ); }
	}

	public function callback( bool $ipn ): void {
		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		if ( ! is_ssl() ) {
			status_header( 403 );
			exit;
		}
		$method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
		if ( $ipn && $method === 'GET' && isset( $_GET['wsdl'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only service description.
			header( 'Content-Type: text/xml; charset=utf-8' );
			$url = htmlspecialchars( WC()->api_request_url( 'jccg_ipn' ), ENT_QUOTES | ENT_XML1, 'UTF-8' );
			echo str_replace( '{{ENDPOINT}}', $url, file_get_contents( __DIR__ . '/ipn.wsdl' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bundled XML template; the only dynamic value is escaped with ENT_XML1 above.
			exit;
		}
		if ( $method !== 'POST' ) {
			status_header( 405 );
			header( 'Allow: POST' );
			exit( 'POST required' ); }
		try {
			if ( absint( wp_unslash( $_SERVER['CONTENT_LENGTH'] ?? 0 ) ) > 32768 ) {
				throw new RuntimeException( esc_html__( 'Request too large.', 'payment-gateway-for-jazzcash' ) ); }
			$type = strtolower( sanitize_text_field( wp_unslash( $_SERVER['CONTENT_TYPE'] ?? '' ) ) );
			$soap = $ipn && ( str_contains( $type, 'text/xml' ) || str_contains( $type, 'application/soap+xml' ) );
			if ( $ipn && ! $soap ) {
				throw new RuntimeException( esc_html__( 'SOAP IPN required.', 'payment-gateway-for-jazzcash' ) ); }
			$raw = file_get_contents( 'php://input', false, null, 0, 32769 );
			if ( ! is_string( $raw ) || strlen( $raw ) > 32768 ) {
				throw new RuntimeException( esc_html__( 'Request too large.', 'payment-gateway-for-jazzcash' ) ); }
			if ( $soap ) {
				$data = AGJC_Protocol::parse_soap( $raw );
			} elseif ( str_starts_with( $type, 'application/x-www-form-urlencoded' ) ) {
				$data = AGJC_Protocol::parse_form( $raw );
			} else {
				throw new RuntimeException( esc_html__( 'Unsupported content type.', 'payment-gateway-for-jazzcash' ) ); }
			[$order, $salt] = $this->handle_notification( $data, $soap );
			if ( $soap ) {
				header( 'Content-Type: text/xml; charset=utf-8' );
				echo AGJC_Protocol::soap_ack( $salt ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- soap_ack escapes the complete dynamic XML text with ENT_XML1.
				exit;
		}
			// No order key/PII is exposed on an unauthenticated or failed-signature response.
			wp_safe_redirect( $this->get_return_url( $order ), 303 );
			exit;
		} catch ( Throwable $e ) {
			// Rate-limit anonymous rejection logging to prevent log-volume abuse.
			if ( ! get_transient( 'agjc_rejection_logged' ) ) {
				$this->log_event( 'callback_rejected' );
				set_transient( 'agjc_rejection_logged', 1, MINUTE_IN_SECONDS );
			}
			status_header( 400 );
			if ( $ipn ) {
				header( 'Content-Type: text/xml; charset=utf-8' );
				echo '<?xml version="1.0"?><s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><s:Fault><faultcode>s:Client</faultcode><faultstring>Notification could not be verified</faultstring></s:Fault></s:Body></s:Envelope>';
			} else {
				echo esc_html__( 'Payment response could not be verified. Please contact the store before paying again.', 'payment-gateway-for-jazzcash' ); }
			exit;
		}
	}
}
