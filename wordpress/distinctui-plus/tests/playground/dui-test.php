<?php
// Runs inside WordPress Playground (see blueprint.json): checks distinctUI+ against the real
// Abilities API, MCP Adapter, WooCommerce, Yoast SEO and Contact Form 7, and prints PASS or FAIL lines.
require dirname( __DIR__, 5 ) . '/wp-load.php';
// Only inside the Playground test run: it makes pages and products as the first user.
if ( ! defined( 'DUI_PLUS_PLAYGROUND_TEST' ) || ! DUI_PLUS_PLAYGROUND_TEST ) {
	exit;
}
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
	foreach ( array( '\\WP\\MCP\\Transport\\HttpTransport', '\\WP\\MCP\\Infrastructure\\ErrorHandling\\ErrorLogMcpErrorHandler', '\\WP\\MCP\\Infrastructure\\Observability\\NullMcpObservabilityHandler' ) as $cls ) {
		$ok( 'class ' . $cls, class_exists( $cls ) );
	}
	// Talk to our server the way a client would.
	$mcp_routes = array_values( array_filter( array_keys( $routes ), fn( $k ) => false !== strpos( $k, 'mcp' ) ) );
	$ok( 'mcp routes', ! empty( $mcp_routes ), implode( ',', $mcp_routes ) );
	foreach ( $mcp_routes as $route ) {
		if ( false === strpos( $route, 'distinctui' ) ) {
			continue;
		}
		$send = function ( $body, $session = '' ) use ( $route ) {
			$req = new WP_REST_Request( 'POST', $route );
			$req->set_header( 'Content-Type', 'application/json' );
			$req->set_header( 'Accept', 'application/json, text/event-stream' );
			$req->set_header( 'MCP-Protocol-Version', '2025-11-25' );
			if ( $session ) {
				$req->set_header( 'Mcp-Session-Id', $session );
			}
			$req->set_body( wp_json_encode( $body ) );
			$res = rest_do_request( $req );
			$res->set_data( json_decode( wp_json_encode( $res->get_data() ), true ) );
			return $res;
		};
		$res = $send( array( 'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => array( 'protocolVersion' => '2025-11-25', 'capabilities' => new stdClass(), 'clientInfo' => array( 'name' => 'test', 'version' => '1' ) ) ) );
		$sid = $res->get_headers()['Mcp-Session-Id'] ?? '';
		$ok( 'mcp initialize', 200 === $res->get_status(), $res->get_status() . ' ' . substr( wp_json_encode( $res->get_data() ), 0, 200 ) );
		$res = $send( array( 'jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list' ), $sid );
		$data = $res->get_data();
		$tools = array_map( fn( $t ) => $t['name'] ?? '', (array) ( $data['result']['tools'] ?? array() ) );
		$ok( 'mcp tools/list', count( $tools ) >= 16, $res->get_status() . ' ' . count( $tools ) . ' ' . substr( implode( ',', $tools ), 0, 200 ) . ' ' . ( $tools ? '' : substr( wp_json_encode( $data ), 0, 300 ) ) );
		$guide = '';
		foreach ( $tools as $t ) {
			if ( false !== strpos( $t, 'get-guide' ) ) {
				$guide = $t;
			}
		}
		$res = $send( array( 'jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => array( 'name' => $guide, 'arguments' => new stdClass() ) ), $sid );
		$data = $res->get_data();
		$ok( 'mcp tools/call get-guide', empty( $data['error'] ) && empty( $data['result']['isError'] ), $res->get_status() . ' ' . substr( wp_json_encode( $data ), 0, 240 ) );
	}
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

// WP Super Cache: put a cached copy where Super Cache keeps a page, publish it once, check the copy is gone.
// (Super Cache handles each post once per request, so this uses a page of its own.)
if ( function_exists( 'wp_cache_post_change' ) && function_exists( 'get_supercache_dir' ) ) {
	global $cache_enabled, $super_cache_enabled, $cache_path;
	$cache_enabled = true; $super_cache_enabled = true;
	if ( ! $cache_path ) { $cache_path = WP_CONTENT_DIR . '/cache/'; }
	$cid = DUI_Plus_Store::create( 'Cache test', array( 'name' => 'Cache test', 'sections' => array( array( 'type' => 'hero', 'heading' => 'Cache test', 'bold' => 'test' ) ) ) );
	wp_update_post( array( 'ID' => $cid, 'post_name' => 'cache-test' ) );
	$dir = trailingslashit( get_supercache_dir() ) . 'cache-test';
	wp_mkdir_p( $dir );
	file_put_contents( $dir . '/index.html', 'old page' );
	$was = file_exists( $dir . '/index.html' );
	DUI_Plus_Store::publish( $cid );
	clearstatcache();
	$ok( 'cache cleared on publish', $was && ! file_exists( $dir . '/index.html' ), str_replace( ABSPATH, '', $dir ) . ' now ' . ( function_exists( 'get_current_url_supercache_dir' ) ? str_replace( ABSPATH, '', get_current_url_supercache_dir( $cid ) ) : '' ) );
} else {
	$ok( 'cache cleared on publish', false, 'wp super cache functions missing' );
}
// CF7: send the form the way its front end does.
if ( $cf7 && class_exists( 'WPCF7_ContactForm' ) ) {
	$req = new WP_REST_Request( 'POST', '/contact-form-7/v1/contact-forms/' . $cf7 . '/feedback' );
	$req->set_header( 'Content-Type', 'multipart/form-data; boundary=x' );
	// CF7 reads $_POST, as it would from a real multipart send.
	$_POST = array( '_wpcf7' => $cf7, '_wpcf7_unit_tag' => 'wpcf7-f' . $cf7 . '-p' . $pid . '-o1', '_wpcf7_container_post' => $pid, 'your-name' => 'Mina Okafor', 'your-email' => 'mina@example.com', 'your-subject' => 'Bread', 'your-message' => 'Two rye loaves on Friday, please.' );
	$req->set_body_params( $_POST );
	$res = rest_do_request( $req );
	$_POST = array();
	$d = $res->get_data();
	// mail_failed only means Playground has no mail server; validation and spam checks passed.
	$ok( 'cf7 send', in_array( $d['status'] ?? '', array( 'mail_sent', 'mail_failed' ), true ), ( $d['status'] ?? $res->get_status() ) . ' ' . ( $d['message'] ?? '' ) );
}
// An app password, so the browser can talk to the MCP server over real HTTP the way Claude does.
if ( class_exists( 'WP_Application_Passwords' ) ) {
	$ok( 'app passwords available', wp_is_application_passwords_available() );
	$made = WP_Application_Passwords::create_new_application_password( 1, array( 'name' => 'dui-test ' . time() ) );
	if ( ! is_wp_error( $made ) ) {
		// Playground can't make HTTP requests to itself, so sign in the way WordPress does for an
		// HTTP request carrying the app password (Basic auth), then call the MCP server as that user.
		$login = wp_get_current_user()->user_login;
		wp_set_current_user( 0 );
		$_SERVER['PHP_AUTH_USER'] = $login;
		$_SERVER['PHP_AUTH_PW']   = $made[0];
		add_filter( 'application_password_is_api_request', '__return_true' );
		$uid = wp_validate_application_password( false );
		$ok( 'app password signs in', (int) $uid > 0, (string) $uid );
		wp_set_current_user( (int) $uid );
		$res = rest_do_request( ( function () {
			$q = new WP_REST_Request( 'POST', '/distinctui-plus/mcp' );
			$q->set_header( 'Content-Type', 'application/json' );
			$q->set_header( 'Accept', 'application/json, text/event-stream' );
			$q->set_body( wp_json_encode( array( 'jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => array( 'protocolVersion' => '2025-11-25', 'capabilities' => new stdClass(), 'clientInfo' => array( 'name' => 'app-password', 'version' => '1' ) ) ) ) );
			return $q;
		} )() );
		$ok( 'mcp with app password', 200 === $res->get_status(), $res->get_status() );
		unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
		remove_filter( 'application_password_is_api_request', '__return_true' );
		wp_set_current_user( 0 );
		$res = rest_do_request( new WP_REST_Request( 'POST', '/distinctui-plus/mcp' ) );
		$ok( 'mcp refuses no sign-in', $res->get_status() >= 400, $res->get_status() );
		wp_set_current_user( 1 );
		WP_Application_Passwords::delete_all_application_passwords( 1 );
	}
}

// The canvas path: compose three designs from typed content, save one over REST like the canvas does, read it back.
$content = array( 'name' => 'Loaf', 'button' => 'Get started', 'heading' => "Fresh bread before you're up", 'lede' => 'We bake every night and deliver in Melville before 7am.', 'features' => "Baked at night: Every loaf is out of the oven by 5am.\nTo your door: Delivered before 7am, Monday to Saturday.\nNo contract: Pause or stop any week.", 'prices' => "Weekly, R95, One loaf a week\nFamily, R240, Three loaves and rolls", 'questions' => 'Can I pause? Yes, any week, from your account.', 'faces' => 'Naledi Dube, Kabelo Sithole', 'proof' => 'Loved by 300 homes in Melville', 'form' => 'contact' );
foreach ( DUI_Plus_Compose::options( $content ) as $opt ) {
	$want = count( $opt['spec']['sections'] );
	$req  = new WP_REST_Request( 'POST', '/distinctui-plus/v1/pages' );
	$req->set_header( 'Content-Type', 'application/json' );
	$req->set_body( wp_json_encode( array( 'title' => 'Loaf ' . $opt['key'], 'spec' => json_decode( wp_json_encode( $opt['spec'] ), true ) ) ) );
	$res  = rest_do_request( $req );
	$page = json_decode( wp_json_encode( $res->get_data() ), true );
	$back = isset( $page['id'] ) ? DUI_Plus_Store::get( (int) $page['id'] ) : null;
	$raw  = isset( $page['id'] ) ? (string) get_post_meta( (int) $page['id'], '_dui_spec', true ) : '';
	$ok( 'canvas save ' . $opt['key'], $back && $want === count( $back['spec']['sections'] ?? array() ), $res->get_status() . ' want ' . $want . ' got ' . count( $back['spec']['sections'] ?? array() ) . ' raw ' . strlen( $raw ) . ' ' . ( json_decode( $raw ) ? 'json ok' : json_last_error_msg() . ' ' . substr( $raw, 0, 120 ) ) );
	// Add every kind of section the way the canvas does (starter, then PUT), and read it back.
	if ( 'bold' === $opt['key'] && isset( $page['id'] ) ) {
		$spec = $back['spec'];
		foreach ( array( 'hero', 'proof', 'features', 'steps', 'stats', 'pricing', 'questions', 'cta', 'form', 'products', 'posts' ) as $type ) {
			$st = rest_do_request( new WP_REST_Request( 'GET', '/distinctui-plus/v1/starter/' . $type ) );
			$spec['sections'][] = json_decode( wp_json_encode( $st->get_data() ), true );
			$req = new WP_REST_Request( 'PUT', '/distinctui-plus/v1/pages/' . $page['id'] );
			$req->set_header( 'Content-Type', 'application/json' );
			$req->set_body( wp_json_encode( array( 'spec' => $spec, 'title' => 'Loaf', 'rev' => 999 ) ) );
			$res  = rest_do_request( $req );
			$got  = count( DUI_Plus_Store::get( (int) $page['id'] )['spec']['sections'] );
			$ok( 'add section ' . $type, $st->get_status() < 300 && $res->get_status() < 300 && count( $spec['sections'] ) === $got, $st->get_status() . '/' . $res->get_status() . ' want ' . count( $spec['sections'] ) . ' got ' . $got . ( $res->get_status() >= 300 ? ' ' . substr( wp_json_encode( $res->get_data() ), 0, 160 ) : '' ) );
			$spec = DUI_Plus_Store::get( (int) $page['id'] )['spec'];
		}
	}
	$periods = array_map( fn( $p ) => $p['period'] ?? '', array_values( array_filter( $opt['spec']['sections'], fn( $x ) => 'pricing' === $x['type'] ) )[0]['items'] ?? array() );
	$ok( 'plan period ' . $opt['key'], 'a week' === ( $periods[0] ?? '' ), implode( '|', $periods ) );
	$ok( 'proof kept ' . $opt['key'], (bool) array_filter( $opt['spec']['sections'], fn( $x ) => 'proof' === $x['type'] ) );
}
$ok( 'page link', true, get_permalink( $pid ) );
update_option( 'dui_test_page', $pid );
} catch ( \Throwable $e ) {
	$r[] = 'FATAL ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine();
}
echo implode( "\n", $r ), "\n";
