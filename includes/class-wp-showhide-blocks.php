<?php
/**
 * Block registration.
 *
 * @package WP-ShowHide
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin's block and renders it.
 *
 * **The block is added beside the shortcode, never in place of it.**
 * `[showhide]` stays registered, documented and supported, and nothing here
 * deprecates it. This plugin has shipped for years and that shortcode sits in
 * an unknowable number of published posts; a block that replaced it would break
 * every one of those pages on update.
 *
 * **Neither entry point calls the other.** The block does not run
 * `do_shortcode()` on itself and the shortcode does not ask this class for
 * anything. They are siblings over WP_ShowHide_Template, which is where the
 * markup lives, and either one carries on working with the other unregistered.
 *
 * The block is dynamic *and* saves content, which is the unusual half of it and
 * follows from `[showhide]` being an enclosing shortcode. Its `save` returns
 * the inner blocks, so post_content holds the writer's paragraphs as ordinary
 * blocks -- editable, searchable, and readable by anything that reads block
 * markup. WordPress renders those first and hands the result to the callback
 * below as `$content`, which is the same string the shortcode callback receives
 * between its tags. Only the wrapper is decided at render time, so a change to
 * the markup reaches every published post without one of them being edited.
 */
class WP_ShowHide_Blocks {

	/**
	 * The block's directory under build/, which is also its name after the
	 * plugin prefix.
	 *
	 * @var string
	 */
	const BLOCK = 'showhide';

	/**
	 * Hooks block registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registers the block against its built metadata.
	 *
	 * A missing `build/` directory means the plugin was installed without its
	 * build step having run -- a git checkout rather than a release zip, since
	 * `bin/build` runs before the deploy copies anything. Registering a block
	 * whose script cannot be enqueued gives an editor that silently fails to
	 * load it, so this returns instead and leaves the shortcode working.
	 *
	 * The name, title, attributes and script handle all come out of the
	 * `block.json` the build copies there, so nothing here restates them.
	 *
	 * @return void
	 */
	public static function register() {
		$metadata = WP_SHOWHIDE_DIR . 'build/' . self::BLOCK;

		if ( ! file_exists( $metadata . '/block.json' ) ) {
			return;
		}

		register_block_type_from_metadata(
			$metadata,
			array( 'render_callback' => array( __CLASS__, 'render' ) )
		);
	}

	/**
	 * Renders the `wp-showhide/showhide` block.
	 *
	 * The second parameter is what makes an enclosing shortcode expressible as
	 * a block at all: WordPress renders the inner blocks and passes the markup
	 * in, so this wraps content it did not build, exactly as the shortcode
	 * callback does with the text between its tags.
	 *
	 * The enqueue is the shortcode callback's line, for the shortcode
	 * callback's reason: the script is registered in the footer and enqueued
	 * only by whatever actually rendered a toggle, so a page with neither a
	 * block nor a shortcode on it pays for nothing. Leaving it out is the
	 * failure this block is likeliest to ship with, because the markup looks
	 * perfect and the button does nothing.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    The rendered inner blocks.
	 * @return string The block markup.
	 */
	public static function render( $attributes, $content = '' ) {
		// Only the pages that actually use the block pay for the script.
		wp_enqueue_script( 'wp-showhide' );

		return WP_ShowHide_Template::render( self::shortcode_atts( $attributes ), $content );
	}

	/**
	 * Turns block attributes into the attributes the renderer takes.
	 *
	 * Two things need translating, and both are the block being a block rather
	 * than the two entry points disagreeing:
	 *
	 * * `hidden` is a real boolean here, where the shortcode's is the prose a
	 *   user typed and is read with a case-insensitive comparison. A block has
	 *   a checkbox, so it has a boolean, and this is where it becomes the
	 *   `yes`/`no` the renderer has always taken.
	 * * an empty label means *the default label*, not an empty button. The
	 *   defaults are translated strings a `block.json` cannot hold, so a blank
	 *   field drops the key entirely and lets `shortcode_atts()` supply the
	 *   same default the shortcode gets.
	 *
	 * @param array $attributes Block attributes, defaults already applied by
	 *                          the block type when WordPress is the caller.
	 * @return array Attributes for WP_ShowHide_Template::render().
	 */
	private static function shortcode_atts( $attributes ) {
		$attributes = wp_parse_args(
			(array) $attributes,
			array(
				'type'     => 'pressrelease',
				'moreText' => '',
				'lessText' => '',
				'hidden'   => true,
			)
		);

		$atts = array(
			'type'   => $attributes['type'],
			'hidden' => $attributes['hidden'] ? 'yes' : 'no',
		);

		if ( '' !== $attributes['moreText'] ) {
			$atts['more_text'] = $attributes['moreText'];
		}

		if ( '' !== $attributes['lessText'] ) {
			$atts['less_text'] = $attributes['lessText'];
		}

		return $atts;
	}
}
