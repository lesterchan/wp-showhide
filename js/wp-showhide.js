/**
 * WP-ShowHide's front end toggle.
 *
 * One delegated listener, so blocks rendered after this file runs need no
 * re-bind. Everything the handler needs is on the markup: labels in
 * data-sh-more / data-sh-less, state in aria-expanded, target in aria-controls.
 */
( function() {
	'use strict';

	document.addEventListener( 'click', function( event ) {
		// closest() is missing on a non-element target, which a click on a
		// text node inside the button reports in some browsers.
		const button = event.target.closest?.( '.sh-toggle' );

		if ( ! button ) {
			return;
		}

		const wrap = button.closest( '.sh-link' );
		const content = document.getElementById(
			button.getAttribute( 'aria-controls' ),
		);
		const expanded = button.getAttribute( 'aria-expanded' ) === 'true';

		// Markup the plugin did not render, or content that has since been
		// removed: leave it alone rather than throwing.
		if ( ! wrap || ! content ) {
			return;
		}

		button.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
		button.textContent = expanded
			? button.dataset.shMore
			: button.dataset.shLess;
		content.hidden = expanded;

		[ wrap, content ].forEach( function( element ) {
			element.classList.toggle( 'sh-show', ! expanded );
			element.classList.toggle( 'sh-hide', expanded );
		} );

		// Fired on .sh-link and bubbling, so a theme can listen once on the
		// document. The names predate the plugin's own hook prefix and are
		// part of its public API, so they keep their spelling.
		[
			expanded ? 'sh-link:less' : 'sh-link:more',
			'sh-link:toggle',
		].forEach( function( name ) {
			wrap.dispatchEvent( new CustomEvent( name, { bubbles: true } ) );
		} );
	} );
}() );
