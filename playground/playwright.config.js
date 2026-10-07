/**
 * Playwright configuration for the documentation screenshots.
 *
 * Run with `npm run screenshots`. The script (playground/screenshots.spec.js) uses the
 * demo playground on port 8889, started by `npm run playground` with
 * playground/blueprint-local.json. If the playground is already running it is used;
 * otherwise it is started for the run and stopped afterwards. Each start is a new site,
 * so the screenshots always show the demo content as the blueprint creates it.
 *
 * The screenshots are written to images/, where the pages in docs/ link to them.
 */
const path = require( 'path' );
const { defineConfig } = require( '@playwright/test' );

const BASE_URL = process.env.STAXO_PLAYGROUND_URL ?? 'http://127.0.0.1:8889';

module.exports = defineConfig( {
	testDir: __dirname,
	testMatch: 'screenshots.spec.js',
	outputDir: path.join( __dirname, '../artifacts/screenshots' ),
	workers: 1,
	fullyParallel: false,
	reporter: 'list',
	timeout: 600_000,
	expect: { timeout: 20_000 },
	use: {
		baseURL: BASE_URL,
		actionTimeout: 30_000,
		navigationTimeout: 60_000,
	},
	webServer: {
		command: 'npm run playground',
		cwd: path.join( __dirname, '..' ),
		url: BASE_URL,
		reuseExistingServer: true,
		// A first start downloads WordPress; later starts take a minute or two.
		timeout: 900_000,
	},
} );
