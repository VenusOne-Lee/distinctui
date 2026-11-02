<?php
/**
 * The licence: a stub until the seller is picked.
 *
 * Lee is choosing between Freemius (keys, updates, tax and refunds in one)
 * and PayFast or Paystack checkout on distinctui.com with our own key
 * service. Everything that needs to know about the licence asks this class,
 * so swapping the stub for the real thing touches this file only:
 *
 *  - verify()    becomes the call to the seller's API.
 *  - status()    stays the same shape: none, active or expired.
 *  - can_build() is the one gate the paid half asks.
 *
 * What the licence never gates: rendering pages, form entries, the theme
 * settings, the rule check, and reading pages. Those are the free core.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Licence status and the build gate.
 */
class DUI_Plus_Licence {

	const OPTION = 'dui_plus_licence';

	/**
	 * The stored licence.
	 *
	 * @return array{key: string, status: string, checked: int, expires: int, plan: string}
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		return array_merge(
			array(
				'key'     => '',
				'status'  => 'none',
				'checked' => 0,
				'expires' => 0,
				'plan'    => '',
			),
			$saved
		);
	}

	/**
	 * none, active or expired.
	 *
	 * The filter lets the real licence client (or a site's own code during
	 * the beta) answer instead of the stub.
	 *
	 * @return string
	 */
	public static function status() {
		$l      = self::get();
		$status = $l['status'];
		if ( 'active' === $status && $l['expires'] && $l['expires'] < time() ) {
			$status = 'expired';
		}
		return (string) apply_filters( 'distinctui_plus_licence_status', $status, $l );
	}

	/**
	 * Can this site build new pages and change them? The free core never asks.
	 *
	 * DUI_PLUS_DEV in wp-config.php opens the builder for local development
	 * and the Version One beta.
	 *
	 * @return bool
	 */
	public static function can_build() {
		if ( defined( 'DUI_PLUS_DEV' ) && DUI_PLUS_DEV ) {
			return true;
		}
		return 'active' === self::status();
	}

	/**
	 * Check a key and store the answer.
	 *
	 * STUB: accepts any key shaped like DUI-XXXX-XXXX-XXXX-XXXX and marks
	 * it active for a year, without asking anyone. Replace with the seller's
	 * activation call once Lee picks Freemius or PayFast/Paystack.
	 *
	 * @param string $key Licence key as typed.
	 * @return array{ok: bool, message: string}
	 */
	public static function verify( $key ) {
		$key = strtoupper( trim( (string) $key ) );
		if ( '' === $key ) {
			delete_option( self::OPTION );
			return array(
				'ok'      => true,
				'message' => __( 'Licence removed. Your pages stay live; building waits for a key.', 'distinctui-plus' ),
			);
		}
		if ( ! preg_match( '/^DUI(-[A-Z0-9]{4}){4}$/', $key ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'That key doesn\'t look right. Keys start with DUI- and have four groups of four letters or numbers, like DUI-AB12-CD34-EF56-GH78.', 'distinctui-plus' ),
			);
		}
		update_option(
			self::OPTION,
			array(
				'key'     => $key,
				'status'  => 'active',
				'checked' => time(),
				'expires' => time() + YEAR_IN_SECONDS,
				'plan'    => 'solo',
			),
			false
		);
		return array(
			'ok'      => true,
			'message' => __( 'Licence on. You can build.', 'distinctui-plus' ),
		);
	}

	/**
	 * Plain words for where the licence stands, for the admin.
	 *
	 * @return string
	 */
	public static function describe() {
		if ( defined( 'DUI_PLUS_DEV' ) && DUI_PLUS_DEV ) {
			return __( 'Developer mode is on (DUI_PLUS_DEV in wp-config.php), so building works without a key.', 'distinctui-plus' );
		}
		switch ( self::status() ) {
			case 'active':
				$l = self::get();
				/* translators: %s: renewal date. */
				return sprintf( __( 'Licence on. Renews %s.', 'distinctui-plus' ), wp_date( get_option( 'date_format' ), $l['expires'] ) );
			case 'expired':
				return __( 'Your licence has ended. Every page you built stays live and keeps taking form entries. Renew to build and get updates again.', 'distinctui-plus' );
			default:
				return __( 'No licence yet. Add your key to start building. Pages you build stay live even if you stop paying.', 'distinctui-plus' );
		}
	}

	/**
	 * A WP_Error for when building is closed, in plain words.
	 *
	 * @return WP_Error
	 */
	public static function closed_error() {
		return new WP_Error(
			'dui_plus_licence',
			'expired' === self::status()
				? __( 'Your distinctUI+ licence has ended, so pages can\'t be changed here. They stay live. Renew under distinctUI+, Settings to build again.', 'distinctui-plus' )
				: __( 'Add your distinctUI+ licence key under distinctUI+, Settings to build pages. Pages already built stay live.', 'distinctui-plus' ),
			array( 'status' => 402 )
		);
	}
}
