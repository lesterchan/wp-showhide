/**
 * The [showhide] shortcode, in a browser.
 *
 * This plugin is one shortcode and one small script, and everything it does
 * happens after a click: the content is revealed, the button relabels itself,
 * and aria-expanded flips. None of that can be asserted by calling the renderer
 * -- what it returns is the closed state, which is the half that already worked.
 */

const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Publish a post whose content is the given markup, and open it.
 *
 * @param {Object}                          requestUtils The e2e request helper.
 * @param {import('@playwright/test').Page} page         Page under test.
 * @param {string}                          content      Post content.
 * @return {Promise<Object>} The created post.
 */
async function openPost( requestUtils, page, content ) {
	const post = await requestUtils.createPost( {
		title: `Show hide ${ Date.now().toString( 36 ) }`,
		content,
		status: 'publish',
	} );

	await page.goto( post.link );

	return post;
}

test.describe( 'The showhide shortcode', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
	} );

	test( 'starts closed, opens on click, and closes again', async ( { page, requestUtils } ) => {
		await openPost(
			requestUtils,
			page,
			'[showhide]The quick brown fox jumps over the lazy dog.[/showhide]'
		);

		const toggle = page.locator( '.sh-toggle' );
		const content = page.locator( '.sh-content' );

		await expect( toggle ).toBeVisible();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( content ).toBeHidden();

		await toggle.click();

		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( content ).toBeVisible();
		await expect( content ).toContainText( 'quick brown fox' );

		await toggle.click();

		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( content ).toBeHidden();
	} );

	test( 'the button says what it will do, and counts the words', async ( {
		page,
		requestUtils,
	} ) => {
		// Nine words, so the closed label says nine and the open one says the
		// same number back. The count is of the hidden content, which is the only
		// thing the reader cannot see for themselves.
		await openPost(
			requestUtils,
			page,
			'[showhide]The quick brown fox jumps over the lazy dog[/showhide]'
		);

		const toggle = page.locator( '.sh-toggle' );

		await expect( toggle ).toHaveText( 'Show Press Release (9 More Words)' );

		await toggle.click();

		await expect( toggle ).toHaveText( 'Hide Press Release (9 Less Words)' );

		await toggle.click();

		await expect( toggle ).toHaveText( 'Show Press Release (9 More Words)' );
	} );

	test( 'hidden="no" starts open', async ( { page, requestUtils } ) => {
		await openPost( requestUtils, page, '[showhide hidden="no"]Already showing[/showhide]' );

		const toggle = page.locator( '.sh-toggle' );

		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( page.locator( '.sh-content' ) ).toBeVisible();
		await expect( toggle ).toHaveText( /Hide Press Release/ );
	} );

	test( 'hidden="No" starts open too', async ( { page, requestUtils } ) => {
		// The attribute is hand-typed prose, so the comparison is case
		// insensitive. hidden="No" used to quietly do the opposite of hidden="no".
		await openPost( requestUtils, page, '[showhide hidden="No"]Capital N[/showhide]' );

		await expect( page.locator( '.sh-toggle' ) ).toHaveAttribute( 'aria-expanded', 'true' );
	} );

	test( 'custom labels are used, both ways round', async ( { page, requestUtils } ) => {
		await openPost(
			requestUtils,
			page,
			'[showhide more_text="Read the rest" less_text="That is enough"]Body[/showhide]'
		);

		const toggle = page.locator( '.sh-toggle' );

		await expect( toggle ).toHaveText( 'Read the rest' );

		await toggle.click();

		await expect( toggle ).toHaveText( 'That is enough' );
	} );

	test( 'the type attribute names the ids and classes', async ( { page, requestUtils } ) => {
		await openPost( requestUtils, page, '[showhide type="spoiler"]Hidden ending[/showhide]' );

		await expect( page.locator( '.spoiler-link' ) ).toHaveCount( 1 );
		await expect( page.locator( '.spoiler-content' ) ).toHaveCount( 1 );

		const controls = await page.locator( '.sh-toggle' ).getAttribute( 'aria-controls' );

		expect( controls ).toMatch( /^spoiler-content-\d+/ );
	} );

	test( 'two blocks on one page work independently', async ( { page, requestUtils } ) => {
		// Each instance needs its own ids, or the second button's aria-controls
		// points at the first block and opening one opens the other.
		await openPost(
			requestUtils,
			page,
			'[showhide]First body[/showhide][showhide]Second body[/showhide]'
		);

		const toggles = page.locator( '.sh-toggle' );
		const contents = page.locator( '.sh-content' );

		await expect( toggles ).toHaveCount( 2 );

		const ids = await contents.evaluateAll( ( nodes ) => nodes.map( ( node ) => node.id ) );
		expect( new Set( ids ).size ).toBe( 2 );

		await toggles.first().click();

		await expect( contents.first() ).toBeVisible();
		await expect( contents.last() ).toBeHidden();

		await toggles.last().click();

		await expect( contents.last() ).toBeVisible();
	} );

	test( 'the toggle is reachable and operable from the keyboard', async ( {
		page,
		requestUtils,
	} ) => {
		await openPost( requestUtils, page, '[showhide]Keyboard body[/showhide]' );

		const toggle = page.locator( '.sh-toggle' );

		// A real button, not a styled link with a click handler: it takes focus
		// in tab order and Enter and Space both activate it, for free.
		await toggle.focus();
		await expect( toggle ).toBeFocused();

		await page.keyboard.press( 'Enter' );
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );

		await page.keyboard.press( 'Space' );
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
	} );

	test( 'a shortcode inside the hidden content is expanded', async ( { page, requestUtils } ) => {
		await openPost(
			requestUtils,
			page,
			'[showhide]Nested: [showhide type="inner"]deep[/showhide][/showhide]'
		);

		await page.locator( '.sh-toggle' ).first().click();

		await expect( page.locator( '.inner-content' ) ).toHaveCount( 1 );
	} );

	test( 'the script is only loaded on a page that uses the shortcode', async ( {
		page,
		requestUtils,
	} ) => {
		const plain = await requestUtils.createPost( {
			title: `No shortcode ${ Date.now().toString( 36 ) }`,
			content: 'Nothing to toggle here.',
			status: 'publish',
		} );

		await page.goto( plain.link );

		// Only the pages that actually use the shortcode pay for the script.
		await expect( page.locator( 'script[src*="wp-showhide"]' ) ).toHaveCount( 0 );

		await openPost( requestUtils, page, '[showhide]Body[/showhide]' );

		await expect( page.locator( 'script[src*="wp-showhide"]' ) ).toHaveCount( 1 );
	} );
} );
