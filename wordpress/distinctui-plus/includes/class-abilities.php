<?php
/**
 * Abilities: what your own Claude can do on this site.
 *
 * Registered with the WordPress Abilities API (WordPress 6.9 and later).
 * The WordPress MCP Adapter plugin turns them into MCP tools, so Claude,
 * Cursor or any MCP client can build pages here, signed in as you, with
 * your permissions. We never run or pay for the AI, which is why
 * distinctUI+ can cost less than the big builders.
 *
 * Every change goes through the same spec, check and fix as the canvas,
 * saves as a draft, and arrives in an open canvas as one step you can undo.
 * Claude can't add a colour or a shadow: the spec has nowhere to put one.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ability registration and callbacks.
 */
class DUI_Plus_Abilities {

	const CATEGORY = 'distinctui-plus';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register' ) );
		add_action( 'mcp_adapter_init', array( __CLASS__, 'mcp_server' ) );
	}

	/**
	 * The category.
	 */
	public static function category() {
		if ( function_exists( 'wp_register_ability_category' ) ) {
			wp_register_ability_category(
				self::CATEGORY,
				array(
					'label'       => __( 'distinctUI+', 'distinctui-plus' ),
					'description' => __( 'Build pages in the distinctUI look: black, white, grey, then one mark that\'s yours.', 'distinctui-plus' ),
				)
			);
		}
	}

	/**
	 * Names of every ability, for the MCP server.
	 *
	 * @return string[]
	 */
	public static function names() {
		return array_map(
			function ( $k ) {
				return 'distinctui-plus/' . $k;
			},
			array_keys( self::definitions() )
		);
	}

	/**
	 * Register every ability.
	 */
	public static function register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		foreach ( self::definitions() as $name => $d ) {
			$args = array(
				'label'               => $d['label'],
				'description'         => $d['description'],
				'category'            => self::CATEGORY,
				'execute_callback'    => $d['run'],
				'permission_callback' => $d['allow'],
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => ! empty( $d['readonly'] ),
						'destructive' => ! empty( $d['destructive'] ),
						'idempotent'  => ! empty( $d['readonly'] ),
					),
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true ),
				),
			);
			// Every ability takes an object, so an MCP client can send {} or nothing at all.
			$args['input_schema'] = array( 'type' => 'object' );
			if ( ! empty( $d['input'] ) ) {
				$args['input_schema']['properties']           = $d['input'];
				$args['input_schema']['additionalProperties'] = false;
			}
			if ( ! empty( $d['required'] ) ) {
				$args['input_schema']['required'] = $d['required'];
			} else {
				$args['input_schema']['default'] = array();
			}
			$args['output_schema'] = $d['output'] ?? array( 'type' => 'object' );
			wp_register_ability( 'distinctui-plus/' . $name, $args );
		}
	}

	/**
	 * A server of our own on the MCP Adapter, at
	 * /wp-json/distinctui-plus/mcp, with only our tools on it. Wrapped so a
	 * change in the adapter's API can't take the site down; the abilities
	 * still reach the adapter's default server through mcp.public.
	 *
	 * @param object $adapter The adapter.
	 */
	public static function mcp_server( $adapter ) {
		$transport = '\WP\MCP\Transport\HttpTransport';
		$errors    = '\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler';
		$observe   = '\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler';
		if ( ! is_object( $adapter ) || ! method_exists( $adapter, 'create_server' ) || ! class_exists( $transport ) ) {
			return;
		}
		try {
			$adapter->create_server(
				'distinctui-plus',
				'distinctui-plus',
				'mcp',
				'distinctUI+',
				__( 'Build pages in the distinctUI look on this WordPress site. Start with get-guide.', 'distinctui-plus' ),
				DUI_PLUS_VERSION,
				array( $transport ),
				class_exists( $errors ) ? $errors : null,
				class_exists( $observe ) ? $observe : null,
				self::names()
			);
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'distinctUI+: MCP server not created: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}

	/* --------------------------------------------------------- Schemas */

	/**
	 * Schema for a spec.
	 *
	 * @return array
	 */
	private static function spec_schema() {
		return array(
			'type'        => 'object',
			'description' => 'A page spec: { name, title, description, sections: [ { type, layout, eyebrow, heading, bold, lede, primary: { label, href }, secondary, items, ... } ] }. See get-guide.',
		);
	}

	/**
	 * Schema for a section.
	 *
	 * @return array
	 */
	private static function section_schema() {
		return array(
			'type'        => 'object',
			'description' => 'One section: { type, layout, eyebrow, heading, bold, lede, primary, secondary, items, faces, fields, submit, source, count, category }. Content only, never styling.',
		);
	}

	/**
	 * The page id input.
	 *
	 * @return array
	 */
	private static function page_id() {
		return array(
			'type'        => 'integer',
			'description' => 'The page id, from list-pages or create-page.',
		);
	}

	/* --------------------------------------------------------- Gates */

	/**
	 * Can read pages.
	 *
	 * @return bool
	 */
	public static function can_read() {
		return current_user_can( 'edit_pages' );
	}

	/**
	 * Gate for changes to one page.
	 *
	 * @param array $input Input.
	 * @return bool|WP_Error
	 */
	public static function can_change( $input = array() ) {
		$id = (int) ( $input['page_id'] ?? 0 );
		if ( $id ? ! current_user_can( 'edit_post', $id ) : ! current_user_can( 'edit_pages' ) ) {
			return false;
		}
		return DUI_Plus_Licence::can_build() ? true : DUI_Plus_Licence::closed_error();
	}

	/* ------------------------------------------------------ Definitions */

	/**
	 * Every ability.
	 *
	 * @return array<string, array>
	 */
	public static function definitions() {
		$read   = array( __CLASS__, 'can_read' );
		$change = array( __CLASS__, 'can_change' );
		return array(
			'get-guide'       => array(
				'label'       => __( 'How to build here', 'distinctui-plus' ),
				'description' => 'Start here. The twelve rules, the page spec format, every section type with its layouts and fields, the site\'s signature, and how to work: change pages through these tools, never by writing block markup or CSS.',
				'readonly'    => true,
				'allow'       => $read,
				'run'         => array( __CLASS__, 'guide' ),
			),
			'list-pages'      => array(
				'label'       => __( 'List pages', 'distinctui-plus' ),
				'description' => 'Every page built with distinctUI+: id, title, whether it\'s live, its version, and whether it has changes not published yet.',
				'readonly'    => true,
				'allow'       => $read,
				'run'         => fn() => array( 'pages' => DUI_Plus_Store::list_pages() ),
			),
			'get-page'        => array(
				'label'       => __( 'Read a page', 'distinctui-plus' ),
				'description' => 'A page\'s spec (the working draft), the rule check, its versions, and any requests the person typed into the canvas\'s Ask box for you. Do those requests, then call done-asks.',
				'readonly'    => true,
				'allow'       => $read,
				'input'       => array( 'page_id' => self::page_id() ),
				'required'    => array( 'page_id' ),
				'run'         => fn( $in ) => DUI_Plus_Store::get( (int) $in['page_id'] ),
			),
			'check-spec'      => array(
				'label'       => __( 'Check a spec', 'distinctui-plus' ),
				'description' => 'Check a page spec against the twelve rules without saving it. Each finding has the rule, what\'s wrong and the fix. Same checks as check_spec on distinctui.com/mcp.',
				'readonly'    => true,
				'allow'       => $read,
				'input'       => array( 'spec' => self::spec_schema() ),
				'required'    => array( 'spec' ),
				'run'         => fn( $in ) => DUI_Plus_Check::check( (array) $in['spec'] ),
			),
			'design-options'  => array(
				'label'       => __( 'Three designs of some content', 'distinctui-plus' ),
				'description' => 'Content first: give name, heading (the promise), lede (what you do), primary (main button), features ("Title: line" per item), plans ("Name, price, what\'s in it"), questions ("Question? Answer"), faces (names of real people who use it; leave out to have no proof section, never invent people), proof (a line about them), form (contact, booking or waitlist), products and posts (true or false). Returns three specs (Calm, Bold, Step by step) that all follow the rules. Show the person the choice, then create-page with the one they pick.',
				'readonly'    => true,
				'allow'       => $read,
				'input'       => array(
					'content' => array(
						'type'        => 'object',
						'description' => 'The content, as above.',
					),
				),
				'required'    => array( 'content' ),
				'run'         => fn( $in ) => array( 'options' => DUI_Plus_Compose::options( (array) $in['content'] ) ),
			),
			'create-page'     => array(
				'label'       => __( 'Make a page', 'distinctui-plus' ),
				'description' => 'Make a new draft page from a spec. It\'s checked and fixed first, and the answer lists what was changed and why. The page isn\'t live until publish-page.',
				'allow'       => $change,
				'input'       => array(
					'title' => array( 'type' => 'string' ),
					'spec'  => self::spec_schema(),
				),
				'required'    => array( 'spec' ),
				'run'         => array( __CLASS__, 'create' ),
			),
			'import-design'   => array(
				'label'       => __( 'Bring in a design', 'distinctui-plus' ),
				'description' => 'Bring in a design from anywhere (a Claude design, Figma, a screenshot, a live site, v0 or Lovable code, an Elementor or WPBakery page). You read it and map each part to a section type, keeping the words and images; send that loose spec here, styling and all. This fixes it into the look (brand colours move to the mark, extra buttons become outlined, shadows go, red errors turn black), saves it as a draft (a new page, or over page_id), and returns the list of changes to tell the person.',
				'allow'       => $change,
				'input'       => array(
					'page_id' => self::page_id(),
					'title'   => array( 'type' => 'string' ),
					'source'  => array(
						'type'        => 'string',
						'description' => 'Where it came from, in a few words, for the change list.',
					),
					'spec'    => self::spec_schema(),
				),
				'required'    => array( 'spec' ),
				'run'         => array( __CLASS__, 'import' ),
			),
			'update-page'     => array(
				'label'       => __( 'Replace a page\'s spec', 'distinctui-plus' ),
				'description' => 'Replace the whole draft spec of a page. Prefer the section tools for small changes. Checked and fixed first; never touches the live page until publish-page.',
				'allow'       => $change,
				'input'       => array(
					'page_id' => self::page_id(),
					'spec'    => self::spec_schema(),
					'title'   => array( 'type' => 'string' ),
				),
				'required'    => array( 'page_id', 'spec' ),
				'run'         => array( __CLASS__, 'update' ),
			),
			'add-section'     => array(
				'label'       => __( 'Add a section', 'distinctui-plus' ),
				'description' => 'Add a section to a page\'s draft at a position (default: before the last call to action, or at the end). Give the full section, or just a type to start from its starter words.',
				'allow'       => $change,
				'input'       => array(
					'page_id'  => self::page_id(),
					'section'  => self::section_schema(),
					'position' => array(
						'type'        => 'integer',
						'description' => 'Index to insert at, from 0.',
					),
				),
				'required'    => array( 'page_id', 'section' ),
				'run'         => array( __CLASS__, 'add_section' ),
			),
			'update-section'  => array(
				'label'       => __( 'Change a section', 'distinctui-plus' ),
				'description' => 'Change one section of a page\'s draft. Only the fields given change, so { "layout": "rows" } swaps the layout and { "heading": "...", "bold": "..." } rewrites the heading.',
				'allow'       => $change,
				'input'       => array(
					'page_id' => self::page_id(),
					'index'   => array( 'type' => 'integer' ),
					'changes' => self::section_schema(),
				),
				'required'    => array( 'page_id', 'index', 'changes' ),
				'run'         => array( __CLASS__, 'update_section' ),
			),
			'move-section'    => array(
				'label'       => __( 'Move a section', 'distinctui-plus' ),
				'description' => 'Move a section in a page\'s draft from one position to another.',
				'allow'       => $change,
				'input'       => array(
					'page_id' => self::page_id(),
					'from'    => array( 'type' => 'integer' ),
					'to'      => array( 'type' => 'integer' ),
				),
				'required'    => array( 'page_id', 'from', 'to' ),
				'run'         => array( __CLASS__, 'move_section' ),
			),
			'remove-section'  => array(
				'label'       => __( 'Remove a section', 'distinctui-plus' ),
				'description' => 'Remove a section from a page\'s draft. It can be brought back by restoring a version, or with undo in the canvas.',
				'allow'       => $change,
				'input'       => array(
					'page_id' => self::page_id(),
					'index'   => array( 'type' => 'integer' ),
				),
				'required'    => array( 'page_id', 'index' ),
				'run'         => array( __CLASS__, 'remove_section' ),
			),
			'set-signature'   => array(
				'label'       => __( 'Set the signature', 'distinctui-plus' ),
				'description' => 'Set the site\'s mark for every page: palette (signal, mzansi, earth, coast or jacaranda), colour (one hex, replaces the first ring), initials (1 to 3 letters inside the ring) and density (airy or compact). The colour only ever appears on rings. Needs the person to be allowed to change the theme.',
				'allow'       => fn() => current_user_can( 'edit_theme_options' ) && DUI_Plus_Licence::can_build(),
				'input'       => array(
					'palette'  => array(
						'type' => 'string',
						'enum' => array( 'signal', 'mzansi', 'earth', 'coast', 'jacaranda', '' ),
					),
					'colour'   => array( 'type' => 'string' ),
					'initials' => array( 'type' => 'string' ),
					'density'  => array(
						'type' => 'string',
						'enum' => array( 'airy', 'compact' ),
					),
				),
				'run'         => array( __CLASS__, 'set_signature' ),
			),
			'publish-page'    => array(
				'label'       => __( 'Publish a page', 'distinctui-plus' ),
				'description' => 'Put a page\'s draft live. Only works when every rule holds. The mark goes from dashed to solid and the version goes up (v0.1, v0.2 ...). Ask the person before publishing.',
				'allow'       => fn( $in = array() ) => current_user_can( 'publish_pages' ) ? self::can_change( $in ) : false,
				'destructive' => true,
				'input'       => array( 'page_id' => self::page_id() ),
				'required'    => array( 'page_id' ),
				'run'         => fn( $in ) => DUI_Plus_Store::publish( (int) $in['page_id'], 'claude' ),
			),
			'restore-version' => array(
				'label'       => __( 'Bring back a version', 'distinctui-plus' ),
				'description' => 'Bring a published version back as the draft. Nothing goes live until publish-page.',
				'allow'       => $change,
				'input'       => array(
					'page_id' => self::page_id(),
					'version' => array( 'type' => 'integer' ),
				),
				'required'    => array( 'page_id', 'version' ),
				'run'         => fn( $in ) => DUI_Plus_Store::restore( (int) $in['page_id'], (int) $in['version'] ),
			),
			'done-asks'       => array(
				'label'       => __( 'Mark requests done', 'distinctui-plus' ),
				'description' => 'After doing what the person asked in the canvas\'s Ask box, mark those requests done so the canvas stops showing them as waiting. Leave ids out to clear them all.',
				'allow'       => $change,
				'input'       => array(
					'page_id' => self::page_id(),
					'ids'     => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				'required'    => array( 'page_id' ),
				'run'         => fn( $in ) => array( 'waiting' => DUI_Plus_Store::done_asks( (int) $in['page_id'], (array) ( $in['ids'] ?? array() ) ) ),
			),
		);
	}

	/* -------------------------------------------------------- Callbacks */

	/**
	 * The guide.
	 *
	 * @return array
	 */
	public static function guide() {
		$rules = DUI_Plus_Check::rules();
		$types = array();
		foreach ( DUI_Plus_Spec::types() as $type => $t ) {
			$types[ $type ] = array(
				'name'    => $t['name'],
				'about'   => $t['about'],
				'layouts' => array_keys( $t['layouts'] ),
			);
		}
		return array(
			'look'      => 'Black, white, grey. Then one mark that\'s yours. Light only.',
			'rules'     => array_map(
				function ( $r ) {
					return $r['n'] . '. ' . $r['title'] . ': ' . $r['text'];
				},
				$rules['rules'] ?? array()
			),
			'left_out'  => $rules['left_out'] ?? array(),
			'signature' => DUI_Plus_Settings::signature(),
			'sections'  => $types,
			'fields'    => array(
				'every section' => 'type, layout, eyebrow, heading, bold (the one word that carries the meaning; it must be in the heading), lede, primary { label, href } (the one black pill), secondary { label, href } (outlined), anchor',
				'proof'         => 'faces: names or { name, initials } (invented people only, up to 5), more ("and 40 salons"), text',
				'features'      => 'items: [ { title, text } ] up to 6',
				'steps'         => 'items: [ { title, text } ] up to 5',
				'stats'         => 'items: [ { value, label } ] up to 4',
				'pricing'       => 'items: [ { name, price, period, text, features: [], cta, href, featured } ] up to 3; one featured',
				'questions'     => 'items: [ { q, a } ]',
				'form'          => 'fields: [ { label, name, type (text, email, tel, textarea, select, date, number, checkbox, url), optional, hint, options } ] (optional: each layout has default fields), submit (say what happens), success; or plugin (cf7, wpforms, gravity) with plugin_id',
				'products'      => 'source: "woocommerce" with count and category, or items: [ { name, price, image, alt, href, cta } ]',
				'posts'         => 'source: "posts" with count and category, or items: [ { title, date, text, href } ]',
				'page'          => 'name, title and description (for search and sharing), sections',
			),
			'how'       => array(
				'Change pages only through these tools. Never write block markup, HTML or CSS: the spec has no place for colours, fonts, shadows or sizes, and that is the point.',
				'Every change is saved as a draft and checked. Read the check in each answer and fix errors before you finish.',
				'Write plainly: buttons say what happens, no exclamation marks, errors name the field and the fix.',
				'Use invented people and products in examples, never real people.',
				'Ask the person before publish-page.',
				'If get-page shows asks, those are requests the person typed into the canvas for you. Do them, then call done-asks.',
			),
			'spec_docs' => 'https://github.com/VenusOne-Lee/distinctui/blob/main/skills/distinctui/references/page-spec.md',
		);
	}

	/**
	 * A change result: the page, its check, and what the fixer changed.
	 *
	 * @param array|WP_Error $page    Page.
	 * @param string[]       $changes Fixes made.
	 * @return array|WP_Error
	 */
	private static function result( $page, array $changes = array() ) {
		if ( is_wp_error( $page ) ) {
			return $page;
		}
		return array(
			'page_id'   => $page['id'],
			'title'     => $page['title'],
			'live'      => $page['live'],
			'has_draft' => $page['has_draft'],
			'canvas'    => DUI_Plus_Canvas::url( $page['id'] ),
			'preview'   => $page['preview'],
			'changed'   => $changes,
			'check'     => $page['check'],
			'spec'      => $page['spec'],
		);
	}

	/**
	 * Create.
	 *
	 * @param array $in Input.
	 * @return array|WP_Error
	 */
	public static function create( $in ) {
		$fixed = DUI_Plus_Check::fix( (array) $in['spec'] );
		$id    = DUI_Plus_Store::create( (string) ( $in['title'] ?? '' ), $fixed['spec'] );
		return is_wp_error( $id ) ? $id : self::result( DUI_Plus_Store::get( $id ), $fixed['changes'] );
	}

	/**
	 * Import.
	 *
	 * @param array $in Input.
	 * @return array|WP_Error
	 */
	public static function import( $in ) {
		$fixed = DUI_Plus_Check::fix( (array) $in['spec'] );
		if ( ! empty( $in['source'] ) ) {
			/* translators: %s: where the design came from. */
			array_unshift( $fixed['changes'], sprintf( __( 'Brought in from %s, keeping the words and images.', 'distinctui-plus' ), sanitize_text_field( $in['source'] ) ) );
		}
		if ( ! empty( $in['page_id'] ) ) {
			return self::result( DUI_Plus_Store::save( (int) $in['page_id'], $fixed['spec'], array( 'title' => (string) ( $in['title'] ?? '' ) ) ), $fixed['changes'] );
		}
		$id = DUI_Plus_Store::create( (string) ( $in['title'] ?? '' ), $fixed['spec'] );
		return is_wp_error( $id ) ? $id : self::result( DUI_Plus_Store::get( $id ), $fixed['changes'] );
	}

	/**
	 * The current draft spec, or an error.
	 *
	 * @param int $id Page id.
	 * @return array|WP_Error
	 */
	private static function draft( $id ) {
		$page = DUI_Plus_Store::get( $id );
		return is_wp_error( $page ) ? $page : $page['spec'];
	}

	/**
	 * Fix and save a spec.
	 *
	 * @param int    $id    Page id.
	 * @param array  $spec  Spec.
	 * @param string $title New title, or ''.
	 * @return array|WP_Error
	 */
	private static function fix_and_save( $id, array $spec, $title = '' ) {
		$fixed = DUI_Plus_Check::fix( $spec );
		return self::result( DUI_Plus_Store::save( $id, $fixed['spec'], array( 'title' => $title ) ), $fixed['changes'] );
	}

	/**
	 * Update a whole page.
	 *
	 * @param array $in Input.
	 * @return array|WP_Error
	 */
	public static function update( $in ) {
		return self::fix_and_save( (int) $in['page_id'], (array) $in['spec'], (string) ( $in['title'] ?? '' ) );
	}

	/**
	 * Add a section.
	 *
	 * @param array $in Input.
	 * @return array|WP_Error
	 */
	public static function add_section( $in ) {
		$spec = self::draft( (int) $in['page_id'] );
		if ( is_wp_error( $spec ) ) {
			return $spec;
		}
		$section = (array) $in['section'];
		if ( count( $section ) <= 2 && ! empty( $section['type'] ) ) {
			$section = array_merge( DUI_Plus_Spec::starter( sanitize_key( $section['type'] ) ), $section );
		}
		$n = count( $spec['sections'] );
		if ( isset( $in['position'] ) ) {
			$at = max( 0, min( $n, (int) $in['position'] ) );
		} else {
			$at = ( $n && 'cta' === $spec['sections'][ $n - 1 ]['type'] ) ? $n - 1 : $n;
		}
		array_splice( $spec['sections'], $at, 0, array( $section ) );
		return self::fix_and_save( (int) $in['page_id'], $spec );
	}

	/**
	 * Find a section or fail plainly.
	 *
	 * @param array $spec Spec.
	 * @param int   $i    Index.
	 * @return true|WP_Error
	 */
	private static function has( array $spec, $i ) {
		if ( isset( $spec['sections'][ $i ] ) ) {
			return true;
		}
		/* translators: 1: index asked for, 2: number of sections. */
		return new WP_Error( 'dui_plus_no_section', sprintf( __( 'There\'s no section %1$d. The page has %2$d, counted from 0.', 'distinctui-plus' ), $i, count( $spec['sections'] ) ), array( 'status' => 404 ) );
	}

	/**
	 * Change a section.
	 *
	 * @param array $in Input.
	 * @return array|WP_Error
	 */
	public static function update_section( $in ) {
		$spec = self::draft( (int) $in['page_id'] );
		$i    = (int) $in['index'];
		if ( is_wp_error( $spec ) ) {
			return $spec;
		}
		$ok = self::has( $spec, $i );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		$spec['sections'][ $i ] = array_merge( $spec['sections'][ $i ], (array) $in['changes'] );
		return self::fix_and_save( (int) $in['page_id'], $spec );
	}

	/**
	 * Move a section.
	 *
	 * @param array $in Input.
	 * @return array|WP_Error
	 */
	public static function move_section( $in ) {
		$spec = self::draft( (int) $in['page_id'] );
		if ( is_wp_error( $spec ) ) {
			return $spec;
		}
		$from = (int) $in['from'];
		$ok   = self::has( $spec, $from );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		$moved = array_splice( $spec['sections'], $from, 1 );
		array_splice( $spec['sections'], max( 0, min( count( $spec['sections'] ), (int) $in['to'] ) ), 0, $moved );
		return self::fix_and_save( (int) $in['page_id'], $spec );
	}

	/**
	 * Remove a section.
	 *
	 * @param array $in Input.
	 * @return array|WP_Error
	 */
	public static function remove_section( $in ) {
		$spec = self::draft( (int) $in['page_id'] );
		if ( is_wp_error( $spec ) ) {
			return $spec;
		}
		$ok = self::has( $spec, (int) $in['index'] );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		array_splice( $spec['sections'], (int) $in['index'], 1 );
		return self::fix_and_save( (int) $in['page_id'], $spec );
	}

	/**
	 * Set the signature.
	 *
	 * @param array $in Input.
	 * @return array
	 */
	public static function set_signature( $in ) {
		$changes = array();
		if ( isset( $in['palette'] ) ) {
			$changes['palette'] = strtolower( (string) $in['palette'] );
		}
		if ( isset( $in['colour'] ) ) {
			$changes['signature'] = (string) $in['colour'];
		}
		if ( isset( $in['initials'] ) ) {
			$changes['initials'] = (string) $in['initials'];
		}
		if ( isset( $in['density'] ) ) {
			$changes['density'] = (string) $in['density'];
		}
		DUI_Plus_Settings::update_look( $changes );
		return array(
			'signature' => DUI_Plus_Settings::signature(),
			'note'      => 'Every page picks this up straight away. The colour shows only on rings.',
		);
	}
}
