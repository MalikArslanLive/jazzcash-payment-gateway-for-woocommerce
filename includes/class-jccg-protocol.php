<?php
defined( 'ABSPATH' ) || exit;

final class AGJC_Protocol {
	/** Decode one form body without PHP's duplicate-key/array coercion. */
	public static function parse_form( string $raw ): array {
		if ( $raw === '' || strlen( $raw ) > 32768 ) {
			throw new RuntimeException( 'Invalid form size.' ); }
		$data = array();
		foreach ( explode( '&', $raw ) as $pair ) {
			$parts = explode( '=', $pair, 2 );
			$key   = urldecode( $parts[0] );
			if ( ! preg_match( '/^(?:pp[A-Za-z0-9_]+|Response)$/D', $key ) || array_key_exists( $key, $data ) ) {
				throw new RuntimeException( 'Invalid or duplicate form field.' );
			}
			$data[ $key ] = urldecode( $parts[1] ?? '' );
		}
		if ( isset( $data['Response'] ) ) {
			if ( count( $data ) !== 1 ) {
				throw new RuntimeException( 'Ambiguous response.' ); }
			$data = json_decode( $data['Response'], true, 16, JSON_THROW_ON_ERROR );
		}
		if ( ! is_array( $data ) || count( $data ) > 80 ) {
			throw new RuntimeException( 'Invalid response.' ); }
		return $data;
	}
	public static function hash( array $fields, string $salt ): string {
		if ( $salt === '' ) {
			throw new RuntimeException( 'Missing signing key.' ); }
		$signed = array();
		foreach ( $fields as $key => $value ) {
			if ( strncasecmp( (string) $key, 'pp', 2 ) !== 0 || strcasecmp( $key, 'pp_SecureHash' ) === 0 ) {
				continue; }
			if ( ! is_scalar( $value ) && $value !== null ) {
				throw new RuntimeException( 'Invalid payment field.' ); }
			if ( $value !== null && (string) $value !== '' ) {
				$signed[ $key ] = (string) $value; }
		}
		ksort( $signed, SORT_STRING );
		$message = $salt . '&' . implode( '&', $signed );
		// JazzCash specifies ISO-8859-1 message bytes and a UTF-8 HMAC key.
		$encoded = iconv( 'UTF-8', 'ISO-8859-1', $message );
		if ( $encoded === false ) {
			throw new RuntimeException( 'Unsupported signing characters.' ); }
		return strtoupper( hash_hmac( 'sha256', $encoded, $salt ) );
	}

	public static function verify( array $fields, string $salt ): bool {
		$received = $fields['pp_SecureHash'] ?? '';
		return is_string( $received ) && preg_match( '/^[a-fA-F0-9]{64}$/D', $received )
			&& hash_equals( self::hash( $fields, $salt ), strtoupper( $received ) );
	}

	public static function amount( string $decimal ): string {
		if ( ! preg_match( '/^(\d+)(?:\.(\d{1,2}))?$/D', $decimal, $m ) ) {
			throw new RuntimeException( 'PKR total must have at most two decimal places.' );
		}
		$minor = ltrim( $m[1] . str_pad( $m[2] ?? '', 2, '0' ), '0' );
		if ( $minor === '' || strlen( $minor ) > 12 ) {
			throw new RuntimeException( 'Invalid amount.' ); }
		return $minor;
	}

	public static function dates( int $hours, ?DateTimeImmutable $now = null ): array {
		if ( $hours < 1 || $hours > 2160 ) {
			throw new RuntimeException( 'Expiry must be 1 to 2160 hours.' ); }
		$now    = ( $now ?? new DateTimeImmutable( 'now' ) )->setTimezone( new DateTimeZone( 'Asia/Karachi' ) );
		$expiry = $now->add( new DateInterval( 'PT' . $hours . 'H' ) );
		return array( $now->format( 'YmdHis' ), $expiry->format( 'YmdHis' ), $expiry->getTimestamp() );
	}

	public static function parse_soap( string $raw ): array {
		if ( strlen( $raw ) > 32768 || stripos( $raw, '<!DOCTYPE' ) !== false || stripos( $raw, '<!ENTITY' ) !== false ) {
			throw new RuntimeException( 'Invalid XML.' );
		}
		$previous = libxml_use_internal_errors( true );
		$doc      = new DOMDocument();
		$ok       = $doc->loadXML( $raw, LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		if ( ! $ok ) {
			throw new RuntimeException( 'Invalid XML.' ); }
		$xp = new DOMXPath( $doc );
		$xp->registerNamespace( 's', 'http://schemas.xmlsoap.org/soap/envelope/' );
		$nodes = $xp->query( '/s:Envelope/s:Body/*' );
		if ( $nodes->length !== 1 || $nodes->item( 0 )->localName !== 'DoUpdatePaymentStatus'
			|| $nodes->item( 0 )->namespaceURI !== 'http://tempuri.org/' ) {
			throw new RuntimeException( 'Unsupported SOAP operation.' );
		}
		$data = array();
		foreach ( $nodes->item( 0 )->childNodes as $node ) {
			if ( ! ( $node instanceof DOMElement ) ) {
				continue; }
			$name = $node->localName;
			if ( isset( $data[ $name ] ) || ! preg_match( '/^pp_[A-Za-z0-9_]+$/D', $name ) || $node->getElementsByTagName( '*' )->length ) {
				throw new RuntimeException( 'Invalid SOAP field.' );
			}
			$data[ $name ] = $node->textContent;
		}
		return $data;
	}

	public static function soap_ack( string $salt ): string {
		$message = str_repeat( ' ', 200 );
		$hash    = self::hash(
			array(
				'pp_ResponseCode'    => '000',
				'pp_ResponseMessage' => $message,
			),
			$salt
		);
		$result  = htmlspecialchars( '000' . $message . $hash, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
		return '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><DoUpdatePaymentStatusResponse xmlns="http://tempuri.org/"><DoUpdatePaymentStatusResult>' . $result . '</DoUpdatePaymentStatusResult></DoUpdatePaymentStatusResponse></soap:Body></soap:Envelope>';
	}
}
