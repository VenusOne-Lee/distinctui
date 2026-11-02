<?php
/**
 * Theme settings: Appearance, distinctUI.
 *
 * Four choices, and nothing that can break the look: the ring palette,
 * your signature colour, what sits inside your mark, and how much room
 * the page breathes with (Airy or Compact). The typeface, greys, corners
 * and sizes are part of the system, so they aren't settings.
 *
 * Stored in one option, `distinctui_settings`. distinctUI+ reads and writes
 * the same option, so the theme and the builder never disagree.
 *
 * @package distinctui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The five ring palettes. The first colour is the signature colour.
 *
 * @return array<string, array{name: string, rings: string[]}>
 */
function distinctui_palettes() {
	return array(
		'signal'    => array( 'name' => 'Signal', 'rings' => array( '#1f3bff', '#ff4f1f', '#7a2cff', '#e8a800', '#000000' ) ),
		'mzansi'    => array( 'name' => 'Mzansi', 'rings' => array( '#e03c31', '#001489', '#ffb81c', '#007749', '#000000' ) ),
		'earth'     => array( 'name' => 'Earth', 'rings' => array( '#b5452b', '#c8860a', '#6b3e26', '#2e3a87', '#000000' ) ),
		'coast'     => array( 'name' => 'Coast', 'rings' => array( '#0067a5', '#ff5a4e', '#00706b', '#d9a400', '#000000' ) ),
		'jacaranda' => array( 'name' => 'Jacaranda', 'rings' => array( '#6f3fc7', '#c2185b', '#2f80ed', '#f26b1d', '#000000' ) ),
	);
}

/**
 * The settings with defaults filled in. An empty palette means "use what
 * the Site Editor's style variation says", so the settings screen never
 * fights a choice made there.
 *
 * @return array{palette: string, signature: string, initials: string, mark_image: int, density: string}
 */
function distinctui_get_settings() {
	$saved = get_option( 'distinctui_settings', array() );
	$saved = is_array( $saved ) ? $saved : array();
	return array_merge(
		array(
			'palette'    => '',
			'signature'  => '',
			'initials'   => '',
			'mark_image' => 0,
			'density'    => 'airy',
		),
		array_intersect_key( $saved, array_flip( array( 'palette', 'signature', 'initials', 'mark_image', 'density' ) ) )
	);
}

/**
 * Clean a settings array before it's saved. Used by the settings screen
 * and by distinctUI+.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function distinctui_sanitize_settings( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$palettes = distinctui_palettes();
	$out      = distinctui_get_settings();

	if ( isset( $input['palette'] ) ) {
		$out['palette'] = isset( $palettes[ $input['palette'] ] ) ? $input['palette'] : '';
	}
	if ( isset( $input['signature'] ) ) {
		$colour           = sanitize_hex_color( $input['signature'] );
		$out['signature'] = $colour ? strtolower( $colour ) : '';
	}
	if ( isset( $input['initials'] ) ) {
		// One to three letters. More than that and it isn't a mark any more.
		$out['initials'] = mb_substr( trim( wp_strip_all_tags( (string) $input['initials'] ) ), 0, 3 );
	}
	if ( isset( $input['mark_image'] ) ) {
		$id                = absint( $input['mark_image'] );
		$out['mark_image'] = ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
	}
	if ( isset( $input['density'] ) ) {
		$out['density'] = 'compact' === $input['density'] ? 'compact' : 'airy';
	}
	return $out;
}

/**
 * The signature colour actually in use: the one you picked, or your
 * palette's first ring.
 *
 * @return string Hex colour, or '' to leave the theme.json value alone.
 */
function distinctui_signature_colour() {
	$s = distinctui_get_settings();
	if ( $s['signature'] ) {
		return $s['signature'];
	}
	$palettes = distinctui_palettes();
	return $s['palette'] && isset( $palettes[ $s['palette'] ] ) ? $palettes[ $s['palette'] ]['rings'][0] : '';
}

/**
 * The CSS the settings add. Empty when nothing has been changed, so a fresh
 * install is exactly theme.json.
 *
 * `html body` outranks the `body` and `:root` rules global styles prints,
 * and custom properties set on body reach every block below it.
 *
 * @return string
 */
function distinctui_settings_css() {
	$s        = distinctui_get_settings();
	$palettes = distinctui_palettes();
	$vars     = array();

	if ( $s['palette'] && isset( $palettes[ $s['palette'] ] ) ) {
		foreach ( $palettes[ $s['palette'] ]['rings'] as $i => $colour ) {
			$vars[ '--wp--custom--ring--' . ( $i + 1 ) ] = $colour;
		}
	}
	$signature = distinctui_signature_colour();
	if ( $signature ) {
		$vars['--wp--custom--signature'] = $signature;
		$vars['--wp--custom--ring--1']   = $signature;
	}
	if ( 'compact' === $s['density'] ) {
		// Compact: sections 80px apart at most instead of 112px, and the big
		// headings one step down. Same rules, more on one screen.
		$vars['--wp--preset--spacing--80']       = 'clamp(3.5rem, 7vw, 5rem)';
		$vars['--wp--preset--font-size--hero']    = 'clamp(2.25rem, 1.6rem + 3vw, 3.75rem)';
		$vars['--wp--preset--font-size--page']    = 'clamp(2rem, 1.6rem + 2vw, 2.75rem)';
		$vars['--wp--preset--font-size--section'] = 'clamp(2rem, 1.6rem + 1.6vw, 2.5rem)';
	}

	if ( ! $vars ) {
		return '';
	}
	$css = '';
	foreach ( $vars as $name => $value ) {
		$css .= $name . ':' . $value . ';';
	}
	return 'html body{' . $css . '}';
}

/**
 * Print the settings CSS after the theme stylesheet, and in the editors.
 */
function distinctui_settings_enqueue() {
	$css = distinctui_settings_css();
	if ( $css ) {
		wp_add_inline_style( 'distinctui', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'distinctui_settings_enqueue', 20 );

/**
 * Same CSS inside the block editor's canvas.
 *
 * @param array $settings Editor settings.
 * @return array
 */
function distinctui_settings_editor_css( $settings ) {
	$css = distinctui_settings_css();
	if ( $css ) {
		$settings['styles'][] = array(
			'css'            => str_replace( 'html body', 'html body, .editor-styles-wrapper', $css ),
			'__unstableType' => 'theme',
			'isGlobalStyles' => false,
		);
	}
	return $settings;
}
add_filter( 'block_editor_settings_all', 'distinctui_settings_editor_css' );

/**
 * The settings screen.
 */
function distinctui_settings_menu() {
	add_theme_page(
		__( 'distinctUI settings', 'distinctui' ),
		__( 'distinctUI', 'distinctui' ),
		'edit_theme_options',
		'distinctui',
		'distinctui_settings_page'
	);
}
add_action( 'admin_menu', 'distinctui_settings_menu' );

/**
 * Register the option so options.php saves it, cleaned.
 */
function distinctui_settings_register() {
	register_setting(
		'distinctui',
		'distinctui_settings',
		array(
			'type'              => 'object',
			'sanitize_callback' => 'distinctui_sanitize_settings',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'distinctui_settings_register' );

/**
 * Media picker for the mark image, only on our screen.
 *
 * @param string $hook Current admin page.
 */
function distinctui_settings_assets( $hook ) {
	if ( 'appearance_page_distinctui' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'distinctui-admin', get_theme_file_uri( 'assets/css/admin.css' ), array(), DISTINCTUI_VERSION );
	wp_enqueue_script( 'distinctui-admin', get_theme_file_uri( 'assets/js/admin.js' ), array(), DISTINCTUI_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'distinctui_settings_assets' );

/**
 * Render the settings screen.
 */
function distinctui_settings_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$s        = distinctui_get_settings();
	$palettes = distinctui_palettes();
	$image    = $s['mark_image'] ? wp_get_attachment_image_url( $s['mark_image'], 'thumbnail' ) : '';
	$colour   = distinctui_signature_colour() ? distinctui_signature_colour() : '#1f3bff';
	?>
	<div class="wrap dui-settings">
		<h1><?php esc_html_e( 'distinctUI', 'distinctui' ); ?></h1>
		<p class="dui-lede"><?php esc_html_e( 'Black, white, grey. Then one mark that\'s yours. These are the only choices the look has, so nothing here can make your site look wrong.', 'distinctui' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'distinctui' ); ?>

			<section class="dui-block">
				<h2><?php esc_html_e( 'Your mark', 'distinctui' ); ?></h2>
				<p class="dui-hint"><?php esc_html_e( 'The ring is the only place your colour shows. Put your initials or a picture inside it.', 'distinctui' ); ?></p>
				<div class="dui-mark-row">
					<span class="dui-mark-preview" style="--mark-colour: <?php echo esc_attr( $colour ); ?>">
						<?php if ( $image ) : ?>
							<img src="<?php echo esc_url( $image ); ?>" alt="">
						<?php else : ?>
							<span><?php echo esc_html( $s['initials'] ? $s['initials'] : mb_substr( get_bloginfo( 'name' ), 0, 1 ) ); ?></span>
						<?php endif; ?>
					</span>
					<div class="dui-fields">
						<label for="dui-initials"><?php esc_html_e( 'Initials', 'distinctui' ); ?>
							<input id="dui-initials" name="distinctui_settings[initials]" value="<?php echo esc_attr( $s['initials'] ); ?>" maxlength="3" placeholder="<?php echo esc_attr( mb_substr( get_bloginfo( 'name' ), 0, 1 ) ); ?>">
						</label>
						<input type="hidden" id="dui-mark-image" name="distinctui_settings[mark_image]" value="<?php echo esc_attr( (string) $s['mark_image'] ); ?>">
						<p class="dui-actions">
							<button type="button" class="dui-pill-ghost" data-dui-pick-image><?php esc_html_e( 'Use a picture', 'distinctui' ); ?></button>
							<button type="button" class="dui-text-link" data-dui-clear-image <?php echo $image ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove the picture', 'distinctui' ); ?></button>
						</p>
					</div>
				</div>
			</section>

			<section class="dui-block">
				<h2><?php esc_html_e( 'Ring palette', 'distinctui' ); ?></h2>
				<p class="dui-hint"><?php esc_html_e( 'Five colours for rings: yours first, then four for people or projects. "Keep the Site Editor\'s choice" uses whatever you picked under Appearance, Editor, Styles.', 'distinctui' ); ?></p>
				<div class="dui-palettes" role="radiogroup" aria-label="<?php esc_attr_e( 'Ring palette', 'distinctui' ); ?>">
					<label class="dui-palette">
						<input type="radio" name="distinctui_settings[palette]" value="" <?php checked( '', $s['palette'] ); ?>>
						<span class="dui-palette-name"><?php esc_html_e( 'Keep the Site Editor\'s choice', 'distinctui' ); ?></span>
					</label>
					<?php foreach ( $palettes as $slug => $p ) : ?>
						<label class="dui-palette">
							<input type="radio" name="distinctui_settings[palette]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $slug, $s['palette'] ); ?>>
							<span class="dui-rings" aria-hidden="true">
								<?php foreach ( $p['rings'] as $ring ) : ?>
									<i style="--ring: <?php echo esc_attr( $ring ); ?>"></i>
								<?php endforeach; ?>
							</span>
							<span class="dui-palette-name"><?php echo esc_html( $p['name'] ); ?><?php echo 'mzansi' === $slug ? ' <small>' . esc_html__( 'credit: Version One', 'distinctui' ) . '</small>' : ''; ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="dui-block">
				<h2><?php esc_html_e( 'Your own colour', 'distinctui' ); ?></h2>
				<p class="dui-hint"><?php esc_html_e( 'Optional. Replaces the palette\'s first ring with your colour. It still only appears on rings.', 'distinctui' ); ?></p>
				<label for="dui-signature" class="dui-inline">
					<input type="color" id="dui-signature-picker" value="<?php echo esc_attr( $colour ); ?>" aria-label="<?php esc_attr_e( 'Pick a colour', 'distinctui' ); ?>">
					<input id="dui-signature" name="distinctui_settings[signature]" value="<?php echo esc_attr( $s['signature'] ); ?>" placeholder="<?php esc_attr_e( 'Use the palette', 'distinctui' ); ?>" pattern="#[0-9a-fA-F]{6}">
				</label>
			</section>

			<section class="dui-block">
				<h2><?php esc_html_e( 'Room', 'distinctui' ); ?></h2>
				<p class="dui-hint"><?php esc_html_e( 'Airy gives each section lots of white space. Compact fits more on a screen, for shops and long pages.', 'distinctui' ); ?></p>
				<div class="dui-choices">
					<label><input type="radio" name="distinctui_settings[density]" value="airy" <?php checked( 'airy', $s['density'] ); ?>> <b><?php esc_html_e( 'Airy', 'distinctui' ); ?></b> <span><?php esc_html_e( 'Sections up to 112px apart, big headings', 'distinctui' ); ?></span></label>
					<label><input type="radio" name="distinctui_settings[density]" value="compact" <?php checked( 'compact', $s['density'] ); ?>> <b><?php esc_html_e( 'Compact', 'distinctui' ); ?></b> <span><?php esc_html_e( 'Sections up to 80px apart, headings one size smaller', 'distinctui' ); ?></span></label>
				</div>
			</section>

			<section class="dui-block dui-locked">
				<h2><?php esc_html_e( 'Kept the same on purpose', 'distinctui' ); ?></h2>
				<p class="dui-hint"><?php esc_html_e( 'One typeface (Bricolage Grotesque), black, white and four greys, black pill buttons, light only. These are the look, so they aren\'t settings.', 'distinctui' ); ?></p>
			</section>

			<p><button type="submit" class="dui-pill"><?php esc_html_e( 'Save', 'distinctui' ); ?></button></p>
		</form>
	</div>
	<?php
}
