<?php
/**
 * Plugin Name:       distinctUI+
 * Plugin URI:        https://distinctui.com
 * Description:       The page builder for the distinctUI theme. Type your content, pick one of three designs that all follow the rules, then edit right on the page. Your own Claude can build with you through the WordPress MCP Adapter. Pages are plain blocks, so they stay live if you stop paying.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Author:            distinctUI
 * Author URI:        https://distinctui.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       distinctui-plus
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DUI_PLUS_VERSION', '0.1.0' );
define( 'DUI_PLUS_FILE', __FILE__ );
define( 'DUI_PLUS_DIR', plugin_dir_path( __FILE__ ) );
define( 'DUI_PLUS_URL', plugin_dir_url( __FILE__ ) );

/*
 * Two halves.
 *
 * The free core always runs, paid or not: it renders and keeps every page
 * you built, takes form entries, applies the theme settings, and checks
 * pages against the rules. Stopping payment never takes a page down.
 *
 * The paid half is building: the canvas, design options, imports and the
 * abilities that change pages. It asks the licence first.
 */
require DUI_PLUS_DIR . 'includes/class-licence.php';
require DUI_PLUS_DIR . 'includes/class-settings.php';
require DUI_PLUS_DIR . 'includes/class-spec.php';
require DUI_PLUS_DIR . 'includes/class-blocks.php';
require DUI_PLUS_DIR . 'includes/class-render.php';
require DUI_PLUS_DIR . 'includes/class-check.php';
require DUI_PLUS_DIR . 'includes/class-compose.php';
require DUI_PLUS_DIR . 'includes/class-store.php';
require DUI_PLUS_DIR . 'includes/class-forms.php';
require DUI_PLUS_DIR . 'includes/class-rest.php';
require DUI_PLUS_DIR . 'includes/class-abilities.php';
require DUI_PLUS_DIR . 'includes/class-canvas.php';
require DUI_PLUS_DIR . 'includes/class-admin.php';
require DUI_PLUS_DIR . 'includes/class-compat.php';

add_action( 'plugins_loaded', array( 'DUI_Plus_Settings', 'init' ) );
add_action( 'plugins_loaded', array( 'DUI_Plus_Store', 'init' ) );
add_action( 'plugins_loaded', array( 'DUI_Plus_Forms', 'init' ) );
add_action( 'plugins_loaded', array( 'DUI_Plus_Rest', 'init' ) );
add_action( 'plugins_loaded', array( 'DUI_Plus_Abilities', 'init' ) );
add_action( 'plugins_loaded', array( 'DUI_Plus_Canvas', 'init' ) );
add_action( 'plugins_loaded', array( 'DUI_Plus_Admin', 'init' ) );
add_action( 'plugins_loaded', array( 'DUI_Plus_Compat', 'init' ) );

/**
 * Nothing to set up on activation beyond the entries post type, so the
 * permalinks don't need a flush. Remember when we were first switched on,
 * for the "your pages stay" promise in the admin.
 */
register_activation_hook(
	__FILE__,
	function () {
		if ( ! get_option( 'dui_plus_installed' ) ) {
			add_option( 'dui_plus_installed', time(), '', false );
		}
	}
);
