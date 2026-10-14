<?php
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 404 );
	exit; }
define( 'ABSPATH', __DIR__ );
function add_action( ...$args ) {}
function __( $s, $d = '' ) {
	return $s; }
function esc_html__($s, $d = '') { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function wp_unslash( $s ) {
	return stripslashes( $s ); }
function get_option( $key, $default = false ) { global $test_options; return $test_options[$key] ?? $default; }
function wp_salt( $scheme ) {
	return 'FAKE-TEST-WORDPRESS-SALT-' . $scheme; }
class WC_Payment_Gateway {
	public $id, $method_title, $method_description, $has_fields, $supports, $title, $description, $enabled, $form_fields;
	public $settings = array();
	function init_settings() {}
	function get_option( $key, $default = '' ) {
		return $this->settings[ $key ] ?? $default; }
}
class Test_DB {
	public $prefix = 'test_';
	function prepare( $q, ...$args ) {
		return $q; }
	function get_var( $q ) {
		return '1'; }
}
$wpdb   = new Test_DB();
$orders = array();
function wc_get_order( $id ) {
	global $orders;
	return $orders[ $id ] ?? false; }
class Test_Order {
	public $meta = array(), $status = 'pending', $notes = array(), $complete_count = 0, $paid = false, $total = '100.25', $currency = 'PKR', $payment_method = 'jccg';
	function get_payment_method() {
		return $this->payment_method; }
	function get_meta( $k ) {
		return $this->meta[ $k ] ?? ''; }
	function update_meta_data( $k, $v ) {
		$this->meta[ $k ] = $v; }
	function get_total() {
		return $this->total; }
	function get_currency() {
		return $this->currency; }
	function is_paid() {
		return in_array( $this->status, array( 'processing', 'completed' ), true ); }
	function has_status( $s ) {
		return in_array( $this->status, (array) $s, true ); }
	function get_date_paid() {
		return $this->paid; }
	function add_order_note( $s ) {
		$this->notes[] = $s; }
	function payment_complete( $id ) {
		++$this->complete_count;
		$this->paid   = true;
		$this->status = 'processing'; }
	function update_status( $s, $note ) {
		$this->status  = $s;
		$this->notes[] = $note; }
	function save() {}
}
require __DIR__ . '/../includes/class-jccg-protocol.php';
require __DIR__ . '/../includes/class-jccg-gateway.php';
$passed = 0;
function check( $condition, $label ) {
	global $passed;
	if ( ! $condition ) {
		throw new RuntimeException( 'FAIL: ' . $label ); }
	++$passed;
	echo "PASS: $label\n";
}
function rejects( callable $fn, $label ) {
	try {
		$fn();
	} catch ( Throwable $e ) {
		check( true, $label );
		return; }
	check( false, $label );
}
// Independent official JazzCash worked example, rather than a self-generated expected hash.
$sample = array(
	'pp_MerchantID' => 'MER123',
	'pp_OrderInfo'  => 'A48cvE28',
	'pp_Amount'     => '2995',
);
check( AGJC_Protocol::hash( $sample, '0F5DD14AE2' ) === strtoupper( 'c7689cda7474eb1adcd343fd0c0b676bad0ba66361cc46db589bdb0da4c1c867' ), 'official JazzCash HMAC vector' );
$sample['pp_SecureHash'] = AGJC_Protocol::hash( $sample, '0F5DD14AE2' );
check( AGJC_Protocol::verify( $sample, '0F5DD14AE2' ), 'valid signature' );
$sample['pp_Amount'] = '2996';
check( ! AGJC_Protocol::verify( $sample, '0F5DD14AE2' ), 'tampered amount rejected' );
check( AGJC_Protocol::amount( '100.25' ) === '10025', 'paisa conversion' );
check( AGJC_Protocol::amount( '0.01' ) === '1', 'one paisa retained' );
check( AGJC_Protocol::amount( '0001.2' ) === '120', 'leading zeros normalized' );
rejects( fn()=>AGJC_Protocol::amount( '1.001' ), 'excess precision rejected' );
rejects( fn()=>AGJC_Protocol::amount( '-1' ), 'negative rejected' );
rejects( fn()=>AGJC_Protocol::amount( '0' ), 'zero rejected' );
rejects( fn()=>AGJC_Protocol::amount( '1e3' ), 'scientific notation rejected' );
[$start, $end, $stamp] = AGJC_Protocol::dates( 72, new DateTimeImmutable( '2026-12-30 20:00:00', new DateTimeZone( 'UTC' ) ) );
check( $start === '20261231010000' && $end === '20270103010000', '72 hours across year boundary in Karachi' );
check( $stamp - ( new DateTimeImmutable( '2026-12-30T20:00:00Z' ) )->getTimestamp() === 259200, '72 elapsed hours exactly' );
rejects( fn()=>AGJC_Protocol::dates( 0 ), 'zero expiry rejected' );
$g = new AGJC_Gateway();
foreach ( array(
	'merchant_id' => 'MERTEST',
	'password'    => 'test-password',
	'salt'        => 'test-integrity-salt',
) as $k => $v ) {
	$g->settings[ 'sandbox_' . $k ] = $g->validate_secret_field( 'sandbox_' . $k, $v );
}
check( ! str_contains( $g->settings['sandbox_salt'], 'test-integrity-salt' ), 'secret stored encrypted' );
check( $g->validate_secret_field( 'sandbox_salt', '' ) === $g->settings['sandbox_salt'], 'blank preserves secret' );
check( $g->validate_secret_field( 'sandbox_salt', '__CLEAR__' ) === '', 'explicit secret removal' );
$ref = 'T42RABCDEF12';
function reset_order() {
	global $orders, $ref;
	$o                         = new Test_Order();
	$o->meta['_jccg_attempts'] = array(
		$ref => array(
			'mode'     => 'sandbox',
			'merchant' => 'MERTEST',
			'amount'   => '10025',
			'date'     => '20261013120000',
			'expiry'   => '20261016120000',
			'expires'  => 1,
			'status'   => 'pending',
		),
	);
	$orders[42]                = $o;
	return $o;
}
function signed( array $changes = array() ) {
	global $ref;
	$d                  = array_merge(
		array(
			'pp_TxnRefNo'             => $ref,
			'pp_TxnDateTime'          => '20261013120000',
			'pp_ResponseCode'         => '000',
			'pp_MerchantID'           => 'MERTEST',
			'pp_Amount'               => '10025',
			'pp_TxnCurrency'          => 'PKR',
			'pp_TxnType'              => 'MPAY',
			'pp_RetreivalReferenceNo' => '123456789012',
		),
		$changes
	);
	$d['pp_SecureHash'] = AGJC_Protocol::hash( $d, 'test-integrity-salt' );
	return $d;
}
$o = reset_order();
$g->handle_notification( signed() );
check( $o->complete_count === 1 && $o->status === 'processing', 'valid callback completes order' );
$g->handle_notification( signed() );
check( $o->complete_count === 1, 'replayed success completes once' );
$g->handle_notification( signed( array( 'pp_ResponseCode' => '199' ) ) );
check( $o->status === 'processing', 'later failure cannot downgrade paid order' );
$o                = reset_order();
$bad              = signed();
$bad['pp_Amount'] = '90025';
rejects( fn()=>$g->handle_notification( $bad ), 'forged callback rejected' );
check( $o->complete_count === 0, 'forgery never fulfils order' );
rejects( fn()=>$g->handle_notification( signed( array( 'pp_Amount' => '90025' ) ) ), 'signed wrong amount rejected' );
rejects( fn()=>$g->handle_notification( signed( array( 'pp_TxnCurrency' => 'USD' ) ) ), 'signed wrong currency rejected' );
rejects( fn()=>$g->handle_notification( signed( array( 'pp_MerchantID' => 'OTHER' ) ) ), 'signed wrong merchant rejected' );
rejects( fn()=>$g->handle_notification( signed( array( 'pp_TxnDateTime' => '20261013120001' ) ) ), 'wrong transaction date rejected' );
rejects( fn()=>$g->handle_notification( signed( array( 'pp_TxnRefNo' => 'T42R11111111' ) ) ), 'unknown attempt rejected' );
$o->total = '200.25';
rejects( fn()=>$g->handle_notification( signed() ), 'edited order amount rejected' );
$o         = reset_order();
$o->status = 'cancelled';
$g->handle_notification( signed() );
check( $o->status === 'cancelled' && $o->complete_count === 0 && count( $o->notes ) === 1, 'cancelled payment queued for manual reconciliation' );
$g->handle_notification( signed() );
check( count( $o->notes ) === 1, 'review note not duplicated' );
$o         = reset_order();
$o->status = 'refunded';
$o->paid   = true;
$g->handle_notification( signed() );
check( $o->status === 'refunded' && $o->complete_count === 0, 'refund cannot be resurrected' );
$o = reset_order();
$g->handle_notification( signed( array( 'pp_ResponseCode' => '124' ) ) );
check( $o->status === 'on-hold' && $o->complete_count === 0, 'ambiguous status held for review' );
$g->handle_notification( signed() );
check( $o->status === 'processing' && $o->complete_count === 1, 'later verified success completes held order' );
$o    = reset_order();
$soap = signed( array( 'pp_Password' => 'test-password' ) );
unset( $soap['pp_Amount'], $soap['pp_TxnCurrency'], $soap['pp_MerchantID'] );
$soap['pp_SecureHash'] = AGJC_Protocol::hash( $soap, 'test-integrity-salt' );
$xml                   = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><DoUpdatePaymentStatus xmlns="http://tempuri.org/">';
foreach ( $soap as $k => $v ) {
	$xml .= '<' . $k . '>' . htmlspecialchars( $v, ENT_XML1 ) . '</' . $k . '>'; }
$xml   .= '</DoUpdatePaymentStatus></s:Body></s:Envelope>';
$parsed = AGJC_Protocol::parse_soap( $xml );
check( $parsed === $soap, 'SOAP parser preserves signed field values' );
$g->handle_notification( $parsed, true );
check( $o->complete_count === 1, 'documented SOAP without amount verifies stored attempt' );
$ack = new DOMDocument();
$ack->loadXML( AGJC_Protocol::soap_ack( 'test-integrity-salt' ) );
check( strlen( $ack->getElementsByTagName( 'DoUpdatePaymentStatusResult' )->item( 0 )->textContent ) === 267, 'fixed width SOAP acknowledgement' );
rejects( fn()=>AGJC_Protocol::parse_soap( '<!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]>' . $xml ), 'external entities rejected' );
rejects( fn()=>AGJC_Protocol::parse_soap( str_replace( '</DoUpdatePaymentStatus>', '<pp_TxnRefNo>duplicate</pp_TxnRefNo></DoUpdatePaymentStatus>', $xml ) ), 'duplicate SOAP fields rejected' );
$o                     = reset_order();
$soap['pp_Password']   = 'wrong';
$soap['pp_SecureHash'] = AGJC_Protocol::hash( $soap, 'test-integrity-salt' );
rejects( fn()=>$g->handle_notification( $soap, true ), 'wrong SOAP password rejected' );
$g->settings['mode'] = 'live';
$o                   = reset_order();
$g->handle_notification( signed() );
check( $o->complete_count === 1, 'late sandbox callback uses original mode credentials' );
echo "\n$passed checks passed.\n";

$body = http_build_query(signed());
check(AGJC_Protocol::parse_form($body) === signed(), 'HTTP form parsing preserves signature fields');
rejects(fn()=>AGJC_Protocol::parse_form($body.'&pp_Amount=1'), 'duplicate HTTP form fields rejected');
rejects(fn()=>AGJC_Protocol::parse_form('pp_Amount%5B%5D=1'), 'PHP array-style form fields rejected');
rejects(fn()=>AGJC_Protocol::parse_form(str_repeat('a',32769)), 'oversized callback rejected');
rejects(fn()=>AGJC_Protocol::parse_form('Response=%7Bbad'), 'malformed response JSON rejected');
rejects(fn()=>AGJC_Protocol::parse_form('Response=%7B%7D&pp_Amount=1'), 'ambiguous wrapped plus flat response rejected');
$missing=signed();unset($missing['pp_SecureHash']);
check(!AGJC_Protocol::verify($missing,'test-integrity-salt'),'missing hash rejected');
$empty=signed();$empty['pp_SecureHash']='';
check(!AGJC_Protocol::verify($empty,'test-integrity-salt'),'empty hash rejected');
check(AGJC_Protocol::hash(['pp_X'=>'0'],'salt') !== AGJC_Protocol::hash(['pp_X'=>''],'salt'),'zero-valued signing field not dropped');
echo "Total: $passed regression checks passed.\n";
