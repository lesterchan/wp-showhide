<?php
/**
 * The release invariants, asserted from the source and from the stored rows.
 *
 * These are the house rules every plugin in this family shares, and every one
 * of them has been broken by an ordinary edit at some point: a header field
 * that drifted out of the canonical order, a new directory shipped without its
 * silence guard, a version bumped in one file of three, a readme header line
 * that lost the two trailing spaces holding it apart from the next.
 *
 * They are the things a restructuring quietly breaks and nothing notices until
 * a release fails its pre-flight months later, so catching them here is far
 * cheaper than catching them there.
 *
 * @package WP-ShowHide
 */

/**
 * @coversNothing
 */
class WP_ShowHide_Metadata_Test extends WP_ShowHide_TestCase {

	const VERSION = '3.0.0';

	/**
	 * The main plugin file.
	 *
	 * @return string
	 */
	protected function plugin_file() {
		return wp_showhide_test_read( 'wp-showhide.php' );
	}

	/**
	 * The readme.
	 *
	 * @return string
	 */
	protected function readme() {
		return wp_showhide_test_read( 'README.md' );
	}

	/**
	 * The readme's header block: everything above the first blank line.
	 *
	 * @return string
	 */
	protected function readme_header() {
		return substr( $this->readme(), 0, (int) strpos( $this->readme(), "\n\n" ) );
	}

	/**
	 * Every directory the plugin ships, plugin root included.
	 *
	 * Dot directories are left out because .github never reaches a user, and
	 * vendor/ and node_modules/ are not ours and never ship.
	 *
	 * @return string[] Absolute paths.
	 */
	protected function shipped_directories() {
		$root  = dirname( __DIR__ );
		$found = array( $root );

		$iterator = new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
				/**
				 * Prune rather than filter afterwards: descending into
				 * node_modules only to throw its contents away costs tens of
				 * thousands of stat() calls.
				 *
				 * @param SplFileInfo $file Current entry.
				 * @return bool
				 */
				static function ( $file ) {
					if ( ! $file->isDir() ) {
						return false;
					}

					$name = $file->getFilename();

					return 'vendor' !== $name && 'node_modules' !== $name && '.' !== $name[0];
				}
			),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $file ) {
			$found[] = $file->getPathname();
		}

		return $found;
	}

	/**
	 * A field from the main plugin file's header docblock.
	 *
	 * @param string $field Field name.
	 * @return string
	 */
	protected function header_field( $field ) {
		$data = get_file_data( dirname( __DIR__ ) . '/wp-showhide.php', array( $field => $field ) );

		return $data[ $field ];
	}

	/**
	 * A field from the readme's header block.
	 *
	 * @param string $field Field name.
	 * @return string
	 */
	protected function readme_field( $field ) {
		preg_match( '/^' . preg_quote( $field, '/' ) . ':\s*(.+?)\s*$/m', $this->readme(), $matches );

		return isset( $matches[1] ) ? $matches[1] : '';
	}

	/**
	 * Every option row the plugin owns, read straight from the table.
	 *
	 * @return string[]
	 */
	protected function stored_option_names() {
		global $wpdb;

		return (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( 'wp_showhide_' ) . '%'
			)
		);
	}

	/**
	 * Header lines need two trailing spaces to render as separate lines.
	 *
	 * Markdown joins consecutive lines into one paragraph unless each is ended
	 * with a hard line break, so a missing pair renders as
	 * "License: GPLv2 or later License URI: https://..." on GitHub. It is
	 * invisible in the source and in a diff, which is exactly why it wants a
	 * test. The last line needs none, having nothing after it to run into.
	 */
	public function test_every_readme_header_line_keeps_its_line_break() {
		$lines = explode( "\n", $this->readme_header() );

		// The first line is the "# WP-ShowHide" heading, not a header field.
		$fields = array_slice( $lines, 1 );

		$this->assertCount( 9, $fields, 'The readme header holds exactly nine fields.' );

		$last = array_pop( $fields );

		foreach ( $fields as $line ) {
			$this->assertStringEndsWith(
				'  ',
				$line,
				"Needs two trailing spaces or it merges with the line below: {$line}"
			);
		}

		$this->assertSame( rtrim( $last ), $last, 'The last field needs no trailing spaces.' );
		$this->assertStringStartsWith( 'License URI:', $last );
	}

	public function test_canonical_lesterchan_urls() {
		$this->assertSame(
			'https://lesterchan.net/portfolio/programming/php/',
			$this->header_field( 'Plugin URI' )
		);
		$this->assertSame( 'https://lesterchan.net', $this->header_field( 'Author URI' ) );
		$this->assertSame( 'https://lesterchan.net/site/donation/', $this->readme_field( 'Donate link' ) );
		$this->assertSame(
			'https://www.gnu.org/licenses/gpl-2.0.html',
			$this->header_field( 'License URI' )
		);
		$this->assertSame( 'https://www.gnu.org/licenses/gpl-2.0.html', $this->readme_field( 'License URI' ) );
	}

	/**
	 * One name, in every plugin. A second contributor has to be added on
	 * wordpress.org as well, so a name here that is not on the listing silently
	 * does nothing.
	 */
	public function test_contributors_is_gamerz_only() {
		$this->assertSame( 'GamerZ', $this->readme_field( 'Contributors' ) );
	}

	public function test_text_domain_is_the_plugin_slug() {
		$this->assertSame( 'wp-showhide', $this->header_field( 'Text Domain' ) );
		$this->assertSame( '/languages', $this->header_field( 'Domain Path' ) );
		$this->assertSame( 'wp-showhide', WP_SHOWHIDE_SLUG );
	}

	/**
	 * Every translation call must carry that same domain, or the string is
	 * looked up in a catalogue that has never heard of it.
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

	public function test_version_matches_everywhere() {
		$this->assertSame( self::VERSION, $this->header_field( 'Version' ) );
		$this->assertSame( self::VERSION, $this->readme_field( 'Stable tag' ) );
		$this->assertSame( self::VERSION, WP_SHOWHIDE_VERSION );
		$this->assertStringContainsString(
			"define( 'WP_SHOWHIDE_VERSION', '" . self::VERSION . "' );",
			$this->plugin_file()
		);
	}

	public function test_requires_headers_match_readme() {
		$this->assertSame( '6.8', $this->header_field( 'Requires at least' ) );
		$this->assertSame( '6.8', $this->readme_field( 'Requires at least' ) );
		$this->assertSame( '8.2', $this->header_field( 'Requires PHP' ) );
		$this->assertSame( '8.2', $this->readme_field( 'Requires PHP' ) );
	}

	/**
	 * The order is neither alphabetical nor intuitive -- Requires at least and
	 * Requires PHP sit before Author -- so it is copied, never composed.
	 */
	public function test_the_plugin_header_fields_are_in_the_canonical_order() {
		preg_match( '#^<\?php\s*/\*\*(.+?)\*/#s', $this->plugin_file(), $matches );

		$this->assertNotEmpty( $matches, 'The plugin file must open with a docblock header.' );

		preg_match_all( '/^\s*\*\s*([A-Z][A-Za-z ]*?):\s/m', $matches[1], $fields );

		$this->assertSame(
			array(
				'Plugin Name',
				'Plugin URI',
				'Description',
				'Version',
				'Requires at least',
				'Requires PHP',
				'Author',
				'Author URI',
				'License',
				'License URI',
				'Text Domain',
				'Domain Path',
			),
			$fields[1]
		);
	}

	/**
	 * The readme order differs from the PHP one on purpose: Requires PHP comes
	 * after Stable tag here. They are not to be harmonised.
	 */
	public function test_the_readme_header_fields_are_in_the_canonical_order() {
		preg_match_all( '/^([A-Z][A-Za-z ]*?):\s/m', $this->readme_header(), $fields );

		$this->assertSame(
			array(
				'Contributors',
				'Donate link',
				'Tags',
				'Requires at least',
				'Tested up to',
				'Stable tag',
				'Requires PHP',
				'License',
				'License URI',
			),
			$fields[1]
		);
	}

	public function test_the_readme_lists_at_most_five_tags() {
		$tags = $this->readme_field( 'Tags' );

		$this->assertNotEmpty( $tags, 'The readme must carry a Tags line.' );
		$this->assertLessThanOrEqual( 5, count( explode( ',', $tags ) ) );
	}

	/**
	 * The second-level headings are a closed set in a fixed order.
	 *
	 * Third-level ones are not: Features, Donations and every changelog version
	 * live below these.
	 */
	public function test_readme_sections_are_the_canonical_set() {
		preg_match_all( '/^## (.+?)\s*$/m', $this->readme(), $sections );

		$this->assertSame(
			array(
				'Description',
				'Usage',
				'Frequently Asked Questions',
				'Screenshots',
				'Changelog',
				'Upgrade Notice',
			),
			$sections[1]
		);
	}

	/**
	 * The donations paragraph is the last h3 of the description, worded
	 * identically in all nineteen plugins.
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
	 * Bare versions: "### 3.0.0", never "### Version 3.0.0".
	 */
	public function test_every_changelog_heading_is_a_bare_version() {
		$this->assertSame( 0, preg_match( '/^### Version /m', $this->readme() ) );
		$this->assertStringContainsString( '### ' . self::VERSION . "\n", $this->readme() );
	}

	/**
	 * Five prefixes, and nothing else.
	 *
	 * The listing on wordpress.org renders the changelog verbatim, so a stray
	 * "Important:" or a lowercase "New:" is visible to every reader of it.
	 */
	public function test_changelog_prefixes_are_canonical() {
		$readme    = $this->readme();
		$changelog = substr( $readme, (int) strpos( $readme, '## Changelog' ) );
		$changelog = substr( $changelog, 0, (int) strpos( $changelog, "\n## Upgrade Notice" ) );

		preg_match_all( '/^\* (.+?):/m', $changelog, $bullets );

		$this->assertNotEmpty( $bullets[1], 'The changelog must carry bullets.' );

		foreach ( $bullets[1] as $prefix ) {
			$this->assertContains(
				$prefix . ':',
				array( 'BREAKING:', 'NEW:', 'CHANGED:', 'FIXED:', 'NOTE:' ),
				"'{$prefix}:' is not one of the five allowed changelog prefixes."
			);
		}
	}

	/**
	 * Raising the floors is a breaking change: a site below either number is
	 * never offered the update, and is owed an explanation for why.
	 */
	public function test_the_raised_floors_are_in_the_upgrade_notice() {
		$notice = substr( $this->readme(), (int) strpos( $this->readme(), '## Upgrade Notice' ) );

		$this->assertStringContainsString( 'WordPress 6.8', $notice );
		$this->assertStringContainsString( 'PHP 8.2', $notice );
	}

	/**
	 * The plugin dropped jQuery in 2.0.0 and must not quietly reacquire it,
	 * through a dependency array or through the script itself.
	 */
	public function test_no_jquery_is_enqueued() {
		do_action( 'wp_enqueue_scripts' );

		$this->assertSame(
			array(),
			wp_scripts()->registered['wp-showhide']->deps,
			'The one script the plugin registers depends on nothing at all.'
		);

		$sources = wp_showhide_test_source_code();

		foreach ( (array) glob( dirname( __DIR__ ) . '/js/*.js' ) as $file ) {
			$sources .= (string) file_get_contents( $file );
		}

		$this->assertStringNotContainsStringIgnoringCase( 'jquery', $sources );
		$this->assertStringNotContainsString( '$(', $sources );
	}

	public function test_every_directory_has_an_index_php() {
		foreach ( $this->shipped_directories() as $directory ) {
			$this->assertFileExists(
				$directory . '/index.php',
				"{$directory} ships to users and so needs an index.php silence guard."
			);

			// phpcbf cannot fix the one-line "// Silence is golden." form.
			$guard = (string) file_get_contents( $directory . '/index.php' );

			$this->assertStringContainsString( '/**', $guard, "{$directory}/index.php must use the docblock form." );
			$this->assertStringContainsString( 'Silence is golden.', $guard );
		}
	}

	/**
	 * Deleting the plugin leaves nothing behind.
	 *
	 * The assertion is deliberately a LIKE over wp_options rather than one
	 * delete_option() check: a row added later and forgotten in uninstall.php
	 * is exactly the failure this is here to catch. The multisite config runs
	 * the same test through uninstall.php's get_sites() branch.
	 */
	public function test_uninstall_removes_every_option_row() {
		WP_ShowHide_Options::maybe_upgrade();

		$this->assertNotEmpty(
			$this->stored_option_names(),
			'There should be rows to remove before uninstall runs.'
		);

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'wp-showhide/wp-showhide.php' );
		}

		require_once dirname( __DIR__ ) . '/uninstall.php';

		wp_cache_flush();

		$this->assertSame(
			array(),
			$this->stored_option_names(),
			'uninstall.php must remove every wp_showhide_* row.'
		);
	}

	/**
	 * The upgrade markers live in their own row, holding those two keys and no
	 * others. Anything else in here means a marker has drifted back into the
	 * settings array, which is the bug this shape exists to make impossible.
	 */
	public function test_version_row_holds_exactly_plugin_and_db() {
		WP_ShowHide_Options::maybe_upgrade();

		$markers = get_option( WP_ShowHide_Options::VERSION );

		$this->assertIsArray( $markers, 'wp_showhide_version must be an array.' );

		$keys = array_keys( $markers );
		sort( $keys );

		$this->assertSame( array( 'db', 'plugin' ), $keys );
		$this->assertSame( WP_SHOWHIDE_VERSION, $markers['plugin'] );
		$this->assertSame( WP_SHOWHIDE_DB_VERSION, $markers['db'] );
	}

	/**
	 * Elsewhere in the family this slot holds
	 * test_settings_sanitizer_never_stores_version_markers(), which guards
	 * against a version marker being kept inside the settings array.
	 *
	 * WP-ShowHide has no settings, no settings row and no sanitiser (section
	 * 2.1), so there is nothing for a marker to hide in. Section 7.2
	 * substitutes this assertion instead: the settings row must never come into
	 * existence. If a later change reintroduces one, the sanitiser test has to
	 * come back with it, and this failing is the reminder.
	 */
	public function test_no_settings_row_exists_to_hide_a_marker_in() {
		WP_ShowHide_Options::maybe_upgrade();

		$this->assertFalse(
			get_option( 'wp_showhide_options' ),
			'WP-ShowHide is exempt from the settings row; reinstating one needs the sanitiser test back.'
		);
	}

	/**
	 * No plugin in this family ships a second, mirrored stylesheet: the front
	 * end uses CSS logical properties instead, so one sheet serves both
	 * directions.
	 */
	public function test_no_rtl_stylesheet_is_registered() {
		$root = dirname( __DIR__ );

		$this->assertSame( array(), (array) glob( $root . '/*-rtl.css' ) );
		$this->assertSame( array(), (array) glob( $root . '/css/*-rtl.css' ) );
		$this->assertStringNotContainsString(
			'wp_style_add_data',
			wp_showhide_test_source_code(),
			"No plugin registers 'rtl' style data."
		);
	}

	/**
	 * The catalogue comes from translate.wordpress.org, and since WP 6.7
	 * calling load_plugin_textdomain() this early trips _doing_it_wrong.
	 */
	public function test_the_plugin_does_not_load_its_own_textdomain() {
		$this->assertStringNotContainsString( 'load_plugin_textdomain', wp_showhide_test_source_code() );
		$this->assertSame( 0, preg_match( '/^### Translations/m', $this->readme() ) );
	}

	/**
	 * The old forums.lesterchan.net is gone, and the rest of these had drifted
	 * to http over twenty years. Code spans are exempt: they document input.
	 */
	public function test_no_insecure_or_dead_links_remain() {
		$readme = preg_replace( '/`[^`]*`/', '', $this->readme() );

		$this->assertSame( 0, preg_match( '#http://#', $readme ), 'Every readme link must use https.' );
		$this->assertSame( 0, preg_match( '#http://#', $this->plugin_file() ) );
		$this->assertStringNotContainsString( 'forums.lesterchan.net', $readme );
	}

	public function test_the_gpl_licence_is_shipped() {
		$licence = wp_showhide_test_read( 'LICENSE' );

		$this->assertStringContainsString( 'GNU GENERAL PUBLIC LICENSE', $licence );
		$this->assertStringContainsString( 'Version 2, June 1991', $licence );
	}

	/**
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
	 * The catalogue is built by translate.wordpress.org, and Travis has been
	 * dead for these repos for years.
	 */
	public function test_no_abandoned_build_or_translation_artefacts_ship() {
		$root = dirname( __DIR__ );

		$this->assertFileDoesNotExist( $root . '/.travis.yml' );
		$this->assertFileDoesNotExist( $root . '/.wp-env.override.json' );
		$this->assertDirectoryDoesNotExist( $root . '/languages' );

		foreach ( array( 'pot', 'po', 'mo' ) as $extension ) {
			$this->assertSame(
				array(),
				(array) glob( $root . '/*.' . $extension ),
				"No .{$extension} files: translate.wordpress.org builds the catalogue."
			);
		}
	}
}
