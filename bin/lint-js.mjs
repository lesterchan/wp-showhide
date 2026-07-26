/**
 * Lint every piece of JavaScript in the repo, including the one with no file.
 *
 * The plugin's toggle script is a heredoc inside class-showhide-template.php
 * so that it can be printed inline, which means `eslint .` cannot see it. This
 * extracts it and lints the text as a virtual file, alongside the real .mjs
 * sources.
 *
 *   node bin/lint-js.mjs          # check
 *   node bin/lint-js.mjs --fix    # fix what is fixable in the real files
 *
 * The virtual file is never fixed in place: it does not exist on disk, and
 * rewriting a heredoc from a linter is a good way to corrupt PHP.
 */

import { ESLint } from 'eslint';
import { toggleScript } from '../tests/js/helper-dom.mjs';

const fix = process.argv.includes( '--fix' );
const eslint = new ESLint( { fix } );

const fileResults = await eslint.lintFiles( [ '.' ] );

if ( fix ) {
	await ESLint.outputFixes( fileResults );
}

// The filePath is what the config's `files` patterns match against; nothing is
// read from or written to it.
const scriptResults = await eslint.lintText( toggleScript() + '\n', {
	filePath: 'toggle.virtual.js',
} );

const results = [ ...fileResults, ...scriptResults ];
const formatter = await eslint.loadFormatter( 'stylish' );
const output = await formatter.format( results );

if ( output ) {
	process.stdout.write( output + '\n' );
}

const errors = results.reduce( ( total, result ) => total + result.errorCount, 0 );
const warnings = results.reduce( ( total, result ) => total + result.warningCount, 0 );

if ( errors > 0 || warnings > 0 ) {
	process.exitCode = 1;
} else {
	process.stdout.write( `Linted ${ results.length } files, no problems.\n` );
}
