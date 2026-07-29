<?php
/**
 * The plugin as a visitor actually meets it.
 *
 * The other cases call do_shortcode() directly. These go through the filter
 * chain and the script printer, which is where ordering bugs live.
 *
 * @package WP-ShowHide
 */

/**
 * Covers the plugin end to end through WordPress's own pipeline.
 */
class WP_ShowHide_Integration_Test extends WP_ShowHide_TestCase {

	/**
	 * Render post content the way a theme does.
	 *
	 * @param string $content Raw post content.
	 * @return string
	 */
	protected function the_content( $content ) {
		return apply_filters( 'the_content', $content );
	}

	/**
	 * Core runs wpautop at priority 10 and do_shortcode at 11, so the shortcode
	 * is paragraph-wrapped before it is expanded. The block still has to come out
	 * intact and addressable.
	 */
	public function test_block_survives_the_the_content_filter_chain() {
		do_action( 'wp_enqueue_scripts' );

		$html = $this->the_content( "Intro paragraph.\n\n[showhide]one two[/showhide]\n\nOutro paragraph." );

		$this->assertSame(
			'pressrelease-link-' . $this->post_id,
			wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' )
		);
		$this->assertSame(
			'pressrelease-content-' . $this->post_id,
			wp_showhide_test_attr( $html, '//button', 'aria-controls' )
		);
		$this->assertStringContainsString( 'Intro paragraph.', $html );
		$this->assertStringContainsString( 'Outro paragraph.', $html );
	}

	/**
	 * The script is only registered until a shortcode enqueues it, so if that
	 * step is missed the toggle is dead markup. This walks the whole path.
	 */
	public function test_the_toggle_handler_reaches_the_footer() {
		do_action( 'wp_enqueue_scripts' );

		$this->the_content( '[showhide]one two[/showhide]' );

		ob_start();
		wp_print_footer_scripts();
		$footer = ob_get_clean();

		$this->assertStringContainsString( 'js/wp-showhide.js', $footer );
		$this->assertStringNotContainsString( 'jquery', strtolower( $footer ) );
	}

	public function test_nothing_is_printed_for_a_page_without_the_shortcode() {
		do_action( 'wp_enqueue_scripts' );

		$this->the_content( 'Just an ordinary paragraph.' );

		ob_start();
		wp_print_footer_scripts();
		$footer = ob_get_clean();

		$this->assertStringNotContainsString( 'js/wp-showhide.js', $footer );
	}

	/**
	 * The style is the one asset that loads everywhere, so dequeueing it is
	 * the documented way for a theme to opt out. It replaced unhooking
	 * showhide_scripts() by name, which no longer works.
	 */
	public function test_the_style_can_be_dequeued_by_a_theme() {
		do_action( 'wp_enqueue_scripts' );

		$this->assertTrue( wp_style_is( 'wp-showhide', 'enqueued' ) );

		wp_dequeue_style( 'wp-showhide' );

		$this->assertFalse( wp_style_is( 'wp-showhide', 'enqueued' ) );

		ob_start();
		wp_print_styles();
		$styles = ob_get_clean();

		$this->assertStringNotContainsString( 'css/wp-showhide.css', $styles );
	}

	/**
	 * The assets hang off wp_enqueue_scripts alone, so the admin never pays
	 * for a front end toggle.
	 */
	public function test_assets_are_not_registered_in_the_admin() {
		$this->assertFalse( has_action( 'admin_enqueue_scripts', array( WP_ShowHide::get_instance(), 'register_assets' ) ) );
		$this->assertFalse( wp_script_is( 'wp-showhide', 'registered' ) );
		$this->assertFalse( wp_style_is( 'wp-showhide', 'registered' ) );
	}

	/**
	 * A [showhide] inside a [showhide] is legal, because the outer block runs
	 * do_shortcode() over its own content.
	 */
	public function test_nested_blocks_each_get_their_own_elements() {
		do_action( 'wp_enqueue_scripts' );

		$html = $this->the_content(
			'[showhide type="outer"]one two [showhide type="inner"]three four[/showhide][/showhide]'
		);

		$ids = array();

		foreach ( wp_showhide_test_xpath( $html )->query( '//div[@id]' ) as $node ) {
			$ids[] = $node->getAttribute( 'id' );
		}

		$this->assertContains( 'outer-link-' . $this->post_id, $ids );
		$this->assertContains( 'inner-link-' . $this->post_id, $ids );
		$this->assertSame( $ids, array_unique( $ids ) );
	}

	/**
	 * The instance counter is keyed by type and post, so two posts using the
	 * same type both keep the unsuffixed id they have always had.
	 */
	public function test_the_instance_counter_does_not_leak_between_posts() {
		$other_id = self::factory()->post->create();

		$first = $this->the_content( '[showhide type="shared"]one two[/showhide]' );

		$GLOBALS['post'] = get_post( $other_id );
		$second          = $this->the_content( '[showhide type="shared"]one two[/showhide]' );

		$this->assertSame( 'shared-link-' . $this->post_id, wp_showhide_test_attr( $first, '//div[contains(@class,"sh-link")]', 'id' ) );
		$this->assertSame( 'shared-link-' . $other_id, wp_showhide_test_attr( $second, '//div[contains(@class,"sh-link")]', 'id' ) );
	}

	/**
	 * A label with no placeholder in it is left exactly as written rather than
	 * having a count appended.
	 */
	public function test_a_label_without_a_placeholder_is_left_alone() {
		$html = $this->the_content( '[showhide more_text="Read on" less_text="Enough"]one two[/showhide]' );

		$this->assertSame( 'Read on', wp_showhide_test_attr( $html, '//button', 'data-sh-more' ) );
		$this->assertSame( 'Enough', wp_showhide_test_attr( $html, '//button', 'data-sh-less' ) );
	}
}
