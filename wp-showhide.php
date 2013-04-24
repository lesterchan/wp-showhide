<?php
/*
Plugin Name: WP-ShowHide
Plugin URI: http://lesterchan.net/portfolio/programming/php/
Description: Allows you to embed content within your blog post via WordPress ShortCode API and toggling the visibility of the cotent via a link. By default the content is hidden and user will have to click on the "Show Content" link to toggle it. Similar to what Engadget is doing for their press releases. Example usage: <code>[showhide type="pressrelease"]Press Release goes in here.[/showhide]</code>
Version: 1.00
Author: Lester 'GaMerZ' Chan
Author URI: http://lesterchan.net
Text Domain: wp-showhide
*/


/*
	Copyright 2013  Lester Chan  (email : lesterchan@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


### Function: Enqueue JavaScripts
add_action('wp_enqueue_scripts', 'showhide_scripts');
function showhide_scripts() {
	wp_enqueue_script('jquery');
}


### Function: Short Code For Inserting Press Release Into Post
add_shortcode('showhide', 'showhide_shortcode');
function showhide_shortcode($atts, $content = null) {
	// Variables
	$post_id = get_the_id();
	$word_count = number_format_i18n(sizeof(explode(' ', strip_tags($content))));

	// Extract ShortCode Attributes
	extract(shortcode_atts(array(
		'type' => 'pressrelease',
		'more_text' => __('Show Press Release (%s More Words)'),
		'less_text' => __('Hide Press Release (%s Less Words)'),
		'hidden' => 'yes'
	), $atts));

	// More/Less Text
	$more_text = sprintf($more_text, $word_count);
	$less_text = sprintf($less_text, $word_count);

	// Determine Whether To Show Or Hide Press Release
	$hidden_css = 'display: none;';
	if($hidden == 'no') {
		$hidden_css = 'display: block;';
		$tmp_text = $more_text;
		$more_text = $less_text;
		$less_text = $tmp_text;
	}

	// Format HTML Output
	$output = '<div class="'.$type.'-link"><a href="#" onclick="showhide_toggle(\''.$type.'\', '.$post_id.', \''.esc_js($more_text).'\', \''.esc_js($less_text).'\'); return false;"><span id="'.$type.'-toggle-'.$post_id.'">'.$more_text.'</span></a></div>';
	$output .= '<div id="'.$type.'-content-'.$post_id.'" class="'.$type.'-content" style="'.$hidden_css.'">'.$content.'</div>';

	return $output;
}


### Function: Add JavaScript To Footer
add_action('wp_footer', 'showhide_footer');
function showhide_footer() {
	echo '<script type="text/javascript">'."\n";
	echo '/* <![CDATA[ */'."\n";
	echo 'function showhide_toggle(a,b,c,d){jQuery("#"+a+"-content-"+b).toggle();jQuery("#"+a+"-toggle-"+b).text(jQuery("#"+a+"-toggle-"+b).text()==c?d:c)};'."\n";
	echo '/* ]]> */'."\n";
	echo '</script>'."\n";
}
?>