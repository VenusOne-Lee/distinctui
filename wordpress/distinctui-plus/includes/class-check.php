<?php
/**
 * The rule check, and one-click fixes.
 *
 * check() is a PHP port of checkSpec() in skills/distinctui/assets/check.js,
 * with the same ids, rules, messages and fixes, so the canvas, the MCP
 * server at distinctui.com/mcp and WordPress abilities all say the same
 * thing. tests/check-parity.mjs runs both on the same specs.
 *
 * The canvas also runs checkHTML() from check.js on the rendered page, for
 * the checks only a finished page can show (colours, contrast, alt text).
 *
 * fix() goes one step further than the shared checker: it changes the spec
 * so the errors go away, and says what it changed in plain words. Imports
 * and AI edits go through it before they land.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks specs against the twelve rules.
 */
class DUI_Plus_Check {

	const PRIMARY_TYPES = array( 'hero', 'cta', 'form', 'pricing' );
	const LEFT_OUT      = '/toast|popup|carousel|slider|modal|spinner/';

	/**
	 * The shared rules.json: from the plugin's copy, or the repo when
	 * running from a checkout.
	 *
	 * @return array
	 */
	public static function rules() {
		static $rules = null;
		if ( null === $rules ) {
			$rules = array();
			foreach ( array( DUI_PLUS_DIR . 'assets/vendor/rules.json', dirname( DUI_PLUS_DIR, 2 ) . '/skills/distinctui/assets/rules.json' ) as $file ) {
				if ( is_readable( $file ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					$rules = (array) json_decode( (string) file_get_contents( $file ), true );
					break;
				}
			}
		}
		return $rules;
	}

	/**
	 * A rule's title.
	 *
	 * @param int|string $rule 1 to 12, access or left-out.
	 * @return string
	 */
	public static function title( $rule ) {
		$titles = array(
			'access'   => 'Everyone can use it',
			'left-out' => 'Left out on purpose',
		);
		foreach ( self::rules()['rules'] ?? array() as $r ) {
			$titles[ $r['n'] ] = $r['title'];
		}
		return $titles[ $rule ] ?? '';
	}

	/**
	 * Check a spec. Same shape as checkSpec() in check.js:
	 * { ok, errors, warnings, findings: [ { id, rule, title, severity, message, fix, where } ] }.
	 *
	 * @param array $spec A spec (cleaned or raw).
	 * @return array
	 */
	public static function check( array $spec ) {
		$findings = array();
		$seen     = array();
		$add      = function ( $id, $rule, $severity, $message, $fix, $where ) use ( &$findings, &$seen ) {
			$key = $id . '|' . $message . '|' . $where;
			if ( isset( $seen[ $key ] ) ) {
				return;
			}
			$seen[ $key ] = true;
			$findings[]   = array(
				'id'       => $id,
				'rule'     => $rule,
				'title'    => self::title( $rule ),
				'severity' => $severity,
				'message'  => $message,
				'fix'      => $fix,
				'where'    => $where,
			);
		};

		$palettes = array_keys( self::rules()['palettes'] ?? array( 'Signal' => 1, 'Mzansi' => 1, 'Earth' => 1, 'Coast' => 1, 'Jacaranda' => 1 ) );
		$sig      = isset( $spec['signature'] ) && is_array( $spec['signature'] ) ? $spec['signature'] : array();
		if ( ! empty( $sig['colour'] ) && ! preg_match( '/^#[0-9a-f]{3,8}$/i', $sig['colour'] ) ) {
			$add( 'sig-colour', 3, 'error', sprintf( 'The signature colour "%s" isn\'t a hex colour.', $sig['colour'] ), 'Use one hex colour, such as #1f3bff, or pick a palette.', 'signature' );
		}
		if ( ! empty( $sig['palette'] ) && ! in_array( ucfirst( strtolower( $sig['palette'] ) ), $palettes, true ) ) {
			$add( 'sig-palette', 3, 'error', sprintf( 'There\'s no palette called "%s".', $sig['palette'] ), 'Pick one of ' . implode( ', ', $palettes ) . '.', 'signature' );
		}
		if ( ( isset( $spec['theme'] ) && 'dark' === $spec['theme'] ) || ! empty( $spec['dark'] ) ) {
			$add( 'dark-mode', 1, 'error', 'The spec asks for dark mode.', 'Remove it. distinctUI is light only.', 'theme' );
		}

		$sections = isset( $spec['sections'] ) && is_array( $spec['sections'] ) ? array_values( $spec['sections'] ) : array();
		if ( ! $sections ) {
			$add( 'empty', 12, 'warning', 'The page has no sections.', 'Start with a hero, then add what the page is for.', 'sections' );
		}
		$heroes = 0;
		foreach ( $sections as $i => $s ) {
			$type  = $s['type'] ?? '';
			$where = 'sections[' . $i . '] (' . ( $type ? $type : '?' ) . ')';
			if ( ! $type ) {
				$add( 'type', 12, 'error', 'A section has no type.', 'Set type to hero, features, pricing, questions, cta, form, products, posts, proof, steps or stats.', $where );
				continue;
			}
			if ( 'hero' === $type ) {
				++$heroes;
			}
			if ( ! empty( $s['heading'] ) ) {
				$words = preg_split( '/\s+/u', trim( $s['heading'] ) );
				if ( empty( $s['bold'] ) && count( $words ) > 1 ) {
					$add( 'no-bold-word', 5, 'warning', sprintf( 'The heading "%s" has no bold word set, so the composer will pick the longest word.', self::snippet( $s['heading'], 50 ) ), 'Set bold to the word that carries the meaning.', $where );
				} elseif ( ! empty( $s['bold'] ) && false === strpos( $s['heading'], $s['bold'] ) ) {
					$add( 'bold-missing', 5, 'warning', sprintf( 'The bold word "%s" isn\'t in the heading.', $s['bold'] ), 'Pick a word that\'s in the heading.', $where );
				}
				if ( preg_match( '/!\s*$/', $s['heading'] ) ) {
					$add( 'exclaim', 12, 'warning', 'The heading ends with an exclamation mark.', 'Say it plainly, without the exclamation mark.', $where );
				}
			}
			$styling = array_merge( $s, isset( $s['_off'] ) ? array_fill_keys( array_keys( $s['_off'] ), true ) : array() );
			foreach ( array( 'colour', 'color', 'background', 'accent', 'shadow', 'gradient', 'font' ) as $k ) {
				if ( isset( $styling[ $k ] ) ) {
					$rule = 'font' === $k ? 4 : ( 'shadow' === $k ? 7 : 3 );
					$add( 'styling', $rule, 'error', sprintf( 'The section sets its own %s.', $k ), 'Sections don\'t take styling. Choose a layout instead; colour goes only on the mark.', $where );
				}
			}
			if ( ! empty( $s['secondary'] ) && empty( $s['primary'] ) ) {
				$add( 'secondary-only', 6, 'warning', 'There\'s a second action but no main one.', 'Make the main action primary. The second one is outlined.', $where );
			}
			if ( isset( $s['actions'] ) && is_array( $s['actions'] ) ) {
				$primaries = array_filter(
					$s['actions'],
					function ( $a ) {
						return is_array( $a ) && 'primary' === ( $a['kind'] ?? '' );
					}
				);
				if ( count( $primaries ) > 1 ) {
					$add( 'two-black-pills', 6, 'error', 'More than one main action in a section.', 'Keep one primary action. Make the others secondary.', $where );
				}
			}
			foreach ( array( $s['primary'] ?? null, $s['secondary'] ?? null ) as $a ) {
				if ( is_array( $a ) && ! empty( $a['label'] ) && preg_match( '/^(submit|click here|click|ok|go)$/i', trim( $a['label'] ) ) ) {
					$add( 'vague-button', 12, 'warning', sprintf( 'A button says "%s".', $a['label'] ), 'Say what happens when it\'s pressed.', $where );
				}
			}
			$items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
			if ( 'pricing' === $type && count( array_filter( array_column( $items, 'featured' ) ) ) > 1 ) {
				$add( 'two-featured', 6, 'error', 'More than one plan is featured.', 'Feature one plan. Its button is the black pill, the others are outlined.', $where );
			}
			if ( 'products' === $type ) {
				foreach ( $items as $k => $p ) {
					if ( ! empty( $p['image'] ) && ! isset( $p['alt'] ) ) {
						$add( 'img-alt', 'access', 'warning', sprintf( 'Product %d has a photo with no alt text, so the composer will use its name.', $k + 1 ), 'Say what\'s in the photo, such as the product on a grey background.', $where );
					}
				}
			}
			if ( 'form' === $type && isset( $s['fields'] ) && is_array( $s['fields'] ) ) {
				foreach ( $s['fields'] as $k => $f ) {
					if ( empty( $f['label'] ) ) {
						$add( 'label', 'access', 'error', sprintf( 'Field %d has no label.', $k + 1 ), 'Every field gets a label above it.', $where );
					}
				}
			}
			if ( 'form' === $type && ! empty( $s['submit'] ) && preg_match( '/^(submit|send|ok)$/i', trim( $s['submit'] ) ) ) {
				$add( 'vague-button', 12, 'warning', sprintf( 'The form button says "%s".', $s['submit'] ), 'Say what happens: "Ask for a slot", "Join the waitlist".', $where );
			}
			if ( preg_match( self::LEFT_OUT, $type ) ) {
				$add( 'left-out', 'left-out', 'error', sprintf( 'A %s section.', $type ), 'Left out on purpose. Use a form, rows or a status notice instead.', $where );
			}
			if ( in_array( $type, self::PRIMARY_TYPES, true ) && 'form' !== $type && 'pricing' !== $type && empty( $s['primary'] ) ) {
				$add( 'no-action', 6, 'warning', 'This section has no main action.', 'Add one primary action with a label that says what happens.', $where );
			}
		}
		if ( $heroes > 1 ) {
			$add( 'many-heroes', 5, 'warning', sprintf( 'There are %d heroes.', $heroes ), 'Use one hero at the top. Other sections use section headings.', 'sections' );
		}
		if ( $sections && 'hero' !== ( $sections[0]['type'] ?? '' ) ) {
			$add( 'hero-first', 5, 'warning', 'The page doesn\'t start with a hero.', 'Start with a hero that says what this is, in a few words.', 'sections[0]' );
		}

		// distinctUI+ adds one check the shared spec can't know about: a type
		// it doesn't render at all.
		$types = DUI_Plus_Spec::types();
		foreach ( $sections as $i => $s ) {
			$type = $s['type'] ?? '';
			if ( $type && ! isset( $types[ $type ] ) && ! preg_match( self::LEFT_OUT, $type ) ) {
				$add( 'unknown-type', 12, 'error', sprintf( 'There\'s no %s section.', $type ), 'Use hero, proof, features, steps, stats, pricing, questions, cta, form, products or posts.', 'sections[' . $i . '] (' . $type . ')' );
			}
		}

		usort(
			$findings,
			function ( $a, $b ) {
				return $a['severity'] === $b['severity'] ? 0 : ( 'error' === $a['severity'] ? -1 : 1 );
			}
		);
		$errors = count(
			array_filter(
				$findings,
				function ( $f ) {
					return 'error' === $f['severity'];
				}
			)
		);
		return array(
			'ok'       => 0 === $errors,
			'errors'   => $errors,
			'warnings' => count( $findings ) - $errors,
			'findings' => $findings,
		);
	}

	/**
	 * Shorten text for a message.
	 *
	 * @param string $s Text.
	 * @param int    $n Length.
	 * @return string
	 */
	private static function snippet( $s, $n ) {
		$s = trim( preg_replace( '/\s+/u', ' ', (string) $s ) );
		return mb_strlen( $s ) > $n ? mb_substr( $s, 0, $n - 1 ) . '…' : $s;
	}

	/**
	 * What a left-out section becomes instead.
	 *
	 * @param string $type Section type.
	 * @return string
	 */
	private static function stand_in( $type ) {
		if ( preg_match( '/carousel|slider|gallery|testimonial/', $type ) ) {
			return 'features';
		}
		if ( preg_match( '/popup|modal|newsletter|signup|subscribe/', $type ) ) {
			return 'form';
		}
		if ( preg_match( '/faq/', $type ) ) {
			return 'questions';
		}
		if ( preg_match( '/team|people|logos|clients/', $type ) ) {
			return 'proof';
		}
		if ( preg_match( '/blog|news|articles/', $type ) ) {
			return 'posts';
		}
		if ( preg_match( '/shop|store/', $type ) ) {
			return 'products';
		}
		if ( preg_match( '/plans|prices/', $type ) ) {
			return 'pricing';
		}
		if ( preg_match( '/how|process/', $type ) ) {
			return 'steps';
		}
		if ( preg_match( '/numbers|metrics/', $type ) ) {
			return 'stats';
		}
		if ( preg_match( '/banner|closing|contact-cta|footer-cta/', $type ) ) {
			return 'cta';
		}
		return '';
	}

	/**
	 * Fix a spec so every error is gone, and say what changed.
	 *
	 * @param array $spec Raw or clean spec.
	 * @return array{spec: array, changes: string[]}
	 */
	public static function fix( array $spec ) {
		$changes = array();
		$types   = DUI_Plus_Spec::types();

		if ( ( isset( $spec['theme'] ) && 'dark' === $spec['theme'] ) || ! empty( $spec['dark'] ) ) {
			unset( $spec['theme'], $spec['dark'] );
			$changes[] = 'The page asked for dark mode, so it\'s light. distinctUI is light only.';
		}
		if ( ! empty( $spec['signature']['colour'] ) && ! sanitize_hex_color( $spec['signature']['colour'] ) ) {
			unset( $spec['signature']['colour'] );
			$changes[] = 'The signature colour wasn\'t a hex colour, so the palette\'s first ring is used.';
		}

		$sections = array();
		$heroes   = 0;
		foreach ( (array) ( $spec['sections'] ?? array() ) as $s ) {
			if ( ! is_array( $s ) ) {
				continue;
			}
			$type = sanitize_key( $s['type'] ?? '' );
			if ( ! isset( $types[ $type ] ) ) {
				$to = self::stand_in( $type );
				if ( $to ) {
					/* translators: 1: old type, 2: new type. */
					$changes[] = sprintf( 'A %1$s became a %2$s section, so nothing is hidden or pops up.', $type ? $type : 'section', $types[ $to ]['name'] );
					$s['type'] = $to;
					unset( $s['layout'] );
					$type = $to;
				} else {
					$changes[] = sprintf( 'Took out a %s section: there\'s no section like it.', $type ? $type : 'nameless' );
					continue;
				}
			}
			if ( isset( $s['_off'] ) && is_array( $s['_off'] ) ) {
				$s = array_merge( $s, $s['_off'] );
				unset( $s['_off'] );
			}
			foreach ( array( 'colour', 'color', 'background', 'accent', 'shadow', 'gradient', 'font', 'style' ) as $k ) {
				if ( isset( $s[ $k ] ) ) {
					unset( $s[ $k ] );
					$changes[] = sprintf(
						'The %1$s section had its own %2$s. Sections don\'t take styling, so it\'s gone%3$s.',
						$types[ $type ]['name'],
						$k,
						in_array( $k, array( 'colour', 'color', 'accent', 'background', 'gradient' ), true ) ? '; your colour lives on the mark' : ''
					);
				}
			}
			// A loose list of buttons from an import: the first is the main
			// action, the next is the second action, the rest are dropped.
			if ( isset( $s['actions'] ) && is_array( $s['actions'] ) ) {
				$list = array_values( array_filter( $s['actions'], 'is_array' ) );
				usort(
					$list,
					function ( $a, $b ) {
						return ( 'primary' === ( $b['kind'] ?? '' ) ) <=> ( 'primary' === ( $a['kind'] ?? '' ) );
					}
				);
				if ( empty( $s['primary'] ) && isset( $list[0] ) ) {
					$s['primary'] = $list[0];
				}
				if ( empty( $s['secondary'] ) && isset( $list[1] ) ) {
					$s['secondary'] = $list[1];
				}
				if ( count( $list ) > 1 && 'primary' === ( $list[1]['kind'] ?? '' ) ) {
					$changes[] = sprintf( 'The %s section had two black pills, so the second is now outlined.', $types[ $type ]['name'] );
				}
				if ( count( $list ) > 2 ) {
					$changes[] = sprintf( 'The %s section had %d buttons. It keeps two: the main one and an outlined one.', $types[ $type ]['name'], count( $list ) );
				}
				unset( $s['actions'] );
			}
			if ( ! empty( $s['secondary'] ) && empty( $s['primary'] ) ) {
				$s['primary'] = $s['secondary'];
				unset( $s['secondary'] );
				$changes[] = sprintf( 'The %s section\'s only button is now its main action.', $types[ $type ]['name'] );
			}
			if ( ! empty( $s['heading'] ) ) {
				$clean = preg_replace( '/!+\s*$/', '', $s['heading'] );
				if ( $clean !== $s['heading'] ) {
					$s['heading'] = rtrim( $clean );
					$changes[]    = sprintf( 'Took the exclamation mark off the %s heading.', mb_strtolower( $types[ $type ]['name'] ) );
				}
				if ( empty( $s['bold'] ) || false === strpos( $s['heading'], $s['bold'] ) ) {
					$s['bold'] = DUI_Plus_Spec::pick_bold( $s['heading'] );
					/* translators: %s: the bold word. */
					$changes[] = sprintf( 'Made "%1$s" the bold word in the %2$s heading. Double-click another word to change it.', $s['bold'], mb_strtolower( $types[ $type ]['name'] ) );
				}
			}
			if ( 'pricing' === $type && ! empty( $s['items'] ) && is_array( $s['items'] ) ) {
				$seen = false;
				foreach ( $s['items'] as $k => $plan ) {
					if ( ! empty( $plan['featured'] ) ) {
						if ( $seen ) {
							$s['items'][ $k ]['featured'] = false;
							$changes[]                    = sprintf( 'Only one plan gets the black edge, so %s is a normal card now.', $plan['name'] ?? 'a plan' );
						}
						$seen = true;
					}
				}
			}
			if ( 'products' === $type && ! empty( $s['items'] ) && is_array( $s['items'] ) ) {
				foreach ( $s['items'] as $k => $p ) {
					if ( ! empty( $p['image'] ) && empty( $p['alt'] ) ) {
						/* translators: %s: product name. */
						$s['items'][ $k ]['alt'] = sprintf( '%s on a grey background', $p['name'] ?? 'The product' );
						$changes[]               = sprintf( 'Gave the photo of %s alt text. Check it says what\'s in the photo.', $p['name'] ?? 'a product' );
					}
				}
			}
			if ( 'form' === $type && ! empty( $s['fields'] ) && is_array( $s['fields'] ) ) {
				foreach ( $s['fields'] as $k => $f ) {
					if ( is_array( $f ) && empty( $f['label'] ) ) {
						$s['fields'][ $k ]['label'] = ucfirst( str_replace( '_', ' ', $f['name'] ?? ( $f['type'] ?? 'Your answer' ) ) );
						$changes[]                  = sprintf( 'Field %d had no label, so it\'s labelled "%s". Check it reads well.', $k + 1, $s['fields'][ $k ]['label'] );
					}
				}
			}
			if ( 'hero' === $type ) {
				++$heroes;
				if ( $heroes > 1 ) {
					$s['type'] = 'cta';
					unset( $s['layout'] );
					$changes[] = 'A second hero became a call to action. A page gets one hero, at the top.';
				}
			}
			$sections[] = $s;
		}
		// The hero goes first.
		foreach ( $sections as $i => $s ) {
			if ( 'hero' === $s['type'] && $i > 0 ) {
				array_splice( $sections, $i, 1 );
				array_unshift( $sections, $s );
				$changes[] = 'Moved the hero to the top.';
				break;
			}
		}
		$spec['sections'] = $sections;
		return array(
			'spec'    => DUI_Plus_Spec::clean( $spec ),
			'changes' => array_values( array_unique( $changes ) ),
		);
	}
}
