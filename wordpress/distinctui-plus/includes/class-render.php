<?php
/**
 * Spec to blocks.
 *
 * Each section type has a few layouts, and each layout writes the same
 * core blocks the theme's patterns use. Draft pages draw the mark dashed;
 * published pages draw it solid. Nothing here can add a colour: the only
 * colour on the page comes from the theme's ring variables.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders page specs as block markup.
 */
class DUI_Plus_Render {

	/**
	 * Render context for the current call.
	 *
	 * @var array{live: bool, post_id: int, sig: array}
	 */
	private static $ctx = array();

	/**
	 * The whole page as block markup.
	 *
	 * @param array $spec Clean spec.
	 * @param array $ctx  live (bool), post_id (int), canvas (bool).
	 * @return string
	 */
	public static function page( array $spec, array $ctx = array() ) {
		$out = array();
		foreach ( $spec['sections'] as $i => $section ) {
			$markup = self::section( $section, $i, $ctx, $spec );
			if ( '' !== $markup ) {
				$out[] = $markup;
			}
		}
		return implode( "\n\n", $out );
	}

	/**
	 * One section as block markup.
	 *
	 * @param array $s    Clean section.
	 * @param int   $i    Index on the page.
	 * @param array $ctx  Context.
	 * @param array $spec The whole spec, for anchors.
	 * @return string
	 */
	public static function section( array $s, $i, array $ctx = array(), array $spec = array() ) {
		self::$ctx = array(
			'live'    => ! empty( $ctx['live'] ),
			'post_id' => isset( $ctx['post_id'] ) ? (int) $ctx['post_id'] : 0,
			'sig'     => DUI_Plus_Settings::signature(),
			'anchor'  => self::anchor( $s, $i, $spec ),
			'index'   => (int) $i,
		);
		$was                     = DUI_Plus_Blocks::$canvas;
		DUI_Plus_Blocks::$canvas = ! empty( $ctx['canvas'] );
		$method                  = 'render_' . $s['type'];
		$markup                  = method_exists( __CLASS__, $method ) ? self::$method( $s ) : '';
		DUI_Plus_Blocks::$canvas = $was;
		return (string) apply_filters( 'distinctui_plus_render_section', $markup, $s, $i, $ctx );
	}

	/**
	 * The section's id: its own anchor, or its type if it's the first of
	 * that type (so #pricing and #questions just work).
	 *
	 * @param array $s    Section.
	 * @param int   $i    Index.
	 * @param array $spec Spec.
	 * @return string
	 */
	private static function anchor( array $s, $i, array $spec ) {
		if ( ! empty( $s['anchor'] ) ) {
			return $s['anchor'];
		}
		foreach ( $spec['sections'] ?? array() as $j => $other ) {
			if ( ( $other['type'] ?? '' ) === $s['type'] ) {
				return $j === $i ? $s['type'] : '';
			}
		}
		return $s['type'];
	}

	/* ------------------------------------------------------------ Parts */

	/**
	 * The outer group every section sits in: the theme's section rhythm.
	 *
	 * @param string $inner   Inner markup.
	 * @param string $width   Content width.
	 * @param string $gap     Gap between children.
	 * @param string $justify left or center.
	 * @return string
	 */
	private static function wrap( $inner, $width = '1152px', $gap = '2rem', $justify = 'left' ) {
		$attrs = array();
		if ( self::$ctx['anchor'] ) {
			$attrs['anchor'] = self::$ctx['anchor'];
		}
		$attrs['align']  = 'wide';
		$attrs['style']  = array(
			'spacing' => array(
				'padding'  => array( 'top' => 'var:preset|spacing|80' ),
				'blockGap' => $gap,
			),
		);
		$attrs['layout'] = array_filter(
			array(
				'type'           => 'constrained',
				'justifyContent' => 'center' === $justify ? '' : 'left',
				'contentSize'    => $width,
			)
		);
		return DUI_Plus_Blocks::group( $inner, $attrs );
	}

	/**
	 * Eyebrow, heading with its bold word, and lede: the head of most sections.
	 *
	 * @param array  $s     Section.
	 * @param int    $level Heading level.
	 * @param string $align '' or center.
	 * @param string $hclass Extra heading class.
	 * @return string
	 */
	private static function head( array $s, $level = 2, $align = '', $hclass = '' ) {
		$out   = '';
		$align = $align ? array( 'align' => $align ) : array();
		if ( ! empty( $s['eyebrow'] ) ) {
			$out .= DUI_Plus_Blocks::p( esc_html( $s['eyebrow'] ), array_merge( $align, array( 'className' => 'is-style-eyebrow' ) ), 'eyebrow' ) . "\n\n";
		}
		if ( ! empty( $s['heading'] ) ) {
			$hattrs = array();
			if ( $align ) {
				$hattrs['textAlign'] = $align['align'];
			}
			if ( $hclass ) {
				$hattrs['className'] = $hclass;
			}
			$out .= DUI_Plus_Blocks::h( DUI_Plus_Blocks::bold( $s['heading'], $s['bold'] ?? '' ), $level, $hattrs, 'heading' ) . "\n\n";
		}
		if ( ! empty( $s['lede'] ) ) {
			$out .= DUI_Plus_Blocks::p( nl2br( esc_html( $s['lede'] ), false ), array_merge( $align, array( 'className' => 'is-style-lede' ) ), 'lede' ) . "\n\n";
		}
		return $out;
	}

	/**
	 * The main and second action. One black pill, at most one outlined pill
	 * (or a text link, in calls to action).
	 *
	 * @param array  $s         Section.
	 * @param string $second    outline or text-link.
	 * @param string $justify   '' or center.
	 * @return string
	 */
	private static function actions( array $s, $second = 'outline', $justify = '' ) {
		$list = array();
		if ( ! empty( $s['primary']['label'] ) ) {
			$list[] = array( $s['primary']['label'], $s['primary']['href'], 'fill', 'primary.label' );
		}
		if ( ! empty( $s['secondary']['label'] ) ) {
			$list[] = array( $s['secondary']['label'], $s['secondary']['href'], $second, 'secondary.label' );
		}
		$attrs = array();
		if ( $justify ) {
			$attrs['layout'] = array(
				'type'           => 'flex',
				'justifyContent' => $justify,
			);
		}
		return DUI_Plus_Blocks::buttons( $list, $attrs );
	}

	/**
	 * The signature mark: your picture in the ring, or your initials.
	 * Dashed until the page is live.
	 *
	 * @param string $size s, m or l.
	 * @param string $align '' or center.
	 * @return string
	 */
	private static function mark( $size = 'l', $align = '' ) {
		$sig    = self::$ctx['sig'];
		$dashed = ! self::$ctx['live'];
		if ( $sig['image'] ) {
			$px = array(
				's' => '40px',
				'm' => '80px',
				'l' => '112px',
			)[ $size ];
			$attrs = array(
				'width'     => $px,
				'height'    => $px,
				'className' => $dashed ? 'is-style-mark-dashed' : 'is-style-mark',
			);
			if ( $align ) {
				$attrs['align'] = $align;
			}
			return DUI_Plus_Blocks::image( $sig['image'], '', $attrs );
		}
		$class = ( $dashed ? 'is-style-mark-dashed' : 'is-style-mark' ) . ' mark-' . $size;
		return DUI_Plus_Blocks::group(
			DUI_Plus_Blocks::p( esc_html( $sig['initials'] ) ),
			array( 'className' => $class . ( $align ? ' has-text-align-center' : '' ) )
		);
	}

	/**
	 * A face in a ring, for proof and people.
	 *
	 * @param array $item Item with initials or image.
	 * @param int   $k    Position, for the ring colour.
	 * @return string
	 */
	private static function face( array $item, $k ) {
		$ring = 'ring-' . ( isset( $item['ring'] ) ? $item['ring'] : ( ( $k % 5 ) + 1 ) );
		if ( ! empty( $item['image'] ) ) {
			return DUI_Plus_Blocks::image(
				$item['image'],
				$item['alt'] ?? ( $item['name'] ?? '' ),
				array(
					'width'     => '48px',
					'height'    => '48px',
					'className' => 'is-style-mark ' . $ring,
				)
			);
		}
		$initials = $item['initials'] ?? self::initials( (string) ( $item['name'] ?? $item['title'] ?? '' ) );
		return DUI_Plus_Blocks::group(
			DUI_Plus_Blocks::p( esc_html( $initials ) ),
			array( 'className' => 'is-style-mark mark-s ' . $ring )
		);
	}

	/**
	 * Initials from a name: first letters of the first two words.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	public static function initials( $name ) {
		$parts = preg_split( '/\s+/u', trim( $name ) );
		$out   = '';
		foreach ( array_slice( (array) $parts, 0, 2 ) as $p ) {
			$out .= mb_strtoupper( mb_substr( $p, 0, 1 ) );
		}
		return $out;
	}

	/**
	 * Card: a title and a line, in a card column.
	 *
	 * @param array  $item  Item.
	 * @param string $base  Slot base.
	 * @param string $title Title key.
	 * @return string
	 */
	private static function card_inner( array $item, $base, $title = 'title' ) {
		$out = '';
		if ( isset( $item[ $title ] ) ) {
			$out .= DUI_Plus_Blocks::h( esc_html( $item[ $title ] ), 3, array(), $base . $title ) . "\n\n";
		}
		if ( isset( $item['text'] ) && '' !== $item['text'] ) {
			$out .= DUI_Plus_Blocks::p( nl2br( esc_html( $item['text'] ), false ), array( 'textColor' => 'grey-800' ), $base . 'text' );
		}
		return $out;
	}

	/* --------------------------------------------------------- Sections */

	/**
	 * Hero.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_hero( array $s ) {
		if ( 'centred' === $s['layout'] ) {
			$inner = self::mark( 'm', 'center' ) . "\n\n" . self::head( $s, 1, 'center', 'is-style-hero' ) . self::actions( $s, 'outline', 'center' );
			return self::wrap( $inner, '52rem', '1.5rem', 'center' );
		}
		$text = DUI_Plus_Blocks::group(
			self::head( $s, 1, '', 'is-style-hero' ) . self::actions( $s ),
			array(
				'style'  => array(
					'spacing' => array( 'blockGap' => '1.25rem' ),
					'layout'  => array(
						'selfStretch' => 'fill',
						'flexSize'    => null,
					),
				),
				'layout' => array(
					'type'           => 'constrained',
					'justifyContent' => 'left',
					'contentSize'    => '46rem',
				),
			)
		);
		$row  = DUI_Plus_Blocks::group(
			self::mark( 'l' ) . "\n\n" . $text,
			array(
				'style'  => array( 'spacing' => array( 'blockGap' => '2.5rem' ) ),
				'layout' => array(
					'type'              => 'flex',
					'flexWrap'          => 'wrap',
					'verticalAlignment' => 'center',
				),
			)
		);
		return self::wrap( $row, '1152px', '2rem' );
	}

	/**
	 * Proof: faces and a line.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_proof( array $s ) {
		$faces = '';
		foreach ( $s['faces'] ?? array() as $k => $item ) {
			$faces .= self::face( $item, $k ) . "\n\n";
		}
		$line = trim( ( $s['more'] ?? '' ) . ( ! empty( $s['more'] ) && ! empty( $s['text'] ) ? '. ' : '' ) . ( $s['text'] ?? '' ) );
		$row = DUI_Plus_Blocks::group(
			DUI_Plus_Blocks::group(
				rtrim( $faces ),
				array(
					'className' => 'dui-faces',
					'layout'    => array(
						'type'     => 'flex',
						'flexWrap' => 'nowrap',
					),
				)
			) . "\n\n" . DUI_Plus_Blocks::p( esc_html( $line ), array( 'textColor' => 'grey-600' ), empty( $s['more'] ) ? 'text' : '' ),
			array(
				'style'  => array( 'spacing' => array( 'blockGap' => '1rem' ) ),
				'layout' => array(
					'type'              => 'flex',
					'flexWrap'          => 'wrap',
					'verticalAlignment' => 'center',
				),
			)
		);
		return self::wrap( self::head( $s ) . $row, '1152px', '1.5rem' );
	}

	/**
	 * Features: three cards, or rows.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_features( array $s ) {
		$items = $s['items'] ?? array();
		$head  = self::head_group( $s );
		if ( 'rows' === $s['layout'] ) {
			$rows = '';
			foreach ( $items as $k => $item ) {
				$rows .= DUI_Plus_Blocks::group(
					self::card_inner( $item, 'items.' . $k . '.' ),
					array(
						'style'  => array(
							'border'  => array(
								'top' => array(
									'color' => 'var:preset|color|grey-200',
									'width' => '1px',
								),
							),
							'spacing' => array(
								'padding'  => array( 'top' => '1.5rem' ),
								'blockGap' => '0.5rem',
							),
						),
						'layout' => array(
							'type'        => 'grid',
							'columnCount' => 2,
						),
					)
				) . "\n\n";
			}
			$list = DUI_Plus_Blocks::group( rtrim( $rows ), array( 'style' => array( 'spacing' => array( 'blockGap' => '1.5rem' ) ) ) );
			return self::wrap( $head . $list );
		}
		$cols = array();
		foreach ( $items as $k => $item ) {
			$cols[] = self::card_inner( $item, 'items.' . $k . '.' );
		}
		return self::wrap( $head . ( $cols ? DUI_Plus_Blocks::columns( $cols ) : '' ) );
	}

	/**
	 * The heading and lede in a narrower group, as the theme's patterns do.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function head_group( array $s ) {
		$head = self::head( $s );
		if ( '' === $head ) {
			return '';
		}
		return DUI_Plus_Blocks::group(
			rtrim( $head ),
			array(
				'style'  => array( 'spacing' => array( 'blockGap' => '1rem' ) ),
				'layout' => array(
					'type'           => 'constrained',
					'justifyContent' => 'left',
					'contentSize'    => '42rem',
				),
			)
		) . "\n\n";
	}

	/**
	 * Steps: numbered, in a grid.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_steps( array $s ) {
		$cells = '';
		foreach ( $s['items'] ?? array() as $k => $item ) {
			$cells .= DUI_Plus_Blocks::group(
				DUI_Plus_Blocks::p( sprintf( '%02d', $k + 1 ), array( 'className' => 'is-style-badge' ) ) . "\n\n" . self::card_inner( $item, 'items.' . $k . '.' ),
				array(
					'style'  => array( 'spacing' => array( 'blockGap' => '0.75rem' ) ),
					'layout' => array(
						'type'        => 'flex',
						'orientation' => 'vertical',
					),
				)
			) . "\n\n";
		}
		$grid = DUI_Plus_Blocks::group(
			rtrim( $cells ),
			array(
				'style'  => array( 'spacing' => array( 'blockGap' => '2rem' ) ),
				'layout' => array(
					'type'               => 'grid',
					'minimumColumnWidth' => '16rem',
				),
			)
		);
		return self::wrap( self::head_group( $s ) . $grid );
	}

	/**
	 * Numbers in a row: big and light, with a grey label.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_stats( array $s ) {
		$cells = '';
		foreach ( $s['items'] ?? array() as $k => $item ) {
			$cells .= DUI_Plus_Blocks::group(
				DUI_Plus_Blocks::p( esc_html( $item['value'] ?? '' ), array( 'fontSize' => 'section', 'className' => 'dui-stat' ), 'items.' . $k . '.value' ) . "\n\n"
				. DUI_Plus_Blocks::p( esc_html( $item['label'] ?? '' ), array( 'className' => 'is-style-meta' ), 'items.' . $k . '.label' ),
				array(
					'style'  => array(
						'border'  => array(
							'top' => array(
								'color' => 'var:preset|color|grey-200',
								'width' => '1px',
							),
						),
						'spacing' => array(
							'padding'  => array( 'top' => '1.25rem' ),
							'blockGap' => '0.25rem',
						),
					),
					'layout' => array(
						'type'        => 'flex',
						'orientation' => 'vertical',
					),
				)
			) . "\n\n";
		}
		$grid = DUI_Plus_Blocks::group(
			rtrim( $cells ),
			array(
				'style'  => array( 'spacing' => array( 'blockGap' => '1.5rem' ) ),
				'layout' => array(
					'type'               => 'grid',
					'minimumColumnWidth' => '12rem',
				),
			)
		);
		return self::wrap( self::head_group( $s ) . $grid );
	}

	/**
	 * Pricing: plans in cards, or one plan.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_pricing( array $s ) {
		$items    = $s['items'] ?? array();
		$featured = self::featured_index( $items );
		if ( 'one' === $s['layout'] && $items ) {
			$items    = array( $featured => $items[ $featured ] );
		}
		$cards = '';
		foreach ( $items as $k => $plan ) {
			$is_feat = ( $k === $featured );
			$base    = 'items.' . $k . '.';
			$price   = '<strong' . DUI_Plus_Blocks::slot( $base . 'price' ) . '>' . esc_html( $plan['price'] ?? '' ) . '</strong>';
			if ( ! empty( $plan['period'] ) ) {
				$price .= ' <span class="has-grey-600-color has-text-color" style="font-size:1rem;letter-spacing:0"' . DUI_Plus_Blocks::slot( $base . 'period' ) . '>' . esc_html( $plan['period'] ) . '</span>';
			}
			$inner  = DUI_Plus_Blocks::h( esc_html( $plan['name'] ?? '' ), 3, array(), $base . 'name' ) . "\n\n";
			$inner .= DUI_Plus_Blocks::wrap(
				'core/paragraph',
				array(
					'style' => array(
						'typography' => array(
							'fontSize'      => '3rem',
							'fontWeight'    => '300',
							'letterSpacing' => '-0.04em',
							'lineHeight'    => '1',
						),
					),
				),
				'<p style="font-size:3rem;font-weight:300;letter-spacing:-0.04em;line-height:1">' . $price . '</p>'
			) . "\n\n";
			if ( ! empty( $plan['text'] ) ) {
				$inner .= DUI_Plus_Blocks::p( esc_html( $plan['text'] ), array( 'textColor' => 'grey-800' ), $base . 'text' ) . "\n\n";
			}
			if ( ! empty( $plan['features'] ) ) {
				$li = '';
				foreach ( $plan['features'] as $f => $feature ) {
					$li .= DUI_Plus_Blocks::wrap( 'core/list-item', array(), '<li' . DUI_Plus_Blocks::slot( $base . 'features.' . $f ) . '>' . esc_html( $feature ) . '</li>' );
				}
				$inner .= DUI_Plus_Blocks::wrap( 'core/list', array( 'className' => 'dui-plan-features' ), '<ul class="wp-block-list dui-plan-features">' . $li . '</ul>' ) . "\n\n";
			}
			$cta    = $plan['cta'] ?? array(
				/* translators: %s: plan name. */
				'label' => sprintf( __( 'Choose %s', 'distinctui-plus' ), $plan['name'] ?? '' ),
				'href'  => $plan['href'] ?? ( $s['primary']['href'] ?? '#start' ),
			);
			$inner .= DUI_Plus_Blocks::buttons(
				array( array( $cta['label'], $cta['href'], $is_feat ? 'fill' : 'outline', $base . 'cta.label' ) ),
				array( 'style' => array( 'spacing' => array( 'margin' => array( 'top' => 'auto' ) ) ) ),
				true
			);
			$cards .= DUI_Plus_Blocks::group(
				$inner,
				array(
					'className' => $is_feat ? 'is-style-feature' : 'is-style-card',
					'style'     => array( 'spacing' => array( 'blockGap' => '1rem' ) ),
					'layout'    => array(
						'type'           => 'flex',
						'orientation'    => 'vertical',
						'justifyContent' => 'stretch',
					),
				)
			) . "\n\n";
		}
		$grid = DUI_Plus_Blocks::group(
			rtrim( $cards ),
			array(
				'style'  => array( 'spacing' => array( 'blockGap' => '1.5rem' ) ),
				'layout' => 'one' === $s['layout']
					? array(
						'type'           => 'constrained',
						'justifyContent' => 'left',
						'contentSize'    => '28rem',
					)
					: array(
						'type'               => 'grid',
						'minimumColumnWidth' => '17rem',
					),
			)
		);
		return self::wrap( self::head_group( $s ) . $grid );
	}

	/**
	 * Which plan is recommended: the first marked featured, or the middle one.
	 *
	 * @param array $items Plans.
	 * @return int
	 */
	public static function featured_index( array $items ) {
		foreach ( $items as $k => $plan ) {
			if ( ! empty( $plan['featured'] ) ) {
				return $k;
			}
		}
		return $items ? (int) floor( ( count( $items ) - 1 ) / 2 ) : 0;
	}

	/**
	 * Questions that open in place.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_questions( array $s ) {
		$list = '';
		foreach ( $s['items'] ?? array() as $k => $item ) {
			$list .= DUI_Plus_Blocks::details( $item['q'] ?? '', $item['a'] ?? '', 'items.' . $k . '.' ) . "\n\n";
		}
		$group = DUI_Plus_Blocks::group(
			rtrim( $list ),
			array(
				'style'  => array(
					'spacing' => array( 'blockGap' => '0' ),
					'border'  => array(
						'top' => array(
							'color' => 'var:preset|color|grey-200',
							'width' => '1px',
						),
					),
				),
				'layout' => array( 'type' => 'default' ),
			)
		);
		return self::wrap( self::head( $s ) . $group, '42rem' );
	}

	/**
	 * Call to action: centred, or split with the pill on the right.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_cta( array $s ) {
		if ( 'split' === $s['layout'] ) {
			$text = DUI_Plus_Blocks::group(
				rtrim( self::head( $s ) ),
				array(
					'style'  => array( 'spacing' => array( 'blockGap' => '0.75rem' ) ),
					'layout' => array(
						'type'           => 'constrained',
						'justifyContent' => 'left',
						'contentSize'    => '36rem',
					),
				)
			);
			$box = DUI_Plus_Blocks::group(
				$text . "\n\n" . self::actions( $s, 'text-link' ),
				array(
					'className' => 'is-style-feature',
					'layout'    => array(
						'type'              => 'flex',
						'flexWrap'          => 'wrap',
						'justifyContent'    => 'space-between',
						'verticalAlignment' => 'center',
					),
				)
			);
			return self::wrap( $box );
		}
		$box = DUI_Plus_Blocks::group(
			self::head( $s, 2, 'center' ) . self::actions( $s, 'text-link', 'center' ),
			array(
				'className' => 'is-style-feature',
				'style'     => array( 'spacing' => array( 'blockGap' => '1.25rem' ) ),
				'layout'    => array( 'type' => 'constrained' ),
			)
		);
		return self::wrap( $box );
	}

	/**
	 * A form. Native forms are plain HTML that posts back to the same page,
	 * so they stand without the plugin's scripts. A form plugin's form goes in
	 * as its shortcode.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_form( array $s ) {
		$form = self::form_markup( $s );
		return self::wrap( self::head( $s ) . $form, '36rem', '1.5rem' );
	}

	/**
	 * The form itself.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	public static function form_markup( array $s ) {
		$codes = array(
			'cf7'     => '[contact-form-7 id="%d"]',
			'wpforms' => '[wpforms id="%d" title="false" description="false"]',
			'gravity' => '[gravityform id="%d" title="false" description="false" ajax="true"]',
		);
		if ( ! empty( $s['plugin'] ) && isset( $codes[ $s['plugin'] ] ) && ! empty( $s['plugin_id'] ) ) {
			return DUI_Plus_Blocks::shortcode( sprintf( $codes[ $s['plugin'] ], (int) $s['plugin_id'] ) );
		}
		$key    = DUI_Plus_Forms::key( $s, self::$ctx['index'] );
		$id     = 'dui-' . $key;
		$fields = '';
		$list   = ! empty( $s['fields'] ) ? $s['fields'] : array_map( array( 'DUI_Plus_Spec', 'clean_field' ), DUI_Plus_Spec::default_fields( $s['layout'] ) );
		foreach ( $list as $k => $f ) {
			$fid   = $id . '-' . $f['name'];
			$label = esc_html( $f['label'] ) . ( $f['optional'] ? ' <span class="dui-optional">' . esc_html__( '(optional)', 'distinctui-plus' ) . '</span>' : '' );
			$req   = $f['optional'] ? '' : ' required';
			$hint  = ! empty( $f['hint'] ) ? '<span class="dui-hint" id="' . esc_attr( $fid ) . '-hint">' . esc_html( $f['hint'] ) . '</span>' : '';
			$desc  = ! empty( $f['hint'] ) ? ' aria-describedby="' . esc_attr( $fid ) . '-hint"' : '';
			$ph    = ! empty( $f['placeholder'] ) ? ' placeholder="' . esc_attr( $f['placeholder'] ) . '"' : '';
			$name  = ' name="' . esc_attr( $f['name'] ) . '" id="' . esc_attr( $fid ) . '"';
			$slot  = DUI_Plus_Blocks::slot( 'fields.' . $k . '.label' );
			if ( 'checkbox' === $f['type'] ) {
				$fields .= '<div class="dui-field dui-field-check"><label for="' . esc_attr( $fid ) . '"><input type="checkbox" value="yes"' . $name . $req . $desc . '> <span' . $slot . '>' . $label . '</span></label>' . $hint . '</div>';
				continue;
			}
			$control = '';
			if ( 'textarea' === $f['type'] ) {
				$control = '<textarea rows="4"' . $name . $req . $desc . $ph . '></textarea>';
			} elseif ( 'select' === $f['type'] ) {
				$opts = '<option value="">' . esc_html__( 'Choose one', 'distinctui-plus' ) . '</option>';
				foreach ( $f['options'] ?? array() as $o ) {
					$opts .= '<option>' . esc_html( $o ) . '</option>';
				}
				$control = '<select' . $name . $req . $desc . '>' . $opts . '</select>';
			} else {
				$auto    = array(
					'email' => ' autocomplete="email"',
					'tel'   => ' autocomplete="tel" inputmode="tel"',
				)[ $f['type'] ] ?? ( 'name' === $f['name'] ? ' autocomplete="name"' : '' );
				$control = '<input type="' . esc_attr( $f['type'] ) . '"' . $name . $req . $desc . $ph . $auto . '>';
			}
			$wide    = in_array( $f['type'], array( 'textarea', 'select' ), true ) ? ' dui-field-wide' : '';
			$fields .= '<div class="dui-field' . $wide . '"><label for="' . esc_attr( $fid ) . '"><span' . $slot . '>' . esc_html( $f['label'] ) . '</span>' . ( $f['optional'] ? ' <span class="dui-optional">' . esc_html__( '(optional)', 'distinctui-plus' ) . '</span>' : '' ) . '</label>' . $hint . $control . '</div>';
		}
		$submit = $s['submit'] ?? array(
			'booking'  => __( 'Ask for a slot', 'distinctui-plus' ),
			'waitlist' => __( 'Join the waitlist', 'distinctui-plus' ),
		)[ $s['layout'] ] ?? __( 'Send your message', 'distinctui-plus' );
		$action = ! empty( $s['action'] ) ? ' action="' . esc_url( $s['action'] ) . '"' : '';
		$html   = '<form class="dui-form" id="' . esc_attr( $id ) . '" method="post"' . $action . ' data-dui-form="' . esc_attr( $key ) . '">'
			. '<input type="hidden" name="dui_form" value="' . esc_attr( self::$ctx['post_id'] . ':' . $key ) . '">'
			. '<div class="dui-hp" aria-hidden="true"><label>' . esc_html__( 'Leave this empty', 'distinctui-plus' ) . '<input type="text" name="dui_website" tabindex="-1" autocomplete="off"></label></div>'
			. $fields
			. '<p class="dui-form-msg" role="status" aria-live="polite" data-sent="' . esc_attr( $s['success'] ?? __( 'Thanks. We\'ve got it, and we\'ll be in touch soon.', 'distinctui-plus' ) ) . '" hidden></p>'
			. '<div class="dui-form-actions"><button type="submit" class="wp-element-button"' . DUI_Plus_Blocks::slot( 'submit' ) . '>' . esc_html( $submit ) . '</button></div>'
			. '</form>';
		return DUI_Plus_Blocks::html( $html );
	}

	/**
	 * Products: from WooCommerce, or typed in.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_products( array $s ) {
		$min = '16rem';
		if ( 'woocommerce' === ( $s['source'] ?? '' ) && class_exists( 'WooCommerce' ) ) {
			$count = (int) ( $s['count'] ?? 3 );
			$card  = DUI_Plus_Blocks::group(
				DUI_Plus_Blocks::dynamic(
					'core/post-featured-image',
					array(
						'isLink'      => true,
						'aspectRatio' => '1',
					)
				) . "\n\n"
				. DUI_Plus_Blocks::dynamic(
					'core/post-title',
					array(
						'level'    => 3,
						'isLink'   => true,
						'fontSize' => 'medium',
					)
				) . "\n\n"
				. DUI_Plus_Blocks::dynamic( 'woocommerce/product-price', array( 'isDescendentOfQueryLoop' => true ) ) . "\n\n"
				. DUI_Plus_Blocks::dynamic( 'woocommerce/product-button', array( 'isDescendentOfQueryLoop' => true ) ),
				array(
					'className' => 'is-style-card dui-product',
					'style'     => array( 'spacing' => array( 'blockGap' => '0.75rem' ) ),
					'layout'    => array(
						'type'        => 'flex',
						'orientation' => 'vertical',
						'flexWrap'    => 'nowrap',
					),
				)
			);
			$query = self::query( 'product', $count, $s['category'] ?? '', 'product_cat', $card, $min );
			return self::wrap( self::head_group( $s ) . $query );
		}
		$items = $s['items'] ?? array();
		$cards = '';
		foreach ( $items as $k => $p ) {
			$base  = 'items.' . $k . '.';
			$inner = '';
			if ( ! empty( $p['image'] ) ) {
				$inner .= DUI_Plus_Blocks::image(
					$p['image'],
					$p['alt'] ?? ( $p['name'] ?? '' ),
					array(
						'aspectRatio' => '1',
						'className'   => 'dui-product-image',
					)
				) . "\n\n";
			}
			$inner .= DUI_Plus_Blocks::h( esc_html( $p['name'] ?? '' ), 3, array( 'fontSize' => 'medium' ), $base . 'name' ) . "\n\n";
			$inner .= DUI_Plus_Blocks::p( esc_html( $p['price'] ?? '' ), array(), $base . 'price' ) . "\n\n";
			$inner .= DUI_Plus_Blocks::buttons(
				array( array( $p['cta']['label'] ?? __( 'View', 'distinctui-plus' ), $p['cta']['href'] ?? ( $p['href'] ?? '#' ), 'outline', $base . 'cta.label' ) ),
				array( 'style' => array( 'spacing' => array( 'margin' => array( 'top' => 'auto' ) ) ) )
			);
			$cards .= DUI_Plus_Blocks::group(
				$inner,
				array(
					'className' => 'is-style-card dui-product',
					'style'     => array( 'spacing' => array( 'blockGap' => '0.75rem' ) ),
					'layout'    => array(
						'type'        => 'flex',
						'orientation' => 'vertical',
						'flexWrap'    => 'nowrap',
					),
				)
			) . "\n\n";
		}
		$grid = DUI_Plus_Blocks::group(
			rtrim( $cards ),
			array(
				'style'  => array( 'spacing' => array( 'blockGap' => '1.5rem' ) ),
				'layout' => array(
					'type'               => 'grid',
					'minimumColumnWidth' => $min,
				),
			)
		);
		return self::wrap( self::head_group( $s ) . $grid );
	}

	/**
	 * Posts: the latest, or one category, as cards. Typed-in items work too.
	 *
	 * @param array $s Section.
	 * @return string
	 */
	private static function render_posts( array $s ) {
		$featured = 'featured' === $s['layout'];
		if ( 'posts' === ( $s['source'] ?? '' ) || empty( $s['items'] ) ) {
			$count = $featured ? 1 : (int) ( $s['count'] ?? 3 );
			$card  = DUI_Plus_Blocks::group(
				DUI_Plus_Blocks::dynamic( 'core/post-featured-image', array( 'aspectRatio' => '16/10' ) ) . "\n\n"
				. DUI_Plus_Blocks::dynamic( 'core/post-date', array( 'fontSize' => 'small' ) ) . "\n\n"
				. DUI_Plus_Blocks::dynamic(
					'core/post-title',
					array_filter(
						array(
							'level'    => $featured ? 2 : 3,
							'isLink'   => true,
						)
					)
				) . "\n\n"
				. DUI_Plus_Blocks::dynamic( 'core/post-excerpt', array( 'excerptLength' => $featured ? 40 : 24 ) ),
				array(
					'className' => 'is-style-card',
					'style'     => array( 'spacing' => array( 'blockGap' => '0.75rem' ) ),
					'layout'    => array(
						'type'        => 'flex',
						'orientation' => 'vertical',
						'flexWrap'    => 'nowrap',
					),
				)
			);
			$query = self::query( 'post', $count, $s['category'] ?? '', 'category', $card, $featured ? '100%' : '18rem' );
			return self::wrap( self::head_group( $s ) . $query, $featured ? '46rem' : '1152px' );
		}
		$items = $featured ? array_slice( $s['items'], 0, 1, true ) : $s['items'];
		$cards = '';
		foreach ( $items as $k => $p ) {
			$base   = 'items.' . $k . '.';
			$title  = ! empty( $p['href'] ) ? '<a href="' . esc_url( $p['href'] ) . '">' . esc_html( $p['title'] ?? '' ) . '</a>' : esc_html( $p['title'] ?? '' );
			$inner  = ! empty( $p['date'] ) ? DUI_Plus_Blocks::p( esc_html( $p['date'] ), array( 'className' => 'is-style-meta' ), $base . 'date' ) . "\n\n" : '';
			$inner .= DUI_Plus_Blocks::h( $title, $featured ? 2 : 3, array(), $base . 'title' ) . "\n\n";
			$inner .= DUI_Plus_Blocks::p( esc_html( $p['text'] ?? '' ), array( 'textColor' => 'grey-800' ), $base . 'text' );
			$cards .= DUI_Plus_Blocks::group(
				$inner,
				array(
					'className' => 'is-style-card',
					'style'     => array( 'spacing' => array( 'blockGap' => '0.75rem' ) ),
					'layout'    => array(
						'type'        => 'flex',
						'orientation' => 'vertical',
					),
				)
			) . "\n\n";
		}
		$grid = DUI_Plus_Blocks::group(
			rtrim( $cards ),
			array(
				'style'  => array( 'spacing' => array( 'blockGap' => '1.5rem' ) ),
				'layout' => array(
					'type'               => 'grid',
					'minimumColumnWidth' => $featured ? '100%' : '18rem',
				),
			)
		);
		return self::wrap( self::head_group( $s ) . $grid, $featured ? '46rem' : '1152px' );
	}

	/**
	 * A query loop in a grid.
	 *
	 * @param string     $type     Post type.
	 * @param int        $count    How many.
	 * @param int|string $term     Term id or slug, or ''.
	 * @param string     $taxonomy Taxonomy for the term.
	 * @param string     $card     The card block template.
	 * @param string     $min      Minimum column width.
	 * @return string
	 */
	private static function query( $type, $count, $term, $taxonomy, $card, $min ) {
		$query = array(
			'perPage'  => $count,
			'pages'    => 0,
			'offset'   => 0,
			'postType' => $type,
			'order'    => 'desc',
			'orderBy'  => 'date',
			'author'   => '',
			'search'   => '',
			'exclude'  => array(),
			'sticky'   => '',
			'inherit'  => false,
		);
		if ( $term ) {
			$t = is_numeric( $term ) ? get_term( (int) $term, $taxonomy ) : get_term_by( 'slug', $term, $taxonomy );
			if ( $t && ! is_wp_error( $t ) ) {
				$query['taxQuery'] = array( $taxonomy => array( (int) $t->term_id ) );
			}
		}
		$template = DUI_Plus_Blocks::wrap(
			'core/post-template',
			array(
				'style'  => array( 'spacing' => array( 'blockGap' => '1.5rem' ) ),
				'layout' => array(
					'type'               => 'grid',
					'minimumColumnWidth' => $min,
				),
			),
			"\n" . $card . "\n"
		);
		$empty    = DUI_Plus_Blocks::wrap(
			'core/query-no-results',
			array(),
			"\n" . DUI_Plus_Blocks::group(
				DUI_Plus_Blocks::p( 'product' === $type ? esc_html__( 'Products show up here once you add them in WooCommerce.', 'distinctui-plus' ) : esc_html__( 'Posts show up here once you publish them.', 'distinctui-plus' ) ),
				array(
					'className' => 'is-style-panel',
					'layout'    => array(
						'type'           => 'constrained',
						'justifyContent' => 'left',
					),
				)
			) . "\n"
		);
		return DUI_Plus_Blocks::wrap(
			'core/query',
			array(
				'queryId' => 10 + self::$ctx['index'],
				'query'   => $query,
				'layout'  => array( 'type' => 'default' ),
			),
			'<div class="wp-block-query">' . $template . "\n\n" . $empty . '</div>'
		);
	}
}
