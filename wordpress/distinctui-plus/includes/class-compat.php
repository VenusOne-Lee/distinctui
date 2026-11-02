<?php
/**
 * Working with the big plugins.
 *
 *  - SEO: the page's title and description go to Yoast, Rank Math or
 *    SEOPress when one is active; otherwise distinctUI+ prints them itself.
 *  - Caches: publishing clears the page in WP Rocket, LiteSpeed, W3 Total
 *    Cache, WP Super Cache, WP Fastest Cache, SiteGround and Breeze.
 *  - Translations: WPML copies the spec once to a new translation (see
 *    wpml-config.xml); Polylang does the same. Each language then has its
 *    own spec and is built on its own.
 *  - WooCommerce: the Products section reads your products (DUI_Plus_Render).
 *  - Form plugins: the Form section can hold a Contact Form 7, WPForms or
 *    Gravity Forms form instead of its own.
 *
 * Their look (WooCommerce, the form plugins, breadcrumbs, Jetpack, the
 * language switchers) lives in the free theme's plugins.css, so it stays
 * even without distinctUI+.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compatibility with other plugins.
 */
class DUI_Plus_Compat {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'distinctui_plus_published', array( __CLASS__, 'seo' ), 5, 2 );
		add_action( 'distinctui_plus_published', array( __CLASS__, 'purge' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'meta' ), 1 );
		add_filter( 'pre_get_document_title', array( __CLASS__, 'title' ), 20 );
		add_filter( 'pll_copy_post_metas', array( __CLASS__, 'polylang' ), 10, 2 );
	}

	/**
	 * Is an SEO plugin handling titles and descriptions?
	 *
	 * @return string yoast, rankmath, seopress, aioseo or ''.
	 */
	public static function seo_plugin() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return 'yoast';
		}
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			return 'rankmath';
		}
		if ( defined( 'SEOPRESS_VERSION' ) ) {
			return 'seopress';
		}
		if ( defined( 'AIOSEO_VERSION' ) ) {
			return 'aioseo';
		}
		return '';
	}

	/**
	 * Hand the title and description to the SEO plugin, without overwriting
	 * anything someone typed there themselves.
	 *
	 * @param int   $post_id Page id.
	 * @param array $spec    Live spec.
	 */
	public static function seo( $post_id, $spec ) {
		$keys = array(
			'yoast'    => array( '_yoast_wpseo_title', '_yoast_wpseo_metadesc' ),
			'rankmath' => array( 'rank_math_title', 'rank_math_description' ),
			'seopress' => array( '_seopress_titles_title', '_seopress_titles_desc' ),
		);
		$plugin = self::seo_plugin();
		if ( ! isset( $keys[ $plugin ] ) ) {
			return;
		}
		$last = (array) get_post_meta( $post_id, '_dui_seo_sent', true );
		foreach ( array( 0 => 'title', 1 => 'description' ) as $k => $field ) {
			$value = $spec[ $field ] ?? '';
			if ( 'description' === $field && '' === $value ) {
				$value = self::first_lede( $spec );
			}
			if ( '' === $value ) {
				continue;
			}
			$meta    = $keys[ $plugin ][ $k ];
			$current = (string) get_post_meta( $post_id, $meta, true );
			// Only write if it's empty or still what we wrote last time.
			if ( '' === $current || ( $last[ $field ] ?? null ) === $current ) {
				update_post_meta( $post_id, $meta, $value );
				$last[ $field ] = $value;
			}
		}
		update_post_meta( $post_id, '_dui_seo_sent', $last );
	}

	/**
	 * The first lede on the page, for a description.
	 *
	 * @param array $spec Spec.
	 * @return string
	 */
	private static function first_lede( array $spec ) {
		foreach ( $spec['sections'] ?? array() as $s ) {
			if ( ! empty( $s['lede'] ) ) {
				return wp_html_excerpt( $s['lede'], 155, '…' );
			}
		}
		return '';
	}

	/**
	 * The live spec of the page being viewed, if it's ours.
	 *
	 * @return array|null
	 */
	private static function current_spec() {
		if ( ! is_singular() ) {
			return null;
		}
		$raw = get_post_meta( get_queried_object_id(), DUI_Plus_Store::META_SPEC, true );
		$v   = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : null;
		return is_array( $v ) ? $v : null;
	}

	/**
	 * With no SEO plugin, print the description and share tags ourselves.
	 */
	public static function meta() {
		if ( self::seo_plugin() ) {
			return;
		}
		$spec = self::current_spec();
		if ( ! $spec ) {
			return;
		}
		$desc  = ! empty( $spec['description'] ) ? $spec['description'] : self::first_lede( $spec );
		$title = ! empty( $spec['title'] ) ? $spec['title'] : get_the_title();
		if ( $desc ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary">' . "\n";
	}

	/**
	 * With no SEO plugin, use the page's own title in the browser tab.
	 *
	 * @param string $title Title.
	 * @return string
	 */
	public static function title( $title ) {
		if ( self::seo_plugin() ) {
			return $title;
		}
		$spec = self::current_spec();
		return ( $spec && ! empty( $spec['title'] ) ) ? $spec['title'] : $title;
	}

	/**
	 * Clear caches for a page that just went live. Most cache plugins do
	 * this on save anyway; this makes sure.
	 *
	 * @param int $post_id Page id.
	 */
	public static function purge( $post_id ) {
		$url = get_permalink( $post_id );
		if ( function_exists( 'rocket_clean_post' ) ) {
			rocket_clean_post( $post_id );
		}
		do_action( 'litespeed_purge_post', $post_id );
		if ( function_exists( 'w3tc_flush_post' ) ) {
			w3tc_flush_post( $post_id );
		}
		if ( function_exists( 'wp_cache_post_change' ) ) {
			wp_cache_post_change( $post_id );
		}
		if ( function_exists( 'wpfc_clear_post_cache_by_id' ) ) {
			wpfc_clear_post_cache_by_id( $post_id );
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache( $url );
		}
		do_action( 'breeze_clear_all_cache' );
		clean_post_cache( $post_id );
	}

	/**
	 * Polylang: copy the spec when a translation is made, then let each
	 * language keep its own.
	 *
	 * @param string[] $metas Meta keys to copy.
	 * @param bool     $sync  True when syncing, false when copying once.
	 * @return string[]
	 */
	public static function polylang( $metas, $sync ) {
		if ( ! $sync ) {
			$metas[] = DUI_Plus_Store::META_SPEC;
		}
		return $metas;
	}
}
