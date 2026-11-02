<?php
/**
 * Where pages live, and how drafts become live.
 *
 * A distinctUI+ page is a normal WordPress page. Its post content is plain
 * blocks; its spec sits beside it in post meta.
 *
 *  - _dui_spec     the spec that's live (or the draft, before first publish)
 *  - _dui_draft    changes not published yet, on a page that's live
 *  - _dui_versions every published version, newest first, to restore
 *  - _dui_rev      goes up on every change, so the canvas sees AI edits
 *  - _dui_hash     a hash of the content we wrote, to notice edits made in
 *                  the block editor
 *  - _dui_asks     requests typed into the canvas's Ask box, for your Claude
 *
 * Drafts never touch a live page. Until a page's first publish its content
 * is rewritten on every save, with the mark dashed. After that, changes
 * wait in _dui_draft until Publish draws the mark solid and bumps the
 * version.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page storage, drafts, publishing and versions.
 */
class DUI_Plus_Store {

	const META_SPEC     = '_dui_spec';
	const META_DRAFT    = '_dui_draft';
	const META_VERSIONS = '_dui_versions';
	const META_REV      = '_dui_rev';
	const META_HASH     = '_dui_hash';
	const META_ASKS     = '_dui_asks';

	/**
	 * Hooks.
	 */
	public static function init() {
		// Keep the spec in revisions too, so WordPress's own Revisions screen
		// can bring a spec back with the content.
		add_filter( 'wp_post_revision_meta_keys', array( __CLASS__, 'revision_meta' ) );
		// Published from WordPress's own Publish button: draw it live too.
		add_action( 'transition_post_status', array( __CLASS__, 'published_elsewhere' ), 10, 3 );
	}

	/**
	 * Busy publishing, so the status hook doesn't run twice.
	 *
	 * @var bool
	 */
	private static $publishing = false;

	/**
	 * A built page went live from the normal editor or Quick Edit. Run our
	 * publish so the mark is solid and there's a version to go back to.
	 *
	 * @param string  $new  New status.
	 * @param string  $old  Old status.
	 * @param WP_Post $post Post.
	 */
	public static function published_elsewhere( $new, $old, $post ) {
		if ( self::$publishing || 'publish' !== $new || 'publish' === $old || ! self::is_built( $post->ID ) || self::versions( $post->ID ) ) {
			return;
		}
		$hash = (string) get_post_meta( $post->ID, self::META_HASH, true );
		if ( $hash && md5( $post->post_content ) !== $hash ) {
			return; // Edited by hand since; leave their content alone.
		}
		add_action(
			'wp_after_insert_post',
			function ( $id ) use ( $post ) {
				if ( $id === $post->ID && ! self::versions( $id ) ) {
					self::publish( $id );
				}
			}
		);
	}

	/**
	 * Revisioned meta keys.
	 *
	 * @param string[] $keys Keys.
	 * @return string[]
	 */
	public static function revision_meta( $keys ) {
		$keys[] = self::META_SPEC;
		return $keys;
	}

	/**
	 * Is this page built with distinctUI+?
	 *
	 * @param int $post_id Post id.
	 * @return bool
	 */
	public static function is_built( $post_id ) {
		return '' !== (string) get_post_meta( $post_id, self::META_SPEC, true );
	}

	/**
	 * Read a JSON meta value.
	 *
	 * @param int    $post_id Post id.
	 * @param string $key     Meta key.
	 * @return array|null
	 */
	private static function json_meta( $post_id, $key ) {
		$raw = get_post_meta( $post_id, $key, true );
		if ( is_array( $raw ) ) {
			return $raw;
		}
		if ( ! is_string( $raw ) || '' === $raw ) {
			return null;
		}
		$v = json_decode( $raw, true );
		return is_array( $v ) ? $v : null;
	}

	/**
	 * Write a JSON meta value. wp_slash because update_post_meta unslashes.
	 *
	 * @param int    $post_id Post id.
	 * @param string $key     Meta key.
	 * @param array  $value   Value.
	 */
	private static function put_json( $post_id, $key, array $value ) {
		update_post_meta( $post_id, $key, wp_slash( wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
	}

	/**
	 * Is the page live?
	 *
	 * @param WP_Post $post Post.
	 * @return bool
	 */
	public static function is_live( $post ) {
		return in_array( $post->post_status, array( 'publish', 'private' ), true ) && self::versions( $post->ID );
	}

	/**
	 * Everything the canvas and abilities need about a page.
	 *
	 * @param int $post_id Post id.
	 * @return array|WP_Error
	 */
	public static function get( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, self::post_types(), true ) ) {
			return new WP_Error( 'dui_plus_no_page', __( 'There\'s no page with that id.', 'distinctui-plus' ), array( 'status' => 404 ) );
		}
		$live     = self::is_live( $post );
		$draft    = self::json_meta( $post_id, self::META_DRAFT );
		$spec     = $draft ? $draft : self::json_meta( $post_id, self::META_SPEC );
		$spec     = DUI_Plus_Spec::clean( $spec ? $spec : DUI_Plus_Spec::blank( $post->post_title ) );
		$versions = self::versions( $post_id );
		$hash     = (string) get_post_meta( $post_id, self::META_HASH, true );
		return array(
			'id'             => $post->ID,
			'title'          => $post->post_title,
			'status'         => $post->post_status,
			'link'           => get_permalink( $post ),
			'preview'        => get_preview_post_link( $post ),
			'edit_link'      => get_edit_post_link( $post, 'raw' ),
			'built'          => self::is_built( $post_id ),
			'live'           => $live,
			'has_draft'      => $live && null !== $draft,
			'version'        => $versions ? $versions[0]['v'] : 0,
			'versions'       => array_map(
				function ( $v ) {
					return array(
						'v'    => $v['v'],
						'time' => $v['time'],
						'by'   => $v['by'] ?? '',
					);
				},
				$versions
			),
			'rev'            => (int) get_post_meta( $post_id, self::META_REV, true ),
			'edited_outside' => $hash && md5( $post->post_content ) !== $hash,
			'asks'           => self::asks( $post_id ),
			'spec'           => $spec,
			'check'          => DUI_Plus_Check::check( $spec ),
		);
	}

	/**
	 * Post types distinctUI+ builds. Pages by default.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		return (array) apply_filters( 'distinctui_plus_post_types', array( 'page' ) );
	}

	/**
	 * Make a new draft page.
	 *
	 * @param string $title Page title.
	 * @param array  $spec  Spec (cleaned here).
	 * @return int|WP_Error Post id.
	 */
	public static function create( $title, array $spec ) {
		$spec = DUI_Plus_Spec::clean( $spec );
		$id   = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => $title ? $title : ( $spec['name'] ? $spec['name'] : __( 'New page', 'distinctui-plus' ) ),
				'post_content' => '',
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		if ( self::theme_has_landing() ) {
			update_post_meta( $id, '_wp_page_template', 'page-landing' );
		}
		$saved = self::save( $id, $spec );
		return is_wp_error( $saved ) ? $saved : $id;
	}

	/**
	 * Does the active theme have the "Landing page (no title)" template?
	 *
	 * @return bool
	 */
	private static function theme_has_landing() {
		return DUI_Plus_Settings::theme_active();
	}

	/**
	 * Save a change. Never touches a live page's content.
	 *
	 * @param int   $post_id Post id.
	 * @param array $spec    Spec (cleaned here).
	 * @param array $opts    title (string), by (string: you or claude).
	 * @return array|WP_Error The page, as get() returns it.
	 */
	public static function save( $post_id, array $spec, array $opts = array() ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'dui_plus_no_page', __( 'There\'s no page with that id.', 'distinctui-plus' ), array( 'status' => 404 ) );
		}
		$spec         = DUI_Plus_Spec::clean( $spec );
		$spec['live'] = false;
		if ( self::is_live( $post ) ) {
			self::put_json( $post_id, self::META_DRAFT, $spec );
		} else {
			self::put_json( $post_id, self::META_SPEC, $spec );
			$content = DUI_Plus_Render::page(
				$spec,
				array(
					'live'    => false,
					'post_id' => $post_id,
				)
			);
			$update  = array(
				'ID'           => $post_id,
				'post_content' => wp_slash( $content ),
			);
			if ( ! empty( $opts['title'] ) ) {
				$update['post_title'] = sanitize_text_field( $opts['title'] );
			}
			self::write_post( $update );
			update_post_meta( $post_id, self::META_HASH, md5( get_post( $post_id )->post_content ) );
		}
		if ( ! empty( $opts['title'] ) && self::is_live( $post ) ) {
			// The title is part of the draft too; it changes on publish.
			$spec['name'] = sanitize_text_field( $opts['title'] );
			self::put_json( $post_id, self::META_DRAFT, $spec );
		}
		self::bump( $post_id );
		return self::get( $post_id );
	}

	/**
	 * Write the post without our own save hooks noticing.
	 *
	 * @param array $update wp_update_post args.
	 * @return int|WP_Error
	 */
	private static function write_post( array $update ) {
		// Revisions on every autosave would be noise; publish makes one.
		$had = has_action( 'post_updated', 'wp_save_post_revision' );
		if ( $had ) {
			remove_action( 'post_updated', 'wp_save_post_revision', $had );
		}
		$r = wp_update_post( $update, true );
		if ( $had ) {
			add_action( 'post_updated', 'wp_save_post_revision', $had );
		}
		return $r;
	}

	/**
	 * Count a change.
	 *
	 * @param int $post_id Post id.
	 */
	public static function bump( $post_id ) {
		update_post_meta( $post_id, self::META_REV, (int) get_post_meta( $post_id, self::META_REV, true ) + 1 );
	}

	/**
	 * Publish: the rules must hold, the mark goes solid, the version goes up.
	 *
	 * @param int    $post_id Post id.
	 * @param string $by      Who published: you or claude.
	 * @return array|WP_Error
	 */
	public static function publish( $post_id, $by = 'you' ) {
		$page = self::get( $post_id );
		if ( is_wp_error( $page ) ) {
			return $page;
		}
		$check = $page['check'];
		if ( ! $check['ok'] ) {
			return new WP_Error(
				'dui_plus_rules',
				sprintf(
					/* translators: %d: number of errors. */
					_n( '%d rule doesn\'t hold yet, so the page can\'t go live. Fix it from the rule line first.', '%d rules don\'t hold yet, so the page can\'t go live. Fix them from the rule line first.', $check['errors'], 'distinctui-plus' ),
					$check['errors']
				),
				array(
					'status' => 409,
					'check'  => $check,
				)
			);
		}
		$spec            = $page['spec'];
		$versions        = self::versions( $post_id );
		$v               = $versions ? $versions[0]['v'] + 1 : 1;
		$spec['live']    = true;
		$spec['version'] = self::label( $v );
		$content         = DUI_Plus_Render::page(
			$spec,
			array(
				'live'    => true,
				'post_id' => $post_id,
			)
		);
		$post            = get_post( $post_id );
		$update          = array(
			'ID'           => $post_id,
			'post_content' => wp_slash( $content ),
			'post_status'  => current_user_can( 'publish_pages' ) ? 'publish' : 'pending',
		);
		if ( 'private' === $post->post_status ) {
			$update['post_status'] = 'private';
		}
		if ( ! empty( $spec['name'] ) && $page['has_draft'] && $spec['name'] !== $post->post_title ) {
			$update['post_title'] = $spec['name'];
		}
		self::put_json( $post_id, self::META_SPEC, $spec );
		delete_post_meta( $post_id, self::META_DRAFT );
		self::$publishing = true;
		$r                = wp_update_post( $update, true );
		self::$publishing = false;
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		update_post_meta( $post_id, self::META_HASH, md5( get_post( $post_id )->post_content ) );

		array_unshift(
			$versions,
			array(
				'v'    => $v,
				'time' => time(),
				'by'   => 'claude' === $by ? 'claude' : 'you',
				'spec' => $spec,
			)
		);
		self::put_versions( $post_id, array_slice( $versions, 0, DUI_Plus_Settings::builder()['keep_versions'] ) );
		self::bump( $post_id );

		/**
		 * After a distinctUI+ page goes live. Caches are cleared here (see
		 * DUI_Plus_Compat).
		 *
		 * @param int   $post_id Post id.
		 * @param array $spec    The live spec.
		 * @param int   $v       Version number.
		 */
		do_action( 'distinctui_plus_published', $post_id, $spec, $v );
		return self::get( $post_id );
	}

	/**
	 * A version as its label: 1 is v0.1, 10 is v1.0, 23 is v2.3.
	 *
	 * @param int $v Version count.
	 * @return string
	 */
	public static function label( $v ) {
		return 'v' . intdiv( (int) $v, 10 ) . '.' . ( (int) $v % 10 );
	}

	/**
	 * Bring an older version back as the draft. Nothing goes live until you
	 * publish again.
	 *
	 * @param int $post_id Post id.
	 * @param int $v       Version number.
	 * @return array|WP_Error
	 */
	public static function restore( $post_id, $v ) {
		foreach ( self::versions( $post_id ) as $version ) {
			if ( (int) $version['v'] === (int) $v ) {
				return self::save( $post_id, $version['spec'] );
			}
		}
		return new WP_Error( 'dui_plus_no_version', __( 'That version isn\'t kept any more.', 'distinctui-plus' ), array( 'status' => 404 ) );
	}

	/**
	 * Throw away unpublished changes on a live page.
	 *
	 * @param int $post_id Post id.
	 * @return array|WP_Error
	 */
	public static function discard( $post_id ) {
		delete_post_meta( $post_id, self::META_DRAFT );
		self::bump( $post_id );
		return self::get( $post_id );
	}

	/**
	 * Published versions, newest first.
	 *
	 * @param int $post_id Post id.
	 * @return array
	 */
	public static function versions( $post_id ) {
		$v = self::json_meta( $post_id, self::META_VERSIONS );
		return is_array( $v ) ? array_values( $v ) : array();
	}

	/**
	 * Store versions.
	 *
	 * @param int   $post_id  Post id.
	 * @param array $versions Versions.
	 */
	private static function put_versions( $post_id, array $versions ) {
		self::put_json( $post_id, self::META_VERSIONS, $versions );
	}

	/**
	 * Requests waiting for Claude.
	 *
	 * @param int $post_id Post id.
	 * @return array
	 */
	public static function asks( $post_id ) {
		$a = self::json_meta( $post_id, self::META_ASKS );
		return is_array( $a ) ? array_values( $a ) : array();
	}

	/**
	 * Add a request from the Ask box.
	 *
	 * @param int    $post_id Post id.
	 * @param string $text    What to do.
	 * @param int    $section Section index it's about, or -1.
	 * @return array The waiting requests.
	 */
	public static function add_ask( $post_id, $text, $section = -1 ) {
		$asks   = self::asks( $post_id );
		$asks[] = array(
			'id'      => wp_generate_uuid4(),
			'text'    => sanitize_textarea_field( $text ),
			'section' => (int) $section,
			'time'    => time(),
			'by'      => get_current_user_id(),
		);
		self::put_json( $post_id, self::META_ASKS, array_slice( $asks, -20 ) );
		self::bump( $post_id );
		return self::asks( $post_id );
	}

	/**
	 * Mark requests done.
	 *
	 * @param int      $post_id Post id.
	 * @param string[] $ids     Request ids, or empty for all.
	 * @return array What's still waiting.
	 */
	public static function done_asks( $post_id, array $ids = array() ) {
		$left = $ids ? array_values(
			array_filter(
				self::asks( $post_id ),
				function ( $a ) use ( $ids ) {
					return ! in_array( $a['id'], $ids, true );
				}
			)
		) : array();
		self::put_json( $post_id, self::META_ASKS, $left );
		self::bump( $post_id );
		return $left;
	}

	/**
	 * Every page built with distinctUI+.
	 *
	 * @param int $limit How many.
	 * @return array
	 */
	public static function list_pages( $limit = 50 ) {
		$q   = new WP_Query(
			array(
				'post_type'      => self::post_types(),
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => $limit,
				'meta_key'       => self::META_SPEC, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'        => 'modified',
				'no_found_rows'  => true,
			)
		);
		$out = array();
		foreach ( $q->posts as $p ) {
			$versions = self::versions( $p->ID );
			$out[]    = array(
				'id'        => $p->ID,
				'title'     => $p->post_title,
				'status'    => $p->post_status,
				'live'      => self::is_live( $p ),
				'version'   => $versions ? self::label( $versions[0]['v'] ) : '',
				'has_draft' => '' !== (string) get_post_meta( $p->ID, self::META_DRAFT, true ),
				'link'      => get_permalink( $p ),
				'modified'  => get_post_modified_time( 'U', true, $p ),
			);
		}
		return $out;
	}
}
