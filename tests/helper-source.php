<?php
/**
 * Source-inspection helpers shared by the test cases.
 *
 * @package WP-ShowHide
 */

/**
 * Every shipped PHP source file, root and includes/.
 *
 * Built from two glob() calls rather than one GLOB_BRACE pattern: GLOB_BRACE is
 * a GNU extension and is not defined in every PHP build, including the one in
 * the wp-env container.
 *
 * @return string[] Absolute paths.
 */
function showhide_test_source_files() {
	$root = dirname( __DIR__ );

	return array_merge(
		(array) glob( $root . '/*.php' ),
		(array) glob( $root . '/includes/*.php' )
	);
}

/**
 * Every shipped PHP source file concatenated, with all comments removed.
 *
 * Comments must not be searchable: these files *document* the symbols they no
 * longer call, so "does the plugin still call load_plugin_textdomain()?"
 * matches the changelog note explaining that it does not, and a test asserting
 * on the raw text fails for the wrong reason -- or worse, passes for one.
 *
 * @param string[] $skip Basenames to leave out.
 * @return string
 */
function showhide_test_source_code( array $skip = array() ) {
	$code = '';

	foreach ( showhide_test_source_files() as $file ) {
		if ( in_array( basename( $file ), $skip, true ) ) {
			continue;
		}

		$code .= php_strip_whitespace( $file );
	}

	return $code;
}

/**
 * Parse a fragment of the plugin's markup into a queryable DOM.
 *
 * The shortcode returns two sibling divs with no common parent, which is not a
 * well-formed document, so they are wrapped before loading. libxml is told to
 * stay quiet about the missing doctype rather than raising warnings the test
 * suite would convert into failures.
 *
 * @param string $html Markup fragment.
 * @return DOMXPath
 */
function showhide_test_xpath( $html ) {
	$doc = new DOMDocument();

	// The wrapper carries no id of its own: tests count the ids in the
	// fragment, and one contributed by the harness would be counted too.
	$previous = libxml_use_internal_errors( true );
	$doc->loadHTML(
		'<?xml encoding="UTF-8"><div class="showhide-test-root">' . $html . '</div>'
	);
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	return new DOMXPath( $doc );
}

/**
 * Read one attribute off the first element matching an XPath expression.
 *
 * @param string $html      Markup fragment.
 * @param string $query     XPath expression.
 * @param string $attribute Attribute name.
 * @return string|null The value, or null when the element or attribute is absent.
 */
function showhide_test_attr( $html, $query, $attribute ) {
	$nodes = showhide_test_xpath( $html )->query( $query );

	if ( ! $nodes || 0 === $nodes->length ) {
		return null;
	}

	$node = $nodes->item( 0 );

	return $node->hasAttribute( $attribute ) ? $node->getAttribute( $attribute ) : null;
}
