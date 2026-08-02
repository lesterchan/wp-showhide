<?php
/**
 * Plugin Name: WP-ShowHide
 * Plugin URI: https://lesterchan.net/portfolio/programming/php/
 * Description: Allows you to embed content within your blog post via WordPress ShortCode API and toggling the visibility of the content via a button.
 * Version: 3.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.2
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
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined( 'ABSPATH' ) || exit;

/**
 * WP-ShowHide version. The last-run value is kept in the wp_showhide_version row.
 */
define( 'WP_SHOWHIDE_VERSION', '3.0.0' );

/**
 * WP-ShowHide slug, which is also the text domain and the asset handle.
 */
define( 'WP_SHOWHIDE_SLUG', 'wp-showhide' );

/**
 * WP-ShowHide main file.
 */
define( 'WP_SHOWHIDE_MAIN_FILE', __FILE__ );

/**
 * WP-ShowHide directory, with a trailing slash.
 */
define( 'WP_SHOWHIDE_DIR', plugin_dir_path( __FILE__ ) );

/**
 * WP-ShowHide URL, with a trailing slash.
 */
define( 'WP_SHOWHIDE_URL', plugin_dir_url( __FILE__ ) );

require_once WP_SHOWHIDE_DIR . 'includes/class-wp-showhide-template.php';
require_once WP_SHOWHIDE_DIR . 'includes/class-wp-showhide.php';

WP_ShowHide::get_instance();
