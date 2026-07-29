<?php
/**
 * Uninstall WP-ShowHide.
 *
 * Runs with the plugin inactive, so nothing here may depend on the plugin's
 * own classes or constants being loaded. The row name is therefore spelled out
 * rather than read from WP_ShowHide_Options.
 *
 * @package WP-ShowHide
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/**
 * Delete the plugin's options for the current site.
 *
 * One row only: WP-ShowHide has nothing a site owner can configure and so
 * stores no settings row at all (STANDARDS.md 2.1). If it ever grows one, it
 * gets deleted here too -- tests/test-metadata.php asserts over wp_options with
 * a LIKE rather than naming rows, so a forgotten row fails the suite.
 *
 * @return void
 */
function wp_showhide_uninstall_site() {
	delete_option( 'wp_showhide_version' );
}

if ( is_multisite() ) {
	/*
	 * 'number' => 0 lifts WP_Site_Query's default cap of 100, which would
	 * otherwise leave the rows behind on every site past the hundredth while
	 * still reporting a successful uninstall. 'fields' => 'ids' avoids
	 * hydrating WP_Site objects the loop never looks at.
	 */
	$wp_showhide_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $wp_showhide_site_ids as $wp_showhide_site_id ) {
		// switch_to_blog() pushes onto a stack, so the restore belongs inside
		// the loop -- restoring once at the end leaves it unwound by one.
		switch_to_blog( (int) $wp_showhide_site_id );

		wp_showhide_uninstall_site();

		restore_current_blog();
	}
} else {
	wp_showhide_uninstall_site();
}
