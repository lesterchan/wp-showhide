<?php
/**
 * The [showhide] shortcode's rendered markup.
 *
 * This is the plugin's entire public contract: themes style these classes and
 * IDs, so every assertion here is a promise to somebody's stylesheet.
 *
 * @package WP-ShowHide
 */

/**
 * Covers the rendered markup of the [showhide] shortcode.
 */
class Test_ShowHide_Shortcode extends WP_UnitTestCase {

	/**
	 * Post the shortcode renders against.
	 *
	 * @var int
	 */
	protected $post_id;

	public function set_up() {
		parent::set_up();

		$this->post_id   = self::factory()->post->create();
		$GLOBALS['post'] = get_post( $this->post_id );
	}

	public function tear_down() {
		unset( $GLOBALS['post'] );

		parent::tear_down();
	}

	/**
	 * Render a shortcode string through the full shortcode pipeline.
	 *
	 * Goes through do_shortcode() rather than calling the callback directly,
	 * so the attribute parsing and nesting behaviour is exercised too.
	 *
	 * @param string $shortcode Shortcode text.
	 * @return string Rendered markup.
	 */
	protected function render( $shortcode ) {
		return do_shortcode( $shortcode );
	}

	public function test_shortcode_is_registered() {
		$this->assertTrue( shortcode_exists( 'showhide' ) );
	}

	public function test_default_render_is_hidden() {
		$html = $this->render( '[showhide]Hello world[/showhide]' );

		$this->assertSame( 'false', showhide_test_attr( $html, '//button', 'aria-expanded' ) );
		$this->assertNotNull( showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'hidden' ) );
		$this->assertStringContainsString( 'sh-hide', showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ) );
		$this->assertStringContainsString( 'sh-hide', showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'class' ) );
	}

	public function test_hidden_no_renders_expanded() {
		$html = $this->render( '[showhide hidden="no"]Hello world[/showhide]' );

		$this->assertSame( 'true', showhide_test_attr( $html, '//button', 'aria-expanded' ) );
		$this->assertNull( showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'hidden' ) );
		$this->assertStringContainsString( 'sh-show', showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ) );
		$this->assertStringContainsString( 'sh-show', showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'class' ) );
	}

	/**
	 * Spellings of "no" a post author will reasonably type.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function spellings_of_no() {
		return array(
			'lower case' => array( 'no' ),
			'title case' => array( 'No' ),
			'upper case' => array( 'NO' ),
			'padded'     => array( ' no ' ),
		);
	}

	/**
	 * @dataProvider spellings_of_no
	 *
	 * @param string $hidden The hidden attribute value.
	 */
	public function test_hidden_no_is_recognised_whatever_its_casing( $hidden ) {
		$html = $this->render( '[showhide hidden="' . $hidden . '"]Hello world[/showhide]' );

		$this->assertSame( 'true', showhide_test_attr( $html, '//button', 'aria-expanded' ) );
	}

	/**
	 * Only "no" opens the block. Everything else -- including the default
	 * "yes", a typo, and an empty value -- leaves it collapsed.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function spellings_that_are_not_no() {
		return array(
			'yes'   => array( 'yes' ),
			'empty' => array( '' ),
			'nope'  => array( 'nope' ),
			'false' => array( 'false' ),
			'zero'  => array( '0' ),
		);
	}

	/**
	 * @dataProvider spellings_that_are_not_no
	 *
	 * @param string $hidden The hidden attribute value.
	 */
	public function test_anything_other_than_no_stays_hidden( $hidden ) {
		$html = $this->render( '[showhide hidden="' . $hidden . '"]Hello world[/showhide]' );

		$this->assertSame( 'false', showhide_test_attr( $html, '//button', 'aria-expanded' ) );
	}

	/**
	 * Expanded blocks open on the "less" label, collapsed ones on "more".
	 */
	public function test_initial_label_matches_the_initial_state() {
		$collapsed = $this->render( '[showhide]one two[/showhide]' );
		$expanded  = $this->render( '[showhide hidden="no"]one two[/showhide]' );

		$this->assertStringContainsString( 'Show Press Release (2 More Words)', $collapsed );
		$this->assertStringContainsString( 'Hide Press Release (2 Less Words)', $expanded );
	}

	public function test_default_classes_and_ids_use_the_default_type() {
		$html = $this->render( '[showhide]Hello world[/showhide]' );

		$this->assertSame( 'pressrelease-link-' . $this->post_id, showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ) );
		$this->assertSame( 'pressrelease-content-' . $this->post_id, showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'id' ) );
		$this->assertStringContainsString( 'pressrelease-link', showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ) );
		$this->assertStringContainsString( 'pressrelease-content', showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'class' ) );
	}

	public function test_custom_type_drives_the_classes_and_ids() {
		$html = $this->render( '[showhide type="links"]Hello world[/showhide]' );

		$this->assertSame( 'links-link-' . $this->post_id, showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ) );
		$this->assertSame( 'links-content-' . $this->post_id, showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'id' ) );
	}

	/**
	 * The type lands in an HTML id, so anything that would break the id -- or
	 * escape the attribute -- is stripped rather than escaped.
	 *
	 * Quotes are absent from these fixtures on purpose: WordPress's own
	 * shortcode attribute regex will not match an attribute containing the
	 * quote character that delimits it, so a quoted payload never reaches the
	 * plugin at all. Angle brackets, ampersands and spaces do.
	 */
	public function test_type_is_restricted_to_id_safe_characters() {
		$html = $this->render( '[showhide type="a b&c<d>e_f-g"]Hello world[/showhide]' );

		$this->assertSame( 'abcde_f-g-link-' . $this->post_id, showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ) );
	}

	public function test_type_stripped_to_nothing_falls_back_to_the_default() {
		$html = $this->render( '[showhide type="<<>>"]Hello world[/showhide]' );

		$this->assertSame( 'pressrelease-link-' . $this->post_id, showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ) );
	}

	/**
	 * The button drives the content through aria-controls, so the two must
	 * agree or the toggle silently does nothing.
	 */
	public function test_aria_controls_points_at_the_content_element() {
		$html = $this->render( '[showhide type="links"]Hello world[/showhide]' );

		$this->assertSame(
			showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'id' ),
			showhide_test_attr( $html, '//button', 'aria-controls' )
		);
	}

	/**
	 * Everything the toggle script reads off the markup.
	 *
	 * The JavaScript suite builds its fixtures from this same shape, so this
	 * test is the join between the two halves: drop an attribute here and the
	 * PHP side fails, change what the script expects and the JS side fails.
	 */
	public function test_markup_carries_every_hook_the_script_reads() {
		$html  = $this->render( '[showhide]one two[/showhide]' );
		$xpath = showhide_test_xpath( $html );

		// The script delegates off .sh-toggle and walks up to .sh-link.
		$this->assertSame( 1, $xpath->query( '//button[contains(@class,"sh-toggle")]' )->length );
		$this->assertSame( 1, $xpath->query( '//div[contains(@class,"sh-link")]//button[contains(@class,"sh-toggle")]' )->length );

		// It reads these four attributes off the button, by name.
		foreach ( array( 'aria-expanded', 'aria-controls', 'data-sh-more', 'data-sh-less' ) as $attribute ) {
			$this->assertNotNull(
				showhide_test_attr( $html, '//button', $attribute ),
				$attribute . ' is what the toggle script reads; it cannot be dropped.'
			);
		}

		// And resolves aria-controls with getElementById.
		$this->assertSame(
			1,
			$xpath->query( '//div[@id="' . showhide_test_attr( $html, '//button', 'aria-controls' ) . '"]' )->length
		);

		// It flips exactly one of these two classes on each element.
		$this->assertStringContainsString( 'sh-hide', showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ) );
		$this->assertStringNotContainsString( 'sh-show', showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ) );
	}

	public function test_toggle_is_a_button_and_not_a_link() {
		$html = $this->render( '[showhide]Hello world[/showhide]' );

		$this->assertSame( 'button', showhide_test_attr( $html, '//button', 'type' ) );
		$this->assertSame( 0, showhide_test_xpath( $html )->query( '//a' )->length );
	}

	public function test_word_count_counts_every_run_of_whitespace() {
		// Newlines and tabs, not just spaces: multi-paragraph content used to
		// be counted as a single word per paragraph.
		$html = $this->render( "[showhide]one two\nthree\tfour\n\nfive[/showhide]" );

		$this->assertStringContainsString( '(5 More Words)', $html );
	}

	public function test_word_count_ignores_markup() {
		$html = $this->render( '[showhide]<p><strong>one</strong> two</p>[/showhide]' );

		$this->assertStringContainsString( '(2 More Words)', $html );
	}

	/**
	 * wp_strip_all_tags() removes script and style *contents*, not just their
	 * tags, so the counter must not be inflated by the code inside them.
	 */
	public function test_word_count_ignores_script_and_style_contents() {
		$html = $this->render( '[showhide]<style>a { color: red; }</style><script>var a = 1;</script>one two[/showhide]' );

		$this->assertStringContainsString( '(2 More Words)', $html );
	}

	public function test_word_count_is_localised() {
		$html = $this->render( '[showhide]' . implode( ' ', array_fill( 0, 1500, 'word' ) ) . '[/showhide]' );

		$this->assertStringContainsString( '(1,500 More Words)', $html );
	}

	public function test_custom_more_and_less_text() {
		$html = $this->render( '[showhide more_text="Open (%s)" less_text="Close (%s)"]one two[/showhide]' );

		$this->assertSame( 'Open (2)', showhide_test_attr( $html, '//button', 'data-sh-more' ) );
		$this->assertSame( 'Close (2)', showhide_test_attr( $html, '//button', 'data-sh-less' ) );
	}

	public function test_numbered_placeholder_is_substituted_too() {
		$html = $this->render( '[showhide more_text="Open (%1$s)"]one two[/showhide]' );

		$this->assertSame( 'Open (2)', showhide_test_attr( $html, '//button', 'data-sh-more' ) );
	}

	/**
	 * The label is user-supplied, so a stray specifier must not reach a
	 * printf-family function and blow up the page.
	 */
	public function test_extra_format_specifiers_do_not_fatal() {
		$html = $this->render( '[showhide more_text="Open (%s) %d %2$s %"]one two[/showhide]' );

		$this->assertSame( 'Open (2) %d %2$s %', showhide_test_attr( $html, '//button', 'data-sh-more' ) );
	}

	/**
	 * A backslash in the label used to survive into the markup differently on
	 * each side, so the button relabelled itself to something else on toggle.
	 */
	public function test_label_with_a_backslash_matches_on_both_sides() {
		$html = $this->render( '[showhide more_text="a\\\\b (%s)"]one two[/showhide]' );

		$this->assertSame(
			showhide_test_attr( $html, '//button', 'data-sh-more' ),
			trim( showhide_test_xpath( $html )->query( '//button' )->item( 0 )->textContent )
		);
	}

	public function test_nested_shortcodes_are_processed() {
		add_shortcode(
			'showhide_test_inner',
			static function () {
				return 'INNER';
			}
		);

		$html = $this->render( '[showhide]before [showhide_test_inner] after[/showhide]' );

		remove_shortcode( 'showhide_test_inner' );

		$this->assertStringContainsString( 'INNER', $html );
	}

	/**
	 * A post may use the same type twice; the first keeps its historical id and
	 * later ones are suffixed, so the document never carries a duplicate id.
	 */
	public function test_repeating_a_type_does_not_produce_duplicate_ids() {
		$html = $this->render(
			'[showhide]one two[/showhide][showhide]three four[/showhide][showhide]five six[/showhide]'
		);

		$ids = array();

		foreach ( showhide_test_xpath( $html )->query( '//div[@id]' ) as $node ) {
			$ids[] = $node->getAttribute( 'id' );
		}

		$this->assertCount( 6, $ids );
		$this->assertSame( $ids, array_unique( $ids ) );
		$this->assertContains( 'pressrelease-link-' . $this->post_id, $ids );
		$this->assertContains( 'pressrelease-link-' . $this->post_id . '-2', $ids );
		$this->assertContains( 'pressrelease-link-' . $this->post_id . '-3', $ids );
	}

	/**
	 * Different types never collided, so they must not gain a suffix.
	 */
	public function test_different_types_are_not_suffixed() {
		$html = $this->render( '[showhide type="alpha"]one[/showhide][showhide type="beta"]two[/showhide]' );

		$this->assertStringContainsString( 'alpha-link-' . $this->post_id . '"', $html );
		$this->assertStringContainsString( 'beta-link-' . $this->post_id . '"', $html );
	}

	/**
	 * Rendering outside the loop has no post, which used to emit a broken
	 * element id. Zero is not pretty but it is valid and stable.
	 */
	public function test_rendering_outside_the_loop_still_produces_valid_ids() {
		unset( $GLOBALS['post'] );

		$html = $this->render( '[showhide type="outside"]one two[/showhide]' );

		$this->assertSame( 'outside-link-0', showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ) );
		$this->assertSame( 'outside-content-0', showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'id' ) );
	}

	public function test_empty_content_renders_a_zero_word_count() {
		$html = $this->render( '[showhide][/showhide]' );

		$this->assertStringContainsString( '(0 More Words)', $html );
	}

	/**
	 * A self-closing tag has no content at all. WordPress hands the callback
	 * an empty string for it rather than null, but the plugin must not depend
	 * on that: the suite converts deprecations into failures, so a null
	 * reaching a string parameter would fail here on PHP 8.1+.
	 */
	public function test_self_closing_shortcode_does_not_warn() {
		$html = $this->render( '[showhide type="selfclosing" /]' );

		$this->assertStringContainsString( '(0 More Words)', $html );
		$this->assertSame( 'selfclosing-content-' . $this->post_id, showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'id' ) );
	}
}
