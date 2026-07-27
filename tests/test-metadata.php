<?php
/**
 * The release invariants, asserted from the source.
 *
 * These are house rules for every lesterchan plugin, and every one of them has
 * been broken by an ordinary edit at some point. Catching them here is cheaper
 * than catching them in the release pre-flight.
 *
 * @package WP-ShowHide
 */

/**
 * Covers the release invariants held in the source files.
 */
class Test_ShowHide_Metadata extends WP_UnitTestCase {

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Absolute path to the readme.
	 *
	 * @var string
	 */
	protected $readme_file;

	public function set_up() {
		parent::set_up();

		$this->plugin_file = dirname( __DIR__ ) . '/wp-showhide.php';
		$this->readme_file = dirname( __DIR__ ) . '/README.md';
	}

	/**
	 * A field from the main plugin file's header docblock.
	 *
	 * @param string $field Field name.
	 * @return string
	 */
	protected function header( $field ) {
		$data = get_file_data( $this->plugin_file, array( $field => $field ) );

		return $data[ $field ];
	}

	/**
	 * A field from the readme's header block.
	 *
	 * @param string $field Field name.
	 * @return string
	 */
	protected function readme( $field ) {
		preg_match(
			'/^' . preg_quote( $field, '/' ) . ':\s*(.+?)\s*$/mi',
			file_get_contents( $this->readme_file ),
			$matches
		);

		return isset( $matches[1] ) ? $matches[1] : '';
	}

	public function test_readme_stable_tag_matches_the_plugin_version() {
		$this->assertSame( $this->header( 'Version' ), $this->readme( 'Stable tag' ) );
	}

	public function test_version_constant_matches_the_plugin_header() {
		$this->assertTrue( defined( 'WP_SHOWHIDE_VERSION' ) );
		$this->assertSame( $this->header( 'Version' ), WP_SHOWHIDE_VERSION );
	}

	public function test_main_file_constant_points_at_the_plugin() {
		$this->assertTrue( defined( 'WP_SHOWHIDE_MAIN_FILE' ) );
		$this->assertSame( realpath( $this->plugin_file ), realpath( WP_SHOWHIDE_MAIN_FILE ) );
	}

	public function test_a_changelog_section_exists_for_this_version() {
		$this->assertMatchesRegularExpression(
			'/^### ' . preg_quote( $this->header( 'Version' ), '/' ) . '\s*$/m',
			file_get_contents( $this->readme_file )
		);
	}

	/**
	 * Bare versions only -- "### Version 2.1.0" breaks the readme parser's
	 * changelog rendering on WordPress.org.
	 */
	public function test_every_changelog_heading_is_a_bare_version() {
		preg_match_all( '/^### (.+)$/m', file_get_contents( $this->readme_file ), $matches );

		$changelog = array_slice(
			$matches[1],
			array_search( $this->header( 'Version' ), $matches[1], true )
		);

		foreach ( $changelog as $heading ) {
			$this->assertMatchesRegularExpression( '/^\d+\.\d+/', $heading, $heading . ' is not a bare version.' );
		}
	}

	public function test_version_floors_agree_across_both_files() {
		$this->assertSame( '6.0', $this->header( 'Requires at least' ) );
		$this->assertSame( '6.0', $this->readme( 'Requires at least' ) );

		$this->assertSame( '7.4', $this->header( 'Requires PHP' ) );
		$this->assertSame( '7.4', $this->readme( 'Requires PHP' ) );
	}

	public function test_canonical_plugin_header_field_order() {
		preg_match( '#^<\?php\s*/\*\*(.+?)\*/#s', file_get_contents( $this->plugin_file ), $matches );

		preg_match_all( '/^\s*\*\s*([A-Z][A-Za-z ]+):/m', $matches[1], $fields );

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

	public function test_canonical_readme_header_field_order() {
		preg_match_all(
			'/^([A-Z][A-Za-z ]+):/m',
			substr( file_get_contents( $this->readme_file ), 0, 600 ),
			$fields
		);

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

	/**
	 * Markdown needs two trailing spaces to keep a line break.
	 *
	 * Without them the header collapses into a paragraph, which is how
	 * "License: GPLv2 or later" ended up rendered on the same line as the
	 * License URI below it. Only the last field is exempt: it is followed by a
	 * blank line, which breaks the line on its own.
	 */
	public function test_every_readme_header_line_keeps_its_line_break() {
		$lines = explode( "\n", file_get_contents( $this->readme_file ) );

		// The block runs from under the "# WP-ShowHide" heading to the first
		// blank line.
		$header = array();

		foreach ( array_slice( $lines, 1 ) as $line ) {
			if ( '' === trim( $line ) ) {
				break;
			}

			$header[] = $line;
		}

		$this->assertCount( 9, $header, 'the readme header should hold nine fields' );

		$last = array_pop( $header );

		foreach ( $header as $line ) {
			$this->assertStringEndsWith(
				'  ',
				$line,
				'"' . trim( $line ) . '" needs two trailing spaces or it runs into the next field.'
			);
		}

		$this->assertSame( rtrim( $last ), $last, 'the last field needs no trailing spaces.' );
	}

	public function test_canonical_lesterchan_urls() {
		$this->assertSame( 'https://lesterchan.net/portfolio/programming/php/', $this->header( 'Plugin URI' ) );
		$this->assertSame( 'https://lesterchan.net', $this->header( 'Author URI' ) );
		$this->assertSame( 'https://lesterchan.net/site/donation/', $this->readme( 'Donate link' ) );
		$this->assertSame( 'https://www.gnu.org/licenses/gpl-2.0.html', $this->header( 'License URI' ) );
	}

	public function test_text_domain_is_the_plugin_slug() {
		$this->assertSame( 'wp-showhide', $this->header( 'Text Domain' ) );
		$this->assertSame( '/languages', $this->header( 'Domain Path' ) );
	}

	public function test_readme_lists_at_most_five_tags() {
		$this->assertLessThanOrEqual( 5, count( explode( ',', $this->readme( 'Tags' ) ) ) );
	}

	/**
	 * Code spans are excepted: the usage examples quote URLs verbatim.
	 */
	public function test_no_insecure_links_in_the_readme() {
		$readme = preg_replace( '/`[^`]*`/', '', file_get_contents( $this->readme_file ) );

		$this->assertStringNotContainsString( 'http://', $readme );
	}

	public function test_no_insecure_links_in_the_plugin_file() {
		$this->assertStringNotContainsString( 'http://', file_get_contents( $this->plugin_file ) );
	}

	/**
	 * translate.wordpress.org builds the catalogue; a checked-in one only ever
	 * goes stale.
	 */
	public function test_no_translation_catalogue_ships() {
		$root = dirname( __DIR__ );

		$this->assertDirectoryDoesNotExist( $root . '/languages' );

		// One glob per extension: GLOB_BRACE is a GNU extension and is not
		// defined in every PHP build, including the wp-env container's.
		foreach ( array( 'pot', 'po', 'mo' ) as $extension ) {
			$this->assertSame( array(), (array) glob( $root . '/*.' . $extension ) );
		}

		$this->assertDoesNotMatchRegularExpression( '/^### Translations/m', file_get_contents( $this->readme_file ) );
	}

	public function test_travis_is_gone_and_a_license_ships() {
		$this->assertFileDoesNotExist( dirname( __DIR__ ) . '/.travis.yml' );
		$this->assertFileExists( dirname( __DIR__ ) . '/LICENSE' );
		$this->assertStringContainsString(
			'GNU GENERAL PUBLIC LICENSE',
			file_get_contents( dirname( __DIR__ ) . '/LICENSE' )
		);
	}
}
