/**
 * Playwright configuration for the Simple Taxonomy Refreshed end-to-end tests.
 *
 * Builds on the `@wordpress/scripts` default (admin login saved by its global setup,
 * one worker, traces and screenshots kept on failure). Differences:
 *  - Tests are in tests/e2e/specs.
 *  - The tests use the site on port 8888 (admin / password): http://127.0.0.1:8888 locally,
 *    where Playground listens on IPv4 only and uses that address as the site URL;
 *    http://localhost:8888 in CI, the wp-env site URL. Use the site's own host name, or
 *    WordPress redirects and the login cookie does not apply.
 *  - Longer time limits than the default: with the plugin on a network share an admin
 *    page takes several seconds.
 *  - Locally, WordPress runs in WordPress Playground (Node only, no Docker), started by
 *    `npm run env:start` with tests/e2e/blueprint.json. Playground is called directly
 *    because wp-env stops it if it is not ready within 120 seconds, and a start from the
 *    network share takes longer. Each start is a new site (SQLite).
 *  - In CI (the CI environment variable is set) wp-env starts it with the Docker runtime
 *    and MySQL, using .wp-env.json.
 *
 * Run with `npm run test:e2e`. If WordPress is already running (for example
 * `npm run env:start` in another window) it is used; otherwise it is started for the
 * run and stopped afterwards.
 */
process.env.WP_BASE_URL ??= process.env.CI
	? 'http://localhost:8888'
	: 'http://127.0.0.1:8888';

const path = require( 'path' );
const { defineConfig } = require( '@playwright/test' );
const baseConfig = require( '@wordpress/scripts/config/playwright.config.js' );

// Leave out the base config's `port`: Playground answers 502 ("WordPress is not ready
// yet") while it boots, so wait for the site URL to answer instead.
const { port, ...baseWebServer } = baseConfig.webServer;

module.exports = defineConfig( {
	...baseConfig,
	testDir: path.join( __dirname, 'tests/e2e/specs' ),
	timeout: 300_000,
	expect: { timeout: 20_000 },
	use: {
		...baseConfig.use,
		actionTimeout: 30_000,
		navigationTimeout: 60_000,
	},
	webServer: {
		...baseWebServer,
		command: process.env.CI ? 'npx wp-env start' : 'npm run env:start',
		url: process.env.WP_BASE_URL,
		// A first start downloads WordPress; later starts take a minute or two.
		timeout: 900_000,
	},
} );
