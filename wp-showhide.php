<?php
/**
 * Plugin Name: WP-ShowHide
 * Plugin URI: https://lesterchan.net/portfolio/programming/php/
 * Description: Allows you to embed content within your blog post via WordPress ShortCode API and toggling the visibility of the content via a button. By default the content is hidden and user will have to click on the "Show Content" button to toggle it. Similar to what Engadget is doing for their press releases. Example usage: <code>[showhide type="pressrelease"]Press Release goes in here.[/showhide]</code>
 * Version: 2.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Lester 'GaMerZ' Chan
 * Author URI: https://lesterchan.net
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-showhide
 * Domain Path: /languages
 *
 * @package WP-ShowHide
 */

/*
	Copyright 2026  Lester Chan  (email : lesterchan@gmail.com)

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

defined( 'ABSPATH' ) || exit;

/**
 * WP-ShowHide version.
 */
define( 'WP_SHOWHIDE_VERSION', '2.1.0' );

/**
 * WP-ShowHide main file.
 */
define( 'WP_SHOWHIDE_MAIN_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-showhide-template.php';
require_once __DIR__ . '/includes/class-showhide.php';
require_once __DIR__ . '/includes/deprecated.php';

ShowHide::get_instance();
