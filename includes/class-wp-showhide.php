<?php
/**
 * WP-ShowHide bootstrap.
 *
 * @package WP-ShowHide
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's shortcode and front end assets up.
 */
class WP_ShowHide {

	/**
	 * Sole instance.
	 *
	 * @var WP_ShowHide|null
	 */
	private static $instance = null;

	/**
	 * Retrieve, creating on first call.
	 *
	 * @return WP_ShowHide
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_shortcode( 'showhide', array( $this, 'shortcode' ) );
	}

	/**
	 * Register the front end script and style.
	 *
	 * Both are registered without a src so that only the inline part is
	 * printed. The script is left for the shortcode to enqueue, so it reaches
	 * only the pages that actually use it. The style is enqueued here instead,
	 * unconditionally, so it lands in the head and the toggle never flashes as
	 * a native button before the CSS arrives.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_script( WP_SHOWHIDE_SLUG, false, array(), WP_SHOWHIDE_VERSION, true );
		wp_add_inline_script( WP_SHOWHIDE_SLUG, WP_ShowHide_Template::script() );

		wp_register_style( WP_SHOWHIDE_SLUG, false, array(), WP_SHOWHIDE_VERSION );
		wp_enqueue_style( WP_SHOWHIDE_SLUG );
		wp_add_inline_style( WP_SHOWHIDE_SLUG, WP_ShowHide_Template::style() );
	}

	/**
	 * Handle [showhide].
	 *
	 * @param array|string $atts    Shortcode attributes.
	 * @param string|null  $content The content to be shown or hidden.
	 * @return string The shortcode markup.
	 */
	public function shortcode( $atts, $content = null ) {
		// Only the pages that actually use the shortcode pay for the script.
		wp_enqueue_script( WP_SHOWHIDE_SLUG );

		return WP_ShowHide_Template::render( $atts, $content );
	}
}
