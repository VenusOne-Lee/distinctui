<?php
/**
 * Runs DUI_Plus_Check::check() outside WordPress, for test/check-parity.test.js.
 * Reads a JSON list of specs on stdin and prints a JSON list of reports.
 *
 * @package distinctui-plus
 */

// phpcs:disable
if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', __DIR__ . '/' );
define( 'DUI_PLUS_DIR', dirname( __DIR__ ) . '/' );
function __( $s ) { return $s; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_hex_color( $c ) { return preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $c ) ? $c : null; }

function absint( $n ) { return abs( (int) $n ); }
function esc_url_raw( $u ) { return (string) $u; }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_title( $s ) { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $s ) ), '-' ); }

require DUI_PLUS_DIR . 'includes/class-spec.php';
require DUI_PLUS_DIR . 'includes/class-check.php';

$specs = json_decode( stream_get_contents( STDIN ), true );
$out   = array();
foreach ( $specs as $spec ) {
	$out[] = DUI_Plus_Check::check( $spec );
}
echo json_encode( $out );
