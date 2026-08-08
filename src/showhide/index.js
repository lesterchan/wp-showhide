/**
 * The `wp-showhide/showhide` block.
 *
 * `[showhide]` is an *enclosing* shortcode -- it wraps content rather than
 * standing in for it -- and that decides the whole shape of this file.
 *
 * A block wrapping a self-closing shortcode saves nothing and previews itself
 * with ServerSideRender. Doing that here would mean asking the writer to type
 * the hidden content into a textarea as raw markup, and would hand the editor a
 * preview it must not let anybody click. So instead the content is real inner
 * blocks: paragraphs, images, anything, edited natively and shown open, because
 * an editor that hid the content behind a button would be hiding the thing the
 * writer came to edit.
 *
 * That is why `save` returns `<InnerBlocks.Content />` and not null. It is the
 * only way the inner blocks reach post_content -- a `save` returning null
 * serialises the block self-closing and the children are simply lost. The block
 * is still dynamic: with a render callback registered, WordPress passes that
 * saved markup to it as the second argument, and the callback wraps it in the
 * same two sibling elements the shortcode wraps its content in.
 *
 * No wrapper element of the block's own, in `save` or in the callback. The
 * shortcode returns two siblings with no common parent, and one entry point
 * that nests its content one div deeper than the other is not the same markup.
 */

import { registerBlockType } from '@wordpress/blocks';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

/**
 * What a fresh block starts with: one empty paragraph to type into.
 *
 * Not locked and not an allow list -- the shortcode hides whatever markup it is
 * given, so the block hides whatever blocks it is given.
 */
const TEMPLATE = [ [ 'core/paragraph' ] ];

/**
 * The editor view.
 *
 * A named component with a capitalised name rather than an `edit()` shorthand
 * on the settings object: useBlockProps is a React hook, and the hook rules can
 * only tell a component from a plain function by that capital.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} The editor view.
 */
function Edit( { attributes, setAttributes } ) {
	const { type, moreText, lessText, hidden } = attributes;

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'wp-showhide-editor-content' },
		{ template: TEMPLATE },
	);

	// The button itself is not drawn here. Its label carries a word count of
	// the rendered content, which PHP does at render time -- drawing an
	// approximation of it in JavaScript would be a second implementation of the
	// one thing this block exists to have exactly one of. So the editor says
	// what will happen instead of pretending to be it.
	const note = hidden
		? __( 'Hidden until a reader opens it.', 'wp-showhide' )
		: __( 'Shown until a reader closes it.', 'wp-showhide' );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Show/Hide', 'wp-showhide' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Start hidden', 'wp-showhide' ) }
						help={ __(
							'Off publishes the block already open, the way hidden="no" does on the shortcode.',
							'wp-showhide',
						) }
						checked={ hidden }
						onChange={ ( value ) =>
							setAttributes( { hidden: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Type', 'wp-showhide' ) }
						help={ __(
							'Names the classes and element ids the markup carries, so a theme can style one kind of block differently from another.',
							'wp-showhide',
						) }
						value={ type }
						onChange={ ( value ) => setAttributes( { type: value } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Button text when closed', 'wp-showhide' ) }
						help={
							/* translators: %s is a literal placeholder in the label, replaced at render time with a word count. */
							__(
								'Leave empty for the default. %s becomes the number of words being hidden, which only the server can count.',
								'wp-showhide',
							)
						}
						value={ moreText }
						onChange={ ( value ) =>
							setAttributes( { moreText: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Button text when open', 'wp-showhide' ) }
						help={
							/* translators: %s is a literal placeholder in the label, replaced at render time with a word count. */
							__(
								'Leave empty for the default. %s becomes the number of words being hidden here too.',
								'wp-showhide',
							)
						}
						value={ lessText }
						onChange={ ( value ) =>
							setAttributes( { lessText: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<p className="wp-showhide-editor-note">{ note }</p>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,

	/**
	 * What lands in post_content.
	 *
	 * The inner blocks and nothing else: no wrapper, no classes, no attributes
	 * of this block's own. Everything the reader sees is added by the render
	 * callback, so changing the markup is a plugin update rather than a
	 * migration of every post that holds one of these.
	 *
	 * @return {Element} The saved content.
	 */
	save() {
		return <InnerBlocks.Content />;
	},
} );
