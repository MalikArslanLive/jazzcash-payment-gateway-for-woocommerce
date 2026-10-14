<?php
/** Prevent a fatal collision during validation while an earlier build is active. */
if ( PHP_SAPI !== 'cli' ) { exit; }
define( 'ABSPATH', __DIR__ );
define( 'AGJC_FILE', '/synthetic/previous-build.php' );
$activation = null;
function register_activation_hook( $file, $callback ) { global $activation; $activation = $callback; }
function is_plugin_active( $path ) { return $path === 'arslan-card-gateway-jazzcash/arslan-card-gateway-jazzcash.php'; }
function esc_html__( $text, $domain ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function wp_die( $message ) { throw new RuntimeException( $message ); }
require __DIR__ . '/../cardraabta-payment-gateway-for-jazzcash.php';
if ( AGJC_FILE !== '/synthetic/previous-build.php' || ! is_callable( $activation ) ) {
    throw new RuntimeException( 'Bootstrap collision guard failed.' );
}
echo "PASS: previous build bootstrap retained without duplicate classes/constants\n";
$rejected = false;
try { $activation(); } catch ( RuntimeException $e ) { $rejected = str_contains( $e->getMessage(), 'Deactivate' ); }
if ( ! $rejected ) { throw new RuntimeException( 'Previous-build activation was not rejected.' ); }
echo "PASS: activation requires deactivation of previous private build\n";
