<?php
/**
 * Tests for the block.
 *
 * @package WP-ShowHide
 */

/**
 * The block, and the promise that it is an addition rather than a replacement.
 *
 * Most of what is worth asserting here is not "the block renders" -- that is
 * one line -- but the four things a later change could quietly break:
 *
 * * the shortcode still works, because it sits in published posts everywhere;
 * * the block wraps its content in the *same* markup the shortcode wraps its
 *   content in, because they are meant to share one renderer and nothing else
 *   checks that they still do;
 * * neither entry point is implemented in terms of the other, which is what
 *   stops the shortcode's attribute parsing leaking into the block;
 * * rendering the block enqueues the toggle script. The script is registered in
 *   the footer and enqueued only by whatever rendered a toggle, so a block that
 *   forgets produces perfect markup and a button that does nothing -- the one
 *   failure here that no markup assertion can see.
 */
class WP_ShowHide_Blocks_Test extends WP_ShowHide_TestCase {

	/**
	 * The block this plugin registers.
	 *
	 * @var string
	 */
	const BLOCK = 'wp-showhide/showhide';

	/**
	 * The shortcode table as it stood before a test edited it.
	 *
	 * @var array
	 */
	private $shortcodes;

	/**
	 * Snapshots the global state these tests deliberately break.
	 *
	 * Two tests below unregister the shortcode or the block on purpose, to
	 * prove neither entry point is implemented in terms of the other. Both
	 * registries are process-global and WP_UnitTestCase restores neither, so
	 * without this the first such test silently disarms every test that runs
	 * after it -- and they fail with `[showhide]` rendering as literal text,
	 * which reads as a broken shortcode rather than a leaky fixture.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->shortcodes = $GLOBALS['shortcode_tags'];

		$this->restore_block();
	}

	/**
	 * Puts both registries back.
	 *
	 * @return void
	 */
	public function tear_down() {
		$GLOBALS['shortcode_tags'] = $this->shortcodes;

		$this->restore_block();

		parent::tear_down();
	}

	/**
	 * Returns the block registry to exactly the one registered block.
	 *
	 * Unregisters before registering rather than registering conditionally: the
	 * plugin has already registered it on `init` by the time any test runs, and
	 * registering a second time is a doing_it_wrong notice the suite fails on.
	 *
	 * @return void
	 */
	private function restore_block() {
		if ( WP_Block_Type_Registry::get_instance()->is_registered( self::BLOCK ) ) {
			unregister_block_type( self::BLOCK );
		}

		WP_ShowHide_Blocks::register();
	}

	/**
	 * Drop the occurrence counter out of the element ids.
	 *
	 * The renderer numbers repeat uses of a type within one post so the
	 * document never carries a duplicate id, and a test that renders the block
	 * and the shortcode is two such uses -- so the second gets `-2` on both ids
	 * and on aria-controls. That difference is the counter working, not the two
	 * entry points disagreeing, and it is the only difference this strips: an
	 * unsuffixed id is left exactly as it is.
	 *
	 * @param string $html Rendered markup.
	 * @return string The same markup with any occurrence suffix removed.
	 */
	private function without_the_occurrence_counter( $html ) {
		return preg_replace( '/-(link|content)-(\d+)-\d+/', '-$1-$2', $html );
	}

	/**
	 * Render the block the way WordPress renders it, wrapper markup and all.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner content, as the inner blocks would render.
	 * @return string
	 */
	private function render_block( $attributes = array(), $content = '' ) {
		return WP_ShowHide_Blocks::render( $attributes, $content );
	}

	// --- registration ----------------------------------------------------

	/**
	 * The block registers, under the prefixed name.
	 *
	 * The `wp-` prefix is deliberate and is the one place the naming rule for
	 * commands and namespaces does not carry: those drop it, because a
	 * collision there is survivable and visible. A block name is written into
	 * post_content and stays there for the life of the post, so a collision
	 * would render another plugin's block inside somebody's published posts.
	 *
	 * @return void
	 */
	public function test_the_block_registers_under_the_prefixed_name() {
		$registry = WP_Block_Type_Registry::get_instance();

		$this->assertTrue( $registry->is_registered( self::BLOCK ), 'The block registers.' );
		$this->assertFalse( $registry->is_registered( 'showhide/showhide' ), 'The unprefixed name is not also claimed.' );
	}

	/**
	 * The block is dynamic, so it carries a render callback.
	 *
	 * Without one the saved markup would be all a reader ever sees, and the
	 * whole reason a shortcode and a block can share a renderer is that the
	 * wrapper is decided at render time for both.
	 *
	 * @return void
	 */
	public function test_the_block_is_dynamic() {
		$this->assertIsCallable(
			WP_Block_Type_Registry::get_instance()->get_registered( self::BLOCK )->render_callback,
			'The block renders server-side.'
		);
	}

	/**
	 * The attributes come from block.json rather than from PHP.
	 *
	 * @return void
	 */
	public function test_the_block_declares_its_attributes() {
		$attributes = WP_Block_Type_Registry::get_instance()->get_registered( self::BLOCK )->attributes;

		$this->assertArrayHasKey( 'type', $attributes, 'The block takes a type.' );
		$this->assertArrayHasKey( 'moreText', $attributes, 'And a closed label.' );
		$this->assertArrayHasKey( 'lessText', $attributes, 'And an open label.' );
		$this->assertArrayHasKey( 'hidden', $attributes, 'And whether it starts hidden.' );

		// The shortcode's is the prose somebody typed, read with a case
		// insensitive comparison. A block has a checkbox, so it has a boolean.
		$this->assertSame( 'boolean', $attributes['hidden']['type'], 'Hidden arrives typed, unlike a shortcode attribute.' );
		$this->assertTrue( $attributes['hidden']['default'], 'And it starts hidden, which is what an attributeless shortcode does.' );
	}

	// --- the shortcode survives -------------------------------------------

	/**
	 * Adding the block did not unregister the shortcode.
	 *
	 * If this ever fails, the block has stopped being an addition and become a
	 * replacement, and every published post holding `[showhide]` renders
	 * literal text.
	 *
	 * @return void
	 */
	public function test_the_shortcode_is_still_registered() {
		$this->assertTrue( shortcode_exists( 'showhide' ), 'The shortcode survives the block.' );
	}

	// --- the block and the shortcode agree ---------------------------------

	/**
	 * The block wraps its content in the markup the shortcode wraps content in.
	 *
	 * This is the assertion the whole design rests on. Two entry points that
	 * merely both work can drift; two that produce identical markup for
	 * identical content are demonstrably going through one renderer.
	 *
	 * @return void
	 */
	public function test_the_block_wraps_its_content_the_way_the_shortcode_does() {
		$content = 'The quick brown fox jumps over the lazy dog.';

		$block     = $this->render_block( array(), $content );
		$shortcode = $this->render( '[showhide]' . $content . '[/showhide]' );

		$this->assertNotSame( '', $block, 'The block rendered something.' );
		$this->assertSame(
			$this->without_the_occurrence_counter( $shortcode ),
			$this->without_the_occurrence_counter( $block ),
			'The block wraps its content in what the shortcode wraps its content in.'
		);
	}

	/**
	 * The same holds for every attribute the block offers.
	 *
	 * Each one has to survive the translation from a block attribute to the
	 * attribute the renderer takes, and `hidden` is the one that changes shape
	 * on the way: a boolean here, `yes`/`no` there.
	 *
	 * @return array<string, array{0: array, 1: string}>
	 */
	public function attribute_pairs() {
		return array(
			'a type'          => array(
				array( 'type' => 'spoiler' ),
				'[showhide type="spoiler"]',
			),
			'starting open'   => array(
				array( 'hidden' => false ),
				'[showhide hidden="no"]',
			),
			'starting closed' => array(
				array( 'hidden' => true ),
				'[showhide hidden="yes"]',
			),
			'both labels'     => array(
				array(
					'moreText' => 'Read the rest',
					'lessText' => 'That is enough',
				),
				'[showhide more_text="Read the rest" less_text="That is enough"]',
			),
			'everything'      => array(
				array(
					'type'     => 'links',
					'moreText' => 'Show links (%s)',
					'lessText' => 'Hide links (%s)',
					'hidden'   => false,
				),
				'[showhide type="links" more_text="Show links (%s)" less_text="Hide links (%s)" hidden="no"]',
			),
		);
	}

	/**
	 * @dataProvider attribute_pairs
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $opening    The equivalent opening shortcode tag.
	 */
	public function test_the_block_and_the_shortcode_agree_on( $attributes, $opening ) {
		$content = 'Four words of content';

		$block     = $this->render_block( $attributes, $content );
		$shortcode = $this->render( $opening . $content . '[/showhide]' );

		$this->assertSame(
			$this->without_the_occurrence_counter( $shortcode ),
			$this->without_the_occurrence_counter( $block ),
			'The block and the shortcode render the same markup.'
		);
	}

	/**
	 * An empty label means the default label, not an empty button.
	 *
	 * The defaults are translated strings, so they cannot live in block.json.
	 * A blank field therefore drops the attribute rather than passing an empty
	 * string through, and this is what pins that it does.
	 *
	 * @return void
	 */
	public function test_an_empty_label_falls_back_to_the_default() {
		$rendered = $this->render_block(
			array(
				'moreText' => '',
				'lessText' => '',
			),
			'one two three'
		);

		$this->assertStringContainsString( 'Show Press Release (3 More Words)', $rendered, 'The closed label is the default one.' );
		$this->assertStringContainsString( 'Hide Press Release (3 Less Words)', $rendered, 'And so is the open one.' );
	}

	/**
	 * A block and a shortcode of the same type in one post get distinct ids.
	 *
	 * The counter that does this lives in the shared renderer, so it counts
	 * both entry points as occurrences of the same type. If it did not, a post
	 * holding one of each would carry two elements with the same id and the
	 * first button would open the second block's content.
	 *
	 * @return void
	 */
	public function test_a_block_and_a_shortcode_in_one_post_do_not_share_an_id() {
		$markup = $this->render_block( array(), 'From the block' )
			. $this->render( '[showhide]From the shortcode[/showhide]' );

		$xpath = wp_showhide_test_xpath( $markup );
		$nodes = $xpath->query( '//div[contains(@class, "sh-content")]' );

		$this->assertSame( 2, $nodes->length, 'Both entry points rendered.' );
		$this->assertNotSame(
			$nodes->item( 0 )->getAttribute( 'id' ),
			$nodes->item( 1 )->getAttribute( 'id' ),
			'The two occurrences are numbered apart.'
		);
	}

	// --- neither is implemented in terms of the other ---------------------

	/**
	 * The block does not render by running the shortcode.
	 *
	 * Routing a block through do_shortcode() would make it inherit shortcode
	 * parsing it has no way to produce, and would break it outright the day
	 * anybody unregistered the shortcode. So: unregister the shortcode, and
	 * assert the block carries on rendering.
	 *
	 * @return void
	 */
	public function test_the_block_renders_with_the_shortcode_unregistered() {
		remove_shortcode( 'showhide' );

		$rendered = $this->render_block( array(), 'Still here' );

		$this->assertStringContainsString( 'sh-toggle', $rendered, 'The block does not need the shortcode.' );
		$this->assertStringContainsString( 'Still here', $rendered, 'And it still carries its content.' );
	}

	/**
	 * The shortcode does not render by running the block.
	 *
	 * The other direction of the same rule, and the one a later "tidy-up" is
	 * likelier to break, because making the shortcode a thin wrapper over the
	 * block reads as removing duplication.
	 *
	 * @return void
	 */
	public function test_the_shortcode_renders_with_the_block_unregistered() {
		unregister_block_type( self::BLOCK );

		$this->assertStringContainsString( 'sh-toggle', $this->render( '[showhide]Body[/showhide]' ), 'The shortcode does not need the block.' );
	}

	/**
	 * The block's own file never calls do_shortcode().
	 *
	 * The renderer it shares with the shortcode does, on the content it is
	 * given, and that is the shared behaviour rather than one entry point
	 * calling the other. What this rules out is the shortcut: building a
	 * `[showhide]…[/showhide]` string in the block callback and handing it to
	 * the parser.
	 *
	 * @return void
	 */
	public function test_the_block_does_not_render_through_the_shortcode_parser() {
		$code = php_strip_whitespace( dirname( __DIR__ ) . '/includes/class-wp-showhide-blocks.php' );

		$this->assertStringNotContainsString( 'do_shortcode', $code, 'The block does not reach for the shortcode parser.' );
		$this->assertStringNotContainsString( '[showhide', $code, 'Nor does it build shortcode text to hand to it.' );
	}

	// --- the script the toggle needs ---------------------------------------

	/**
	 * Rendering the block enqueues the toggle script.
	 *
	 * The script is registered in the footer and enqueued by whatever rendered
	 * a toggle, so that a page using neither entry point pays for nothing. A
	 * block that skipped this would render markup that looks right and a button
	 * that does nothing at all, which no assertion about markup can see.
	 *
	 * @return void
	 */
	public function test_rendering_the_block_enqueues_the_toggle_script() {
		do_action( 'wp_enqueue_scripts' );

		$this->assertFalse( wp_script_is( WP_SHOWHIDE_SLUG, 'enqueued' ), 'Registering the assets does not enqueue the script on its own.' );

		$this->render_block( array(), 'Body' );

		$this->assertTrue( wp_script_is( WP_SHOWHIDE_SLUG, 'enqueued' ), 'The block enqueues the script the toggle needs.' );
	}

	/**
	 * And the shortcode still does, which is the same line for the same reason.
	 *
	 * Asserted beside the block rather than only in the asset tests, because
	 * the two are a pair: whichever of them is edited, this file is where the
	 * other one is checked.
	 *
	 * @return void
	 */
	public function test_rendering_the_shortcode_enqueues_the_toggle_script_too() {
		do_action( 'wp_enqueue_scripts' );

		$this->render( '[showhide]Body[/showhide]' );

		$this->assertTrue( wp_script_is( WP_SHOWHIDE_SLUG, 'enqueued' ), 'The shortcode enqueues the script the toggle needs.' );
	}

	// --- rendering through the block parser -------------------------------

	/**
	 * A post holding the block comment renders the toggle around its content.
	 *
	 * The tests above call the callback directly, which does not prove the
	 * registration wired it to the name that gets saved into post_content, and
	 * it does not prove the inner blocks reach the callback at all. This goes
	 * through do_blocks(), the way a published post does.
	 *
	 * @return void
	 */
	public function test_a_saved_block_renders_through_the_block_parser() {
		$rendered = do_blocks(
			'<!-- wp:' . self::BLOCK . ' -->'
			. '<!-- wp:paragraph --><p>Hidden paragraph</p><!-- /wp:paragraph -->'
			. '<!-- /wp:' . self::BLOCK . ' -->'
		);

		$this->assertStringContainsString( 'sh-toggle', $rendered, 'The saved block renders its toggle.' );

		// Inside the content element rather than merely somewhere in the
		// output: a wrapper that dropped its content, or appended it after the
		// closing div, would pass a bare assertStringContainsString().
		$xpath = wp_showhide_test_xpath( $rendered );
		$nodes = $xpath->query( '//div[contains(@class, "sh-content")]//p' );

		$this->assertSame( 1, $nodes->length, 'The inner block is inside the content element.' );
		$this->assertSame( 'Hidden paragraph', $nodes->item( 0 )->textContent, 'And it is the paragraph that was written.' );
	}

	/**
	 * The saved block's attributes reach the render callback.
	 *
	 * @return void
	 */
	public function test_a_saved_block_carries_its_attributes_through() {
		$rendered = do_blocks(
			'<!-- wp:' . self::BLOCK . ' {"type":"spoiler","hidden":false} -->'
			. '<!-- wp:paragraph --><p>The ending</p><!-- /wp:paragraph -->'
			. '<!-- /wp:' . self::BLOCK . ' -->'
		);

		$this->assertStringContainsString( 'spoiler-link', $rendered, 'The type names the classes.' );
		$this->assertStringContainsString( 'aria-expanded="true"', $rendered, 'And hidden:false publishes it open.' );
		$this->assertStringNotContainsString( ' hidden>', $rendered, 'So the content element carries no hidden attribute.' );
	}

	/**
	 * An empty block renders the toggle over nothing rather than fatalling.
	 *
	 * A writer can delete every inner block and leave the wrapper, and the word
	 * count is of a string that is then empty. The shortcode has the same edge
	 * -- a self-closing `[showhide]` passes null -- and it is the reason the
	 * renderer casts before counting.
	 *
	 * @return void
	 */
	public function test_a_block_with_no_content_still_renders() {
		$rendered = $this->render_block();

		$this->assertStringContainsString( 'sh-toggle', $rendered, 'The toggle renders.' );
		$this->assertStringContainsString( '(0 More Words)', $rendered, 'And it counts nothing as nothing.' );
	}
}
