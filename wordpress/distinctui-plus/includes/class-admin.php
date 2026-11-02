<?php
/**
 * The distinctUI+ menu: your pages, form entries and settings, plus
 * "Edit with distinctUI+" links where you'd look for them.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screens and links.
 */
class DUI_Plus_Admin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_dui_licence', array( __CLASS__, 'save_licence' ) );
		add_filter( 'page_row_actions', array( __CLASS__, 'row_action' ), 10, 2 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 80 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_filter( 'display_post_states', array( __CLASS__, 'post_state' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( DUI_PLUS_FILE ), array( __CLASS__, 'plugin_links' ) );
	}

	/**
	 * The menu.
	 */
	public static function menu() {
		$ring = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="10" r="7" fill="none" stroke="#a7aaad" stroke-width="2.5"/><path d="M10 6.5v7M6.5 10h7" stroke="#a7aaad" stroke-width="1.8"/></svg>' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		add_menu_page( __( 'distinctUI+', 'distinctui-plus' ), __( 'distinctUI+', 'distinctui-plus' ), 'edit_pages', 'distinctui-plus', array( __CLASS__, 'pages_screen' ), $ring, 21 );
		add_submenu_page( 'distinctui-plus', __( 'Your pages', 'distinctui-plus' ), __( 'Your pages', 'distinctui-plus' ), 'edit_pages', 'distinctui-plus', array( __CLASS__, 'pages_screen' ) );
		add_submenu_page( 'distinctui-plus', __( 'distinctUI+ settings', 'distinctui-plus' ), __( 'Settings', 'distinctui-plus' ), 'manage_options', 'dui-settings', array( __CLASS__, 'settings_screen' ) );
	}

	/**
	 * Admin styles for our screens.
	 *
	 * @param string $hook Screen hook.
	 */
	public static function assets( $hook ) {
		if ( false !== strpos( $hook, 'distinctui-plus' ) || false !== strpos( $hook, 'dui-settings' ) ) {
			wp_enqueue_style( 'dui-plus-admin', DUI_PLUS_URL . 'assets/admin.css', array(), DUI_PLUS_VERSION );
		}
	}

	/**
	 * Your pages.
	 */
	public static function pages_screen() {
		$pages = DUI_Plus_Store::list_pages( 100 );
		?>
		<div class="wrap dui-admin">
			<h1 class="dui-title"><?php esc_html_e( 'Your', 'distinctui-plus' ); ?> <b><?php esc_html_e( 'pages', 'distinctui-plus' ); ?></b></h1>
			<p class="dui-lede"><?php echo esc_html( DUI_Plus_Licence::describe() ); ?></p>
			<p><a class="dui-pill" href="<?php echo esc_url( DUI_Plus_Canvas::url() ); ?>"><?php esc_html_e( 'Make a page', 'distinctui-plus' ); ?></a></p>
			<?php if ( ! $pages ) : ?>
				<div class="dui-panel">
					<p><b><?php esc_html_e( 'No pages yet.', 'distinctui-plus' ); ?></b> <?php esc_html_e( 'Type your content, pick one of three designs, then edit on the page. Or ask your Claude to build one.', 'distinctui-plus' ); ?></p>
				</div>
			<?php else : ?>
				<ul class="dui-rows">
					<?php foreach ( $pages as $p ) : ?>
						<li>
							<span class="dui-dot <?php echo $p['live'] ? 'is-live' : ''; ?>" aria-hidden="true"></span>
							<span class="dui-row-main">
								<a href="<?php echo esc_url( DUI_Plus_Canvas::url( $p['id'] ) ); ?>"><b><?php echo esc_html( $p['title'] ? $p['title'] : __( '(no title)', 'distinctui-plus' ) ); ?></b></a>
								<span class="dui-meta">
									<?php
									if ( $p['live'] ) {
										/* translators: %s: version label. */
										echo esc_html( sprintf( __( 'Live, %s', 'distinctui-plus' ), $p['version'] ) );
										if ( $p['has_draft'] ) {
											echo ' · ' . esc_html__( 'changes not published yet', 'distinctui-plus' );
										}
									} else {
										esc_html_e( 'Not live yet', 'distinctui-plus' );
									}
									?>
								</span>
							</span>
							<?php if ( $p['live'] ) : ?>
								<a class="dui-text-link" href="<?php echo esc_url( $p['link'] ); ?>"><?php esc_html_e( 'View', 'distinctui-plus' ); ?></a>
							<?php endif; ?>
							<a class="dui-pill-ghost" href="<?php echo esc_url( DUI_Plus_Canvas::url( $p['id'] ) ); ?>"><?php esc_html_e( 'Open', 'distinctui-plus' ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Is the MCP Adapter here?
	 *
	 * @return bool
	 */
	private static function adapter_active() {
		return class_exists( '\WP\MCP\Core\McpAdapter' ) || defined( 'WP_MCP_VERSION' ) || did_action( 'mcp_adapter_init' );
	}

	/**
	 * Settings.
	 */
	public static function settings_screen() {
		$b       = DUI_Plus_Settings::builder();
		$l       = DUI_Plus_Licence::get();
		$msg     = isset( $_GET['dui_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['dui_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$mcp     = rest_url( 'distinctui-plus/mcp' );
		$user    = wp_get_current_user();
		$config  = array(
			'mcpServers' => array(
				'my-site' => array(
					'command' => 'npx',
					'args'    => array( '-y', '@automattic/mcp-wordpress-remote@latest' ),
					'env'     => array(
						'WP_API_URL'      => $mcp,
						'WP_API_USERNAME' => $user->user_login,
						'WP_API_PASSWORD' => __( 'your application password', 'distinctui-plus' ),
					),
				),
			),
		);
		?>
		<div class="wrap dui-admin">
			<h1 class="dui-title"><?php esc_html_e( 'distinctUI+', 'distinctui-plus' ); ?> <b><?php esc_html_e( 'settings', 'distinctui-plus' ); ?></b></h1>
			<?php if ( $msg ) : ?>
				<p class="dui-status" role="status"><?php echo esc_html( $msg ); ?></p>
			<?php endif; ?>

			<section class="dui-block">
				<h2><?php esc_html_e( 'Licence', 'distinctui-plus' ); ?></h2>
				<p class="dui-hint"><?php echo esc_html( DUI_Plus_Licence::describe() ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dui-inline">
					<input type="hidden" name="action" value="dui_licence">
					<?php wp_nonce_field( 'dui_licence' ); ?>
					<label for="dui-key" class="screen-reader-text"><?php esc_html_e( 'Licence key', 'distinctui-plus' ); ?></label>
					<input id="dui-key" name="key" value="<?php echo esc_attr( $l['key'] ); ?>" placeholder="DUI-AB12-CD34-EF56-GH78" autocomplete="off" spellcheck="false">
					<button class="dui-pill" type="submit"><?php echo $l['key'] ? esc_html__( 'Update the key', 'distinctui-plus' ) : esc_html__( 'Turn on building', 'distinctui-plus' ); ?></button>
				</form>
				<p class="dui-hint"><?php esc_html_e( 'Your pages are plain WordPress blocks. If the licence ends, they stay live and keep taking form entries; only building and updates stop.', 'distinctui-plus' ); ?></p>
			</section>

			<section class="dui-block">
				<h2><?php esc_html_e( 'Your look', 'distinctui-plus' ); ?></h2>
				<p class="dui-hint">
					<?php
					if ( DUI_Plus_Settings::theme_active() ) {
						esc_html_e( 'Your mark, its colour and Airy or Compact are set once for the whole site, in the theme.', 'distinctui-plus' );
						echo ' <a href="' . esc_url( admin_url( 'themes.php?page=distinctui' ) ) . '">' . esc_html__( 'Open Appearance, distinctUI', 'distinctui-plus' ) . '</a>';
					} else {
						esc_html_e( 'distinctUI+ builds pages for the free distinctUI theme. Install it under Appearance, Themes to see pages in the look.', 'distinctui-plus' );
					}
					?>
				</p>
			</section>

			<form method="post" action="options.php">
				<?php settings_fields( 'dui_plus' ); ?>
				<section class="dui-block">
					<h2><?php esc_html_e( 'Building', 'distinctui-plus' ); ?></h2>
					<div class="dui-choices">
						<label><input type="checkbox" name="<?php echo esc_attr( DUI_Plus_Settings::OPTION ); ?>[phone_beside]" value="1" <?php checked( $b['phone_beside'] ); ?>> <b><?php esc_html_e( 'Show the phone beside the desktop', 'distinctui-plus' ); ?></b> <span><?php esc_html_e( 'Most people see your site on a phone first.', 'distinctui-plus' ); ?></span></label>
						<label><input type="radio" name="<?php echo esc_attr( DUI_Plus_Settings::OPTION ); ?>[start]" value="content" <?php checked( 'content', $b['start'] ); ?>> <b><?php esc_html_e( 'New pages start from your content', 'distinctui-plus' ); ?></b> <span><?php esc_html_e( 'Type it once, pick one of three designs.', 'distinctui-plus' ); ?></span></label>
						<label><input type="radio" name="<?php echo esc_attr( DUI_Plus_Settings::OPTION ); ?>[start]" value="blank" <?php checked( 'blank', $b['start'] ); ?>> <b><?php esc_html_e( 'New pages start with a hero only', 'distinctui-plus' ); ?></b> <span><?php esc_html_e( 'Add sections yourself from the left.', 'distinctui-plus' ); ?></span></label>
					</div>
					<p>
						<label for="dui-versions"><?php esc_html_e( 'Versions kept per page', 'distinctui-plus' ); ?></label><br>
						<input id="dui-versions" type="number" min="5" max="100" name="<?php echo esc_attr( DUI_Plus_Settings::OPTION ); ?>[keep_versions]" value="<?php echo esc_attr( (string) $b['keep_versions'] ); ?>">
					</p>
				</section>
				<section class="dui-block">
					<h2><?php esc_html_e( 'Forms', 'distinctui-plus' ); ?></h2>
					<p class="dui-hint"><?php esc_html_e( 'Every answer is kept under distinctUI+, Form entries, and emailed here.', 'distinctui-plus' ); ?></p>
					<label for="dui-form-email"><?php esc_html_e( 'Send answers to', 'distinctui-plus' ); ?></label><br>
					<input id="dui-form-email" type="email" name="<?php echo esc_attr( DUI_Plus_Settings::OPTION ); ?>[form_email]" value="<?php echo esc_attr( $b['form_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
				</section>
				<p><button type="submit" class="dui-pill"><?php esc_html_e( 'Save', 'distinctui-plus' ); ?></button></p>
			</form>

			<section class="dui-block">
				<h2><?php esc_html_e( 'Build with your Claude', 'distinctui-plus' ); ?></h2>
				<p class="dui-hint"><?php esc_html_e( 'Claude (or any AI that speaks MCP) can build pages here, signed in as you. It uses your own Claude plan, so there are no AI credits to buy. It can only use distinctUI+ tools, so every change follows the rules.', 'distinctui-plus' ); ?></p>
				<ol class="dui-steps">
					<li>
						<?php if ( self::adapter_active() ) : ?>
							<b><?php esc_html_e( 'The WordPress MCP Adapter is on.', 'distinctui-plus' ); ?></b>
						<?php else : ?>
							<?php esc_html_e( 'Install and turn on the WordPress MCP Adapter plugin (from github.com/WordPress/mcp-adapter).', 'distinctui-plus' ); ?>
						<?php endif; ?>
					</li>
					<li>
						<?php esc_html_e( 'Make an application password for yourself:', 'distinctui-plus' ); ?>
						<a href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>"><?php esc_html_e( 'Users, Profile, Application passwords', 'distinctui-plus' ); ?></a>.
					</li>
					<li>
						<?php esc_html_e( 'Add this to Claude (Settings, Developer, Edit config), with your application password:', 'distinctui-plus' ); ?>
						<pre class="dui-code"><?php echo esc_html( wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
					</li>
					<li><?php esc_html_e( 'Ask Claude: "Use distinctUI+ to make a landing page for my bakery." It reads the guide first, then builds a draft you can open here.', 'distinctui-plus' ); ?></li>
				</ol>
			</section>
		</div>
		<?php
	}

	/**
	 * Save the licence key.
	 */
	public static function save_licence() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You can\'t change the licence.', 'distinctui-plus' ), 403 );
		}
		check_admin_referer( 'dui_licence' );
		$r = DUI_Plus_Licence::verify( isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '' );
		wp_safe_redirect( add_query_arg( 'dui_msg', rawurlencode( $r['message'] ), admin_url( 'admin.php?page=dui-settings' ) ) );
		exit;
	}

	/**
	 * "Edit with distinctUI+" on the Pages list.
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post    Page.
	 * @return array
	 */
	public static function row_action( $actions, $post ) {
		if ( current_user_can( 'edit_post', $post->ID ) && in_array( $post->post_type, DUI_Plus_Store::post_types(), true ) ) {
			$label = DUI_Plus_Store::is_built( $post->ID ) ? __( 'Edit with distinctUI+', 'distinctui-plus' ) : __( 'Rebuild with distinctUI+', 'distinctui-plus' );
			$actions = array_merge( array( 'dui' => '<a href="' . esc_url( DUI_Plus_Canvas::url( $post->ID ) ) . '">' . esc_html( $label ) . '</a>' ), $actions );
		}
		return $actions;
	}

	/**
	 * "distinctUI+" next to built pages in the Pages list.
	 *
	 * @param string[] $states States.
	 * @param WP_Post  $post   Page.
	 * @return string[]
	 */
	public static function post_state( $states, $post ) {
		if ( DUI_Plus_Store::is_built( $post->ID ) ) {
			$states['dui'] = '' !== (string) get_post_meta( $post->ID, DUI_Plus_Store::META_DRAFT, true )
				? __( 'distinctUI+, changes not published', 'distinctui-plus' )
				: __( 'distinctUI+', 'distinctui-plus' );
		}
		return $states;
	}

	/**
	 * "Edit with distinctUI+" in the toolbar on built pages.
	 *
	 * @param WP_Admin_Bar $bar Toolbar.
	 */
	public static function admin_bar( $bar ) {
		if ( is_admin() || ! is_singular() ) {
			return;
		}
		$id = get_queried_object_id();
		if ( $id && DUI_Plus_Store::is_built( $id ) && current_user_can( 'edit_post', $id ) ) {
			$bar->add_node(
				array(
					'id'    => 'dui-plus',
					'title' => __( 'Edit with distinctUI+', 'distinctui-plus' ),
					'href'  => DUI_Plus_Canvas::url( $id ),
				)
			);
		}
	}

	/**
	 * Settings link on the Plugins screen.
	 *
	 * @param string[] $links Links.
	 * @return string[]
	 */
	public static function plugin_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=dui-settings' ) ) . '">' . esc_html__( 'Settings', 'distinctui-plus' ) . '</a>' );
		return $links;
	}

	/**
	 * Say plainly when the theme isn't active, on our screens and Plugins.
	 */
	public static function notices() {
		$screen = get_current_screen();
		if ( ! $screen || DUI_Plus_Settings::theme_active() || ! current_user_can( 'switch_themes' ) ) {
			return;
		}
		if ( 'plugins' !== $screen->id && false === strpos( $screen->id, 'distinctui-plus' ) && false === strpos( $screen->id, 'dui-settings' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><b>' . esc_html__( 'distinctUI+ builds pages for the free distinctUI theme.', 'distinctui-plus' ) . '</b> '
			. esc_html__( 'Install and activate it so your pages look right.', 'distinctui-plus' )
			. ' <a href="' . esc_url( 'https://distinctui.com/distinctui-wordpress.zip' ) . '">' . esc_html__( 'Download the theme', 'distinctui-plus' ) . '</a></p></div>';
	}
}
