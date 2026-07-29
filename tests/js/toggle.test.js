/**
 * The delegated toggle handler.
 *
 * This is the half of the plugin PHPUnit cannot reach: everything below runs
 * the shipped js/wp-showhide.js against a real DOM.
 */

import { describe, expect, test } from 'vitest';
import { block, page, toggleScript } from './helper-dom.js';

describe( 'the shipped script', () => {
	test( 'is the file the plugin enqueues, not a copy', () => {
		const js = toggleScript();

		expect( js ).toMatch( /addEventListener\( 'click'/ );
		expect( js ).toMatch( /sh-toggle/ );
		expect( js ).not.toMatch( /jQuery|\$\(/ );
	} );

	test( 'declares nothing globally', () => {
		const { window } = page( block() );

		// The whole file is one IIFE, so the page gains no new global.
		expect( window.button ).toBeUndefined();
		expect( window.toggle ).toBeUndefined();
	} );
} );

describe( 'toggling a block', () => {
	test( 'a collapsed block opens on click', () => {
		const { state, click } = page( block() );

		expect( state()[ 0 ] ).toEqual( {
			id: 'pressrelease-link-1',
			expanded: 'false',
			label: 'Show Press Release (4 More Words)',
			hidden: true,
			linkClass: 'wp-showhide sh-link pressrelease-link sh-hide',
			contentClass: 'wp-showhide sh-content pressrelease-content sh-hide',
		} );

		click( '.sh-toggle' );

		expect( state()[ 0 ] ).toEqual( {
			id: 'pressrelease-link-1',
			expanded: 'true',
			label: 'Hide Press Release (4 Less Words)',
			hidden: false,
			linkClass: 'wp-showhide sh-link pressrelease-link sh-show',
			contentClass: 'wp-showhide sh-content pressrelease-content sh-show',
		} );
	} );

	test( 'a block that starts open closes on click', () => {
		const { state, click } = page( block( { expanded: true } ) );

		expect( state()[ 0 ].expanded ).toBe( 'true' );
		expect( state()[ 0 ].hidden ).toBe( false );

		click( '.sh-toggle' );

		expect( state()[ 0 ].expanded ).toBe( 'false' );
		expect( state()[ 0 ].hidden ).toBe( true );
		expect( state()[ 0 ].label ).toBe( 'Show Press Release (4 More Words)' );
		expect( state()[ 0 ].linkClass ).toMatch( /sh-hide/ );
	} );

	test( 'two clicks return the block to exactly where it started', () => {
		const { state, click } = page( block() );

		const before = state();

		click( '.sh-toggle' );
		click( '.sh-toggle' );

		expect( state() ).toEqual( before );
	} );

	test( 'blocks toggle independently of one another', () => {
		const { state, click } = page(
			block( { type: 'alpha', id: 7 } ) + block( { type: 'beta', id: 7 } ),
		);

		click( '#alpha-link-7 .sh-toggle' );

		const [ alpha, beta ] = state();

		expect( alpha.expanded ).toBe( 'true' );
		expect( beta.expanded ).toBe( 'false' );
		expect( beta.hidden ).toBe( true );
	} );

	test( 'sh-show and sh-hide stay mutually exclusive on both elements', () => {
		const { state, click } = page( block() );

		for ( let i = 0; i < 3; i++ ) {
			click( '.sh-toggle' );

			const { linkClass, contentClass } = state()[ 0 ];

			for ( const className of [ linkClass, contentClass ] ) {
				expect( className.includes( 'sh-show' ) ).toBe(
					! className.includes( 'sh-hide' ),
				);
			}

			expect( linkClass.includes( 'sh-show' ) ).toBe(
				contentClass.includes( 'sh-show' ),
			);
		}
	} );
} );

describe( 'event delegation', () => {
	test( 'the three custom events fire on .sh-link and bubble', () => {
		const { recordEvents, click } = page( block() );

		const events = recordEvents();

		click( '.sh-toggle' );
		expect( events ).toEqual( [
			'sh-link:more:pressrelease-link-1',
			'sh-link:toggle:pressrelease-link-1',
		] );

		click( '.sh-toggle' );
		expect( events.slice( 2 ) ).toEqual( [
			'sh-link:less:pressrelease-link-1',
			'sh-link:toggle:pressrelease-link-1',
		] );
	} );

	test( 'clicking inside the button still toggles, via delegation', () => {
		const { document, state, click } = page( block() );

		document.querySelector( '.sh-toggle' ).innerHTML =
			'<span class="inner">Show</span>';

		click( '.inner' );

		expect( state()[ 0 ].expanded ).toBe( 'true' );
	} );

	test( 'clicking anything that is not a toggle changes nothing', () => {
		const { state, click } = page(
			'<p id="elsewhere">Not a toggle</p>' + block(),
		);

		const before = state();

		click( '#elsewhere' );
		click( '.sh-content' );

		expect( state() ).toEqual( before );
	} );

	test( 'a block added after the script ran still toggles', () => {
		const { document, state, click } = page( block() );

		// The listener is on the document, so markup that arrives later --
		// from a lazy-loaded archive, say -- needs no re-binding.
		document.body.insertAdjacentHTML(
			'beforeend',
			block( { type: 'late', id: 9 } ),
		);

		click( '#late-link-9 .sh-toggle' );

		expect( state()[ 1 ].expanded ).toBe( 'true' );
		expect( state()[ 1 ].hidden ).toBe( false );
	} );
} );

describe( 'markup the plugin did not render', () => {
	test( 'a toggle with no .sh-link wrapper is ignored rather than throwing', () => {
		const { document, state, click } = page(
			'<button type="button" class="sh-toggle" aria-expanded="false" ' +
				'aria-controls="orphan-content" data-sh-more="More" data-sh-less="Less">More</button>' +
				'<div id="orphan-content" class="sh-content" hidden>Content</div>',
		);

		click( '.sh-toggle' );

		expect(
			document
				.querySelector( '.sh-toggle' )
				.getAttribute( 'aria-expanded' ),
		).toBe( 'false' );
		expect( document.getElementById( 'orphan-content' ).hidden ).toBe(
			true,
		);
		expect( state() ).toEqual( [] );
	} );

	test( 'a toggle pointing at a missing content element is ignored rather than throwing', () => {
		const { document, click } = page(
			'<div id="x-link-1" class="sh-link sh-hide">' +
				'<button type="button" class="sh-toggle" aria-expanded="false" ' +
				'aria-controls="does-not-exist" data-sh-more="More" data-sh-less="Less">More</button>' +
				'</div>',
		);

		click( '.sh-toggle' );

		expect(
			document
				.querySelector( '.sh-toggle' )
				.getAttribute( 'aria-expanded' ),
		).toBe( 'false' );
		expect( document.querySelector( '.sh-link' ).className ).toBe(
			'sh-link sh-hide',
		);
	} );
} );

describe( 'the label', () => {
	test( 'is written as text, so a markup-shaped label cannot inject', () => {
		const payload = '<img src=x onerror=alert(1)>';
		const { document, click } = page( block( { less: payload } ) );

		click( '.sh-toggle' );

		const button = document.querySelector( '.sh-toggle' );

		expect( button.textContent ).toBe( payload );
		expect( document.querySelectorAll( 'img' ) ).toHaveLength( 0 );
		expect( button.innerHTML ).toContain( '&lt;img' );
	} );
} );
