<?php
/**
 * Builder settings, and the bridge to the theme's settings.
 *
 * The look (palette, your colour, your mark, Airy or Compact) lives in the
 * theme's `distinctui_settings` option, so the theme and the builder share
 * one source. The builder's own preferences live in `dui_plus_settings`.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for the theme look and the builder.
 */
class DUI_Plus_Settings {

	const OPTION = 'dui_plus_settings';

	/**
	 * The palettes, in case the theme isn't active yet.
	 *
	 * @var array<string, string[]>
	 */
	const PALETTES = array(
		'signal'    => array( '#1f3bff', '#ff4f1f', '#7a2cff', '#e8a800', '#000000' ),
		'mzansi'    => array( '#e03c31', '#001489', '#ffb81c', '#007749', '#000000' ),
		'earth'     => array( '#b5452b', '#c8860a', '#6b3e26', '#2e3a87', '#000000' ),
		'coast'     => array( '#0067a5', '#ff5a4e', '#00706b', '#d9a400', '#000000' ),
		'jacaranda' => array( '#6f3fc7', '#c2185b', '#2f80ed', '#f26b1d', '#000000' ),
	);

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the builder option for options.php.
	 */
	public static function register() {
		register_setting(
			'dui_plus',
			self::OPTION,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Builder preferences with defaults.
	 *
	 * @return array{phone_beside: bool, form_email: string, start: string, keep_versions: int}
	 */
	public static function builder() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		return array_merge(
			array(
				'phone_beside'  => true,
				'form_email'    => '',
				'start'         => 'content',
				'keep_versions' => 20,
			),
			$saved
		);
	}

	/**
	 * Clean builder preferences.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$email = isset( $input['form_email'] ) ? sanitize_email( $input['form_email'] ) : '';
		return array(
			'phone_beside'  => ! empty( $input['phone_beside'] ),
			'form_email'    => is_email( $email ) ? $email : '',
			'start'         => ( isset( $input['start'] ) && 'blank' === $input['start'] ) ? 'blank' : 'content',
			'keep_versions' => isset( $input['keep_versions'] ) ? max( 5, min( 100, absint( $input['keep_versions'] ) ) ) : 20,
		);
	}

	/**
	 * Is the distinctUI theme (or a child of it) active?
	 *
	 * @return bool
	 */
	public static function theme_active() {
		// By what it is, not its folder: a copy from GitHub can be called distinctui-main.
		return 'distinctui' === get_template() || function_exists( 'distinctui_get_settings' ) || 'distinctUI' === wp_get_theme( get_template() )->get( 'Name' );
	}

	/**
	 * The theme look settings, whether or not the theme is active.
	 *
	 * @return array{palette: string, signature: string, initials: string, mark_image: int, density: string}
	 */
	public static function look() {
		if ( function_exists( 'distinctui_get_settings' ) ) {
			return distinctui_get_settings();
		}
		$saved = get_option( 'distinctui_settings', array() );
		return array_merge(
			array(
				'palette'    => '',
				'signature'  => '',
				'initials'   => '',
				'mark_image' => 0,
				'density'    => 'airy',
			),
			is_array( $saved ) ? $saved : array()
		);
	}

	/**
	 * Change the look. Only the keys given change.
	 *
	 * @param array $changes palette, signature, initials, mark_image, density.
	 * @return array The look after the change.
	 */
	public static function update_look( array $changes ) {
		$current = self::look();
		$next    = array_merge( $current, array_intersect_key( $changes, $current ) );
		if ( function_exists( 'distinctui_sanitize_settings' ) ) {
			$next = distinctui_sanitize_settings( $next );
		} else {
			$next['palette']    = isset( self::PALETTES[ $next['palette'] ] ) ? $next['palette'] : '';
			$next['signature']  = sanitize_hex_color( $next['signature'] ) ? strtolower( $next['signature'] ) : '';
			$next['initials']   = mb_substr( trim( wp_strip_all_tags( (string) $next['initials'] ) ), 0, 3 );
			$next['mark_image'] = absint( $next['mark_image'] );
			$next['density']    = 'compact' === $next['density'] ? 'compact' : 'airy';
		}
		update_option( 'distinctui_settings', $next );
		return $next;
	}

	/**
	 * Everything a renderer needs to draw the mark.
	 *
	 * @return array{colour: string, palette: string, rings: string[], initials: string, image: string, density: string}
	 */
	public static function signature() {
		$look    = self::look();
		$palette = $look['palette'] ? $look['palette'] : 'signal';
		$rings   = self::PALETTES[ $palette ] ?? self::PALETTES['signal'];
		$colour  = $look['signature'] ? $look['signature'] : $rings[0];
		$name    = wp_strip_all_tags( get_bloginfo( 'name' ) );
		return array(
			'colour'   => $colour,
			'palette'  => $palette,
			'rings'    => $rings,
			'initials' => $look['initials'] ? $look['initials'] : ( $name ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) : 'D' ),
			'image'    => $look['mark_image'] ? (string) wp_get_attachment_image_url( $look['mark_image'], 'medium' ) : '',
			'density'  => $look['density'],
		);
	}
}
