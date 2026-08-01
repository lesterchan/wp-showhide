<?php
/**
 * The release invariants, asserted from the source and from the stored rows.
 *
 * Everything §7.2 asks of all nineteen plugins now lives in
 * Plugin_Metadata_TestCase. What is left here is what only WP-ShowHide can
 * say: the version it ships, its class prefix, the breaks its Upgrade Notice
 * has to cover, and the fact that a shortcode and its two asset files store
 * nothing between requests.
 *
 * @package WP-ShowHide
 */

/**
 * WP-ShowHide's half of the shared metadata contract.
 *
 * @coversNothing
 */
class WP_ShowHide_Metadata_Test extends Plugin_Metadata_TestCase {

	/**
	 * The version this release ships.
	 *
	 * @return string
	 */
	protected function expected_version() {
		return '3.0.0';
	}

	/**
	 * The prefix every class the plugin declares carries.
	 *
	 * @return string
	 */
	protected function class_prefix() {
		return 'WP_ShowHide';
	}

	/**
	 * What a site owner updating from the released 1.02 would notice.
	 *
	 * Posts need no editing, which is the first thing the notice says. What did
	 * change is everything a theme could have reached for: three global
	 * functions that are gone, two classes that are renamed, and the
	 * unhooking recipe people used to suppress the stylesheet, which no longer
	 * removes anything now that the assets are files with handles.
	 *
	 * @return string[]
	 */
	protected function upgrade_notice_subjects() {
		return array(
			'WordPress 6.8',
			'PHP 8.2',
			'`[showhide]`',
			'`showhide_scripts()`',
			'`showhide_js()`',
			'`showhide_shortcode()`',
			'`ShowHide`',
			'`WP_ShowHide`',
			'`ShowHide_Template`',
			'`WP_ShowHide_Template`',
			"wp_dequeue_style( 'wp-showhide' )",
			'`wp_showhide_version`',
		);
	}

	/**
	 * This plugin keeps no version marker row (§2.1).
	 *
	 * One shortcode, one script and one stylesheet. No settings, no schema and
	 * no migration, so there is nothing for a marker to mark.
	 *
	 * @return bool
	 */
	protected function has_version_row() {
		return false;
	}

	/**
	 * This plugin keeps no settings row either, and so has no sanitiser.
	 *
	 * @return bool
	 */
	protected function has_settings_row() {
		return false;
	}

	/**
	 * The one row uninstall will ever find.
	 *
	 * Nothing writes it now. An early build of the unreleased 3.0.0 did write
	 * wp_showhide_version, so uninstall.php is the only thing that will ever
	 * take it off a site that ran that build -- and the shared uninstall test
	 * needs a row to exist before it can prove one was removed.
	 *
	 * @return void
	 */
	protected function seed_option_rows() {
		update_option( 'wp_showhide_version', array( 'plugin' => '3.0.0' ) );
	}

	/**
	 * Register the script and the stylesheet.
	 *
	 * Both are registered on wp_enqueue_scripts, unconditionally, so firing the
	 * action is all the shared asset tests need to see the handles.
	 *
	 * @return void
	 */
	protected function register_plugin_assets() {
		do_action( 'wp_enqueue_scripts' );
	}

	/**
	 * At most five tags: wordpress.org shows five and ignores the rest.
	 */
	public function test_the_readme_lists_at_most_five_tags() {
		$tags = $this->readme_field( 'Tags' );

		$this->assertNotEmpty( $tags, 'The readme must carry a Tags line.' );
		$this->assertLessThanOrEqual( 5, count( explode( ',', $tags ) ) );
	}

	/**
	 * No Translations section: translate.wordpress.org is the only route in.
	 */
	public function test_the_readme_has_no_translations_section() {
		$this->assertSame( 0, preg_match( '/^### Translations/m', $this->readme() ) );
	}

	/**
	 * Every translation call carries the plugin's own text domain.
	 *
	 * A call without it is looked up in a catalogue that has never heard of the
	 * string, and falls back to English in every locale.
	 */
	public function test_every_translation_call_uses_the_plugin_text_domain() {
		preg_match_all( '/(?:__|_n|_x)\((.*?)\);/s', wp_showhide_test_source_code(), $calls );

		$this->assertNotEmpty( $calls[1], 'The plugin has translatable strings to check.' );

		foreach ( $calls[1] as $arguments ) {
			$this->assertStringContainsString(
				"'wp-showhide'",
				$arguments,
				"A translation call is missing the text domain: {$arguments}"
			);
		}
	}

	/**
	 * The donations paragraph is the last h3 of the description.
	 *
	 * Worded identically in all nineteen plugins, and positioned so it cannot
	 * end up under Usage or the FAQ.
	 */
	public function test_the_donations_section_closes_the_description() {
		$description = substr( $this->readme(), (int) strpos( $this->readme(), '## Description' ) );
		$description = substr( $description, 0, (int) strpos( $description, "\n## Usage" ) );

		preg_match_all( '/^### (.+?)\s*$/m', $description, $headings );

		$this->assertSame( 'Donations', end( $headings[1] ) );
		$this->assertStringContainsString(
			'I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks, I will really appreciate it. If not feel free to use it without any obligations.',
			$description
		);
	}

	/**
	 * The old forums.lesterchan.net is gone, and the rest had drifted to http.
	 *
	 * Code spans are exempt: they document input rather than link anywhere.
	 */
	public function test_no_insecure_or_dead_links_remain() {
		$readme = (string) preg_replace( '/`[^`]*`/', '', $this->readme() );

		$this->assertSame( 0, preg_match( '#http://#', $readme ), 'Every readme link must use https.' );
		$this->assertSame( 0, preg_match( '#http://#', $this->plugin_file() ) );
		$this->assertStringNotContainsString( 'forums.lesterchan.net', $readme );
	}

	/**
	 * The licence says the same thing in all three places it is stated.
	 *
	 * The header says "GPLv2 or later" and composer.json says
	 * GPL-2.0-or-later, so the comment block below the header has to offer the
	 * later-version option too. Until 3.0.0 it did not, and the plugin shipped
	 * a licence statement that contradicted itself twice over.
	 */
	public function test_the_licence_comment_offers_the_later_version_option() {
		$this->assertSame( 'GPLv2 or later', $this->header_field( 'License' ) );
		$this->assertStringContainsString(
			'either version 2 of the License, or' . "\n\t(at your option) any later version.",
			$this->plugin_file()
		);
		$this->assertStringContainsString( '"license": "GPL-2.0-or-later"', wp_showhide_test_read( 'composer.json' ) );
	}

	/**
	 * The plugin writes no option row at all, ever.
	 *
	 * Stronger than the two shared opt-out assertions, which each name one row.
	 * WP-ShowHide is one shortcode and its two asset files; it keeps no state
	 * between requests, so under §2.1 it stores nothing -- not a settings row,
	 * not the version markers, and not some third row a later change might
	 * invent.
	 */
	public function test_the_plugin_stores_nothing() {
		do_action( 'plugins_loaded' );
		do_action( 'init' );

		$this->assertSame(
			array(),
			$this->stored_option_names(),
			'WP-ShowHide wrote an option row; it is meant to store nothing at all.'
		);
	}
}
