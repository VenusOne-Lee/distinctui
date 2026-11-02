<?php
/**
 * REST routes for the canvas: distinctui-plus/v1.
 *
 * Reading, checking and rendering are free core. Changing a page asks the
 * licence first.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The canvas's REST API.
 */
class DUI_Plus_Rest {

	const NS = 'distinctui-plus/v1';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Can the current person edit this page?
	 *
	 * @param WP_REST_Request $r Request.
	 * @return bool
	 */
	public static function can_edit( $r ) {
		$id = (int) $r['id'];
		return $id ? current_user_can( 'edit_post', $id ) : current_user_can( 'edit_pages' );
	}

	/**
	 * Permission for changes: edit rights, then the licence.
	 *
	 * @param WP_REST_Request $r Request.
	 * @return bool|WP_Error
	 */
	public static function can_build( $r ) {
		if ( ! self::can_edit( $r ) ) {
			return false;
		}
		return DUI_Plus_Licence::can_build() ? true : DUI_Plus_Licence::closed_error();
	}

	/**
	 * Register routes.
	 */
	public static function routes() {
		$edit  = array( __CLASS__, 'can_edit' );
		$build = array( __CLASS__, 'can_build' );
		$id    = '/pages/(?P<id>\d+)';

		register_rest_route(
			self::NS,
			'/pages',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => fn() => DUI_Plus_Store::list_pages(),
					'permission_callback' => $edit,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create' ),
					'permission_callback' => $build,
				),
			)
		);
		register_rest_route(
			self::NS,
			$id,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => fn( $r ) => self::out( DUI_Plus_Store::get( (int) $r['id'] ) ),
					'permission_callback' => $edit,
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( __CLASS__, 'save' ),
					'permission_callback' => $build,
				),
			)
		);
		foreach ( array(
			'publish' => fn( $r ) => self::out( DUI_Plus_Store::publish( (int) $r['id'] ) ),
			'restore' => fn( $r ) => self::out( DUI_Plus_Store::restore( (int) $r['id'], (int) $r['version'] ) ),
			'discard' => fn( $r ) => self::out( DUI_Plus_Store::discard( (int) $r['id'] ) ),
			'ask'     => fn( $r ) => array( 'asks' => DUI_Plus_Store::add_ask( (int) $r['id'], (string) $r['text'], isset( $r['section'] ) ? (int) $r['section'] : -1 ) ),
			'asks'    => fn( $r ) => array( 'asks' => DUI_Plus_Store::done_asks( (int) $r['id'], (array) ( $r['done'] ?? array() ) ) ),
		) as $action => $cb ) {
			register_rest_route(
				self::NS,
				$id . '/' . $action,
				array(
					'methods'             => 'POST',
					'callback'            => $cb,
					'permission_callback' => $build,
				)
			);
		}
		register_rest_route(
			self::NS,
			$id . '/rev',
			array(
				'methods'             => 'GET',
				'callback'            => fn( $r ) => array( 'rev' => (int) get_post_meta( (int) $r['id'], DUI_Plus_Store::META_REV, true ) ),
				'permission_callback' => $edit,
			)
		);
		register_rest_route(
			self::NS,
			'/render',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'render' ),
				'permission_callback' => $edit,
			)
		);
		register_rest_route(
			self::NS,
			'/frame',
			array(
				'methods'             => 'GET',
				'callback'            => fn() => array( 'head' => self::frame_head() ),
				'permission_callback' => $edit,
			)
		);
		register_rest_route(
			self::NS,
			'/options',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'options' ),
				'permission_callback' => $build,
			)
		);
		register_rest_route(
			self::NS,
			'/check',
			array(
				'methods'             => 'POST',
				'callback'            => fn( $r ) => DUI_Plus_Check::check( (array) $r['spec'] ),
				'permission_callback' => $edit,
			)
		);
		register_rest_route(
			self::NS,
			'/fix',
			array(
				'methods'             => 'POST',
				'callback'            => fn( $r ) => DUI_Plus_Check::fix( (array) $r['spec'] ),
				'permission_callback' => $edit,
			)
		);
		register_rest_route(
			self::NS,
			'/starter/(?P<type>[a-z]+)',
			array(
				'methods'             => 'GET',
				'callback'            => fn( $r ) => DUI_Plus_Spec::starter( (string) $r['type'] ),
				'permission_callback' => $edit,
			)
		);
		register_rest_route(
			self::NS,
			'/look',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => fn() => array(
						'look'      => DUI_Plus_Settings::look(),
						'signature' => DUI_Plus_Settings::signature(),
					),
					'permission_callback' => $edit,
				),
				array(
					'methods'             => 'POST',
					'callback'            => fn( $r ) => array(
						'look'      => DUI_Plus_Settings::update_look( (array) $r->get_json_params() ),
						'signature' => DUI_Plus_Settings::signature(),
						'head'      => self::frame_head(),
					),
					'permission_callback' => fn() => current_user_can( 'edit_theme_options' ),
				),
			)
		);
	}

	/**
	 * Pass a WP_Error through, or the data.
	 *
	 * @param mixed $v Value.
	 * @return mixed
	 */
	private static function out( $v ) {
		return $v;
	}

	/**
	 * New page.
	 *
	 * @param WP_REST_Request $r Request: title, spec.
	 * @return array|WP_Error
	 */
	public static function create( $r ) {
		$id = DUI_Plus_Store::create( (string) $r['title'], (array) $r['spec'] );
		return is_wp_error( $id ) ? $id : DUI_Plus_Store::get( $id );
	}

	/**
	 * Save a change. If Claude changed the page since the canvas last looked
	 * (rev is behind), say so instead of overwriting.
	 *
	 * @param WP_REST_Request $r Request: spec, title, rev.
	 * @return array|WP_Error
	 */
	public static function save( $r ) {
		$id  = (int) $r['id'];
		$now = (int) get_post_meta( $id, DUI_Plus_Store::META_REV, true );
		if ( isset( $r['rev'] ) && (int) $r['rev'] < $now && empty( $r['force'] ) ) {
			return new WP_Error(
				'dui_plus_behind',
				__( 'This page changed somewhere else since you opened it. Load the new version, then make your change again.', 'distinctui-plus' ),
				array(
					'status' => 409,
					'rev'    => $now,
				)
			);
		}
		return DUI_Plus_Store::save( $id, (array) $r['spec'], array( 'title' => (string) $r['title'] ) );
	}

	/**
	 * Render sections for the canvas. Each section comes back on its own,
	 * with its layout CSS, so swapping one section redraws only that one.
	 *
	 * @param WP_REST_Request $r Request: spec, only (indices), live, post_id.
	 * @return array
	 */
	public static function render( $r ) {
		$spec = DUI_Plus_Spec::clean( (array) $r['spec'] );
		$only = isset( $r['only'] ) ? array_map( 'intval', (array) $r['only'] ) : null;
		$out  = array();
		$css  = '';
		foreach ( $spec['sections'] as $i => $s ) {
			if ( null !== $only && ! in_array( $i, $only, true ) ) {
				continue;
			}
			$item  = self::render_section( $s, $i, $spec, (int) $r['post_id'], ! empty( $r['live'] ) );
			$out[] = array(
				'i'    => $i,
				'html' => $item['html'],
			);
			$css  .= $item['css'];
		}
		return array(
			'sections' => $out,
			'css'      => $css,
			'check'    => DUI_Plus_Check::check( $spec ),
		);
	}

	/**
	 * One section's HTML and CSS, with layout class names made unique so
	 * sections drawn in different requests can't clash.
	 *
	 * @param array $s       Section.
	 * @param int   $i       Index.
	 * @param array $spec    Spec.
	 * @param int   $post_id Page id, for forms.
	 * @param bool  $live    Draw the mark solid.
	 * @return array{html: string, css: string}
	 */
	public static function render_section( array $s, $i, array $spec, $post_id = 0, $live = false ) {
		static $n = 0;
		++$n;
		$markup = DUI_Plus_Render::section(
			$s,
			$i,
			array(
				'canvas'  => true,
				'live'    => $live,
				'post_id' => $post_id,
			),
			$spec
		);
		$store = WP_Style_Engine_CSS_Rules_Store::get_store( 'block-supports' );
		// Shortcodes too, so a Contact Form 7, WPForms or Gravity form shows as itself.
		$html = do_shortcode( do_blocks( $markup ) );
		// Every layout rule this section's markup uses. Newer WordPress names
		// containers by a hash of their styles, so two sections can share a
		// rule that was stored before this one ran: take rules by use, not by age.
		$css = '';
		foreach ( $store->get_all_rules() as $selector => $rule ) {
			if ( preg_match_all( '/\.((?:wp-container|wp-elements)-[A-Za-z0-9_-]+)/', (string) $selector, $m ) ) {
				foreach ( $m[1] as $class ) {
					if ( false !== strpos( $html, $class ) ) {
						$css .= $rule->get_css();
						break;
					}
				}
			}
		}
		$tag  = 'd' . wp_rand( 1000, 9999 ) . $n;
		$swap = function ( $text ) use ( $tag ) {
			return preg_replace( '/\b(wp-container-[a-z0-9-]*?is-layout-|wp-elements-)([a-f0-9]+)\b/', '$1' . $tag . '-$2', $text );
		};
		return array(
			'html' => $swap( $html ),
			'css'  => $swap( $css ),
		);
	}

	/**
	 * Everything the canvas iframe needs in its head to look exactly like
	 * the live site: the theme's stylesheets, global styles, the settings
	 * CSS and the font.
	 *
	 * @return string HTML.
	 */
	public static function frame_head() {
		$links = array(
			includes_url( 'css/dist/block-library/style.min.css' ),
			includes_url( 'css/dist/block-library/theme.min.css' ),
		);
		if ( DUI_Plus_Settings::theme_active() ) {
			$links[] = get_template_directory_uri() . '/assets/css/theme.css';
			$links[] = get_template_directory_uri() . '/assets/css/plugins.css';
		}
		$head = '';
		foreach ( $links as $href ) {
			$head .= '<link rel="stylesheet" href="' . esc_url( add_query_arg( 'ver', DUI_PLUS_VERSION, $href ) ) . '">';
		}
		ob_start();
		if ( function_exists( 'wp_print_font_faces' ) ) {
			wp_print_font_faces();
		}
		$head .= ob_get_clean();
		$css   = wp_get_global_stylesheet();
		if ( function_exists( 'wp_get_global_styles_custom_css' ) ) {
			$css .= wp_get_global_styles_custom_css();
		}
		if ( function_exists( 'distinctui_settings_css' ) ) {
			$css .= distinctui_settings_css();
		}
		$head .= '<style id="dui-global">' . $css . '</style>';
		return $head;
	}

	/**
	 * Three designs of your content, ready to show.
	 *
	 * @param WP_REST_Request $r Request: content.
	 * @return array
	 */
	public static function options( $r ) {
		$options = DUI_Plus_Compose::options( (array) $r['content'] );
		foreach ( $options as $k => $o ) {
			$html = '';
			$css  = '';
			foreach ( $o['spec']['sections'] as $i => $s ) {
				$item  = self::render_section( $s, $i, $o['spec'] );
				$html .= $item['html'];
				$css  .= $item['css'];
			}
			$options[ $k ]['html'] = $html;
			$options[ $k ]['css']  = $css;
		}
		return $options;
	}
}
