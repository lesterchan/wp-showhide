<?php
/**
 * Deprecated functions kept for backwards compatibility.
 *
 * These were the plugin's internals before 2.1.0, but they lived in the global
 * namespace and a theme may well be calling them. They still work; they just
 * forward to the classes now, and warn when WP_DEBUG is on.
 *
 * Note that unhooking by name no longer works: the enqueue callback is now
 * ShowHide::register_assets(), so
 * remove_action( 'wp_enqueue_scripts', 'showhide_scripts' ) has nothing to
 * remove. Themes that only want the CSS gone should dequeue it instead, which
 * has always been the supported route:
 *
 *     add_action( 'wp_enqueue_scripts', function () {
 *         wp_dequeue_style( 'wp-showhide' );
 *     }, 20 );
 *
 * @package WP-ShowHide
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'showhide_scripts' ) ) {
	/**
	 * Register the front end script and style.
	 *
	 * @deprecated 2.1.0 Use ShowHide::register_assets().
	 *
	 * @return void
	 */
	function showhide_scripts() {
		_deprecated_function( __FUNCTION__, '2.1.0', 'ShowHide::register_assets()' );

		ShowHide::get_instance()->register_assets();
	}
}

if ( ! function_exists( 'showhide_js' ) ) {
	/**
	 * The front end JavaScript that toggles the content.
	 *
	 * @deprecated 2.1.0 Use ShowHide_Template::script().
	 *
	 * @return string The JavaScript, without an enclosing script tag.
	 */
	function showhide_js() {
		_deprecated_function( __FUNCTION__, '2.1.0', 'ShowHide_Template::script()' );

		return ShowHide_Template::script();
	}
}

if ( ! function_exists( 'showhide_shortcode' ) ) {
	/**
	 * Render one [showhide] block.
	 *
	 * @deprecated 2.1.0 Use ShowHide::shortcode().
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content The content to be shown or hidden.
	 * @return string The shortcode markup.
	 */
	function showhide_shortcode( $atts, $content = null ) {
		_deprecated_function( __FUNCTION__, '2.1.0', 'ShowHide::shortcode()' );

		return ShowHide::get_instance()->shortcode( $atts, $content );
	}
}
