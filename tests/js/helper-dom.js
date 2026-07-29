/**
 * Test harness for the toggle script.
 *
 * The script under test is read off disk from js/wp-showhide.js and run in a
 * real document, so these tests exercise the exact file the plugin ships.
 * There is no build step and no second copy to drift.
 */

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { JSDOM } from 'jsdom';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..', '..' );

/**
 * The toggle script, as shipped.
 *
 * @return {string} JavaScript source.
 */
export function toggleScript() {
	return readFileSync( join( root, 'js', 'wp-showhide.js' ), 'utf8' );
}

/**
 * Markup for one [showhide] block.
 *
 * Mirrors WP_ShowHide_Template::render(). The PHP suite pins that the real
 * output carries every attribute this depends on, so the two cannot silently
 * diverge without a test failing on one side or the other.
 *
 * @param {Object}  options          Block options.
 * @param {string}  options.type     Type attribute.
 * @param {number}  options.id       Post id.
 * @param {boolean} options.expanded Whether the block starts open.
 * @param {string}  options.more     Collapsed label.
 * @param {string}  options.less     Expanded label.
 * @param {string}  options.content  Inner content.
 * @return {string} HTML.
 */
export function block( {
	type = 'pressrelease',
	id = 1,
	expanded = false,
	more = 'Show Press Release (4 More Words)',
	less = 'Hide Press Release (4 Less Words)',
	content = 'Alpha beta gamma delta',
} = {} ) {
	const state = expanded ? 'sh-show' : 'sh-hide';
	const linkId = `${ type }-link-${ id }`;
	const contentId = `${ type }-content-${ id }`;
	const escape = ( value ) =>
		value
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( /"/g, '&quot;' );

	return (
		`<div id="${ linkId }" class="sh-link ${ type }-link ${ state }">` +
		`<button type="button" class="sh-toggle" aria-expanded="${ expanded }" ` +
		`aria-controls="${ contentId }" data-sh-more="${ escape( more ) }" ` +
		`data-sh-less="${ escape( less ) }">${ escape(
			expanded ? less : more,
		) }</button>` +
		'</div>' +
		`<div id="${ contentId }" class="sh-content ${ type }-content ${ state }"` +
		`${ expanded ? '' : ' hidden' }>${ content }</div>`
	);
}

/**
 * A fresh page with the toggle script already running.
 *
 * One document per test: the script attaches a single delegated listener to
 * document, so reusing a page would stack listeners and every click would fire
 * the handler more than once.
 *
 * @param {string} body Markup for the body.
 * @return {Object} The window, plus helpers bound to it.
 */
export function page( body ) {
	const dom = new JSDOM(
		`<!doctype html><html><body>${ body }<script>${ toggleScript() }</script></body></html>`,
		{ runScripts: 'dangerously' },
	);

	const { window } = dom;
	const { document } = window;

	/**
	 * The observable state of every block on the page.
	 *
	 * @return {Array<Object>} One entry per .sh-link, in document order.
	 */
	const state = () =>
		[ ...document.querySelectorAll( '.sh-link' ) ].map( ( wrap ) => {
			const button = wrap.querySelector( '.sh-toggle' );
			const content = document.getElementById(
				button.getAttribute( 'aria-controls' ),
			);

			return {
				id: wrap.id,
				expanded: button.getAttribute( 'aria-expanded' ),
				label: button.textContent,
				hidden: content ? content.hidden : null,
				linkClass: wrap.className,
				contentClass: content ? content.className : null,
			};
		} );

	/**
	 * Record every custom event the script dispatches.
	 *
	 * @return {Array<string>} Growing list of "name:targetId".
	 */
	const recordEvents = () => {
		const seen = [];

		for ( const name of [
			'sh-link:more',
			'sh-link:less',
			'sh-link:toggle',
		] ) {
			// Listening on document proves the events bubble, which themes
			// rely on.
			document.addEventListener( name, ( event ) =>
				seen.push( `${ name }:${ event.target.id }` ),
			);
		}

		return seen;
	};

	const click = ( selector ) =>
		document
			.querySelector( selector )
			.dispatchEvent(
				new window.MouseEvent( 'click', { bubbles: true } ),
			);

	return { window, document, state, recordEvents, click };
}
