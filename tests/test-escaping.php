<?php
/**
 * Escaping of the attacker-reachable shortcode attributes.
 *
 * Every value here comes out of post content, which contributors can author,
 * so all four attributes are treated as hostile.
 *
 * @package WP-ShowHide
 */

/**
 * Covers escaping of the shortcode attributes.
 */
class WP_ShowHide_Escaping_Test extends WP_ShowHide_TestCase {

	/**
	 * Payloads that reach the plugin through a double-quoted attribute.
	 *
	 * A payload containing the delimiting quote is deliberately absent:
	 * WordPress's own shortcode attribute regex is `"([^"]*)"`, so such a
	 * shortcode never matches and the plugin is never called. Testing it here
	 * would assert on core's parser, not on this plugin, and would pass even
	 * if the plugin escaped nothing at all.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function hostile_labels() {
		return array(
			'tag'          => array( '<img src=x onerror=alert(1)>' ),
			'closing tag'  => array( '</button><script>alert(1)</script>' ),
			'entity quote' => array( '&quot; onmouseover=&quot;alert(1)' ),
			'bare handler' => array( '<b onmouseover=alert(1)>hi</b>' ),
		);
	}

	/**
	 * @dataProvider hostile_labels
	 *
	 * @param string $label Hostile more_text value.
	 */
	public function test_more_text_cannot_break_out_of_its_attribute( $label ) {
		$html = do_shortcode( '[showhide more_text="' . $label . '"]one two[/showhide]' );

		$xpath = wp_showhide_test_xpath( $html );

		$this->assertSame( 0, $xpath->query( '//script' )->length );
		$this->assertSame( 0, $xpath->query( '//img' )->length );
		$this->assertSame( 1, $xpath->query( '//button' )->length );
		$this->assertNull( wp_showhide_test_attr( $html, '//button', 'onmouseover' ), 'The more text cannot break out and add an event handler to the button.' );

		// The payload survives as inert text, which is the point: it is a
		// label. Decoded, because esc_attr() does not double-encode, so an
		// entity the author wrote themselves is passed through and the parser
		// resolves it once on the way back out.
		$this->assertSame(
			html_entity_decode( $label, ENT_QUOTES, 'UTF-8' ),
			wp_showhide_test_attr( $html, '//button', 'data-sh-more' )
		);
	}

	/**
	 * @dataProvider hostile_labels
	 *
	 * @param string $label Hostile less_text value.
	 */
	public function test_less_text_cannot_break_out_of_its_attribute( $label ) {
		$html = do_shortcode( '[showhide less_text="' . $label . '"]one two[/showhide]' );

		$xpath = wp_showhide_test_xpath( $html );

		$this->assertSame( 0, $xpath->query( '//script' )->length );
		$this->assertSame( 0, $xpath->query( '//img' )->length );
		$this->assertSame( 1, $xpath->query( '//button' )->length );
		$this->assertSame(
			html_entity_decode( $label, ENT_QUOTES, 'UTF-8' ),
			wp_showhide_test_attr( $html, '//button', 'data-sh-less' )
		);
	}

	/**
	 * The type reaches an id, a class and nothing else, and is stripped rather
	 * than escaped -- so a hostile value leaves no trace at all.
	 */
	public function test_type_cannot_inject_markup_or_extra_attributes() {
		$html = do_shortcode( '[showhide type="x<img src=y> onclick=alert(1)"]one two[/showhide]' );

		$xpath = wp_showhide_test_xpath( $html );

		$this->assertSame( 0, $xpath->query( '//img' )->length );
		$this->assertNull( wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'onclick' ), 'The type cannot break out and add an event handler to the link.' );
		$this->assertSame( 'ximgsrcyonclickalert1-link-' . $this->post_id, wp_showhide_test_attr( $html, '//div[contains(@class,"sh-link")]', 'id' ) );
	}

	/**
	 * The button label and its data-* twin are read back by the toggle script
	 * and written with textContent, so they must decode to exactly the same
	 * string. An ampersand encoded once on one side and twice on the other is
	 * how that desyncs.
	 */
	public function test_label_decodes_identically_in_the_text_and_the_dataset() {
		$html = do_shortcode( '[showhide more_text="Tom &amp; Jerry (%s)"]one two[/showhide]' );

		$this->assertSame( 'Tom & Jerry (2)', wp_showhide_test_attr( $html, '//button', 'data-sh-more' ) );
		$this->assertSame(
			'Tom & Jerry (2)',
			trim( wp_showhide_test_xpath( $html )->query( '//button' )->item( 0 )->textContent )
		);
	}

	/**
	 * Content is passed through do_shortcode() and is otherwise the author's
	 * own HTML, exactly as WordPress would render it outside the shortcode.
	 * It is deliberately *not* escaped; this pins that it is also not mangled.
	 */
	public function test_content_html_is_preserved() {
		$html = do_shortcode( '[showhide]<p class="intro">Hello <em>world</em></p>[/showhide]' );

		$this->assertStringContainsString( '<p class="intro">Hello <em>world</em></p>', $html );
	}
}
