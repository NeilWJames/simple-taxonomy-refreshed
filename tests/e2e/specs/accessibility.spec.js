/**
 * Accessibility: axe checks (WCAG 2.2 A and AA, and axe's best practices) of the plugin's screens and front-end
 * output, and keyboard use of the parts that review findings A1 to A12 changed.
 *
 * Fixture staxo-config-e2e-a11y.json: the four suite taxonomies, with the admin list
 * filter on for test_hier and test_flat, and Terms Control on test_cntl (1 or 2 terms,
 * checked as terms are changed). Posts are created with one test_cntl term, as the REST
 * API refuses posts outside the limits. axe only looks at the plugin's own markup, so
 * that WordPress core and theme issues do not fail these tests.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	LIST_URL,
	ADD_URL,
	editUrl,
	resetStaxo,
	loadConfig,
	createTerms,
	editPostClassic,
	collectPageErrors,
	expectNoAxeViolations,
} from '../helpers';

const ORDER_URL = 'admin.php?page=staxo_order';
const IMPORT_URL = 'admin.php?page=staxo_import';
const TOOL_URLS = {
	'Taxonomy Values Migrator': 'admin.php?page=staxo_convert',
	'Terms Merge': 'admin.php?page=staxo_merge',
	'Rename Taxonomy Slug': 'admin.php?page=staxo_rename',
	'Export/Import Configuration': 'admin.php?page=staxo_config_file',
};

/**
 * Create a post with one Control Terms term (the minimum), and any other fields.
 *
 * @param {Object} requestUtils RequestUtils fixture.
 * @param {Object} data         Post fields (title, status, content, terms…).
 * @return {Promise<Object>} The post.
 */
async function createPostInLimits( requestUtils, data ) {
	const [ red ] = await createTerms( requestUtils, 'test_cntl', [
		`red-${ Date.now() }`,
	] );
	return requestUtils.rest( {
		method: 'POST',
		path: '/wp/v2/posts',
		data: { test_cntl: [ red ], ...data },
	} );
}

test.describe( 'Accessibility', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
		await loadConfig( requestUtils, 'staxo-config-e2e-a11y.json' );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
	} );

	test( 'taxonomy list and forms: no axe violations on any tab', async ( {
		admin,
		page,
	} ) => {
		const errors = collectPageErrors( page );
		await admin.visitAdminPage( LIST_URL );
		await expectNoAxeViolations( page, '#wpbody-content .wrap' );

		for ( const url of [ ADD_URL, editUrl( 'test_hier' ) ] ) {
			await admin.visitAdminPage( url );
			const tabs = page.getByRole( 'tab' );
			const count = await tabs.count();
			expect( count ).toBeGreaterThan( 3 );
			for ( let i = 0; i < count; i++ ) {
				await tabs.nth( i ).click();
				await expectNoAxeViolations( page, '#wpbody-content .wrap' );
			}
		}
		expect( errors ).toEqual( [] );
	} );

	test( 'taxonomy form tabs work with the keyboard (A6)', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( ADD_URL );
		const main = page.getByRole( 'tab', { name: 'Main Options' } );
		const visibility = page.getByRole( 'tab', { name: 'Visibility' } );
		const last = page.getByRole( 'tab', { name: 'Term Control' } );

		// Only the selected tab is in the Tab order.
		await expect( main ).toHaveAttribute( 'tabindex', '0' );
		await expect( visibility ).toHaveAttribute( 'tabindex', '-1' );

		await main.focus();
		await page.keyboard.press( 'ArrowRight' );
		await expect( visibility ).toBeFocused();
		await expect( visibility ).toHaveAttribute( 'aria-selected', 'true' );
		await expect( visibility ).toHaveAttribute( 'tabindex', '0' );
		await expect( main ).toHaveAttribute( 'tabindex', '-1' );
		await expect(
			page.getByRole( 'tabpanel', { name: 'Visibility' } )
		).toBeVisible();

		await page.keyboard.press( 'End' );
		await expect( last ).toBeFocused();
		await page.keyboard.press( 'ArrowRight' );
		await expect( main ).toBeFocused();

		// Tab leaves the tab list for the panel.
		await page.keyboard.press( 'Tab' );
		await expect(
			page.getByRole( 'tabpanel', { name: 'Main Options' } )
		).toBeFocused();

		// The Add button says why it is unavailable (A12).
		const add = page.getByRole( 'button', { name: 'Add taxonomy' } );
		await expect( add ).toBeDisabled();
		await expect( add ).toHaveAccessibleDescription(
			/Enter a Name \(slug\) to add the taxonomy/
		);
		await page.getByLabel( 'Name (slug)', { exact: true } ).fill( 'x' );
		await expect( add ).toBeEnabled();
	} );

	test( 'Taxonomy List Order: no axe violations, and reordering with the keyboard (A1)', async ( {
		admin,
		page,
	} ) => {
		const errors = collectPageErrors( page );
		await admin.visitAdminPage( ORDER_URL );
		await expectNoAxeViolations( page, '#wpbody-content .wrap' );

		const list = page.locator( '#post_list' );
		const names = () =>
			list
				.locator( 'li' )
				.evaluateAll( ( items ) =>
					items.map( ( item ) => item.dataset.name )
				);
		const before = await names();
		const first = before[ 0 ];

		const down = list.getByRole( 'button', {
			name: `Move down: ${ first }`,
		} );
		await down.focus();
		await page.keyboard.press( 'Enter' );

		const after = await names();
		expect( after[ 1 ] ).toBe( first );
		await expect( down ).toBeFocused();
		await expect( page.locator( '#a11y-speak-polite' ) ).toContainText(
			`${ first } moved to position 2 of ${ before.length }.`
		);
		await expect( page.locator( '#post_arr' ) ).toHaveValue(
			JSON.stringify( after )
		);

		const save = page.getByRole( 'button', {
			name: 'Save these orderings',
		} );
		await expect( save ).toBeEnabled();
		await save.click();
		await expect(
			page.getByText( '"Posts" admin list taxonomies updated.' )
		).toBeVisible();
		expect( await names() ).toEqual( after );
		expect( errors ).toEqual( [] );
	} );

	test( 'Terms Import: no axe violations, and the Tab key (A5)', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( IMPORT_URL );
		await expectNoAxeViolations( page, '#wpbody-content .wrap' );

		const list = page.getByLabel( 'Terms to import', { exact: true } );
		await expect( list ).toHaveAccessibleDescription(
			/press Escape and then Tab/
		);
		const importButton = page.getByRole( 'button', {
			name: 'Import these words as terms',
		} );

		// No hierarchy: Tab moves on.
		await list.fill( 'red' );
		await page.keyboard.press( 'Tab' );
		await expect( list ).not.toBeFocused();

		// Hierarchy by tabs: Tab types a tab; Escape then Tab moves on.
		await page
			.getByLabel( 'Import uses a hierarchy ?' )
			.selectOption( 'tab' );
		await list.fill( 'Music' );
		await list.press( 'End' );
		await page.keyboard.press( 'Tab' );
		await expect( list ).toBeFocused();
		await expect( list ).toHaveValue( 'Music\t' );
		await page.keyboard.press( 'Escape' );
		await page.keyboard.press( 'Tab' );
		await expect( importButton ).toBeFocused();
	} );

	test( 'term tools and configuration screens: no axe violations', async ( {
		admin,
		page,
	} ) => {
		for ( const [ title, url ] of Object.entries( TOOL_URLS ) ) {
			await admin.visitAdminPage( url );
			await expect(
				page.getByRole( 'heading', { level: 1, name: title } )
			).toBeVisible();
			await expectNoAxeViolations( page, '#wpbody-content .wrap' );
		}
	} );

	test( 'Posts list: the filter dropdowns have labels (A7)', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		await createTerms( requestUtils, 'test_hier', [ 'Music' ] );
		await createTerms( requestUtils, 'flat-terms', [ 'red' ] );
		await createPostInLimits( requestUtils, {
			title: 'A11y post',
			status: 'publish',
		} );

		await admin.visitAdminPage( 'edit.php' );
		const filters = page.locator( '#posts-filter .tablenav.top .actions' );
		await expect(
			filters.locator( 'select[name="test_hier"]' )
		).toHaveCount( 1 );
		await expect(
			filters.locator( 'select[name="test_flat"]' )
		).toHaveCount( 1 );
		for ( const name of [ 'test_hier', 'test_flat' ] ) {
			await expect(
				filters.locator( `select[name="${ name }"]` )
			).toHaveAccessibleName( /\S/ );
		}
		await expectNoAxeViolations( page, '#posts-filter .tablenav.top' );
	} );

	test( 'classic editor: tags box at its maximum says why (A8)', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const errors = collectPageErrors( page );
		// One term to start with; the maximum is 2.
		const post = await createPostInLimits( requestUtils, {
			title: 'A11y classic post',
			status: 'draft',
		} );
		await editPostClassic( admin, post.id );

		const box = page.locator( '#test_cntl' );
		const input = box.locator( '#new-tag-test_cntl' );
		await expect( input ).not.toHaveAttribute( 'readonly', /.*/ );
		await input.fill( 'Two' );
		await box.locator( 'input.tagadd' ).click();
		await expect( input ).toHaveAttribute( 'readonly', 'readonly' );
		await expect( input ).toHaveAccessibleDescription(
			/has the maximum number of terms \(2\)\. Remove a term to add another\./
		);
		await expect( page.locator( '#a11y-speak-polite' ) ).toContainText(
			'Remove a term to add another.'
		);
		await expectNoAxeViolations( page, '#tagsdiv-test_cntl' );

		// Below the maximum again: the hint goes.
		await box.locator( '.tagchecklist li button' ).first().click();
		await expect( input ).not.toHaveAttribute( 'readonly', /.*/ );
		await expect( box.locator( '#staxo-full-test_cntl' ) ).toHaveCount( 0 );
		expect( errors ).toEqual( [] );
	} );

	test( 'front end: Taxonomy Cloud and Post Terms blocks (A4, A10)', async ( {
		page,
		requestUtils,
	} ) => {
		const [ music, jazz ] = await createTerms( requestUtils, 'test_hier', [
			'Music',
			'Jazz',
		] );
		const content = [
			'<!-- wp:simple-taxonomy-refreshed/cloud-widget {"title":"Genre list","taxonomy":"test_hier","disptype":"list","minposts":0,"numdisp":0} /-->',
			'<!-- wp:simple-taxonomy-refreshed/cloud-widget {"title":"Genre cloud","taxonomy":"test_hier","disptype":"cloud","minposts":0,"numdisp":0,"small":40} /-->',
			'<!-- wp:simple-taxonomy-refreshed/staxo-terms {"tax":"test_hier"} /-->',
		].join( '\n' );
		const post = await createPostInLimits( requestUtils, {
			title: 'A11y blocks',
			status: 'publish',
			content,
			test_hier: [ music, jazz ],
		} );
		// Music on two posts, Jazz on one: the cloud uses two sizes.
		await createPostInLimits( requestUtils, {
			title: 'A11y second post',
			status: 'publish',
			test_hier: [ music ],
		} );

		await page.goto( post.link );

		// The block titles are headings; the list is a plain list.
		await expect(
			page.getByRole( 'heading', { level: 2, name: 'Genre list' } )
		).toBeVisible();
		await expect(
			page.getByRole( 'heading', { level: 2, name: 'Genre cloud' } )
		).toBeVisible();
		const list = page.locator( 'ul.staxo-terms-list' );
		await expect( list ).toHaveCount( 1 );
		await expect( list ).not.toHaveAttribute( 'role', /.*/ );
		await expect( list.getByRole( 'listitem' ) ).toHaveCount( 2 );

		// The smallest text is 75% (the block asks for 40%).
		const sizes = await page
			.locator( '.staxo-terms-cloud a' )
			.evaluateAll( ( links ) =>
				links.map(
					( link ) => parseFloat( link.style.fontSize ) || 100
				)
			);
		expect( Math.min( ...sizes ) ).toBe( 75 );

		await expectNoAxeViolations( page, [
			'.wp-block-simple-taxonomy-refreshed-cloud-widget',
			'.wp-block-simple-taxonomy-refreshed-staxo-terms',
		] );
	} );
} );
