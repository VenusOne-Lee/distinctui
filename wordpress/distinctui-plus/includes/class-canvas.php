<?php
/**
 * The canvas: a full-screen builder inside WordPress.
 *
 * On the left, the page's sections and what you can add. In the middle,
 * the page itself, drawn with the live site's own CSS, with your phone
 * beside it. Along the bottom, the rule line. No settings panel: you pick
 * layouts and type on the page.
 *
 * It's still WordPress underneath, so roles, revisions and other plugins
 * keep working. Opening it is free; changing pages asks the licence.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The canvas screen.
 */
class DUI_Plus_Canvas {

	const SLUG = 'dui-canvas';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
	}

	/**
	 * Link to the canvas for a page, or for a new page.
	 *
	 * @param int $post_id Page id, or 0.
	 * @return string
	 */
	public static function url( $post_id = 0 ) {
		$args = array( 'page' => self::SLUG );
		if ( $post_id ) {
			$args['post'] = (int) $post_id;
		}
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * "New page" under distinctUI+ opens the canvas. The screen draws
	 * itself before WordPress's admin header, so it's full screen.
	 */
	public static function menu() {
		$hook = add_submenu_page(
			'distinctui-plus',
			__( 'New page', 'distinctui-plus' ),
			__( 'New page', 'distinctui-plus' ),
			'edit_pages',
			self::SLUG,
			'__return_null'
		);
		if ( $hook ) {
			add_action( 'load-' . $hook, array( __CLASS__, 'screen' ) );
		}
	}

	/**
	 * Draw the canvas and stop.
	 */
	public static function screen() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You can\'t edit this page.', 'distinctui-plus' ), 403 );
		}
		$page = $post_id ? DUI_Plus_Store::get( $post_id ) : null;
		if ( is_wp_error( $page ) ) {
			wp_die( esc_html( $page->get_error_message() ), 404 );
		}

		$types = array();
		foreach ( DUI_Plus_Spec::types() as $type => $t ) {
			$types[ $type ] = array(
				'name'    => $t['name'],
				'about'   => $t['about'],
				'layouts' => $t['layouts'],
			);
		}
		$post = $post_id ? get_post( $post_id ) : null;
		$data = array(
			'rest'        => esc_url_raw( rest_url( DUI_Plus_Rest::NS . '/' ) ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'page'        => $page,
			'otherContent' => $post && ! $page['built'] && '' !== trim( $post->post_content ),
			'types'       => $types,
			'canBuild'    => DUI_Plus_Licence::can_build(),
			'licence'     => DUI_Plus_Licence::describe(),
			'canPublish'  => current_user_can( 'publish_pages' ),
			'canLook'     => current_user_can( 'edit_theme_options' ),
			'builder'     => DUI_Plus_Settings::builder(),
			'signature'   => DUI_Plus_Settings::signature(),
			'siteName'    => get_bloginfo( 'name' ),
			'themeActive' => DUI_Plus_Settings::theme_active(),
			'checkJs'     => self::check_js_url(),
			'back'        => admin_url( 'admin.php?page=distinctui-plus' ),
			'settings'    => admin_url( 'admin.php?page=dui-settings' ),
			'mcp'         => esc_url_raw( rest_url( 'distinctui-plus/mcp' ) ),
		);

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		$title = $page ? $page['title'] : __( 'New page', 'distinctui-plus' );
		?>
<!doctype html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title><?php echo esc_html( $title . ' · distinctUI+' ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( DUI_PLUS_URL . 'assets/canvas.css?ver=' . DUI_PLUS_VERSION ); ?>">
<?php if ( DUI_Plus_Settings::theme_active() ) : ?>
<?php
	ob_start();
	if ( function_exists( 'wp_print_font_faces' ) ) {
		wp_print_font_faces();
	}
	echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core's font face CSS.
?>
<?php endif; ?>
</head>
<body>
<div id="dui-app" aria-busy="true"><p class="dui-boot"><?php esc_html_e( 'Opening the canvas…', 'distinctui-plus' ); ?></p></div>
<noscript><p class="dui-boot"><?php esc_html_e( 'The canvas needs JavaScript. Your pages are safe; you can still edit them in the block editor.', 'distinctui-plus' ); ?></p></noscript>
<script id="dui-data" type="application/json"><?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ); ?></script>
<script type="module" src="<?php echo esc_url( DUI_PLUS_URL . 'assets/canvas.js?ver=' . DUI_PLUS_VERSION ); ?>"></script>
</body>
</html>
		<?php
		exit;
	}

	/**
	 * The shared checker: the copy bundled in the plugin zip, or the one
	 * served by distinctui.com when running from a checkout.
	 *
	 * @return string
	 */
	public static function check_js_url() {
		if ( is_readable( DUI_PLUS_DIR . 'assets/vendor/check.js' ) ) {
			return DUI_PLUS_URL . 'assets/vendor/check.js?ver=' . DUI_PLUS_VERSION;
		}
		return 'https://distinctui.com/assets/check.js';
	}
}
