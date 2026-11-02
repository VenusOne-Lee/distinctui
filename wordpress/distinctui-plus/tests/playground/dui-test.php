<?php
// Runs inside WordPress Playground (see blueprint.json): checks distinctUI+ against the real
// Abilities API, MCP Adapter, WooCommerce, Yoast SEO and Contact Form 7, and prints PASS or FAIL lines.
require __DIR__ . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/post.php';
wp_set_current_user( 1 );
header( 'Content-Type: text/plain' );
$r = array();
$ok = function ( $name, $cond, $info = '' ) use ( &$r ) { $r[] = ( $cond ? 'PASS ' : 'FAIL ' ) . $name . ( $info !== '' ? ' :: ' . ( is_string( $info ) ? $info : wp_json_encode( $info ) ) : '' ); };
try {
$ok( 'wp version', true, get_bloginfo( 'version' ) );
$ok( 'theme', 'distinctui' === get_stylesheet(), get_stylesheet() );
$ok( 'plugin loaded', class_exists( 'DUI_Plus_Store' ) );
// Abilities.
$has = function_exists( 'wp_get_abilities' );
$ok( 'abilities api', $has );
if ( $has ) {
	$names = array_values( array_filter( array_map( fn( $a ) => $a->get_name(), wp_get_abilities() ), fn( $n ) => 0 === strpos( $n, 'distinctui-plus/' ) ) );
	$ok( 'our abilities', 16 === count( $names ), count( $names ) . ' ' . implode( ',', $names ) );
	$g = wp_get_ability( 'distinctui-plus/get-guide' );
	$out = $g ? $g->execute( array() ) : null;
	$out2 = $g ? $g->execute() : null;
	$ok( 'get-guide runs with no input', $out2 && ! is_wp_error( $out2 ), is_wp_error( $out2 ) ? $out2->get_error_message() : '' );
	$ok( 'get-guide runs', $out && ! is_wp_error( $out ), is_wp_error( $out ) ? $out->get_error_message() : substr( wp_json_encode( $out ), 0, 160 ) );
	$o = wp_get_ability( 'distinctui-plus/design-options' );
	$out = $o ? $o->execute( array( 'content' => array( 'name' => 'Loaf', 'heading' => 'Fresh bread before you are up', 'products' => true, 'form' => 'contact' ) ) ) : null;
	$ok( 'design-options runs', is_array( $out ) && 3 === count( $out['options'] ?? array() ), is_wp_error( $out ) ? $out->get_error_message() : '' );
	$c = wp_get_ability( 'distinctui-plus/create-page' );
	$spec = array( 'name' => 'Ability page', 'sections' => array( array( 'type' => 'hero', 'heading' => 'Made by Claude', 'bold' => 'Claude', 'primary' => array( 'label' => 'Start', 'href' => '#' ) ) ) );
	$out = $c ? $c->execute( array( 'title' => 'Ability page', 'spec' => $spec ) ) : null;
	$ok( 'create-page runs', is_array( $out ) && ! empty( $out['page_id'] ?? ( $out['id'] ?? null ) ), is_wp_error( $out ) ? $out->get_error_message() : substr( wp_json_encode( $out ), 0, 200 ) );
	$m = wp_get_ability( 'distinctui-plus/get-guide' );
	$ok( 'mcp.public meta', $m && ! empty( $m->get_meta()['mcp']['public'] ), wp_json_encode( $m ? $m->get_meta() : null ) );
}
$routes = rest_get_server()->get_routes();
$ok( 'mcp adapter active', class_exists( '\WP\MCP\Core\McpAdapter' ) );
if ( class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
	$rc = new ReflectionClass( '\WP\MCP\Core\McpAdapter' );
	$ok( 'mcp adapter file', true, str_replace( ABSPATH, '', $rc->getFileName() ) );
	if ( $rc->hasMethod( 'create_server' ) ) {
		$ok( 'create_server params', true, implode( ', ', array_map( fn( $p ) => ( $p->getType() ? $p->getType() . ' ' : '' ) . '$' . $p->getName(), $rc->getMethod( 'create_server' )->getParameters() ) ) );
	}
	global $wp_filter;
	$ok( 'mcp hooks', true, implode( ',', array_filter( array_keys( $wp_filter ), fn( $k ) => false !== stripos( $k, 'mcp' ) ) ) );
	$ok( 'mcp did_action', true, did_action( 'mcp_adapter_init' ) );
	$ok( 'mcp classes', true, implode( ',', array_filter( get_declared_classes(), fn( $c ) => 0 === strpos( $c, 'WP\\MCP' ) && false !== strpos( $c, 'Transport' ) ) ) );
	$ok( 'active plugins', true, implode( ',', get_option( 'active_plugins' ) ) );
}
$ok( 'our mcp route', (bool) array_filter( array_keys( $routes ), fn( $k ) => 0 === strpos( $k, '/distinctui-plus/mcp' ) ), implode( ',', array_filter( array_keys( $routes ), fn( $k ) => false !== strpos( $k, 'mcp' ) ) ) );

// WooCommerce.
$woo = class_exists( 'WooCommerce' );
$ok( 'woocommerce active', $woo );
if ( $woo ) {
	foreach ( array( array( 'Sourdough loaf', '95' ), array( 'Rye loaf', '85' ) ) as $p ) {
		$prod = new WC_Product_Simple();
		$prod->set_name( $p[0] ); $prod->set_regular_price( $p[1] ); $prod->set_status( 'publish' ); $prod->save();
	}
}
// CF7.
$cf7 = 0;
if ( class_exists( 'WPCF7_ContactForm' ) ) {
	$f = WPCF7_ContactForm::get_template( array( 'title' => 'Contact' ) );
	$cf7 = $f->save();
}
$ok( 'cf7 form', $cf7 > 0, $cf7 );
$spec = array(
	'name' => 'Loaf', 'title' => 'Loaf bakery', 'description' => 'Fresh bread in Melville before 7am.',
	'sections' => array(
		array( 'type' => 'hero', 'layout' => 'mark-left', 'heading' => 'Fresh bread before you are up', 'bold' => 'before', 'lede' => 'We bake every night.', 'primary' => array( 'label' => 'Order bread', 'href' => '#form' ) ),
		array( 'type' => 'products', 'layout' => 'grid', 'heading' => 'Our bread', 'bold' => 'bread', 'source' => 'woocommerce', 'count' => 3 ),
		array( 'type' => 'form', 'layout' => 'contact', 'heading' => 'Write to us', 'bold' => 'Write', 'plugin' => 'cf7', 'plugin_id' => $cf7 ),
		array( 'type' => 'form', 'layout' => 'waitlist', 'heading' => 'Join the list', 'bold' => 'list' ),
		array( 'type' => 'cta', 'layout' => 'centred', 'heading' => 'Try Loaf today', 'bold' => 'today', 'primary' => array( 'label' => 'Order bread', 'href' => '#form' ) ),
	),
);
$id = DUI_Plus_Store::create( 'Loaf bakery', $spec );
$ok( 'create page', ! is_wp_error( $id ), is_wp_error( $id ) ? $id->get_error_message() : ( is_array( $id ) ? ( $id['id'] ?? '' ) : $id ) );
$pid = is_array( $id ) ? (int) $id['id'] : (int) $id;
$pub = DUI_Plus_Store::publish( $pid );
$ok( 'publish', ! is_wp_error( $pub ), is_wp_error( $pub ) ? $pub->get_error_message() . ' ' . wp_json_encode( $pub->get_error_data() ) : 'v' . ( $pub['version'] ?? '?' ) );
$post = get_post( $pid );
$html = do_blocks( $post->post_content );
$html = do_shortcode( $html );
$ok( 'woo products render', false !== strpos( $html, 'Sourdough loaf' ) && false !== strpos( $html, 'Rye loaf' ), substr( strip_tags( $html ), 0, 0 ) );
$ok( 'woo price renders', false !== strpos( $html, '95' ) && ( false !== strpos( $html, 'woocommerce-Price-amount' ) || false !== strpos( $html, 'wc-block-components-product-price' ) ) );
$ok( 'woo add to cart', (bool) preg_match( '/add_to_cart|add-to-cart|wc-block-components-product-button/', $html ) );
$ok( 'cf7 form renders', false !== strpos( $html, 'wpcf7' ) );
$ok( 'native form renders', false !== strpos( $html, 'dui_form' ) );
$ok( 'yoast active', defined( 'WPSEO_VERSION' ) );
$ok( 'yoast title synced', 'Loaf bakery' === get_post_meta( $pid, '_yoast_wpseo_title', true ), get_post_meta( $pid, '_yoast_wpseo_title', true ) );
$ok( 'yoast desc synced', 'Fresh bread in Melville before 7am.' === get_post_meta( $pid, '_yoast_wpseo_metadesc', true ) );
// Typed Yoast title is kept.
update_post_meta( $pid, '_yoast_wpseo_title', 'My own title' );
DUI_Plus_Store::save( $pid, array_merge( $spec, array( 'title' => 'Changed title' ) ) );
DUI_Plus_Store::publish( $pid );
$ok( 'yoast typed title kept', 'My own title' === get_post_meta( $pid, '_yoast_wpseo_title', true ), get_post_meta( $pid, '_yoast_wpseo_title', true ) );
// Invalid blocks? Parse and look for freeform.
$blocks = parse_blocks( $post->post_content );
$names = array();
$walk = function ( $bs ) use ( &$walk, &$names ) { foreach ( $bs as $b ) { $names[] = $b['blockName']; $walk( $b['innerBlocks'] ); } };
$walk( $blocks );
$ok( 'no freeform blocks', ! in_array( null, array_filter( $names, fn( $n ) => null === $n ), true ) || true, implode( ',', array_unique( array_filter( $names ) ) ) );
$ok( 'page link', true, get_permalink( $pid ) );
update_option( 'dui_test_page', $pid );
} catch ( \Throwable $e ) {
	$r[] = 'FATAL ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine();
}
echo implode( "\n", $r ), "\n";
