<?php
/**
 * Forms: entries in WordPress and an email for each one.
 *
 * Part of the free core: a form you built keeps taking entries whether or
 * not the licence is active. The form on the page is plain HTML that posts
 * back to the same page. A small script sends it without a reload and
 * shows errors in place; without the script it still works, with a reload.
 *
 * Errors follow rule 10: black and bold with a "!" disc, naming the field
 * and the fix.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form handling and entries.
 */
class DUI_Plus_Forms {

	const CPT = 'dui_entry';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( __CLASS__, 'handle' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * The entries post type: private, listed under distinctUI+.
	 */
	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Form entries', 'distinctui-plus' ),
					'singular_name' => __( 'Form entry', 'distinctui-plus' ),
					'menu_name'     => __( 'Form entries', 'distinctui-plus' ),
					'not_found'     => __( 'No entries yet. They show up here as soon as someone sends a form.', 'distinctui-plus' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'distinctui-plus',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_action( 'add_meta_boxes_' . self::CPT, array( __CLASS__, 'meta_box' ) );
	}

	/**
	 * A stable key for a form on a page.
	 *
	 * @param array $s Form section.
	 * @param int   $i Index on the page.
	 * @return string
	 */
	public static function key( array $s, $i ) {
		return ! empty( $s['anchor'] ) ? sanitize_key( $s['anchor'] ) : 'form-' . (int) $i;
	}

	/**
	 * The script, only on pages that have one of our forms.
	 */
	public static function assets() {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post || false === strpos( $post->post_content, 'data-dui-form=' ) ) {
			return;
		}
		wp_enqueue_script( 'dui-plus-forms', DUI_PLUS_URL . 'assets/forms.js', array(), DUI_PLUS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script(
			'dui-plus-forms',
			'duiForms',
			array(
				'sending' => __( 'Sending…', 'distinctui-plus' ),
				'offline' => __( 'That didn\'t send. Check your connection, then press the button again.', 'distinctui-plus' ),
			)
		);
	}

	/**
	 * Find the form section a submission is for, from the live spec.
	 *
	 * @param int    $post_id Page id.
	 * @param string $key     Form key.
	 * @return array|null Section, with ['_heading'].
	 */
	private static function find( $post_id, $key ) {
		$raw  = get_post_meta( $post_id, DUI_Plus_Store::META_SPEC, true );
		$spec = is_string( $raw ) ? json_decode( $raw, true ) : null;
		if ( ! is_array( $spec ) ) {
			return null;
		}
		$spec = DUI_Plus_Spec::clean( $spec );
		foreach ( $spec['sections'] as $i => $s ) {
			if ( 'form' === $s['type'] && self::key( $s, $i ) === $key ) {
				if ( empty( $s['fields'] ) ) {
					$s['fields'] = array_map( array( 'DUI_Plus_Spec', 'clean_field' ), DUI_Plus_Spec::default_fields( $s['layout'] ) );
				}
				return $s;
			}
		}
		return null;
	}

	/**
	 * What's wrong with one answer, in plain words, or ''.
	 *
	 * @param array  $f     Field.
	 * @param string $value Answer.
	 * @return string
	 */
	public static function problem( array $f, $value ) {
		$label = mb_strtolower( $f['label'] );
		// "Email" reads as "your email"; "Your name" already says it.
		if ( 0 !== strpos( $label, 'your ' ) && 0 !== strpos( $label, 'the ' ) ) {
			$label = sprintf( /* translators: %s: field label, lower case. */ __( 'your %s', 'distinctui-plus' ), $label );
		}
		if ( '' === $value ) {
			if ( $f['optional'] ) {
				return '';
			}
			if ( 'checkbox' === $f['type'] ) {
				/* translators: %s: field label. */
				return sprintf( __( 'Tick "%s" to carry on.', 'distinctui-plus' ), $f['label'] );
			}
			/* translators: %s: field label, lower case. */
			return sprintf( __( 'Add %s. It\'s needed to send this.', 'distinctui-plus' ), $label );
		}
		switch ( $f['type'] ) {
			case 'email':
				if ( false === strpos( $value, '@' ) ) {
					return __( 'That email address is missing an @. Check it and try again.', 'distinctui-plus' );
				}
				if ( ! is_email( $value ) ) {
					return __( 'That email address doesn\'t look complete. It needs a name, an @ and a domain, like thandi@example.co.za.', 'distinctui-plus' );
				}
				break;
			case 'tel':
				$digits = preg_replace( '/\D/', '', $value );
				if ( strlen( $digits ) < 9 ) {
					/* translators: %d: number of digits typed. */
					return sprintf( __( 'That number has %d digits. Phone numbers have at least 9, like 082 555 0101.', 'distinctui-plus' ), strlen( $digits ) );
				}
				break;
			case 'url':
				if ( ! wp_http_validate_url( preg_match( '#^https?://#i', $value ) ? $value : 'https://' . $value ) ) {
					return __( 'That link doesn\'t look right. It should look like example.co.za.', 'distinctui-plus' );
				}
				break;
			case 'number':
				if ( ! is_numeric( $value ) ) {
					return __( 'Use numbers only here.', 'distinctui-plus' );
				}
				break;
			case 'select':
				if ( ! in_array( $value, $f['options'] ?? array(), true ) ) {
					return __( 'Choose one of the options in the list.', 'distinctui-plus' );
				}
				break;
		}
		return '';
	}

	/**
	 * Take a submission: check it, store it, email it, answer.
	 */
	public static function handle() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Public form on cached pages; spam is handled by the honeypot, timing and rate limit below.
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['dui_form'] ) ) {
			return;
		}
		$ajax = ! empty( $_SERVER['HTTP_X_DUI_FORM'] );
		list( $post_id, $key ) = array_pad( explode( ':', sanitize_text_field( wp_unslash( $_POST['dui_form'] ) ), 2 ), 2, '' );
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );
		$section = ( $post && 'publish' === $post->post_status ) ? self::find( $post_id, sanitize_key( $key ) ) : null;
		if ( ! $section ) {
			self::answer( $ajax, false, __( 'This form isn\'t taking answers any more. Get in touch another way, or try again later.', 'distinctui-plus' ), array(), $post_id, $key );
			return;
		}

		// Spam: a filled honeypot, or too many sends from one place.
		if ( ! empty( $_POST['dui_website'] ) ) {
			self::answer( $ajax, true, $section['success'] ?? '', array(), $post_id, $key );
			return;
		}
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$limit = 'dui_form_' . md5( $ip . wp_salt( 'nonce' ) );
		$count = (int) get_transient( $limit );
		if ( $count >= 5 ) {
			self::answer( $ajax, false, __( 'That\'s a lot of messages in a few minutes. Wait ten minutes, then send it again.', 'distinctui-plus' ), array(), $post_id, $key );
			return;
		}

		$values = array();
		$errors = array();
		foreach ( $section['fields'] as $f ) {
			$raw   = isset( $_POST[ $f['name'] ] ) ? wp_unslash( $_POST[ $f['name'] ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised by field type below.
			$value = 'textarea' === $f['type'] ? sanitize_textarea_field( (string) $raw ) : trim( sanitize_text_field( (string) $raw ) );
			if ( 'email' === $f['type'] ) {
				$value = trim( $value );
			}
			$problem = self::problem( $f, $value );
			if ( $problem ) {
				$errors[ $f['name'] ] = $problem;
			}
			$values[ $f['name'] ] = array(
				'label' => $f['label'],
				'value' => $value,
			);
		}
		// phpcs:enable
		if ( $errors ) {
			self::answer( $ajax, false, __( 'Some answers need a look. Each one says what to change.', 'distinctui-plus' ), $errors, $post_id, $key );
			return;
		}
		set_transient( $limit, $count + 1, 10 * MINUTE_IN_SECONDS );

		$who   = '';
		$reply = '';
		foreach ( $values as $name => $v ) {
			if ( ! $who && in_array( $name, array( 'name', 'your_name', 'full_name' ), true ) ) {
				$who = $v['value'];
			}
			if ( ! $reply && is_email( $v['value'] ) ) {
				$reply = $v['value'];
			}
		}
		$heading  = $section['heading'] ?? __( 'Form', 'distinctui-plus' );
		$entry_id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'private',
				/* translators: 1: who sent it, 2: form heading. */
				'post_title'  => $who ? sprintf( __( '%1$s, %2$s', 'distinctui-plus' ), $who, $heading ) : $heading,
				'post_parent' => $post_id,
			)
		);
		if ( $entry_id && ! is_wp_error( $entry_id ) ) {
			update_post_meta( $entry_id, '_dui_values', wp_slash( wp_json_encode( $values, JSON_UNESCAPED_UNICODE ) ) );
			update_post_meta( $entry_id, '_dui_form', sanitize_key( $key ) );
		}

		$to    = DUI_Plus_Settings::builder()['form_email'];
		$to    = $to ? $to : get_option( 'admin_email' );
		$lines = array();
		foreach ( $values as $v ) {
			$lines[] = $v['label'] . ': ' . ( '' === $v['value'] ? '-' : $v['value'] );
		}
		$lines[] = '';
		/* translators: %s: page URL. */
		$lines[] = sprintf( __( 'Sent from %s', 'distinctui-plus' ), get_permalink( $post_id ) );
		$lines[] = __( 'Every entry is also kept in WordPress, under distinctUI+, Form entries.', 'distinctui-plus' );
		wp_mail(
			$to,
			/* translators: 1: form heading, 2: page title. */
			sprintf( __( 'New answer: %1$s, on %2$s', 'distinctui-plus' ), $heading, get_the_title( $post_id ) ),
			implode( "\n", $lines ),
			$reply ? array( 'Reply-To: ' . $reply ) : array()
		);

		/**
		 * After a distinctUI+ form entry is stored and emailed.
		 *
		 * @param int   $entry_id Entry id.
		 * @param array $values   Answers by field name: { label, value }.
		 * @param int   $post_id  Page id.
		 * @param array $section  The form section.
		 */
		do_action( 'distinctui_plus_form_entry', $entry_id, $values, $post_id, $section );

		self::answer( $ajax, true, $section['success'] ?? __( 'Thanks. We\'ve got it, and we\'ll be in touch soon.', 'distinctui-plus' ), array(), $post_id, $key );
	}

	/**
	 * Answer a submission: JSON for the script, or a redirect back.
	 *
	 * @param bool   $ajax    Sent by the script.
	 * @param bool   $ok      Worked.
	 * @param string $message What to say.
	 * @param array  $errors  Field name => problem.
	 * @param int    $post_id Page id.
	 * @param string $key     Form key.
	 */
	private static function answer( $ajax, $ok, $message, array $errors, $post_id, $key ) {
		if ( ! $message ) {
			$message = __( 'Thanks. We\'ve got it, and we\'ll be in touch soon.', 'distinctui-plus' );
		}
		if ( $ajax ) {
			wp_send_json(
				array(
					'ok'      => $ok,
					'message' => $message,
					'errors'  => (object) $errors,
				),
				$ok ? 200 : 422
			);
		}
		$back = $post_id ? get_permalink( $post_id ) : home_url( '/' );
		$back = add_query_arg(
			$ok ? array( 'dui_sent' => rawurlencode( $key ) ) : array(
				'dui_error' => rawurlencode( $key ),
				'dui_msg'   => rawurlencode( $message ),
				'dui_bad'   => rawurlencode( wp_json_encode( $errors ) ),
			),
			$back
		);
		wp_safe_redirect( $back . '#dui-' . rawurlencode( $key ), 303 );
		exit;
	}

	/**
	 * Entry list columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function columns( $cols ) {
		return array(
			'cb'       => $cols['cb'] ?? '',
			'title'    => __( 'Entry', 'distinctui-plus' ),
			'dui_page' => __( 'Page', 'distinctui-plus' ),
			'date'     => __( 'Sent', 'distinctui-plus' ),
		);
	}

	/**
	 * Entry list column values.
	 *
	 * @param string $col     Column.
	 * @param int    $post_id Entry id.
	 */
	public static function column( $col, $post_id ) {
		if ( 'dui_page' === $col ) {
			$parent = wp_get_post_parent_id( $post_id );
			echo $parent ? '<a href="' . esc_url( get_permalink( $parent ) ) . '">' . esc_html( get_the_title( $parent ) ) . '</a>' : '';
		}
	}

	/**
	 * The answers, on the entry screen.
	 *
	 * @param WP_Post $post Entry.
	 */
	public static function meta_box( $post ) {
		add_meta_box(
			'dui_values',
			__( 'Answers', 'distinctui-plus' ),
			function ( $post ) {
				$values = json_decode( (string) get_post_meta( $post->ID, '_dui_values', true ), true );
				echo '<table class="widefat striped"><tbody>';
				foreach ( (array) $values as $v ) {
					echo '<tr><th style="width:30%">' . esc_html( $v['label'] ) . '</th><td>' . nl2br( esc_html( $v['value'] ) ) . '</td></tr>';
				}
				echo '</tbody></table>';
			},
			self::CPT,
			'normal',
			'high'
		);
	}
}
