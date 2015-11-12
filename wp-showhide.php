<?php
/*
Plugin Name: WP-ShowHide
Plugin URI: http://lesterchan.net/portfolio/programming/php/
Description: Allows you to embed content within your blog post via WordPress ShortCode API and toggling the visibility of the content via a link. By default the content is hidden and user will have to click on the "Show Content" link to toggle it. Similar to what Engadget is doing for their press releases. Example usage: <code>[showhide type="pressrelease"]Press Release goes in here.[/showhide]</code>
Version: 1.04
Author: Lester 'GaMerZ' Chan
Author URI: http://lesterchan.net
Text Domain: wp-showhide
Domain Path: /languages/
License: GPL2
*/

/*  Copyright 2015  Lester Chan  (email : lesterchan@gmail.com)

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


### Function: Enqueue JavaScripts
add_action( 'wp_enqueue_scripts', 'showhide_scripts' );
function showhide_scripts() {
	wp_enqueue_script( 'jquery' );
}

### Function: Load Translation
add_action( 'plugins_loaded', 'showhide_textdomain' );
function showhide_textdomain() {
	load_plugin_textdomain( 'wp-showhide', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

### Function: Short Code For Inserting Press Release Into Post
add_shortcode( 'showhide', 'showhide_shortcode' );
function showhide_shortcode( $atts, $content = null ) {
	// Variables
	$post_id = get_the_id();
	$word_count = number_format_i18n( sizeof( explode( ' ', strip_tags( $content ) ) ) );

	// Extract ShortCode Attributes
	$attributes = shortcode_atts( array(
		'type' => 'pressrelease',
		'more_text' => __( 'Show Press Release (%s More Words)', 'wp-showhide' ),
		'less_text' => __( 'Hide Press Release (%s Less Words)', 'wp-showhide' ),
		'hidden' => 'yes'
	), $atts );

	// More/Less Text
	$more_text = sprintf( $attributes['more_text'], $word_count );
	$less_text = sprintf( $attributes['less_text'], $word_count );

	// Determine Whether To Show Or Hide Press Release
	$hidden_class = 'sh-hide';
	$hidden_css = 'display: none;';
	$hidden_aria_expanded = 'false';
	if( $attributes['hidden'] === 'no' ) {
		$hidden_class = 'sh-show';
		$hidden_css = 'display: block;';
		$hidden_aria_expanded = 'true';
		$tmp_text = $more_text;
		$more_text = $less_text;
		$less_text = $tmp_text;
	}

	// Format HTML Output
	$output  = '<div id="' . $attributes['type'] . '-link-' . $post_id . '" class="sh-link ' . $attributes['type'] . '-link ' . $hidden_class .'"><a href="#" onclick="showhide_toggle(\'' . esc_js( $attributes['type'] ) . '\', ' . $post_id . ', \'' . esc_js( $more_text ) . '\', \'' . esc_js( $less_text ) . '\'); return false;" aria-expanded="' . $hidden_aria_expanded .'"><span id="' . $attributes['type'] . '-toggle-' . $post_id . '">' . $more_text . '</span></a></div>';
	$output .= '<div id="' . $attributes['type'] . '-content-' . $post_id . '" class="sh-content ' . $attributes['type'] . '-content ' . $hidden_class . '" style="' . $hidden_css . '">' . do_shortcode( $content ) . '</div>';

	return $output;
}

### Function: Add JavaScript To Footer
add_action( 'wp_footer', 'showhide_footer' );
function showhide_footer() {
?>
	<?php if( WP_DEBUG ): ?>
		<script type="text/javascript">
			function showhide_toggle(type, post_id, more_text, less_text) {
				var   $link = jQuery("#"+ type + "-link-" + post_id)
					, $link_a = jQuery('a', $link)
					, $content = jQuery("#"+ type + "-content-" + post_id)
					, $toggle = jQuery("#"+ type + "-toggle-" + post_id)
					, show_hide_class = 'sh-show sh-hide';
				$link.toggleClass(show_hide_class);
				$content.toggleClass(show_hide_class).toggle();
				if($link_a.attr('aria-expanded') === 'true') {
					$link_a.attr('aria-expanded', 'false');
				} else {
					$link_a.attr('aria-expanded', 'true');
				}
				if($toggle.text() === more_text) {
					$toggle.text(less_text);
				} else {
					$toggle.text(more_text);
				}
			}
		</script>
	<?php else : ?>
		<script type="text/javascript">function showhide_toggle(a,b,d,f){var e=jQuery("#"+a+"-link-"+b),c=jQuery("a",e),g=jQuery("#"+a+"-content-"+b);a=jQuery("#"+a+"-toggle-"+b);e.toggleClass("sh-show sh-hide");g.toggleClass("sh-show sh-hide").toggle();"true"===c.attr("aria-expanded")?c.attr("aria-expanded","false"):c.attr("aria-expanded","true");a.text()===d?a.text(f):a.text(d)};</script>
	<?php endif; ?>
<?php
}