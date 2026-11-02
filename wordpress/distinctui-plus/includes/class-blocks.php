<?php
/**
 * Small helpers that write core block markup.
 *
 * Every page distinctUI+ makes is plain core blocks with the theme's block
 * styles, so it opens in the normal block editor without warnings and stays
 * standing if the plugin is switched off. These helpers write each block the
 * way the block's own save function would, matching the theme's patterns.
 *
 * In canvas mode, editable text gets a data-dui-slot attribute naming its
 * place in the spec. That markup is only ever shown in the canvas; it's
 * never saved.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core block writers.
 */
class DUI_Plus_Blocks {

	/**
	 * Canvas mode adds slot attributes.
	 *
	 * @var bool
	 */
	public static $canvas = false;

	/**
	 * Wrap HTML in block comments.
	 *
	 * @param string $name  Block name, like core/group.
	 * @param array  $attrs Block attributes.
	 * @param string $html  Inner markup.
	 * @return string
	 */
	public static function wrap( $name, array $attrs, $html ) {
		return get_comment_delimited_block_content( $name, $attrs, $html );
	}

	/**
	 * A slot attribute, in canvas mode only.
	 *
	 * @param string $slot Path in the section, like heading or items.1.title.
	 * @return string
	 */
	public static function slot( $slot ) {
		return ( self::$canvas && '' !== $slot ) ? ' data-dui-slot="' . esc_attr( $slot ) . '"' : '';
	}

	/**
	 * Text with a chosen word made bold. The first match wins; if the word
	 * isn't there the heading stays all light (and the checker says so).
	 *
	 * @param string $text Plain text.
	 * @param string $bold The bold word or phrase.
	 * @return string HTML.
	 */
	public static function bold( $text, $bold ) {
		$text = (string) $text;
		$bold = trim( (string) $bold );
		if ( '' === $bold ) {
			return esc_html( $text );
		}
		$at = mb_stripos( $text, $bold );
		if ( false === $at ) {
			return esc_html( $text );
		}
		return esc_html( mb_substr( $text, 0, $at ) )
			. '<strong>' . esc_html( mb_substr( $text, $at, mb_strlen( $bold ) ) ) . '</strong>'
			. esc_html( mb_substr( $text, $at + mb_strlen( $bold ) ) );
	}

	/**
	 * Class list from block attributes, in the order the editor writes them.
	 *
	 * @param array    $attrs Block attributes.
	 * @param string[] $base  Classes the block always has.
	 * @return string
	 */
	private static function classes( array $attrs, array $base = array() ) {
		$c = $base;
		if ( ! empty( $attrs['align'] ) && in_array( $attrs['align'], array( 'wide', 'full' ), true ) ) {
			$c[] = 'align' . $attrs['align'];
		}
		if ( ! empty( $attrs['textAlign'] ) ) {
			$c[] = 'has-text-align-' . $attrs['textAlign'];
		}
		if ( ! empty( $attrs['textColor'] ) ) {
			$c[] = 'has-' . $attrs['textColor'] . '-color';
			$c[] = 'has-text-color';
		}
		if ( ! empty( $attrs['fontSize'] ) ) {
			$c[] = 'has-' . $attrs['fontSize'] . '-font-size';
		}
		if ( ! empty( $attrs['className'] ) ) {
			$c[] = $attrs['className'];
		}
		return implode( ' ', array_filter( $c ) );
	}

	/**
	 * A paragraph.
	 *
	 * @param string $html  Inner HTML (already escaped).
	 * @param array  $attrs Attributes: className, textColor, fontSize, align.
	 * @param string $slot  Canvas slot.
	 * @return string
	 */
	public static function p( $html, array $attrs = array(), $slot = '' ) {
		$a = $attrs;
		if ( ! empty( $a['align'] ) ) {
			$a['textAlign'] = $a['align'];
			unset( $a['align'] );
		}
		$class = self::classes( $a );
		return self::wrap(
			'core/paragraph',
			$attrs,
			'<p' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . self::slot( $slot ) . '>' . $html . '</p>'
		);
	}

	/**
	 * A heading.
	 *
	 * @param string $html  Inner HTML (already escaped).
	 * @param int    $level 1 to 6.
	 * @param array  $attrs Attributes: className, textAlign.
	 * @param string $slot  Canvas slot.
	 * @return string
	 */
	public static function h( $html, $level = 2, array $attrs = array(), $slot = '' ) {
		$attrs_out = $attrs;
		if ( 2 !== $level ) {
			$attrs_out = array_merge( array( 'level' => $level ), $attrs );
		}
		$class = self::classes( $attrs, array( 'wp-block-heading' ) );
		return self::wrap(
			'core/heading',
			$attrs_out,
			'<h' . $level . ' class="' . esc_attr( $class ) . '"' . self::slot( $slot ) . '>' . $html . '</h' . $level . '>'
		);
	}

	/**
	 * Inline styles a group writes for padding, margin and a top border.
	 *
	 * @param array $style The style attribute.
	 * @return string CSS.
	 */
	private static function style_css( array $style ) {
		$css = array();
		if ( isset( $style['border']['top'] ) ) {
			$css[] = 'border-top-color:' . self::preset( $style['border']['top']['color'] );
			$css[] = 'border-top-width:' . $style['border']['top']['width'];
		}
		foreach ( array( 'padding', 'margin' ) as $box ) {
			if ( isset( $style['spacing'][ $box ] ) ) {
				foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
					if ( isset( $style['spacing'][ $box ][ $side ] ) ) {
						$css[] = $box . '-' . $side . ':' . self::preset( $style['spacing'][ $box ][ $side ] );
					}
				}
			}
		}
		return implode( ';', $css );
	}

	/**
	 * Turn var:preset|spacing|80 into var(--wp--preset--spacing--80).
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function preset( $value ) {
		if ( 0 === strpos( (string) $value, 'var:' ) ) {
			return 'var(--wp--' . str_replace( '|', '--', substr( $value, 4 ) ) . ')';
		}
		return (string) $value;
	}

	/**
	 * A group.
	 *
	 * @param string $inner Inner block markup.
	 * @param array  $attrs Attributes: align, className, anchor, style, layout, textAlign.
	 * @param string $slot  Canvas slot for the section wrapper.
	 * @return string
	 */
	public static function group( $inner, array $attrs = array(), $slot = '' ) {
		$class = self::classes( $attrs, array( 'wp-block-group' ) );
		$css   = isset( $attrs['style'] ) ? self::style_css( $attrs['style'] ) : '';
		$open  = '<div'
			. ( ! empty( $attrs['anchor'] ) ? ' id="' . esc_attr( $attrs['anchor'] ) . '"' : '' )
			. ' class="' . esc_attr( $class ) . '"'
			. ( $css ? ' style="' . esc_attr( $css ) . '"' : '' )
			. self::slot( $slot ) . '>';
		return self::wrap( 'core/group', $attrs, $open . $inner . '</div>' );
	}

	/**
	 * A row of buttons.
	 *
	 * @param array  $buttons List of [label, href, style, slot] where style is
	 *                        fill, outline or text-link.
	 * @param array  $attrs   Attributes for core/buttons.
	 * @param bool   $full    Full-width buttons (plan cards).
	 * @return string
	 */
	public static function buttons( array $buttons, array $attrs = array(), $full = false ) {
		$inner = '';
		foreach ( $buttons as $b ) {
			list( $label, $href, $style, $slot ) = array_pad( $b, 4, '' );
			if ( '' === trim( (string) $label ) ) {
				continue;
			}
			$battrs = array();
			$class  = array( 'wp-block-button' );
			if ( $full ) {
				$battrs['width'] = 100;
				$class[]         = 'has-custom-width wp-block-button__width-100';
			}
			if ( 'outline' === $style || 'text-link' === $style ) {
				$battrs           = array_merge( array( 'className' => 'is-style-' . $style ), $battrs );
				$class[]          = 'is-style-' . $style;
			}
			$inner .= self::wrap(
				'core/button',
				$battrs,
				'<div class="' . esc_attr( implode( ' ', $class ) ) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $href ? $href : '#' ) . '"' . self::slot( $slot ) . '>' . esc_html( $label ) . '</a></div>'
			) . "\n\n";
		}
		if ( '' === $inner ) {
			return '';
		}
		$css = isset( $attrs['style'] ) ? self::style_css( $attrs['style'] ) : '';
		return self::wrap(
			'core/buttons',
			$attrs,
			'<div class="wp-block-buttons"' . ( $css ? ' style="' . esc_attr( $css ) . '"' : '' ) . '>' . rtrim( $inner ) . '</div>'
		);
	}

	/**
	 * Columns of cards (the theme's "Three cards" pattern).
	 *
	 * @param string[] $columns Inner markup for each column.
	 * @param string   $class   Column class, like is-style-card.
	 * @return string
	 */
	public static function columns( array $columns, $class = 'is-style-card' ) {
		$inner = '';
		foreach ( $columns as $col ) {
			$inner .= self::wrap(
				'core/column',
				$class ? array( 'className' => $class ) : array(),
				'<div class="wp-block-column' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">' . $col . '</div>'
			) . "\n\n";
		}
		return self::wrap(
			'core/columns',
			array( 'style' => array( 'spacing' => array( 'blockGap' => array( 'left' => '1.5rem', 'top' => '1rem' ) ) ) ),
			'<div class="wp-block-columns">' . rtrim( $inner ) . '</div>'
		);
	}

	/**
	 * A question that opens in place.
	 *
	 * @param string $q        Question.
	 * @param string $a        Answer.
	 * @param string $slotbase Slot prefix, like items.0.
	 * @return string
	 */
	public static function details( $q, $a, $slotbase = '' ) {
		return self::wrap(
			'core/details',
			array(),
			'<details class="wp-block-details"><summary' . self::slot( $slotbase ? $slotbase . 'q' : '' ) . '>' . esc_html( $q ) . '</summary>'
			. self::p( nl2br( esc_html( $a ), false ), array(), $slotbase ? $slotbase . 'a' : '' )
			. '</details>'
		);
	}

	/**
	 * An image.
	 *
	 * @param string $src   URL.
	 * @param string $alt   Alt text.
	 * @param array  $attrs className, width and height (like 112px).
	 * @return string
	 */
	public static function image( $src, $alt, array $attrs = array() ) {
		$a     = array_merge( array( 'sizeSlug' => 'full', 'linkDestination' => 'none' ), $attrs );
		$class = array( 'wp-block-image', 'size-full' );
		$style = '';
		if ( ! empty( $attrs['width'] ) ) {
			$a       = array_merge( array( 'width' => $attrs['width'], 'height' => $attrs['height'], 'scale' => 'cover' ), $a );
			$class[] = 'is-resized';
			$style   = ' style="object-fit:cover;width:' . esc_attr( $attrs['width'] ) . ';height:' . esc_attr( $attrs['height'] ) . '"';
		} elseif ( ! empty( $attrs['aspectRatio'] ) ) {
			$a     = array_merge( array( 'aspectRatio' => $attrs['aspectRatio'], 'scale' => 'cover' ), $a );
			$style = ' style="aspect-ratio:' . esc_attr( $attrs['aspectRatio'] ) . ';object-fit:cover"';
		}
		if ( ! empty( $attrs['className'] ) ) {
			$class[] = $attrs['className'];
		}
		return self::wrap(
			'core/image',
			$a,
			'<figure class="' . esc_attr( implode( ' ', $class ) ) . '"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '"' . $style . '/></figure>'
		);
	}

	/**
	 * Raw HTML (used for the native form, so it stands without the plugin).
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	public static function html( $html ) {
		return self::wrap( 'core/html', array(), $html );
	}

	/**
	 * A shortcode (form plugins).
	 *
	 * @param string $code Shortcode.
	 * @return string
	 */
	public static function shortcode( $code ) {
		return self::wrap( 'core/shortcode', array(), $code );
	}

	/**
	 * A dynamic block with no inner markup, like core/post-title.
	 *
	 * @param string $name  Block name.
	 * @param array  $attrs Attributes.
	 * @return string
	 */
	public static function dynamic( $name, array $attrs = array() ) {
		return self::wrap( $name, $attrs, '' );
	}
}
