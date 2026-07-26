/**
 * WordPress JS coding standards for WP-ShowHide.
 *
 * "recommended-with-formatting" uses native ESLint formatting rules rather
 * than delegating to Prettier, so no Prettier install is needed.
 *
 * The plugin's own script has no file of its own -- it is a heredoc inside
 * includes/class-showhide-template.php, printed inline so it costs no HTTP
 * request. bin/lint-js.mjs extracts it and lints it as a virtual browser file;
 * the block below is what that virtual file matches.
 *
 * Excluded from the SVN deploy, so this never ships to users.
 */
import wordpress from '@wordpress/eslint-plugin';
import globals from 'globals';

export default [
	{
		ignores: [ '**/node_modules/**', '**/vendor/**' ],
	},
	...wordpress.configs[ 'recommended-with-formatting' ],
	{
		settings: {
			react: { version: '18.0' },
		},
	},
	{
		// The extracted toggle script. Runs in the browser, and predates any
		// build step, so it stays ES5-compatible on purpose.
		files: [ '**/toggle.virtual.js' ],
		languageOptions: {
			sourceType: 'script',
			globals: {
				...globals.browser,
			},
		},
	},
	{
		// The test suite and this file: Node, ESM.
		files: [ 'tests/js/**/*.mjs', 'bin/*.mjs', 'eslint.config.mjs' ],
		languageOptions: {
			sourceType: 'module',
			globals: {
				...globals.node,
			},
		},
	},
];
