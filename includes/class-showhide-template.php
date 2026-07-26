<?php
/**
 * Markup and assets for WP-ShowHide.
 *
 * Everything a theme can see is built here: the two sibling elements the
 * shortcode returns, and the script and style that drive them.
 *
 * @package WP-ShowHide
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the shortcode's markup and the inline assets that go with it.
 */
class ShowHide_Template {

	/**
	 * How many times each type has been rendered for each post.
	 *
	 * A post may use the same type more than once. The first occurrence keeps
	 * the element id it has always had and later ones are suffixed, so the
	 * document never carries a duplicate id. Per request, like the render it
	 * is numbering.
	 *
	 * @var array<string, int>
	 */
	private static $instances = array();

	/**
	 * Render one [showhide] block.
	 *
	 * @param array|string $atts    Shortcode attributes. Accepts 'type',
	 *                              'more_text', 'less_text' and 'hidden'.
	 * @param string|null  $content The content to be shown or hidden.
	 * @return string The shortcode markup.
	 */
	public static function render( $atts, $content = null ) {
		$post_id = absint( get_the_ID() );

		// Cast before counting: a self-closing shortcode has no content, and
		// passing null to a string parameter is deprecated on PHP 8.1+.
		$content = (string) $content;

		$word_count = number_format_i18n(
			count( preg_split( '/\s+/', wp_strip_all_tags( $content ), -1, PREG_SPLIT_NO_EMPTY ) )
		);

		$attributes = shortcode_atts(
			array(
				'type'      => 'pressrelease',
				// translators: %s: Number of words in the hidden content.
				'more_text' => __( 'Show Press Release (%s More Words)', 'wp-showhide' ),
				// translators: %s: Number of words in the hidden content.
				'less_text' => __( 'Hide Press Release (%s Less Words)', 'wp-showhide' ),
				'hidden'    => 'yes',
			),
			$atts
		);

		$type = self::sanitize_type( $attributes['type'] );

		// More/less text, using str_replace() instead of sprintf() as the text
		// can be user supplied and a stray specifier must not be fatal.
		$more_text = str_replace( array( '%1$s', '%s' ), $word_count, $attributes['more_text'] );
		$less_text = str_replace( array( '%1$s', '%s' ), $word_count, $attributes['less_text'] );

		// The attribute is hand-typed prose, so the comparison is case
		// insensitive; hidden="No" used to quietly do the opposite.
		$expanded     = ( 'no' === strtolower( trim( $attributes['hidden'] ) ) );
		$hidden_class = $expanded ? 'sh-show' : 'sh-hide';

		$instance   = self::next_instance( $type, $post_id );
		$link_id    = $type . '-link-' . $post_id . $instance;
		$content_id = $type . '-content-' . $post_id . $instance;

		$output  = '<div id="' . esc_attr( $link_id ) . '" class="sh-link ' . esc_attr( $type ) . '-link ' . $hidden_class . '">';
		$output .= '<button type="button" class="sh-toggle" aria-expanded="' . ( $expanded ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $content_id ) . '" data-sh-more="' . esc_attr( $more_text ) . '" data-sh-less="' . esc_attr( $less_text ) . '">' . esc_html( $expanded ? $less_text : $more_text ) . '</button>';
		$output .= '</div>';
		$output .= '<div id="' . esc_attr( $content_id ) . '" class="sh-content ' . esc_attr( $type ) . '-content ' . $hidden_class . '"' . ( $expanded ? '' : ' hidden' ) . '>' . do_shortcode( $content ) . '</div>';

		return $output;
	}

	/**
	 * Reduce the type attribute to characters that are valid in an HTML id.
	 *
	 * Stripped rather than escaped, because the value lands in an id and a
	 * class where an escaped quote would still be wrong.
	 *
	 * @param string $type Raw type attribute.
	 * @return string A usable type, falling back to the default.
	 */
	private static function sanitize_type( $type ) {
		$type = preg_replace( '/[^A-Za-z0-9_\x{00A0}-\x{10FFFF}-]/u', '', $type );

		// preg_replace() returns null when the subject is not valid UTF-8.
		return ( null === $type || '' === $type ) ? 'pressrelease' : $type;
	}

	/**
	 * The id suffix for the next render of a type within a post.
	 *
	 * @param string $type    Sanitized type.
	 * @param int    $post_id Post being rendered.
	 * @return string Empty for the first occurrence, "-2", "-3" and so on after.
	 */
	private static function next_instance( $type, $post_id ) {
		$key = $type . '-' . $post_id;

		self::$instances[ $key ] = isset( self::$instances[ $key ] ) ? self::$instances[ $key ] + 1 : 1;

		return self::$instances[ $key ] > 1 ? '-' . self::$instances[ $key ] : '';
	}

	/**
	 * The front end JavaScript that toggles the content.
	 *
	 * @return string The JavaScript, without an enclosing script tag.
	 */
	public static function script() {
		return <<<'JS'
( function () {
	document.addEventListener( 'click', function ( e ) {
		var button = e.target.closest ? e.target.closest( '.sh-toggle' ) : null;
		if ( ! button ) {
			return;
		}

		var wrap = button.closest( '.sh-link' ),
			content = document.getElementById( button.getAttribute( 'aria-controls' ) ),
			expanded = button.getAttribute( 'aria-expanded' ) === 'true';

		if ( ! wrap || ! content ) {
			return;
		}

		button.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
		button.textContent = expanded ? button.dataset.shMore : button.dataset.shLess;
		content.hidden = expanded;

		[ wrap, content ].forEach( function ( el ) {
			el.classList.toggle( 'sh-show', ! expanded );
			el.classList.toggle( 'sh-hide', expanded );
		} );

		[ expanded ? 'sh-link:less' : 'sh-link:more', 'sh-link:toggle' ].forEach( function ( name ) {
			wrap.dispatchEvent( new CustomEvent( name, { bubbles: true } ) );
		} );
	} );
}() );
JS;
	}

	/**
	 * The front end CSS.
	 *
	 * Keeps the toggle looking like the text link it used to be, and raises
	 * the specificity of the hidden state above a theme that sets display on
	 * .sh-content.
	 *
	 * @return string The CSS, without an enclosing style tag.
	 */
	public static function style() {
		return '.sh-toggle{background:none;border:0;padding:0;margin:0;font:inherit;color:inherit;cursor:pointer;text-decoration:underline}.sh-content[hidden]{display:none}';
	}
}
