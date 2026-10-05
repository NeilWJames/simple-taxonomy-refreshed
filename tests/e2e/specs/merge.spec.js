/**
 * Terms Merge screen (Taxonomies > Terms Merge): the four steps in the browser.
 *
 * Fixture staxo-config-e2e-merge.json: e2e_genre ("Genres"), hierarchical, for posts,
 * no Terms Control. Terms and posts are created through the REST API:
 *
 *   Music          Art
 *   └ Jazz
 *     └ Bebop
 *
 *   "E2E Bebop post" → Bebop; "E2E Jazz and Bebop post" → Jazz, Bebop.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { resetStaxo, loadConfig } from '../helpers';

const MERGE_URL = 'admin.php?page=staxo_merge';
const TAX = 'e2e_genre';

/**
 * Create a term through the REST API.
 *
 * @param {Object} requestUtils RequestUtils fixture.
 * @param {string} name         Term name.
 * @param {number} parent       Parent term ID (0 for top level).
 * @return {Promise<number>} Term ID.
 */
async function createTerm( requestUtils, name, parent = 0 ) {
	const term = await requestUtils.rest( {
		method: 'POST',
		path: `/wp/v2/${ TAX }`,
		data: { name, parent },
	} );
	return term.id;
}

/**
 * Read a term through the REST API.
 *
 * @param {Object} requestUtils RequestUtils fixture.
 * @param {number} id           Term ID.
 * @return {Promise<Object>} Term.
 */
function getTerm( requestUtils, id ) {
	return requestUtils.rest( { path: `/wp/v2/${ TAX }/${ id }` } );
}

/**
 * The merge form's button. It is one input whose label changes at each step.
 *
 * @param {import('@playwright/test').Page} page Current page.
 * @param {string}                          name Label for this step.
 */
const stepButton = ( page, name ) =>
	page.getByRole( 'button', { name, exact: true } );

/**
 * Go through steps one to three: taxonomy, destination, sources.
 *
 * @param {import('@playwright/test').Page} page        Current page.
 * @param {string}                          destination Destination term name.
 * @param {string[]}                        sources     Source term names.
 */
async function chooseTerms( page, destination, sources ) {
	await page.getByLabel( 'Genres', { exact: true } ).check();
	await stepButton( page, 'Select Taxonomy' ).click();

	await expect(
		page.getByText( 'Selected Taxonomy : Genres' )
	).toBeVisible();
	await page.getByLabel( destination, { exact: true } ).check();
	await stepButton( page, 'Select Destination Term' ).click();

	// The destination is listed but cannot be chosen as a source.
	await expect(
		page.getByRole( 'checkbox', { name: destination, exact: true } )
	).toBeDisabled();
	for ( const source of sources ) {
		await page
			.getByRole( 'checkbox', { name: source, exact: true } )
			.check();
	}
	await stepButton( page, 'Select Source Term(s)' ).click();

	await expect(
		page.getByText( `Destination Term : ${ destination }` )
	).toBeVisible();
}

test.describe( 'Terms Merge', () => {
	let ids;
	let posts;

	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
		await loadConfig( requestUtils, 'staxo-config-e2e-merge.json' );

		const music = await createTerm( requestUtils, 'Music' );
		const jazz = await createTerm( requestUtils, 'Jazz', music );
		const bebop = await createTerm( requestUtils, 'Bebop', jazz );
		const art = await createTerm( requestUtils, 'Art' );
		ids = { music, jazz, bebop, art };

		posts = {
			bebop: await requestUtils.createPost( {
				title: 'E2E Bebop post',
				status: 'publish',
				[ TAX ]: [ bebop ],
			} ),
			both: await requestUtils.createPost( {
				title: 'E2E Jazz and Bebop post',
				status: 'publish',
				[ TAX ]: [ jazz, bebop ],
			} ),
		};
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
	} );

	test( 'merges a term into another through all four steps', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		await admin.visitAdminPage( MERGE_URL );
		await expect( stepButton( page, 'Select Taxonomy' ) ).toBeDisabled();

		await chooseTerms( page, 'Jazz', [ 'Bebop' ] );
		await stepButton( page, 'Confirm Action' ).click();

		await expect(
			page.getByText(
				'Some posts were already linked to the destination term.'
			)
		).toBeVisible();
		await expect(
			page.getByText(
				'All objects updated, destination term count is now : 2'
			)
		).toBeVisible();
		// Finished: no more steps.
		await expect( stepButton( page, 'Confirm Action' ) ).toHaveCount( 0 );

		// Bebop is gone and both posts have Jazz, once.
		const remaining = await requestUtils.rest( {
			path: `/wp/v2/${ TAX }`,
			params: { search: 'Bebop' },
		} );
		expect( remaining ).toEqual( [] );
		for ( const post of Object.values( posts ) ) {
			const saved = await requestUtils.rest( {
				path: `/wp/v2/posts/${ post.id }`,
			} );
			expect( saved[ TAX ] ).toEqual( [ ids.jazz ] );
		}
	} );

	test( 'moves the child terms of a merged term under the destination by default', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		await admin.visitAdminPage( MERGE_URL );
		await chooseTerms( page, 'Art', [ 'Music' ] );

		const under = page.getByLabel( 'Move them under the destination term', {
			exact: true,
		} );
		await expect( under ).toBeChecked();
		await stepButton( page, 'Confirm Action' ).click();
		await expect( page.getByText( 'All objects updated' ) ).toBeVisible();

		const jazz = await getTerm( requestUtils, ids.jazz );
		expect( jazz.parent ).toBe( ids.art );
	} );

	test( 'can move the child terms of a merged term up a level instead', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		await admin.visitAdminPage( MERGE_URL );
		await chooseTerms( page, 'Art', [ 'Music' ] );

		await page
			.getByLabel(
				"Move them up a level (under the source term's parent)",
				{
					exact: true,
				}
			)
			.check();
		await stepButton( page, 'Confirm Action' ).click();
		await expect(
			page.getByText(
				'Child terms of the source term(s) have been moved up a level.'
			)
		).toBeVisible();

		const jazz = await getTerm( requestUtils, ids.jazz );
		expect( jazz.parent ).toBe( 0 );
	} );
} );
