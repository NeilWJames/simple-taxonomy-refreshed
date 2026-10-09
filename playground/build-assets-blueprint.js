/**
 * Build the WordPress.org Live Preview blueprint (assets/blueprints/blueprint.json).
 *
 * Run with `npm run assets-blueprint` when the Live Preview should change.
 *
 * The Live Preview cannot load other files from the plugin's WordPress.org assets
 * folder, so the demo code is put inside the blueprint. The blueprint is made from:
 *  - playground/blueprint.json (the GitHub demo), with its plugin install step
 *    replaced by one that installs the released plugin from WordPress.org;
 *  - playground/demo-content.php, whose code replaces the step that loads it.
 *
 * The demo code then runs against the released plugin, so only build this blueprint
 * once the plugin code the demo needs has been released.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const ROOT = path.join( __dirname, '..' );
const SOURCE = path.join( __dirname, 'blueprint.json' );
const DEMO = path.join( __dirname, 'demo-content.php' );
const TARGET = path.join( ROOT, 'assets', 'blueprints', 'blueprint.json' );

// WordPress.org refuses blueprints larger than this.
const MAX_BYTES = 100 * 1024;

const blueprint = JSON.parse( fs.readFileSync( SOURCE, 'utf8' ) );

// The released plugin, from WordPress.org.
const install = blueprint.steps.findIndex(
	( step ) => 'installPlugin' === step.step
);
if ( -1 === install ) {
	throw new Error( 'playground/blueprint.json has no installPlugin step.' );
}
blueprint.steps[ install ] = {
	step: 'installPlugin',
	pluginData: {
		resource: 'wordpress.org/plugins',
		slug: 'simple-taxonomy-refreshed',
	},
	options: {
		activate: true,
	},
};

// The demo code itself, in place of loading playground/demo-content.php.
const load = blueprint.steps.findIndex(
	( step ) =>
		'runPHP' === step.step && step.code.includes( 'demo-content.php' )
);
if ( -1 === load ) {
	throw new Error(
		'playground/blueprint.json has no runPHP step that loads demo-content.php.'
	);
}
const demo = fs
	.readFileSync( DEMO, 'utf8' )
	.replace( /\r\n/g, '\n' )
	.replace( /^<\?php\s*/, '' );
blueprint.steps[ load ] = {
	step: 'runPHP',
	code:
		"<?php\nrequire '/wordpress/wp-load.php';\n\n" +
		demo.trimEnd() +
		'\n\nstaxo_demo_load();\n',
};

blueprint.meta = {
	...blueprint.meta,
	description:
		"The released plugin from WordPress.org, with five custom taxonomies, extra functions on Tags, sample posts and a page of the plugin's blocks. Built from the playground folder by npm run assets-blueprint.",
};

const output = JSON.stringify( blueprint, null, '\t' ) + '\n';
const bytes = Buffer.byteLength( output, 'utf8' );
if ( bytes > MAX_BYTES ) {
	throw new Error(
		`The blueprint is ${ bytes } bytes; WordPress.org accepts at most ${ MAX_BYTES }.`
	);
}

fs.mkdirSync( path.dirname( TARGET ), { recursive: true } );
fs.writeFileSync( TARGET, output );
// eslint-disable-next-line no-console
console.log(
	`Wrote ${ path.relative( ROOT, TARGET ) } (${ bytes } bytes). Commit it to the WordPress.org assets/blueprints folder.`
);
