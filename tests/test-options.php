<?php
/**
 * The stored row and the upgrade routine that writes it.
 *
 * WP-ShowHide has nothing for a site owner to configure, so it stores no
 * settings row at all -- only the version markers. That absence is the point
 * worth pinning: it is a deliberate exemption (STANDARDS.md 2.1), not a row
 * that failed to be created, and a later change that quietly reintroduces an
 * empty autoloaded option should fail here.
 *
 * @package WP-ShowHide
 */

/**
 * @covers WP_ShowHide_Options
 */
class WP_ShowHide_Options_Test extends WP_ShowHide_TestCase {

	public function test_the_marker_row_is_the_canonical_name() {
		$this->assertSame( 'wp_showhide_version', WP_ShowHide_Options::VERSION );
	}

	/**
	 * The 2.1 exemption, asserted directly. A plugin with no settings owns no
	 * settings row, so nothing here may create one -- not on upgrade, not on
	 * activation, not ever.
	 */
	public function test_no_settings_row_is_ever_created() {
		delete_option( 'wp_showhide_options' );

		WP_ShowHide_Options::maybe_upgrade();

		$this->assertFalse(
			get_option( 'wp_showhide_options' ),
			'WP-ShowHide has no settings, so it must not store a settings row.'
		);
	}

	/**
	 * The class must not grow the settings API back by accident either: no
	 * OPTION constant, and no sanitiser for a form that does not exist.
	 */
	public function test_the_class_exposes_no_settings_api() {
		$this->assertFalse(
			defined( 'WP_ShowHide_Options::OPTION' ),
			'A settings row constant means a settings row is coming back.'
		);
		$this->assertFalse(
			method_exists( 'WP_ShowHide_Options', 'sanitize' ),
			'A sanitiser with no register_setting() to call it is ceremony.'
		);
	}

	public function test_the_upgrade_check_runs_on_plugins_loaded() {
		$this->assertSame(
			10,
			has_action( 'plugins_loaded', array( 'WP_ShowHide_Options', 'maybe_upgrade' ) ),
			'The upgrade check has to be registered for the row ever to appear.'
		);
	}

	public function test_both_markers_are_written_together() {
		delete_option( WP_ShowHide_Options::VERSION );

		WP_ShowHide_Options::maybe_upgrade();

		$this->assertSame(
			array(
				'plugin' => WP_SHOWHIDE_VERSION,
				'db'     => WP_SHOWHIDE_DB_VERSION,
			),
			get_option( WP_ShowHide_Options::VERSION )
		);
	}

	public function test_an_older_marker_pair_is_brought_forward() {
		update_option(
			WP_ShowHide_Options::VERSION,
			array(
				'plugin' => '2.0.0',
				'db'     => '0',
			)
		);

		WP_ShowHide_Options::maybe_upgrade();

		$markers = get_option( WP_ShowHide_Options::VERSION );

		$this->assertSame( WP_SHOWHIDE_VERSION, $markers['plugin'] );
		$this->assertSame( WP_SHOWHIDE_DB_VERSION, $markers['db'] );
	}

	/**
	 * A run with the markers already current must not write. The check happens
	 * on every request, so a write here would be a write on every request.
	 */
	public function test_a_second_run_writes_nothing() {
		WP_ShowHide_Options::maybe_upgrade();

		$before = get_num_queries();

		WP_ShowHide_Options::maybe_upgrade();

		$this->assertSame(
			$before,
			get_num_queries(),
			'The markers already agreed, so the upgrade routine had nothing to do.'
		);
	}

	/**
	 * A row someone has replaced with a string, which get_option() hands back
	 * exactly as stored, must not become an array offset error.
	 */
	public function test_a_corrupt_marker_row_is_rewritten_rather_than_trusted() {
		update_option( WP_ShowHide_Options::VERSION, 'not an array' );

		WP_ShowHide_Options::maybe_upgrade();

		$this->assertSame(
			array(
				'plugin' => WP_SHOWHIDE_VERSION,
				'db'     => WP_SHOWHIDE_DB_VERSION,
			),
			get_option( WP_ShowHide_Options::VERSION )
		);
	}

	public function test_markers_normalises_a_corrupt_row_without_writing() {
		update_option( WP_ShowHide_Options::VERSION, 'not an array' );

		$this->assertSame( array(), WP_ShowHide_Options::markers() );
	}

	/**
	 * The marker row is autoloaded: it is read on every request, and an extra
	 * query for one tiny row is the wrong trade.
	 */
	public function test_the_marker_row_is_autoloaded() {
		delete_option( WP_ShowHide_Options::VERSION );

		WP_ShowHide_Options::maybe_upgrade();

		wp_cache_delete( 'alloptions', 'options' );

		$this->assertArrayHasKey( WP_ShowHide_Options::VERSION, wp_load_alloptions() );
	}
}
