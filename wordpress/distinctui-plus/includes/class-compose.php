<?php
/**
 * Content first, then three designs.
 *
 * You type your content once: your name, your promise, what you do, what it
 * does, your prices, your questions. distinctUI+ lays the same words out
 * three ways, each following every rule, by choosing different layouts and
 * section orders. It never invents colours or styles, and it doesn't need
 * an AI: the options appear as fast as the page can draw them.
 *
 * @package distinctui-plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns content into design options.
 */
class DUI_Plus_Compose {

	/**
	 * Clean the content form. Lists can come as arrays, or as text with one
	 * thing per line ("Title: what it does", "Name, price, what's in it",
	 * "Question? Answer").
	 *
	 * @param array $c Raw content.
	 * @return array
	 */
	public static function content( array $c ) {
		$t   = array( 'DUI_Plus_Spec', 'text' );
		$out = array(
			'name'      => call_user_func( $t, $c['name'] ?? '' ),
			'heading'   => call_user_func( $t, $c['heading'] ?? ( $c['promise'] ?? '' ) ),
			'bold'      => call_user_func( $t, $c['bold'] ?? '' ),
			'lede'      => DUI_Plus_Spec::para( $c['lede'] ?? ( $c['what'] ?? '' ) ),
			'primary'   => DUI_Plus_Spec::action( $c['primary'] ?? ( $c['button'] ?? __( 'Get started', 'distinctui-plus' ) ) ),
			'features'  => array(),
			'plans'     => array(),
			'questions' => array(),
			'form'      => in_array( $c['form'] ?? '', array( 'contact', 'booking', 'waitlist' ), true ) ? $c['form'] : '',
			'posts'     => ! empty( $c['posts'] ),
			'products'  => ! empty( $c['products'] ),
			'faces'     => array(),
			'proof'     => call_user_func( $t, $c['proof'] ?? '' ),
		);
		// Real people who use it, one name per line or comma separated.
		// Without them there's no proof section: we never make people up.
		$faces = $c['faces'] ?? array();
		$faces = is_array( $faces ) ? $faces : preg_split( '/\r?\n|,/', (string) $faces );
		foreach ( $faces as $f ) {
			$f = call_user_func( $t, is_array( $f ) ? ( $f['name'] ?? '' ) : $f );
			if ( '' !== $f ) {
				$out['faces'][] = array( 'name' => $f );
			}
		}
		$out['faces'] = array_slice( $out['faces'], 0, 6 );
		if ( '#' === $out['primary']['href'] ) {
			$out['primary']['href'] = $out['form'] ? '#form' : ( ! empty( $c['plans'] ) ? '#pricing' : '#start' );
		}

		foreach ( self::lines( $c['features'] ?? array() ) as $f ) {
			if ( is_array( $f ) ) {
				$out['features'][] = array( 'title' => call_user_func( $t, $f['title'] ?? '' ), 'text' => DUI_Plus_Spec::para( $f['text'] ?? '' ) );
				continue;
			}
			$k                 = strpos( $f, ':' );
			$out['features'][] = false === $k
				? array( 'title' => call_user_func( $t, $f ), 'text' => '' )
				: array( 'title' => call_user_func( $t, substr( $f, 0, $k ) ), 'text' => DUI_Plus_Spec::para( trim( substr( $f, $k + 1 ) ) ) );
		}
		foreach ( self::lines( $c['plans'] ?? ( $c['prices'] ?? array() ) ) as $p ) {
			if ( is_array( $p ) ) {
				$out['plans'][] = DUI_Plus_Spec::clean_item( 'pricing', $p );
				continue;
			}
			$parts          = array_map( 'trim', explode( ',', $p ) );
			$out['plans'][] = array(
				'name'   => call_user_func( $t, $parts[0] ?? '' ),
				'price'  => call_user_func( $t, $parts[1] ?? '' ),
				'period' => __( 'a month', 'distinctui-plus' ),
				'text'   => DUI_Plus_Spec::para( implode( ', ', array_slice( $parts, 2 ) ) ),
			);
		}
		foreach ( self::lines( $c['questions'] ?? array() ) as $q ) {
			if ( is_array( $q ) ) {
				$out['questions'][] = array( 'q' => call_user_func( $t, $q['q'] ?? '' ), 'a' => DUI_Plus_Spec::para( $q['a'] ?? '' ) );
				continue;
			}
			$k                  = strpos( $q, '?' );
			$out['questions'][] = false === $k
				? array( 'q' => call_user_func( $t, $q ), 'a' => '' )
				: array( 'q' => call_user_func( $t, substr( $q, 0, $k + 1 ) ), 'a' => DUI_Plus_Spec::para( trim( substr( $q, $k + 1 ) ) ) );
		}
		$out['features']  = array_slice( array_filter( $out['features'], fn( $f ) => '' !== $f['title'] ), 0, 6 );
		$out['plans']     = array_slice( array_filter( $out['plans'], fn( $p ) => '' !== ( $p['name'] ?? '' ) ), 0, 3 );
		$out['questions'] = array_slice( array_filter( $out['questions'], fn( $q ) => '' !== $q['q'] ), 0, 8 );
		if ( ! $out['bold'] || false === strpos( $out['heading'], $out['bold'] ) ) {
			$out['bold'] = DUI_Plus_Spec::pick_bold( $out['heading'] );
		}
		return $out;
	}

	/**
	 * A list as an array, or text split into lines.
	 *
	 * @param mixed $v Value.
	 * @return array
	 */
	private static function lines( $v ) {
		if ( is_array( $v ) ) {
			return array_values( $v );
		}
		return array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) $v ) ) ) );
	}

	/**
	 * Three designs of the same content.
	 *
	 * @param array $raw Content (see content()).
	 * @return array List of { key, name, about, spec }.
	 */
	public static function options( array $raw ) {
		$c    = self::content( $raw );
		$name = $c['name'] ? $c['name'] : get_bloginfo( 'name' );

		$hero = array(
			'type'      => 'hero',
			'eyebrow'   => $name,
			'heading'   => $c['heading'] ? $c['heading'] : __( 'Say what you do in a few words', 'distinctui-plus' ),
			'bold'      => $c['bold'],
			'lede'      => $c['lede'],
			'primary'   => $c['primary'],
			'secondary' => $c['features'] ? array( 'label' => __( 'See how it works', 'distinctui-plus' ), 'href' => '#features' ) : null,
		);
		/* translators: %s: product or business name. */
		$what_heading = sprintf( __( 'What %s does for you', 'distinctui-plus' ), $name );
		$features     = $c['features'] ? array(
			'type'    => 'features',
			'anchor'  => 'features',
			'eyebrow' => __( 'What it does', 'distinctui-plus' ),
			'heading' => $what_heading,
			'bold'    => $name,
			'items'   => $c['features'],
		) : null;
		$steps        = $c['features'] ? array(
			'type'    => 'steps',
			'anchor'  => 'features',
			'eyebrow' => __( 'How it works', 'distinctui-plus' ),
			/* translators: %d: number of steps. */
			'heading' => sprintf( _n( '%d step and you\'re in', '%d steps and you\'re in', count( $c['features'] ), 'distinctui-plus' ), count( $c['features'] ) ),
			'bold'    => _n( 'step', 'steps', count( $c['features'] ), 'distinctui-plus' ),
			'items'   => $c['features'],
		) : null;
		$featured     = $c['plans'] ? DUI_Plus_Render::featured_index( $c['plans'] ) : 0;
		$plans        = $c['plans'];
		foreach ( $plans as $k => $p ) {
			$plans[ $k ]['featured'] = ( $k === $featured );
		}
		$pricing   = $plans ? array(
			'type'    => 'pricing',
			'eyebrow' => __( 'Pricing', 'distinctui-plus' ),
			'heading' => __( 'Plain prices', 'distinctui-plus' ),
			'bold'    => __( 'prices', 'distinctui-plus' ),
			'items'   => $plans,
		) : null;
		$questions = $c['questions'] ? array(
			'type'    => 'questions',
			'eyebrow' => __( 'Questions', 'distinctui-plus' ),
			'heading' => __( 'Things people ask first', 'distinctui-plus' ),
			'bold'    => __( 'ask', 'distinctui-plus' ),
			'items'   => $c['questions'],
		) : null;
		$form      = $c['form'] ? array_merge( DUI_Plus_Spec::starter( 'form' ), array( 'layout' => $c['form'], 'anchor' => 'form', 'fields' => array(), 'submit' => null ) ) : null;
		if ( $form ) {
			unset( $form['fields'], $form['submit'] );
			$form['heading'] = array(
				'booking'  => __( 'Ask for a slot', 'distinctui-plus' ),
				'waitlist' => __( 'Be the first to know', 'distinctui-plus' ),
				'contact'  => __( 'Ask us anything', 'distinctui-plus' ),
			)[ $c['form'] ];
			$form['bold']    = DUI_Plus_Spec::pick_bold( $form['heading'] );
		}
		$products = $c['products'] ? DUI_Plus_Spec::starter( 'products' ) : null;
		$posts    = $c['posts'] ? DUI_Plus_Spec::starter( 'posts' ) : null;
		$cta      = function ( $layout ) use ( $name, $c ) {
			return array(
				'type'    => 'cta',
				'layout'  => $layout,
				/* translators: %s: product or business name. */
				'heading' => sprintf( __( 'Try %s today', 'distinctui-plus' ), $name ),
				'bold'    => __( 'today', 'distinctui-plus' ),
				'lede'    => $c['lede'] ? '' : __( 'It takes a minute to start.', 'distinctui-plus' ),
				'primary' => $c['primary'],
			);
		};
		$with = function ( $s, $layout ) {
			if ( ! $s ) {
				return null;
			}
			$s['layout'] = $layout;
			return $s;
		};
		$proof = $c['faces'] ? array(
			'type'   => 'proof',
			'layout' => 'faces',
			'faces'  => $c['faces'],
			'more'   => $c['proof'],
		) : null;

		$designs = array(
			array(
				'key'      => 'calm',
				'name'     => __( 'Calm', 'distinctui-plus' ),
				'about'    => __( 'Your mark beside the promise, three cards, a quiet close in the centre.', 'distinctui-plus' ),
				'sections' => array( $with( $hero, 'mark-left' ), $proof, $with( $features, 'cards' ), $products, $with( $pricing, 'cards' ), $questions, $posts, $form, $cta( 'centred' ) ),
			),
			array(
				'key'      => 'bold',
				'name'     => __( 'Bold', 'distinctui-plus' ),
				'about'    => __( 'A big centred promise, rows instead of cards, a split close.', 'distinctui-plus' ),
				'sections' => array( $with( $hero, 'centred' ), $with( $features, 'rows' ), $products, $with( $pricing, ( $pricing && 1 === count( $pricing['items'] ?? array() ) ) ? 'one' : 'cards' ), $form, $with( $posts, 'featured' ), $questions, $cta( 'split' ) ),
			),
			array(
				'key'      => 'steps',
				'name'     => __( 'Step by step', 'distinctui-plus' ),
				'about'    => __( 'Leads with how it works in numbered steps, then prices and questions.', 'distinctui-plus' ),
				'sections' => array( $with( $hero, 'mark-left' ), $with( $steps, 'rows' ), $form, $with( $pricing, 'cards' ), $products, $questions, $posts, $cta( 'split' ) ),
			),
		);

		$out = array();
		foreach ( $designs as $d ) {
			$spec             = DUI_Plus_Spec::blank( $name );
			$spec['sections'] = array_values( array_filter( $d['sections'] ) );
			$spec             = DUI_Plus_Spec::clean( $spec );
			$out[]            = array(
				'key'   => $d['key'],
				'name'  => $d['name'],
				'about' => $d['about'],
				'spec'  => $spec,
				'check' => DUI_Plus_Check::check( $spec ),
			);
		}
		return $out;
	}
}
