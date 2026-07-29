<?php
/**
 * The plugin's stored row.
 *
 * WP-ShowHide has no settings screen and nothing a site owner can change: the
 * [showhide] shortcode carries its type, its labels and its initial state as
 * inline attributes, so every choice belongs to the post that makes it. It
 * therefore stores no settings row at all -- an empty autoloaded option and a
 * sanitiser nothing registers would be ceremony, not consistency (STANDARDS.md
 * 2.1).
 *
 * wp_showhide_version is the pair of upgrade markers, and earns its place on
 * its own: without it a future release has no way to tell which version it is
 * upgrading from. It is kept outside any settings array by the same rule that
 * applies everywhere else, so that a settings save and an upgrade can never
 * overwrite each other -- which matters the day this plugin does grow a
 * setting.
 *
 * @package WP-ShowHide
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and upgrades the version markers.
 */
class WP_ShowHide_Options {

	/**
	 * Upgrade markers row, holding 'plugin' and 'db'. Autoloaded.
	 */
	const VERSION = 'wp_showhide_version';

	/**
	 * Register the upgrade check.
	 *
	 * Hooked to plugins_loaded rather than to activation: an activation hook
	 * does not run for a plugin that was network-activated before this version,
	 * nor for one dropped into mu-plugins, and the check costs one autoloaded
	 * read once the markers agree.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_upgrade' ) );
	}

	/**
	 * The stored upgrade markers, normalised.
	 *
	 * @return array
	 */
	public static function markers() {
		$markers = get_option( self::VERSION, array() );

		return is_array( $markers ) ? $markers : array();
	}

	/**
	 * Bring the stored markers up to the running version.
	 *
	 * Both are written in one call, so an upgrade that dies half way never
	 * records itself as finished.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$markers = self::markers();

		$plugin = isset( $markers['plugin'] ) ? (string) $markers['plugin'] : '';
		$db     = isset( $markers['db'] ) ? (string) $markers['db'] : '';

		if ( WP_SHOWHIDE_VERSION === $plugin && WP_SHOWHIDE_DB_VERSION === $db ) {
			return;
		}

		update_option(
			self::VERSION,
			array(
				'plugin' => WP_SHOWHIDE_VERSION,
				'db'     => WP_SHOWHIDE_DB_VERSION,
			),
			true
		);
	}
}
