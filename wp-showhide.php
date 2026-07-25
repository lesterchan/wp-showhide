<?php
/**
 * Plugin Name: WP-ShowHide
 * Plugin URI: https://lesterchan.net/portfolio/programming/php/
 * Description: Allows you to embed content within your blog post via WordPress ShortCode API and toggling the visibility of the content via a link. By default the content is hidden and user will have to click on the "Show Content" link to toggle it. Similar to what Engadget is doing for their press releases. Example usage: <code>[showhide type="pressrelease"]Press Release goes in here.[/showhide]</code>
 * Version: 2.0.0
 * Requires at least: 4.5
 * Requires PHP: 7.4
 * Author: Lester 'GaMerZ' Chan
 * Author URI: https://lesterchan.net
 * Text Domain: wp-showhide
 * Domain Path: /languages/
 * License: GPL2
 *
 * @package WP-ShowHide
 */

/*
	Copyright 2025  Lester Chan  (email : lesterchan@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action( 'wp_enqueue_scripts', 'showhide_scripts' );

/**
 * Registers the front end script and style.
 *
 * The script is registered without a src so that only the inline script is
 * printed, and only on the pages where the shortcode enqueues it. The style is
 * enqueued unconditionally so that the toggle is styled in the head and never
 * flashes as a native button.
 *
 * @return void
 */
function showhide_scripts() {
	wp_register_script( 'wp-showhide', false, array(), '2.0.0', true );
	wp_add_inline_script( 'wp-showhide', showhide_js() );

	wp_register_style( 'wp-showhide', false, array(), '2.0.0' );
	wp_enqueue_style( 'wp-showhide' );
	wp_add_inline_style( 'wp-showhide', '.sh-toggle{background:none;border:0;padding:0;margin:0;font:inherit;color:inherit;cursor:pointer;text-decoration:underline}.sh-content[hidden]{display:none}' );
}

/**
 * Returns the front end JavaScript that toggles the content.
 *
 * @return string The JavaScript, without an enclosing script tag.
 */
function showhide_js() {
	return <<<'JS'
( function () {
	document.addEventListener( 'click', function ( e ) {
		var button = e.target.closest ? e.target.closest( '.sh-toggle' ) : null;
		if ( ! button ) {
			return;
		}

		var wrap = button.closest( '.sh-link' ),
			content = document.getElementById( button.getAttribute( 'aria-controls' ) ),
			expanded = button.getAttribute( 'aria-expanded' ) === 'true';

		if ( ! content ) {
			return;
		}

		button.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
		button.textContent = expanded ? button.dataset.shMore : button.dataset.shLess;
		content.hidden = expanded;

		[ wrap, content ].forEach( function ( el ) {
			if ( el ) {
				el.classList.toggle( 'sh-show', ! expanded );
				el.classList.toggle( 'sh-hide', expanded );
			}
		} );

		[ expanded ? 'sh-link:less' : 'sh-link:more', 'sh-link:toggle' ].forEach( function ( name ) {
			wrap.dispatchEvent( new CustomEvent( name, { bubbles: true } ) );
		} );
	} );

	// Deprecated: Retained So 1.x Callers Of showhide_toggle() Keep Working
	window.showhide_toggle = function ( type, post_id ) {
		var wrap = document.getElementById( type + '-link-' + post_id ),
			button = wrap ? wrap.querySelector( '.sh-toggle' ) : null;
		if ( button ) {
			button.click();
		}
	};
}() );
JS;
}

add_shortcode( 'showhide', 'showhide_shortcode' );

/**
 * Renders the [showhide] shortcode.
 *
 * @param array|string $atts    Shortcode attributes. Accepts 'type', 'more_text',
 *                              'less_text' and 'hidden'.
 * @param string|null  $content The content to be shown or hidden.
 * @return string The shortcode markup.
 */
function showhide_shortcode( $atts, $content = null ) {
	// Variables.
	$post_id    = absint( get_the_id() );
	$word_count = number_format_i18n( count( preg_split( '/\s+/', wp_strip_all_tags( (string) $content ), -1, PREG_SPLIT_NO_EMPTY ) ) );

	// Extract ShortCode Attributes.
	$attributes = shortcode_atts(
		array(
			'type'      => 'pressrelease',
			// translators: %s: Number of words in the hidden content.
			'more_text' => __( 'Show Press Release (%s More Words)', 'wp-showhide' ),
			// translators: %s: Number of words in the hidden content.
			'less_text' => __( 'Hide Press Release (%s Less Words)', 'wp-showhide' ),
			'hidden'    => 'yes',
		),
		$atts
	);

	// Sanitize the type as it is used as an HTML ID and class.
	$type               = preg_replace( '/[^A-Za-z0-9_\x{00A0}-\x{10FFFF}-]/u', '', $attributes['type'] );
	$attributes['type'] = ( null === $type || '' === $type ) ? 'pressrelease' : $type;

	// More/less text, using str_replace() instead of sprintf() as the text can be user supplied.
	$more_text = str_replace( array( '%1$s', '%s' ), $word_count, $attributes['more_text'] );
	$less_text = str_replace( array( '%1$s', '%s' ), $word_count, $attributes['less_text'] );

	// Determine whether to show or hide the press release.
	$expanded     = ( 'no' === $attributes['hidden'] );
	$hidden_class = $expanded ? 'sh-show' : 'sh-hide';

	// Only loaded on the pages that actually use the shortcode.
	wp_enqueue_script( 'wp-showhide' );

	// A post can use the same type more than once, so suffix repeats to keep the IDs unique.
	static $instances   = array();
	$base               = $attributes['type'] . '-' . $post_id;
	$instances[ $base ] = isset( $instances[ $base ] ) ? $instances[ $base ] + 1 : 1;
	$instance           = $instances[ $base ] > 1 ? '-' . $instances[ $base ] : '';

	// Format HTML output.
	$link_id    = $attributes['type'] . '-link-' . $post_id . $instance;
	$content_id = $attributes['type'] . '-content-' . $post_id . $instance;

	$output  = '<div id="' . esc_attr( $link_id ) . '" class="sh-link ' . esc_attr( $attributes['type'] ) . '-link ' . $hidden_class . '">';
	$output .= '<button type="button" class="sh-toggle" aria-expanded="' . ( $expanded ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $content_id ) . '" data-sh-more="' . esc_attr( $more_text ) . '" data-sh-less="' . esc_attr( $less_text ) . '">' . esc_html( $expanded ? $less_text : $more_text ) . '</button>';
	$output .= '</div>';
	$output .= '<div id="' . esc_attr( $content_id ) . '" class="sh-content ' . esc_attr( $attributes['type'] ) . '-content ' . $hidden_class . '"' . ( $expanded ? '' : ' hidden' ) . '>' . do_shortcode( $content ) . '</div>';

	return $output;
}
