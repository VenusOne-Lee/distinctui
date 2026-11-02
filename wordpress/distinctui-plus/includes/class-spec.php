<?php
/**
 * Page specs.
 *
 * A page is a spec, not markup: an ordered list of sections, each with
 * named slots. People and AI write the spec; the renderer turns it into
 * plain blocks. A spec has nowhere to put a blue button or a shadow.
 *
 * The format is shared with the distinctUI MCP server and the free
 * checker (skills/distinctui/references/page-spec.md):
 *
 *   { name, signature: { colour, palette, initials }, density, live,
 *     seo: { title, description },
 *     sections: [ { type, layout, eyebrow, heading, bold, lede,
 *                   primary: { label, href }, secondary, items, ... } ] }
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Section types, layouts and spec cleaning.
 */
class DUI_Plus_Spec {

	/**
	 * Every section type, its layouts (first is the default) and a plain name.
	 *
	 * @return array<string, array{name: string, layouts: array<string, string>, about: string}>
	 */
	public static function types() {
		return array(
			'hero'      => array(
				'name'    => __( 'Hero', 'distinctui-plus' ),
				'about'   => __( 'The promise at the top: your mark, a headline with one bold word, a line about it and one black pill.', 'distinctui-plus' ),
				'layouts' => array(
					'mark-left' => __( 'Mark left', 'distinctui-plus' ),
					'centred'   => __( 'Centred', 'distinctui-plus' ),
				),
			),
			'proof'     => array(
				'name'    => __( 'Proof', 'distinctui-plus' ),
				'about'   => __( 'Faces in their rings and one line about who uses it.', 'distinctui-plus' ),
				'layouts' => array( 'faces' => __( 'Faces', 'distinctui-plus' ) ),
			),
			'features'  => array(
				'name'    => __( 'Features', 'distinctui-plus' ),
				'about'   => __( 'What it does, in three short parts.', 'distinctui-plus' ),
				'layouts' => array(
					'cards' => __( 'Three cards', 'distinctui-plus' ),
					'rows'  => __( 'Rows', 'distinctui-plus' ),
				),
			),
			'steps'     => array(
				'name'    => __( 'Steps', 'distinctui-plus' ),
				'about'   => __( 'How it works, in numbered steps.', 'distinctui-plus' ),
				'layouts' => array( 'rows' => __( 'Numbered', 'distinctui-plus' ) ),
			),
			'stats'     => array(
				'name'    => __( 'Numbers', 'distinctui-plus' ),
				'about'   => __( 'Two to four numbers that matter, each with a label.', 'distinctui-plus' ),
				'layouts' => array( 'row' => __( 'In a row', 'distinctui-plus' ) ),
			),
			'pricing'   => array(
				'name'    => __( 'Pricing', 'distinctui-plus' ),
				'about'   => __( 'Plans. The one you recommend gets the black edge and the only black pill.', 'distinctui-plus' ),
				'layouts' => array(
					'cards'  => __( 'Plans in cards', 'distinctui-plus' ),
					'one'   => __( 'One plan', 'distinctui-plus' ),
				),
			),
			'questions' => array(
				'name'    => __( 'Questions', 'distinctui-plus' ),
				'about'   => __( 'Questions people ask first, with plain answers that open in place.', 'distinctui-plus' ),
				'layouts' => array( 'list' => __( 'List', 'distinctui-plus' ) ),
			),
			'cta'       => array(
				'name'    => __( 'Call to action', 'distinctui-plus' ),
				'about'   => __( 'The last ask: one line and one black pill.', 'distinctui-plus' ),
				'layouts' => array(
					'centred' => __( 'Centred', 'distinctui-plus' ),
					'split'   => __( 'Split', 'distinctui-plus' ),
				),
			),
			'form'      => array(
				'name'    => __( 'Form', 'distinctui-plus' ),
				'about'   => __( 'Contact, booking request or waitlist. Entries land in WordPress and you get an email.', 'distinctui-plus' ),
				'layouts' => array(
					'contact'  => __( 'Contact', 'distinctui-plus' ),
					'booking'  => __( 'Booking request', 'distinctui-plus' ),
					'waitlist' => __( 'Waitlist', 'distinctui-plus' ),
				),
			),
			'products'  => array(
				'name'    => __( 'Products', 'distinctui-plus' ),
				'about'   => __( 'Products on grey, from WooCommerce or typed in.', 'distinctui-plus' ),
				'layouts' => array( 'grid' => __( 'Grid', 'distinctui-plus' ) ),
			),
			'posts'     => array(
				'name'    => __( 'Posts', 'distinctui-plus' ),
				'about'   => __( 'Your latest posts, or one category, as cards.', 'distinctui-plus' ),
				'layouts' => array(
					'cards'    => __( 'Latest three', 'distinctui-plus' ),
					'featured' => __( 'One featured', 'distinctui-plus' ),
				),
			),
		);
	}

	/**
	 * An empty page spec.
	 *
	 * @param string $name Page name.
	 * @return array
	 */
	public static function blank( $name = '' ) {
		return array(
			'name'        => (string) $name,
			'title'       => '',
			'description' => '',
			'density'     => 'compact' === DUI_Plus_Settings::look()['density'] ? 'compact' : 'comfortable',
			'live'        => false,
			'sections'    => array(),
		);
	}

	/**
	 * Clean a spec from anywhere (the canvas, an ability, an import). Text is
	 * stripped of tags, links are cleaned, and unknown keys are dropped.
	 * Unknown section types are kept, so the checker can say what to use
	 * instead; the renderer skips them.
	 *
	 * @param mixed $spec Raw spec.
	 * @return array
	 */
	public static function clean( $spec ) {
		$spec = is_array( $spec ) ? $spec : array();
		$out  = self::blank( isset( $spec['name'] ) ? self::text( $spec['name'] ) : '' );

		if ( isset( $spec['density'] ) ) {
			$out['density'] = 'compact' === $spec['density'] ? 'compact' : 'comfortable';
		}
		$out['live'] = ! empty( $spec['live'] );
		// Older drafts kept these under seo.
		$seo                = isset( $spec['seo'] ) && is_array( $spec['seo'] ) ? $spec['seo'] : array();
		$out['title']       = self::text( $spec['title'] ?? ( $seo['title'] ?? '' ) );
		$out['description'] = self::text( $spec['description'] ?? ( $seo['description'] ?? '' ) );
		if ( isset( $spec['version'] ) ) {
			$out['version'] = self::text( $spec['version'] );
		}
		if ( isset( $spec['theme'] ) ) {
			$out['theme'] = self::text( $spec['theme'] );
		}
		if ( ! empty( $spec['dark'] ) ) {
			$out['dark'] = true;
		}
		if ( isset( $spec['signature'] ) && is_array( $spec['signature'] ) ) {
			// Kept for the shared format; the site's own signature always wins when rendering.
			$out['signature'] = array(
				'colour'   => (string) sanitize_hex_color( $spec['signature']['colour'] ?? '' ),
				'palette'  => sanitize_key( $spec['signature']['palette'] ?? '' ),
				'initials' => mb_substr( self::text( $spec['signature']['initials'] ?? '' ), 0, 3 ),
			);
		}
		$sections = isset( $spec['sections'] ) && is_array( $spec['sections'] ) ? array_values( $spec['sections'] ) : array();
		foreach ( array_slice( $sections, 0, 40 ) as $section ) {
			if ( is_array( $section ) ) {
				$out['sections'][] = self::clean_section( $section );
			}
		}
		return $out;
	}

	/**
	 * Clean one section.
	 *
	 * @param array $s Raw section.
	 * @return array
	 */
	public static function clean_section( array $s ) {
		$types = self::types();
		$type  = sanitize_key( $s['type'] ?? '' );
		$out   = array( 'type' => $type );

		$layouts       = isset( $types[ $type ] ) ? array_keys( $types[ $type ]['layouts'] ) : array();
		$layout        = isset( $s['layout'] ) ? sanitize_key( (string) $s['layout'] ) : '';
		$out['layout'] = in_array( $layout, $layouts, true ) ? $layout : ( $layouts[0] ?? $layout );

		foreach ( array( 'eyebrow', 'heading', 'bold' ) as $k ) {
			if ( isset( $s[ $k ] ) ) {
				$out[ $k ] = self::text( $s[ $k ] );
			}
		}
		foreach ( array( 'lede', 'text', 'success' ) as $k ) {
			if ( isset( $s[ $k ] ) ) {
				$out[ $k ] = self::para( $s[ $k ] );
			}
		}
		foreach ( array( 'primary', 'secondary' ) as $k ) {
			if ( ! empty( $s[ $k ] ) ) {
				$out[ $k ] = self::action( $s[ $k ] );
			}
		}
		if ( isset( $s['anchor'] ) ) {
			$out['anchor'] = sanitize_title( $s['anchor'] );
		}
		if ( 'proof' === $type ) {
			$faces = $s['faces'] ?? ( $s['items'] ?? array() );
			$out['faces'] = array();
			foreach ( array_slice( array_values( is_array( $faces ) ? $faces : array() ), 0, 5 ) as $face ) {
				$out['faces'][] = is_array( $face ) ? self::clean_item( 'proof', $face ) : array( 'name' => self::text( $face ) );
			}
			unset( $s['items'] );
			if ( isset( $s['more'] ) ) {
				$out['more'] = self::text( $s['more'] );
			}
		}
		if ( isset( $s['action'] ) ) {
			$out['action'] = esc_url_raw( (string) $s['action'] );
		}
		if ( isset( $s['submit'] ) ) {
			$out['submit'] = self::text( $s['submit'] );
		}
		if ( isset( $s['source'] ) ) {
			$out['source'] = sanitize_key( $s['source'] );
		}
		if ( isset( $s['count'] ) ) {
			$out['count'] = max( 1, min( 12, absint( $s['count'] ) ) );
		}
		if ( isset( $s['category'] ) ) {
			$out['category'] = is_numeric( $s['category'] ) ? absint( $s['category'] ) : sanitize_title( $s['category'] );
		}
		if ( isset( $s['plugin'] ) ) {
			$out['plugin']    = in_array( $s['plugin'], array( 'cf7', 'wpforms', 'gravity', 'jetpack' ), true ) ? $s['plugin'] : '';
			$out['plugin_id'] = absint( $s['plugin_id'] ?? 0 );
		}
		if ( isset( $s['items'] ) && is_array( $s['items'] ) ) {
			$out['items'] = array();
			foreach ( array_slice( array_values( $s['items'] ), 0, 12 ) as $item ) {
				$out['items'][] = self::clean_item( $type, $item );
			}
		}
		if ( isset( $s['fields'] ) && is_array( $s['fields'] ) ) {
			$out['fields'] = array();
			foreach ( array_slice( array_values( $s['fields'] ), 0, 12 ) as $f ) {
				if ( is_array( $f ) ) {
					$out['fields'][] = self::clean_field( $f );
				}
			}
		}
		// Anything that tried to set a colour, a font or a shadow is kept
		// aside so the checker can name it and say what happened.
		$off_look = array_intersect_key( $s, array_flip( array( 'color', 'colour', 'background', 'accent', 'gradient', 'font', 'shadow' ) ) );
		if ( $off_look ) {
			$out['_off'] = array_map(
				function ( $v ) {
					return is_scalar( $v ) ? self::text( $v ) : 'set';
				},
				$off_look
			);
		}
		return $out;
	}

	/**
	 * Clean one item, by section type. Strings become the main slot.
	 *
	 * @param string $type Section type.
	 * @param mixed  $item Raw item.
	 * @return array
	 */
	public static function clean_item( $type, $item ) {
		if ( ! is_array( $item ) ) {
			$item = array( 'title' => $item );
		}
		$out = array();
		foreach ( array( 'title', 'name', 'q', 'value', 'label', 'price', 'period', 'date', 'initials', 'alt' ) as $k ) {
			if ( isset( $item[ $k ] ) ) {
				$out[ $k ] = self::text( $item[ $k ] );
			}
		}
		foreach ( array( 'text', 'a' ) as $k ) {
			if ( isset( $item[ $k ] ) ) {
				$out[ $k ] = self::para( $item[ $k ] );
			}
		}
		foreach ( array( 'href', 'image' ) as $k ) {
			if ( ! empty( $item[ $k ] ) ) {
				$out[ $k ] = esc_url_raw( (string) $item[ $k ] );
			}
		}
		if ( isset( $item['featured'] ) ) {
			$out['featured'] = (bool) $item['featured'];
		}
		if ( isset( $item['features'] ) && is_array( $item['features'] ) ) {
			$out['features'] = array_map( array( __CLASS__, 'text' ), array_slice( array_values( $item['features'] ), 0, 8 ) );
		}
		if ( ! empty( $item['cta'] ) ) {
			// cta is a label with the item's href, or { label, href }.
			$out['cta'] = is_array( $item['cta'] ) ? self::action( $item['cta'] ) : self::action( array( 'label' => $item['cta'], 'href' => $item['href'] ?? '#' ) );
		}
		if ( isset( $item['ring'] ) ) {
			$out['ring'] = max( 1, min( 5, absint( $item['ring'] ) ) );
		}
		return $out;
	}

	/**
	 * Clean one form field.
	 *
	 * @param array $f Raw field.
	 * @return array
	 */
	public static function clean_field( array $f ) {
		$types = array( 'text', 'email', 'tel', 'textarea', 'date', 'number', 'select', 'checkbox', 'url' );
		$label = self::text( $f['label'] ?? '' );
		$name  = sanitize_key( $f['name'] ?? '' );
		$out   = array(
			'label'    => $label,
			'type'     => in_array( $f['type'] ?? 'text', $types, true ) ? $f['type'] : 'text',
			'name'     => $name ? $name : sanitize_key( str_replace( ' ', '_', strtolower( $label ) ) ),
			'optional' => ! empty( $f['optional'] ),
		);
		if ( ! empty( $f['hint'] ) ) {
			$out['hint'] = self::text( $f['hint'] );
		}
		if ( ! empty( $f['placeholder'] ) ) {
			$out['placeholder'] = self::text( $f['placeholder'] );
		}
		if ( 'select' === $out['type'] && ! empty( $f['options'] ) && is_array( $f['options'] ) ) {
			$out['options'] = array_map( array( __CLASS__, 'text' ), array_slice( array_values( $f['options'] ), 0, 30 ) );
		}
		return $out;
	}

	/**
	 * An action: a string label, or { label, href }.
	 *
	 * @param mixed $a Raw action.
	 * @return array{label: string, href: string}
	 */
	public static function action( $a ) {
		if ( is_string( $a ) ) {
			return array(
				'label' => self::text( $a ),
				'href'  => '#',
			);
		}
		$a    = is_array( $a ) ? $a : array();
		$href = isset( $a['href'] ) ? (string) $a['href'] : '#';
		return array(
			'label' => self::text( $a['label'] ?? '' ),
			'href'  => ( '' !== $href && '#' === $href[0] ) ? '#' . sanitize_title( substr( $href, 1 ) ) : esc_url_raw( $href ),
		);
	}

	/**
	 * One line of text, no tags.
	 *
	 * @param mixed $v Raw value.
	 * @return string
	 */
	public static function text( $v ) {
		return is_scalar( $v ) ? sanitize_text_field( (string) $v ) : '';
	}

	/**
	 * A paragraph, no tags, line breaks kept.
	 *
	 * @param mixed $v Raw value.
	 * @return string
	 */
	public static function para( $v ) {
		return is_scalar( $v ) ? sanitize_textarea_field( (string) $v ) : '';
	}

	/**
	 * Pick the word that should be bold in a heading: the longest word that
	 * isn't a small joining word. Used when nobody has chosen one.
	 *
	 * @param string $heading Heading text.
	 * @return string
	 */
	public static function pick_bold( $heading ) {
		$skip  = array( 'your', 'the', 'and', 'while', 'with', 'for', 'you', 'our', 'a', 'an', 'of', 'to', 'in', 'on', 'it', 'is', 'we', 'that', 'this', 'from', 'what', 'when', 'how', 'more', 'less' );
		$words = preg_split( '/\s+/u', trim( preg_replace( '/[^\p{L}\p{N}\s\'-]/u', ' ', (string) $heading ) ) );
		$best  = '';
		foreach ( (array) $words as $w ) {
			if ( '' === $w || in_array( mb_strtolower( $w ), $skip, true ) ) {
				continue;
			}
			if ( mb_strlen( $w ) > mb_strlen( $best ) ) {
				$best = $w;
			}
		}
		return $best ? $best : (string) ( $words[0] ?? '' );
	}

	/**
	 * The fields each form layout starts with, when a spec gives none.
	 *
	 * @param string $layout contact, booking or waitlist.
	 * @return array
	 */
	public static function default_fields( $layout ) {
		$name  = array( 'label' => __( 'Your name', 'distinctui-plus' ), 'type' => 'text', 'name' => 'name' );
		$email = array( 'label' => __( 'Email', 'distinctui-plus' ), 'type' => 'email', 'name' => 'email' );
		switch ( $layout ) {
			case 'booking':
				return array(
					$name,
					array( 'label' => __( 'Mobile number', 'distinctui-plus' ), 'type' => 'tel', 'name' => 'phone', 'placeholder' => '082 555 0101' ),
					array( 'label' => __( 'When suits you', 'distinctui-plus' ), 'type' => 'text', 'name' => 'when', 'placeholder' => __( 'Saturday morning', 'distinctui-plus' ) ),
					array( 'label' => __( 'Anything we should know', 'distinctui-plus' ), 'type' => 'textarea', 'name' => 'note', 'optional' => true ),
				);
			case 'waitlist':
				return array( $email );
			default:
				return array( $name, $email, array( 'label' => __( 'Message', 'distinctui-plus' ), 'type' => 'textarea', 'name' => 'message' ) );
		}
	}

	/**
	 * A new section of a type, with words that say what goes there.
	 *
	 * @param string $type Section type.
	 * @return array
	 */
	public static function starter( $type ) {
		$s = array(
			'hero'      => array(
				'eyebrow'   => __( 'What it is', 'distinctui-plus' ),
				'heading'   => __( 'Say what you do in a few words', 'distinctui-plus' ),
				'bold'      => __( 'do', 'distinctui-plus' ),
				'lede'      => __( 'Then say who it\'s for, and what they can do today.', 'distinctui-plus' ),
				'primary'   => array( 'label' => __( 'Get started', 'distinctui-plus' ), 'href' => '#start' ),
				'secondary' => array( 'label' => __( 'See how it works', 'distinctui-plus' ), 'href' => '#how' ),
			),
			'proof'     => array(
				'faces' => array( 'Ayanda Mthembu', 'Tumi Khoza', 'Lerato Nkosi', 'Sara Pillay' ),
				'more'  => __( 'and 40 more', 'distinctui-plus' ),
				'text'  => __( 'Say who already uses it, in one line.', 'distinctui-plus' ),
			),
			'features'  => array(
				'eyebrow' => __( 'What it does', 'distinctui-plus' ),
				'heading' => __( 'Three things it does well', 'distinctui-plus' ),
				'bold'    => __( 'well', 'distinctui-plus' ),
				'items'   => array(
					array( 'title' => __( 'The first thing', 'distinctui-plus' ), 'text' => __( 'What it does for someone, in a sentence.', 'distinctui-plus' ) ),
					array( 'title' => __( 'The second thing', 'distinctui-plus' ), 'text' => __( 'What it does for someone, in a sentence.', 'distinctui-plus' ) ),
					array( 'title' => __( 'The third thing', 'distinctui-plus' ), 'text' => __( 'What it does for someone, in a sentence.', 'distinctui-plus' ) ),
				),
			),
			'steps'     => array(
				'eyebrow' => __( 'How it works', 'distinctui-plus' ),
				'heading' => __( 'Three steps and you\'re in', 'distinctui-plus' ),
				'bold'    => __( 'steps', 'distinctui-plus' ),
				'items'   => array(
					array( 'title' => __( 'Sign up', 'distinctui-plus' ), 'text' => __( 'What happens first.', 'distinctui-plus' ) ),
					array( 'title' => __( 'Set it up', 'distinctui-plus' ), 'text' => __( 'What they do next.', 'distinctui-plus' ) ),
					array( 'title' => __( 'Use it', 'distinctui-plus' ), 'text' => __( 'What they get.', 'distinctui-plus' ) ),
				),
			),
			'stats'     => array(
				'heading' => __( 'The numbers so far', 'distinctui-plus' ),
				'bold'    => __( 'numbers', 'distinctui-plus' ),
				'items'   => array(
					array( 'value' => '120', 'label' => __( 'shops', 'distinctui-plus' ) ),
					array( 'value' => '4,800', 'label' => __( 'bookings a month', 'distinctui-plus' ) ),
					array( 'value' => '2 min', 'label' => __( 'to set up', 'distinctui-plus' ) ),
				),
			),
			'pricing'   => array(
				'eyebrow' => __( 'Pricing', 'distinctui-plus' ),
				'heading' => __( 'Plain prices', 'distinctui-plus' ),
				'bold'    => __( 'prices', 'distinctui-plus' ),
				'items'   => array(
					array( 'name' => __( 'Free', 'distinctui-plus' ), 'price' => 'R0', 'period' => __( 'a month', 'distinctui-plus' ), 'text' => __( 'For trying it out.', 'distinctui-plus' ) ),
					array( 'name' => __( 'Pro', 'distinctui-plus' ), 'price' => 'R149', 'period' => __( 'a month', 'distinctui-plus' ), 'text' => __( 'For most people.', 'distinctui-plus' ), 'featured' => true ),
					array( 'name' => __( 'Team', 'distinctui-plus' ), 'price' => 'R399', 'period' => __( 'a month', 'distinctui-plus' ), 'text' => __( 'For a team of five.', 'distinctui-plus' ) ),
				),
			),
			'questions' => array(
				'eyebrow' => __( 'Questions', 'distinctui-plus' ),
				'heading' => __( 'Good questions', 'distinctui-plus' ),
				'bold'    => __( 'questions', 'distinctui-plus' ),
				'items'   => array(
					array( 'q' => __( 'What does it cost?', 'distinctui-plus' ), 'a' => __( 'Answer plainly, in a sentence or two.', 'distinctui-plus' ) ),
					array( 'q' => __( 'Can I leave any time?', 'distinctui-plus' ), 'a' => __( 'Say how, and what they keep.', 'distinctui-plus' ) ),
					array( 'q' => __( 'Who do I talk to?', 'distinctui-plus' ), 'a' => __( 'A person, by email. Say how soon they answer.', 'distinctui-plus' ) ),
				),
			),
			'cta'       => array(
				'heading' => __( 'Ready when you are', 'distinctui-plus' ),
				'bold'    => __( 'are', 'distinctui-plus' ),
				'lede'    => __( 'Say what happens when they press the button.', 'distinctui-plus' ),
				'primary' => array( 'label' => __( 'Get started', 'distinctui-plus' ), 'href' => '#start' ),
			),
			'form'      => array(
				'eyebrow' => __( 'Contact', 'distinctui-plus' ),
				'heading' => __( 'Ask us anything', 'distinctui-plus' ),
				'bold'    => __( 'anything', 'distinctui-plus' ),
				'lede'    => __( 'We reply by email within one working day.', 'distinctui-plus' ),
				'fields'  => self::default_fields( 'contact' ),
				'submit'  => __( 'Send your message', 'distinctui-plus' ),
				'success' => __( 'Thanks. We\'ve got your message and we\'ll reply within one working day.', 'distinctui-plus' ),
			),
			'products'  => array(
				'eyebrow' => __( 'Shop', 'distinctui-plus' ),
				'heading' => __( 'What we sell', 'distinctui-plus' ),
				'bold'    => __( 'sell', 'distinctui-plus' ),
				'source'  => class_exists( 'WooCommerce' ) ? 'woocommerce' : '',
				'count'   => 3,
				'items'   => array(
					array( 'name' => __( 'A product', 'distinctui-plus' ), 'price' => 'R180', 'href' => '#' ),
					array( 'name' => __( 'Another product', 'distinctui-plus' ), 'price' => 'R150', 'href' => '#' ),
					array( 'name' => __( 'A gift card', 'distinctui-plus' ), 'price' => 'R300', 'href' => '#' ),
				),
			),
			'posts'     => array(
				'eyebrow' => __( 'Journal', 'distinctui-plus' ),
				'heading' => __( 'Notes from the work', 'distinctui-plus' ),
				'bold'    => __( 'work', 'distinctui-plus' ),
				'source'  => 'posts',
				'count'   => 3,
			),
		);
		if ( 'proof' === $type ) {
			// Invented people, never real ones.
			$s['proof']['faces'] = array_map(
				function ( $n ) {
					return array( 'name' => $n );
				},
				$s['proof']['faces']
			);
		}
		$out = array_merge( array( 'type' => $type ), $s[ $type ] ?? array() );
		return self::clean_section( $out );
	}
}
