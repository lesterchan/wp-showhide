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
class WP_ShowHide_Shortcode_Test extends WP_ShowHide_TestCase {

	public function test_shortcode_is_registered() {
		$this->assertTrue( shortcode_exists( 'showhide' ), 'The showhide shortcode is registered.' );
	}

	public function test_default_render_is_hidden() {
		$html = $this->render( '[showhide]Hello world[/showhide]' );

		$this->assertSame( 'false', wp_showhide_test_attr( $html, '//button', 'aria-expanded' ), 'The content starts collapsed, and aria-expanded says so.' );
		$this->assertNotNull( wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'hidden' ), 'The content carries the hidden attribute by default.' );
		$this->assertStringContainsString( 'sh-hide', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ), 'The link element starts in the hidden state class.' );
		$this->assertStringContainsString( 'sh-hide', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'class' ), 'The content element starts in it too.' );
	}

	public function test_hidden_no_renders_expanded() {
		$html = $this->render( '[showhide hidden="no"]Hello world[/showhide]' );

		$this->assertSame( 'true', wp_showhide_test_attr( $html, '//button', 'aria-expanded' ), 'hidden=no starts expanded, and aria-expanded says so.' );
		$this->assertNull( wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'hidden' ), 'hidden=no renders the content expanded.' );
		$this->assertStringContainsString( 'sh-show', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ), 'The link element starts in the shown state class.' );
		$this->assertStringContainsString( 'sh-show', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'class' ), 'The content element starts in it too.' );
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

		$this->assertSame( 'true', wp_showhide_test_attr( $html, '//button', 'aria-expanded' ), 'The attribute is matched case insensitively.' );
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

		$this->assertSame( 'false', wp_showhide_test_attr( $html, '//button', 'aria-expanded' ), 'Only no expands; anything else leaves it collapsed.' );
	}

	/**
	 * Expanded blocks open on the "less" label, collapsed ones on "more".
	 */
	public function test_initial_label_matches_the_initial_state() {
		$collapsed = $this->render( '[showhide]one two[/showhide]' );
		$expanded  = $this->render( '[showhide hidden="no"]one two[/showhide]' );

		$this->assertStringContainsString( 'Show Press Release (2 More Words)', $collapsed, 'Collapsed, the button offers the expand label.' );
		$this->assertStringContainsString( 'Hide Press Release (2 Less Words)', $expanded, 'Expanded, it offers the collapse label.' );
	}

	/**
	 * Both elements carry the root class the stylesheet is scoped under. The
	 * shortcode returns two siblings rather than one wrapper, so there is no
	 * single outermost element for it to sit on alone.
	 */
	public function test_both_elements_carry_the_stylesheet_root_class() {
		$html = $this->render( '[showhide]Hello world[/showhide]' );

		$this->assertStringContainsString( 'wp-showhide', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ), 'The link element carries the stylesheet root class.' );
		$this->assertStringContainsString( 'wp-showhide', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'class' ), 'The content element carries it too.' );
	}

	public function test_default_classes_and_ids_use_the_default_type() {
		$html = $this->render( '[showhide]Hello world[/showhide]' );

		$this->assertSame( 'pressrelease-link-' . $this->post_id, wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ), 'With no type given the default names the link id.' );
		$this->assertSame( 'pressrelease-content-' . $this->post_id, wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'id' ), 'And the content id.' );
		$this->assertStringContainsString( 'pressrelease-link', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ), 'The default names the link class as well as the id.' );
		$this->assertStringContainsString( 'pressrelease-content', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'class' ), 'And the content class.' );
	}

	public function test_custom_type_drives_the_classes_and_ids() {
		$html = $this->render( '[showhide type="links"]Hello world[/showhide]' );

		$this->assertSame( 'links-link-' . $this->post_id, wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ), 'A custom type names the link id.' );
		$this->assertSame( 'links-content-' . $this->post_id, wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'id' ), 'And the content id.' );
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

		$this->assertSame( 'abcde_f-g-link-' . $this->post_id, wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ), 'Characters an id cannot carry are stripped rather than escaped.' );
	}

	public function test_type_stripped_to_nothing_falls_back_to_the_default() {
		$html = $this->render( '[showhide type="<<>>"]Hello world[/showhide]' );

		$this->assertSame( 'pressrelease-link-' . $this->post_id, wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ), 'A type stripped to nothing falls back to the default rather than an empty id.' );
	}

	/**
	 * The button drives the content through aria-controls, so the two must
	 * agree or the toggle silently does nothing.
	 */
	public function test_aria_controls_points_at_the_content_element() {
		$html = $this->render( '[showhide type="links"]Hello world[/showhide]' );

		$this->assertSame(
			wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'id' ),
			wp_showhide_test_attr( $html, '//button', 'aria-controls' ),
			'aria-controls names the content element, which is what makes the button operable.'
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
		$xpath = wp_showhide_test_xpath( $html );

		// The script delegates off .sh-toggle and walks up to .sh-link.
		$this->assertSame( 1, $xpath->query( '//button[contains(@class,"sh-toggle")]' )->length, 'Exactly one toggle button, which the script binds by class.' );
		$this->assertSame( 1, $xpath->query( '//div[contains(@class,"sh-link")]//button[contains(@class,"sh-toggle")]' )->length, 'The button is inside the link element, where the stylesheet expects it.' );

		// It reads these four attributes off the button, by name.
		foreach ( array( 'aria-expanded', 'aria-controls', 'data-sh-more', 'data-sh-less' ) as $attribute ) {
			$this->assertNotNull(
				wp_showhide_test_attr( $html, '//button', $attribute ),
				$attribute . ' is what the toggle script reads; it cannot be dropped.'
			);
		}

		// And resolves aria-controls with getElementById.
		$this->assertSame(
			1,
			$xpath->query( '//div[@id="' . wp_showhide_test_attr( $html, '//button', 'aria-controls' ) . '"]' )->length,
			'The element aria-controls names exists, so the reference resolves.'
		);

		// It flips exactly one of these two classes on each element.
		$this->assertStringContainsString( 'sh-hide', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ), 'The initial state class is on the element.' );
		$this->assertStringNotContainsString( 'sh-show', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'class' ), 'And only one of the pair, so the script has no ambiguity to resolve.' );
	}

	public function test_toggle_is_a_button_and_not_a_link() {
		$html = $this->render( '[showhide]Hello world[/showhide]' );

		$this->assertSame( 'button', wp_showhide_test_attr( $html, '//button', 'type' ), 'The toggle is type=button, so it never submits a surrounding form.' );
		$this->assertSame( 0, wp_showhide_test_xpath( $html )->query( '//a' )->length, 'It is not an anchor, which would navigate with JavaScript off.' );
	}

	public function test_word_count_counts_every_run_of_whitespace() {
		// Newlines and tabs, not just spaces: multi-paragraph content used to
		// be counted as a single word per paragraph.
		$html = $this->render( "[showhide]one two\nthree\tfour\n\nfive[/showhide]" );

		$this->assertStringContainsString( '(5 More Words)', $html, 'Runs of whitespace count as one separator, not several.' );
	}

	public function test_word_count_ignores_markup() {
		$html = $this->render( '[showhide]<p><strong>one</strong> two</p>[/showhide]' );

		$this->assertStringContainsString( '(2 More Words)', $html, 'Markup is not counted as words.' );
	}

	/**
	 * Script and style *contents* are removed by wp_strip_all_tags(), not just
	 * their tags, so the counter must not be inflated by the code inside them.
	 */
	public function test_word_count_ignores_script_and_style_contents() {
		$html = $this->render( '[showhide]<style>a { color: red; }</style><script>var a = 1;</script>one two[/showhide]' );

		$this->assertStringContainsString( '(2 More Words)', $html, 'Script and style contents are not counted either.' );
	}

	public function test_word_count_is_localised() {
		$html = $this->render( '[showhide]' . implode( ' ', array_fill( 0, 1500, 'word' ) ) . '[/showhide]' );

		$this->assertStringContainsString( '(1,500 More Words)', $html, 'The count is localised, so a large number reads as the site would write it.' );
	}

	public function test_custom_more_and_less_text() {
		$html = $this->render( '[showhide more_text="Open (%s)" less_text="Close (%s)"]one two[/showhide]' );

		$this->assertSame( 'Open (2)', wp_showhide_test_attr( $html, '//button', 'data-sh-more' ), 'A custom expand label is used, with the count substituted into it.' );
		$this->assertSame( 'Close (2)', wp_showhide_test_attr( $html, '//button', 'data-sh-less' ), 'A custom collapse label is used the same way.' );
	}

	public function test_numbered_placeholder_is_substituted_too() {
		$html = $this->render( '[showhide more_text="Open (%1$s)"]one two[/showhide]' );

		$this->assertSame( 'Open (2)', wp_showhide_test_attr( $html, '//button', 'data-sh-more' ), 'A numbered placeholder is substituted as well as a bare one.' );
	}

	/**
	 * The label is user-supplied, so a stray specifier must not reach a
	 * printf-family function and blow up the page.
	 */
	public function test_extra_format_specifiers_do_not_fatal() {
		$html = $this->render( '[showhide more_text="Open (%s) %d %2$s %"]one two[/showhide]' );

		$this->assertSame( 'Open (2) %d %2$s %', wp_showhide_test_attr( $html, '//button', 'data-sh-more' ), 'Specifiers the label supplies beyond the count are left as text rather than fatal.' );
	}

	/**
	 * A backslash in the label used to survive into the markup differently on
	 * each side, so the button relabelled itself to something else on toggle.
	 */
	public function test_label_with_a_backslash_matches_on_both_sides() {
		$html = $this->render( '[showhide more_text="a\\\\b (%s)"]one two[/showhide]' );

		$this->assertSame(
			wp_showhide_test_attr( $html, '//button', 'data-sh-more' ),
			trim( wp_showhide_test_xpath( $html )->query( '//button' )->item( 0 )->textContent ),
			'A backslash in the label survives identically in the dataset and the visible text.'
		);
	}

	public function test_nested_shortcodes_are_processed() {
		add_shortcode(
			'wp_showhide_test_inner',
			static function () {
				return 'INNER';
			}
		);

		$html = $this->render( '[showhide]before [wp_showhide_test_inner] after[/showhide]' );

		remove_shortcode( 'wp_showhide_test_inner' );

		$this->assertStringContainsString( 'INNER', $html, 'A shortcode inside the content is processed rather than printed.' );
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

		foreach ( wp_showhide_test_xpath( $html )->query( '//div[@id]' ) as $node ) {
			$ids[] = $node->getAttribute( 'id' );
		}

		$this->assertCount( 6, $ids, 'Three shortcodes produce two ids each, a link and a content panel.' );
		$this->assertSame( $ids, array_unique( $ids ), 'Repeating a type produces no duplicate id, which would break aria-controls.' );
		$this->assertContains( 'pressrelease-link-' . $this->post_id, $ids, 'The first instance takes the unsuffixed id.' );
		$this->assertContains( 'pressrelease-link-' . $this->post_id . '-2', $ids, 'The second is suffixed rather than colliding.' );
		$this->assertContains( 'pressrelease-link-' . $this->post_id . '-3', $ids, 'And the third continues the sequence.' );
	}

	/**
	 * Different types never collided, so they must not gain a suffix.
	 */
	public function test_different_types_are_not_suffixed() {
		$html = $this->render( '[showhide type="alpha"]one[/showhide][showhide type="beta"]two[/showhide]' );

		$this->assertStringContainsString( 'alpha-link-' . $this->post_id . '"', $html, 'A type used once takes the unsuffixed id.' );
		$this->assertStringContainsString( 'beta-link-' . $this->post_id . '"', $html, 'A different type is counted separately, so it is unsuffixed too.' );
	}

	/**
	 * Rendering outside the loop has no post, which used to emit a broken
	 * element id. Zero is not pretty but it is valid and stable.
	 */
	public function test_rendering_outside_the_loop_still_produces_valid_ids() {
		unset( $GLOBALS['post'] );

		$html = $this->render( '[showhide type="outside"]one two[/showhide]' );

		$this->assertSame( 'outside-link-0', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ), 'Outside the loop there is no post id, so the id falls back to zero rather than breaking.' );
		$this->assertSame( 'outside-content-0', wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'id' ), 'The content id falls back the same way, so the pair still match.' );
	}

	public function test_empty_content_renders_a_zero_word_count() {
		$html = $this->render( '[showhide][/showhide]' );

		$this->assertStringContainsString( '(0 More Words)', $html, 'Empty content counts zero words rather than warning.' );
	}

	/**
	 * A self-closing tag has no content at all. WordPress hands the callback
	 * an empty string for it rather than null, but the plugin must not depend
	 * on that: the suite converts deprecations into failures, so a null
	 * reaching a string parameter would fail here on PHP 8.1+.
	 */
	public function test_self_closing_shortcode_does_not_warn() {
		$html = $this->render( '[showhide type="selfclosing" /]' );

		$this->assertStringContainsString( '(0 More Words)', $html, 'A self-closing shortcode has no content, and counts zero without warning.' );
		$this->assertSame( 'selfclosing-content-' . $this->post_id, wp_showhide_test_attr( $html, '//div[contains(@class,"sh-content")]', 'id' ), 'It still renders both elements, so the markup is not half built.' );
	}
}
