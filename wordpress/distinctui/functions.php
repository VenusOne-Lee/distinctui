<?php
/**
 * distinctUI theme setup.
 *
 * Black, white, grey. Then one mark that's yours.
 * https://distinctui.com · MIT licence
 *
 * @package distinctui
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DISTINCTUI_VERSION', wp_get_theme( 'distinctui' )->get( 'Version' ) ? wp_get_theme( 'distinctui' )->get( 'Version' ) : '0.1.0' );

/**
 * Editor styles, so the editor looks like the site.
 */
function distinctui_setup() {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );
	add_theme_support( 'wp-block-styles' );
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'distinctui_setup' );

/**
 * The front-end stylesheet.
 */
function distinctui_enqueue() {
	wp_enqueue_style(
		'distinctui',
		get_theme_file_uri( 'assets/css/theme.css' ),
		array(),
		DISTINCTUI_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'distinctui_enqueue' );

/**
 * Block styles. Each one is a rule from the system with a name you can pick.
 */
function distinctui_block_styles() {
	$styles = array(
		'core/heading'    => array(
			'hero' => __( 'Hero', 'distinctui' ),
		),
		'core/paragraph'  => array(
			'lede'    => __( 'Lede', 'distinctui' ),
			'eyebrow' => __( 'Eyebrow', 'distinctui' ),
			'meta'    => __( 'Meta', 'distinctui' ),
			'badge'   => __( 'Badge', 'distinctui' ),
			'error'   => __( 'Error', 'distinctui' ),
		),
		'core/button'     => array(
			'text-link' => __( 'Text link', 'distinctui' ),
		),
		'core/group'      => array(
			'card'    => __( 'Card', 'distinctui' ),
			'feature' => __( 'Feature card', 'distinctui' ),
			'panel'   => __( 'Panel', 'distinctui' ),
			'note'    => __( 'Note', 'distinctui' ),
		),
		'core/column'     => array(
			'card'  => __( 'Card', 'distinctui' ),
			'panel' => __( 'Panel', 'distinctui' ),
		),
		'core/image'      => array(
			'mark'        => __( 'Mark', 'distinctui' ),
			'mark-dashed' => __( 'Mark, not live', 'distinctui' ),
			'mark-plain'  => __( 'Mark, plain', 'distinctui' ),
		),
		'core/avatar'     => array(
			'mark'        => __( 'Mark', 'distinctui' ),
			'mark-dashed' => __( 'Mark, not live', 'distinctui' ),
			'mark-plain'  => __( 'Mark, plain', 'distinctui' ),
		),
		'core/site-title' => array(
			'logo' => __( 'Logo with ring', 'distinctui' ),
		),
	);

	foreach ( $styles as $block => $variations ) {
		foreach ( $variations as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'distinctui_block_styles' );

/**
 * A pattern category, so the theme's sections are easy to find.
 */
function distinctui_pattern_categories() {
	register_block_pattern_category(
		'distinctui',
		array(
			'label'       => __( 'distinctUI', 'distinctui' ),
			'description' => __( 'Sections in the distinctUI look.', 'distinctui' ),
		)
	);
}
add_action( 'init', 'distinctui_pattern_categories' );
