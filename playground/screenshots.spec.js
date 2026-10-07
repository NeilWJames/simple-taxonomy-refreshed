/**
 * Screenshots for the documentation (docs/*.md), taken from the demo playground.
 *
 * Run with `npm run screenshots` (config: playground/playwright.config.js). Each image is
 * written to images/<name>.png; the name is the one the docs use. The tests run in file
 * order and share one logged-in page. Those that change the site (Terms Import, Terms
 * Merge) come last, so the other screenshots show the demo content as created.
 */
const path = require( 'path' );
const { test, expect } = require( '@playwright/test' );

const IMAGES = path.join( __dirname, '..', 'images' );
const ARTIFACTS = path.join( __dirname, '..', 'artifacts' );
const BASE_URL = process.env.STAXO_PLAYGROUND_URL ?? 'http://127.0.0.1:8889';

/** @type {import('@playwright/test').BrowserContext} */
let context;
/** @type {import('@playwright/test').Page} */
let page;

/**
 * Save a screenshot of part of the page.
 *
 * @param {string}                                 name   Image name (no extension).
 * @param {import('@playwright/test').Locator=}    target What to capture; default: the admin page content.
 */
async function shot( name, target ) {
	await ( target ?? page.locator( '#wpbody-content' ) ).screenshot( {
		path: path.join( IMAGES, `${ name }.png` ),
		animations: 'disabled',
	} );
}

/**
 * Save a screenshot of the browser window, or of the whole page.
 *
 * @param {string}  name     Image name (no extension).
 * @param {boolean} fullPage Whether to capture the whole page.
 */
async function pageShot( name, fullPage = false ) {
	await page.screenshot( {
		path: path.join( IMAGES, `${ name }.png` ),
		fullPage,
		animations: 'disabled',
	} );
}

/**
 * Run some steps with a different window size, then restore the usual one.
 *
 * Used for wide lists, and for the block editor sidebar, which scrolls within the
 * window: a screenshot of it only shows what the window shows.
 *
 * @param {number}              width  Window width.
 * @param {number}              height Window height.
 * @param {() => Promise<void>} steps  Steps to run.
 */
async function withWindow( width, height, steps ) {
	await page.setViewportSize( { width, height } );
	try {
		await steps();
	} finally {
		await page.setViewportSize( { width: 1280, height: 900 } );
	}
}

/**
 * Open an admin page.
 *
 * @param {string} url Path after /wp-admin/.
 */
async function admin( url ) {
	await page.goto( `/wp-admin/${ url }` );
}

/** The taxonomy settings form (add and edit). */
const formWrap = () => page.locator( '#wpbody-content .wrap' ).first();

/**
 * Show a tab of the taxonomy form.
 *
 * @param {string} name Tab name.
 */
async function showTab( name ) {
	await page.getByRole( 'tab', { name, exact: true } ).click();
}

/**
 * Open the block editor and wait for it, without the welcome guide.
 *
 * @param {string} url Path after /wp-admin/.
 */
async function openEditor( url ) {
	await admin( url );
	await page.waitForFunction(
		() => window.wp?.data?.select( 'core/editor' )?.getCurrentPostId?.()
	);
	await page.evaluate( () => {
		const prefs = window.wp.data.dispatch( 'core/preferences' );
		prefs.set( 'core/edit-post', 'welcomeGuide', false );
	} );
	// On a new site the welcome guide opens a moment after the editor, and hides the
	// rest of the screen from the tests until it is closed.
	try {
		await welcomeGuide().waitFor( { state: 'visible', timeout: 5_000 } );
		await welcomeGuide().getByRole( 'button', { name: 'Close' } ).click();
	} catch {
		// Not shown.
	}
	// Show the settings sidebar.
	const settings = page
		.getByRole( 'region', { name: 'Editor top bar' } )
		.getByRole( 'button', { name: 'Settings', exact: true } );
	if ( 'true' !== ( await settings.getAttribute( 'aria-pressed' ) ) ) {
		await settings.click();
	}
}

/** The block editor's welcome guide. */
const welcomeGuide = () =>
	page.getByRole( 'dialog' ).filter( { hasText: 'Welcome to the' } );

/** The block editor's settings sidebar. */
const sidebar = () => page.locator( '.interface-complementary-area' ).first();

/**
 * A panel of the block editor's settings sidebar.
 *
 * @param {string} name Panel title.
 */
const panel = ( name ) =>
	sidebar()
		.locator( '.components-panel__body' )
		.filter( {
			has: page.getByRole( 'button', { name, exact: true } ),
		} );

/**
 * Open a panel of the block editor's settings sidebar.
 *
 * @param {string} name Panel title.
 */
async function openPanel( name ) {
	const button = sidebar().getByRole( 'button', { name, exact: true } );
	if ( 'false' === ( await button.getAttribute( 'aria-expanded' ) ) ) {
		await button.click();
	}
}

/**
 * Open the editor for a post or page from its admin list.
 *
 * @param {string} list  Admin list path, e.g. edit.php?post_status=draft.
 * @param {string} title Post title.
 */
async function editFromList( list, title ) {
	await admin( list );
	const link = page.locator( 'a.row-title', { hasText: title } );
	const href = await link.getAttribute( 'href' );
	await openEditor( href.replace( /^.*\/wp-admin\//, '' ) );
}

test.beforeAll( async ( { browser } ) => {
	context = await browser.newContext( {
		baseURL: BASE_URL,
		viewport: { width: 1280, height: 900 },
		acceptDownloads: true,
	} );
	page = await context.newPage();
	// Leave pages with unsaved changes without asking.
	page.on( 'dialog', ( dialog ) => dialog.accept() );
	// Close the editor's welcome guide whenever it gets in the way.
	await page.addLocatorHandler( welcomeGuide(), async () => {
		await welcomeGuide().getByRole( 'button', { name: 'Close' } ).click();
	} );

	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( 'admin' );
	await page.locator( '#user_pass' ).fill( 'password' );
	await page.locator( '#wp-submit' ).click();
	await page.waitForURL( /wp-admin/ );
} );

test.afterAll( async () => {
	await context.close();
} );

test( 'taxonomy list and menu', async () => {
	await admin( 'admin.php?page=staxo_settings' );
	await expect( page.locator( '#the-list-custom tr' ) ).toHaveCount( 5 );
	await shot( 'menu', page.locator( '#toplevel_page_staxo_settings' ) );
	await shot( 'all-taxonomies' );
} );

test( 'add taxonomy form', async () => {
	await admin( 'admin.php?page=staxo_settings&action=add' );
	await shot( 'add-taxonomy', formWrap() );
} );

test( 'taxonomy form tabs (Genres)', async () => {
	await admin( 'admin.php?page=staxo_settings&action=edit&taxonomy_name=genre' );
	const tabs = {
		'Main Options': 'tab-main',
		Visibility: 'tab-visibility',
		Labels: 'tab-labels',
		'Rewrite URL': 'tab-rewrite',
		Permissions: 'tab-permissions',
		REST: 'tab-rest',
		Other: 'tab-other',
		WPGraphQL: 'tab-wpgraphql',
	};
	for ( const [ tab, name ] of Object.entries( tabs ) ) {
		await showTab( tab );
		await shot( name, formWrap() );
	}
} );

test( 'Admin List Filter and Term Count tabs (Topics)', async () => {
	await admin( 'admin.php?page=staxo_settings&action=edit&taxonomy_name=topic' );
	await showTab( 'Admin List Filter' );
	await shot( 'tab-admin-filter', formWrap() );
	await showTab( 'Term Count' );
	await shot( 'tab-term-count', formWrap() );
} );

test( 'Term Control tab (Audiences and Sections)', async () => {
	await admin(
		'admin.php?page=staxo_settings&action=edit&taxonomy_name=audience'
	);
	await showTab( 'Term Control' );
	await shot( 'tab-term-control', formWrap() );

	await admin(
		'admin.php?page=staxo_settings&action=edit&taxonomy_name=section'
	);
	await showTab( 'Term Control' );
	await shot( 'tab-term-control-notify', formWrap() );
} );

test( 'extra functions for Tags', async () => {
	await admin(
		'admin.php?page=staxo_settings&action=edit&taxonomy_name=post_tag'
	);
	await shot( 'external-wpgraphql', formWrap() );
	await showTab( 'Admin List Filter' );
	await shot( 'external-admin-filter', formWrap() );
	await showTab( 'Term Count' );
	await shot( 'external-term-count', formWrap() );
} );

test( 'Export PHP (Genres)', async () => {
	await admin( 'admin.php?page=staxo_settings' );
	// The row actions appear on hover.
	await page.locator( '#the-list-custom tr', { hasText: 'Genres' } ).hover();
	const download = page.waitForEvent( 'download', { timeout: 30_000 } );
	await page
		.getByRole( 'link', { name: 'Export PHP - Genres', exact: true } )
		.click();
	// Saved for the docs, not an image.
	await ( await download ).saveAs( path.join( ARTIFACTS, 'genre-export.php' ) );
} );

test( 'posts list: columns and filters', async () => {
	// Wide enough for all the columns.
	await withWindow( 1700, 900, async () => {
		await admin( 'edit.php' );
		await shot( 'posts-list' );

		// Filter on the Topics term Physics.
		const filter = page
			.locator( '#posts-filter .tablenav.top select' )
			.filter( {
				has: page.locator( 'option', { hasText: 'Physics' } ),
			} );
		const value = await filter
			.locator( 'option', { hasText: 'Physics' } )
			.first()
			.getAttribute( 'value' );
		await filter.selectOption( value );
		await page.locator( '#post-query-submit' ).click();
		await shot( 'posts-list-filtered' );
	} );
} );

test( 'terms screen with counts (Topics)', async () => {
	await admin( 'edit-tags.php?taxonomy=topic&post_type=post' );
	await shot( 'terms-topics' );
} );

test( 'Quick Edit', async () => {
	await admin( 'edit.php' );
	const row = page.locator( 'tr', {
		has: page.locator( 'a.row-title', { hasText: 'A night of jazz' } ),
	} );
	await row.hover();
	await row.locator( 'button.editinline' ).click();
	const edit = page.locator( 'tr.inline-editor' );
	await expect( edit ).toBeVisible();
	await shot( 'quick-edit', edit );
	await edit.getByRole( 'button', { name: 'Cancel' } ).click();
} );

test( 'block editor: taxonomy panels', async () => {
	// Diagnostics, reported if the Sections terms do not appear.
	const log = [];
	const onConsole = ( msg ) => {
		if ( [ 'error', 'warning' ].includes( msg.type() ) ) {
			log.push( `console ${ msg.type() }: ${ msg.text() }` );
		}
	};
	const onError = ( error ) => log.push( `page error: ${ error.message }` );
	const onResponse = ( response ) => {
		const url = decodeURIComponent( response.url() );
		if ( url.includes( 'wp/v2/section' ) || url.includes( 'taxonomies' ) ) {
			log.push( `${ response.status() } ${ url }` );
		}
	};
	page.on( 'console', onConsole );
	page.on( 'pageerror', onError );
	page.on( 'response', onResponse );

	await withWindow( 1280, 2000, async () => {
		await openEditor( 'post-new.php' );
		await openPanel( 'Sections' );
		await openPanel( 'Audiences' );
		// Wait for the terms: the panel shows a spinner while it loads them.
		try {
			await expect(
				page
					.getByRole( 'radiogroup', { name: 'Sections', exact: true } )
					.getByRole( 'radio', { name: 'News', exact: true } )
			).toBeVisible();
		} catch ( error ) {
			await page.screenshot( {
				path: path.join( ARTIFACTS, 'debug-sections.png' ),
			} );
			const html = await panel( 'Sections' ).innerHTML();
			throw new Error(
				[ error.message, 'Panel HTML:', html, 'Log:', ...log ].join(
					'\n'
				)
			);
		} finally {
			page.off( 'console', onConsole );
			page.off( 'pageerror', onError );
			page.off( 'response', onResponse );
		}
		await shot( 'editor-sections', panel( 'Sections' ) );
		await shot( 'editor-audiences', panel( 'Audiences' ) );
	} );
} );

test( 'block editor: Terms Control notices', async () => {
	await editFromList( 'edit.php?post_status=draft', 'Rock notes (draft)' );
	const notice = page
		.locator( '.components-notice__content' )
		.filter( { hasText: 'Audiences' } )
		.first();
	await expect( notice ).toBeVisible();
	await pageShot( 'editor-notice' );

	// Try to save the draft without an Audience.
	await page.evaluate( () =>
		window.wp.data
			.dispatch( 'core/editor' )
			.editPost( { title: 'Rock notes (draft), edited' } )
	);
	await page
		.getByRole( 'region', { name: 'Editor top bar' } )
		.getByRole( 'button', { name: 'Save draft', exact: true } )
		.click();
	await expect(
		page
			.locator( '.components-notice__content' )
			.filter( { hasText: 'Not enough terms' } )
			.first()
	).toBeVisible();
	await pageShot( 'editor-save-blocked' );
} );

test( 'block editor: the blocks page', async () => {
	await editFromList( 'edit.php?post_type=page', 'Taxonomy blocks' );
	const canvas =
		( await page.locator( 'iframe[name="editor-canvas"]' ).count() ) > 0
			? page.frameLocator( 'iframe[name="editor-canvas"]' )
			: page;
	const cloud = canvas
		.locator( '[data-type="simple-taxonomy-refreshed/cloud-widget"]' )
		.first();
	await expect( cloud ).toBeVisible();
	await cloud.click();
	await pageShot( 'editor-blocks' );
	await withWindow( 1280, 2000, async () => {
		await shot( 'block-cloud-settings', sidebar() );
	} );
} );

test( 'front end', async () => {
	await page.goto( '/a-night-of-jazz/' );
	await expect( page.getByText( 'Genres:' ).first() ).toBeVisible();
	await pageShot( 'front-post' );

	await page.goto( '/taxonomy-blocks/' );
	await pageShot( 'front-blocks', true );
} );

test( 'Taxonomy List Order', async () => {
	await admin( 'admin.php?page=staxo_order' );
	await shot( 'list-order' );
} );

test( 'Configuration Export/Import', async () => {
	await admin( 'admin.php?page=staxo_config_file' );
	await shot( 'config' );
	await page.locator( '#toggle-import_form' ).click();
	await expect( page.locator( '#import_form' ) ).toBeVisible();
	await shot( 'config-import', page.locator( '#import_form' ) );
} );

test( 'Rename Taxonomy Slug', async () => {
	await admin( 'admin.php?page=staxo_rename' );
	await page.locator( 'input#colour' ).check();
	await shot( 'rename' );
} );

test( 'Terms Migrate', async () => {
	await admin( 'admin.php?page=staxo_convert' );
	await shot( 'terms-migrate' );

	const row = ( name ) =>
		page.locator( 'tr', { has: page.locator( `td[id="${ name }"]` ) } );
	await row( 'genre' ).locator( 'input.copy' ).check();
	await row( 'category' ).locator( 'input.oput' ).check();
	await shot( 'terms-migrate-choose' );
	await page.locator( '#staxo_convert' ).click();
	await expect( page.locator( '#import_content' ) ).not.toBeEmpty();
	await shot( 'terms-migrate-result' );
} );

test( 'Terms Import', async () => {
	await admin( 'admin.php?page=staxo_import' );
	await page.locator( '#taxonomy' ).selectOption( 'genre' );
	await page.locator( '#hierarchy' ).selectOption( 'space' );
	await page
		.locator( '#import_content' )
		.fill(
			[
				'Music',
				' Classical',
				'  Baroque',
				'  Romantic',
				'Art',
				' Photography',
			].join( '\n' )
		);
	await shot( 'terms-import' );
	await page
		.getByRole( 'button', { name: 'Import these words as terms' } )
		.click();
	await expect( page.getByText( 'term lines processed' ) ).toBeVisible();
	await shot( 'terms-import-done' );
} );

test( 'Terms Merge', async () => {
	const step = ( name ) => page.getByRole( 'button', { name, exact: true } );

	await admin( 'admin.php?page=staxo_merge' );
	await page.getByLabel( 'Colours', { exact: true } ).check();
	await shot( 'terms-merge-1' );
	await step( 'Select Taxonomy' ).click();

	await page.getByLabel( 'Blue', { exact: true } ).check();
	await shot( 'terms-merge-2' );
	await step( 'Select Destination Term' ).click();

	await page.getByRole( 'checkbox', { name: 'Cyan', exact: true } ).check();
	await shot( 'terms-merge-3' );
	await step( 'Select Source Term(s)' ).click();

	await expect( page.getByText( 'Destination Term : Blue' ) ).toBeVisible();
	await shot( 'terms-merge-4' );
	await step( 'Confirm Action' ).click();

	await expect( page.getByText( 'All objects updated' ) ).toBeVisible();
	await shot( 'terms-merge-5' );
} );
