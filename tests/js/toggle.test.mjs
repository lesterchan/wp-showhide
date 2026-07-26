/**
 * The delegated toggle handler.
 *
 * This is the half of the plugin PHPUnit cannot reach: everything below runs
 * the shipped script against real DOM.
 */

import test from 'node:test';
import assert from 'node:assert/strict';
import { block, page, toggleScript } from './helper-dom.mjs';

test( 'the script is read out of the PHP source, not copied', () => {
	const js = toggleScript();

	assert.match( js, /addEventListener\( 'click'/ );
	assert.match( js, /sh-toggle/ );
	assert.ok( ! js.includes( 'jQuery' ) && ! js.includes( '$(' ), 'must not reach for jQuery' );
} );

test( 'a collapsed block opens on click', () => {
	const { state, click } = page( block() );

	assert.deepEqual( state()[ 0 ], {
		id: 'pressrelease-link-1',
		expanded: 'false',
		label: 'Show Press Release (4 More Words)',
		hidden: true,
		linkClass: 'sh-link pressrelease-link sh-hide',
		contentClass: 'sh-content pressrelease-content sh-hide',
	} );

	click( '.sh-toggle' );

	assert.deepEqual( state()[ 0 ], {
		id: 'pressrelease-link-1',
		expanded: 'true',
		label: 'Hide Press Release (4 Less Words)',
		hidden: false,
		linkClass: 'sh-link pressrelease-link sh-show',
		contentClass: 'sh-content pressrelease-content sh-show',
	} );
} );

test( 'a block that starts open closes on click', () => {
	const { state, click } = page( block( { expanded: true } ) );

	assert.equal( state()[ 0 ].expanded, 'true' );
	assert.equal( state()[ 0 ].hidden, false );

	click( '.sh-toggle' );

	assert.equal( state()[ 0 ].expanded, 'false' );
	assert.equal( state()[ 0 ].hidden, true );
	assert.equal( state()[ 0 ].label, 'Show Press Release (4 More Words)' );
	assert.match( state()[ 0 ].linkClass, /sh-hide/ );
} );

test( 'two clicks return the block to exactly where it started', () => {
	const { state, click } = page( block() );

	const before = JSON.stringify( state() );

	click( '.sh-toggle' );
	click( '.sh-toggle' );

	assert.equal( JSON.stringify( state() ), before );
} );

test( 'the three custom events fire on .sh-link and bubble', () => {
	const { recordEvents, click } = page( block() );

	const events = recordEvents();

	click( '.sh-toggle' );
	assert.deepEqual( events, [
		'sh-link:more:pressrelease-link-1',
		'sh-link:toggle:pressrelease-link-1',
	] );

	click( '.sh-toggle' );
	assert.deepEqual( events.slice( 2 ), [
		'sh-link:less:pressrelease-link-1',
		'sh-link:toggle:pressrelease-link-1',
	] );
} );

test( 'blocks toggle independently of one another', () => {
	const { state, click } = page(
		block( { type: 'alpha', id: 7 } ) + block( { type: 'beta', id: 7 } )
	);

	click( '#alpha-link-7 .sh-toggle' );

	const [ alpha, beta ] = state();

	assert.equal( alpha.expanded, 'true' );
	assert.equal( beta.expanded, 'false', 'the untouched block must not move' );
	assert.equal( beta.hidden, true );
} );

test( 'clicking inside the button still toggles, via delegation', () => {
	const { document, state, click } = page( block() );

	document.querySelector( '.sh-toggle' ).innerHTML = '<span class="inner">Show</span>';

	click( '.inner' );

	assert.equal( state()[ 0 ].expanded, 'true' );
} );

test( 'clicking anything that is not a toggle changes nothing', () => {
	const { state, click } = page( '<p id="elsewhere">Not a toggle</p>' + block() );

	const before = JSON.stringify( state() );

	click( '#elsewhere' );
	click( '.sh-content' );

	assert.equal( JSON.stringify( state() ), before );
} );

test( 'a toggle with no .sh-link wrapper is ignored rather than throwing', () => {
	const { document, state, click } = page(
		'<button type="button" class="sh-toggle" aria-expanded="false" ' +
		'aria-controls="orphan-content" data-sh-more="More" data-sh-less="Less">More</button>' +
		'<div id="orphan-content" class="sh-content" hidden>Content</div>'
	);

	click( '.sh-toggle' );

	assert.equal( document.querySelector( '.sh-toggle' ).getAttribute( 'aria-expanded' ), 'false' );
	assert.equal( document.getElementById( 'orphan-content' ).hidden, true );
	assert.deepEqual( state(), [], 'no .sh-link means nothing to report' );
} );

test( 'a toggle pointing at a missing content element is ignored rather than throwing', () => {
	const { document, click } = page(
		'<div id="x-link-1" class="sh-link sh-hide">' +
		'<button type="button" class="sh-toggle" aria-expanded="false" ' +
		'aria-controls="does-not-exist" data-sh-more="More" data-sh-less="Less">More</button>' +
		'</div>'
	);

	click( '.sh-toggle' );

	assert.equal( document.querySelector( '.sh-toggle' ).getAttribute( 'aria-expanded' ), 'false' );
	assert.equal( document.querySelector( '.sh-link' ).className, 'sh-link sh-hide' );
} );

test( 'the label is written as text, so a markup-shaped label cannot inject', () => {
	const payload = '<img src=x onerror=alert(1)>';
	const { document, click } = page( block( { less: payload } ) );

	click( '.sh-toggle' );

	const button = document.querySelector( '.sh-toggle' );

	assert.equal( button.textContent, payload, 'the label reads back as the literal string' );
	assert.equal( document.querySelectorAll( 'img' ).length, 0, 'and creates no element' );
	assert.ok( button.innerHTML.includes( '&lt;img' ), 'because textContent escaped it' );
} );

test( 'sh-show and sh-hide stay mutually exclusive on both elements', () => {
	const { state, click } = page( block() );

	for ( let i = 0; i < 3; i++ ) {
		click( '.sh-toggle' );

		const { linkClass, contentClass } = state()[ 0 ];

		for ( const className of [ linkClass, contentClass ] ) {
			const show = className.includes( 'sh-show' );
			const hide = className.includes( 'sh-hide' );

			assert.ok( show !== hide, `exactly one state class expected, got "${ className }"` );
		}

		assert.equal(
			linkClass.includes( 'sh-show' ),
			contentClass.includes( 'sh-show' ),
			'the wrapper and the content must agree'
		);
	}
} );
