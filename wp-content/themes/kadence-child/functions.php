<?php
/**
 * Kadence child theme functions. Keep this file to: enqueueing, and the
 * front-end weight cleanup described in the project brief (section 4).
 * Anything product-specific (the wheel, the other tools) lives in the
 * carkcevirici-tools plugin, not here.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the parent Kadence stylesheet, then this child theme's own file.
 */
function carkcevirici_child_enqueue_styles() {
	$parent_style = 'kadence-style';

	wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css', array(), wp_get_theme( 'kadence' )->get( 'Version' ) );
	wp_enqueue_style(
		'kadence-child-style',
		get_stylesheet_uri(),
		array( $parent_style ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'carkcevirici_child_enqueue_styles' );

/**
 * Front-end weight cleanup. None of this touches wp-admin.
 */
function carkcevirici_trim_head_bloat() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'carkcevirici_trim_head_bloat' );

/**
 * oEmbed's own front-end JS is rarely used (we don't embed external media
 * in tool pages); drop it along with the DNS prefetch for Twitter/s.w.org.
 */
function carkcevirici_dequeue_embed_script() {
	wp_deregister_script( 'wp-embed' );
}
add_action( 'wp_footer', 'carkcevirici_dequeue_embed_script' );

function carkcevirici_remove_dns_prefetch( $hints, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$hints = array_diff( $hints, array( '//s.w.org' ) );
	}
	return $hints;
}
add_filter( 'wp_resource_hints', 'carkcevirici_remove_dns_prefetch', 10, 2 );

/**
 * Dashicons are only needed for logged-in users with the admin bar showing.
 */
function carkcevirici_dequeue_dashicons() {
	if ( ! is_user_logged_in() ) {
		wp_deregister_style( 'dashicons' );
	}
}
add_action( 'wp_enqueue_scripts', 'carkcevirici_dequeue_dashicons', 20 );

/**
 * Attachment pages are not part of the site's content strategy; send them
 * to their parent post rather than let a thin, near-duplicate page get
 * indexed (Rank Math's own attachment noindex rule covers the rest).
 */
function carkcevirici_redirect_attachment_pages() {
	if ( is_attachment() ) {
		$parent_id = wp_get_post_parent_id( get_queried_object_id() );
		wp_safe_redirect( $parent_id ? get_permalink( $parent_id ) : home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'carkcevirici_redirect_attachment_pages' );
