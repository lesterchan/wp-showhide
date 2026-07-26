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
class ShowHide {

	/**
	 * Handle shared by the script and the style.
	 */
	const HANDLE = 'wp-showhide';

	/**
	 * Sole instance.
	 *
	 * @var ShowHide|null
	 */
	private static $instance = null;

	/**
	 * Retrieve, creating on first call.
	 *
	 * @return ShowHide
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
		wp_register_script( self::HANDLE, false, array(), WP_SHOWHIDE_VERSION, true );
		wp_add_inline_script( self::HANDLE, ShowHide_Template::script() );

		wp_register_style( self::HANDLE, false, array(), WP_SHOWHIDE_VERSION );
		wp_enqueue_style( self::HANDLE );
		wp_add_inline_style( self::HANDLE, ShowHide_Template::style() );
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
		wp_enqueue_script( self::HANDLE );

		return ShowHide_Template::render( $atts, $content );
	}
}
