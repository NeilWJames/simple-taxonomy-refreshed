/**
 * Terms Control in the block editor.
 *
 * Two fixtures, each with one flat taxonomy for posts that needs 1 or 2 terms on any
 * saved post (Terms Control "Any (Except Trash)"):
 *  - staxo-config-e2e-notice.json: e2e_notes, labelled Writer's "Notes", checked when
 *    the post is saved. Opening a draft that has no term shows a notice (written by
 *    PHP as an inline script; quotes in the label once broke that script, review H2),
 *    and the REST API refuses to save it.
 *  - staxo-config-e2e-hard.json: e2e_hard, labelled Editor's Terms, checked as terms
 *    are changed. staxo-client.js block_limit() locks saving while the count is outside
 *    the limits and shows why.
 *
 * The draft is created before the fixture is loaded, so the controls do not stop it
 * being created without terms.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { resetStaxo, loadConfig } from '../helpers';

/**
 * Collect uncaught script errors on the page (an inline script that does not parse
 * shows up here).
 *
 * @param {import('@playwright/test').Page} page Current page.
 * @return {string[]} Messages, filled as errors happen.
 */
function collectPageErrors( page ) {
	const errors = [];
	page.on( 'pageerror', ( error ) => errors.push( error.message ) );
	return errors;
}

/**
 * The editor notices showing a text. (The screen-reader live region repeats notice
 * text and keeps it after the notice has gone, so look only at the notices.)
 *
 * @param {import('@playwright/test').Page} page Editor page.
 * @param {string}                          text Text the notice contains.
 */
const notice = ( page, text ) =>
	page.locator( '.components-notice__content' ).filter( { hasText: text } );

/**
 * Set the post's terms in a taxonomy through the editor's data store, as the
 * taxonomy panel does.
 *
 * @param {import('@playwright/test').Page} page     Editor page.
 * @param {string}                          taxonomy Taxonomy REST base.
 * @param {number[]}                        termIds  Term IDs.
 */
async function setPostTerms( page, taxonomy, termIds ) {
	await page.evaluate(
		( [ tax, ids ] ) =>
			window.wp.data
				.dispatch( 'core/editor' )
				.editPost( { [ tax ]: ids } ),
		[ taxonomy, termIds ]
	);
}

/**
 * Whether the editor's post saving is locked.
 *
 * @param {import('@playwright/test').Page} page Editor page.
 * @return {Promise<boolean>} Locked.
 */
function isSavingLocked( page ) {
	return page.evaluate( () =>
		window.wp.data.select( 'core/editor' ).isPostSavingLocked()
	);
}

/**
 * Create terms through the REST API.
 *
 * @param {Object}   requestUtils RequestUtils fixture.
 * @param {string}   taxonomy     Taxonomy REST base.
 * @param {string[]} names        Term names.
 * @return {Promise<number[]>} Term IDs.
 */
async function createTerms( requestUtils, taxonomy, names ) {
	const ids = [];
	for ( const name of names ) {
		const term = await requestUtils.rest( {
			method: 'POST',
			path: `/wp/v2/${ taxonomy }`,
			data: { name },
		} );
		ids.push( term.id );
	}
	return ids;
}

test.describe( 'Terms Control in the block editor', () => {
	let postId;

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
	} );

	test.describe( 'checked when the post is saved', () => {
		test.beforeEach( async ( { requestUtils } ) => {
			await requestUtils.deleteAllPosts();
			await resetStaxo( requestUtils );
			const post = await requestUtils.createPost( {
				title: 'E2E draft without notes',
				status: 'draft',
			} );
			postId = post.id;
			await loadConfig( requestUtils, 'staxo-config-e2e-notice.json' );
		} );

		test( 'shows a notice when a draft has too few terms (label with quotes)', async ( {
			admin,
			page,
		} ) => {
			const errors = collectPageErrors( page );
			await admin.editPost( postId );

			await expect(
				notice(
					page,
					'The number of terms for taxonomy (Writer\'s "Notes") is less than the required minimum number 1.'
				)
			).toBeVisible();
			expect( errors ).toEqual( [] );
		} );

		test( 'refuses to save a draft with too few terms', async ( {
			admin,
			page,
		} ) => {
			await admin.editPost( postId );
			// "Save draft" is only offered once the post has a change.
			await page.evaluate( () =>
				window.wp.data
					.dispatch( 'core/editor' )
					.editPost( { title: 'E2E draft without notes, edited' } )
			);
			await page
				.getByRole( 'region', { name: 'Editor top bar' } )
				.getByRole( 'button', { name: 'Save draft', exact: true } )
				.click();

			await expect(
				notice(
					page,
					'Not enough terms entered for Taxonomy "Writer\'s "Notes""'
				)
			).toBeVisible();
		} );

		test( 'saves once a term is added', async ( {
			admin,
			editor,
			page,
			requestUtils,
		} ) => {
			const [ noteId ] = await createTerms( requestUtils, 'e2e_notes', [
				'First note',
			] );
			await admin.editPost( postId );

			await setPostTerms( page, 'e2e_notes', [ noteId ] );
			await editor.saveDraft();

			const saved = await requestUtils.rest( {
				path: `/wp/v2/posts/${ postId }`,
				params: { context: 'edit' },
			} );
			expect( saved.e2e_notes ).toEqual( [ noteId ] );
		} );
	} );

	test.describe( 'checked as terms are changed', () => {
		test.beforeEach( async ( { requestUtils } ) => {
			await requestUtils.deleteAllPosts();
			await resetStaxo( requestUtils );
			const post = await requestUtils.createPost( {
				title: 'E2E draft without terms',
				status: 'draft',
			} );
			postId = post.id;
			await loadConfig( requestUtils, 'staxo-config-e2e-hard.json' );
		} );

		test( 'locks saving while a draft has too few terms', async ( {
			admin,
			page,
		} ) => {
			const errors = collectPageErrors( page );
			await admin.editPost( postId );

			await expect(
				notice(
					page,
					"The number of terms for taxonomy (Editor's Terms) is less than the required minimum number 1. Saving is blocked."
				)
			).toBeVisible();
			await expect.poll( () => isSavingLocked( page ) ).toBe( true );
			expect( errors ).toEqual( [] );
		} );

		test( 'unlocks within the limits and locks again above the maximum', async ( {
			admin,
			editor,
			page,
			requestUtils,
		} ) => {
			const ids = await createTerms( requestUtils, 'e2e_hard', [
				'One',
				'Two',
				'Three',
			] );
			await admin.editPost( postId );
			await expect.poll( () => isSavingLocked( page ) ).toBe( true );

			// One term: within the limits.
			await setPostTerms( page, 'e2e_hard', [ ids[ 0 ] ] );
			await expect.poll( () => isSavingLocked( page ) ).toBe( false );
			await expect(
				notice( page, 'less than the required minimum number 1' )
			).toBeHidden();

			// Three terms: above the maximum of 2.
			await setPostTerms( page, 'e2e_hard', ids );
			await expect(
				notice(
					page,
					"The number of terms for taxonomy (Editor's Terms) is greater than the required maximum number 2. Saving is blocked."
				)
			).toBeVisible();
			await expect.poll( () => isSavingLocked( page ) ).toBe( true );

			// Two terms: within the limits again, and the draft saves.
			await setPostTerms( page, 'e2e_hard', ids.slice( 0, 2 ) );
			await expect.poll( () => isSavingLocked( page ) ).toBe( false );
			await editor.saveDraft();

			const saved = await requestUtils.rest( {
				path: `/wp/v2/posts/${ postId }`,
				params: { context: 'edit' },
			} );
			expect( [ ...saved.e2e_hard ].sort() ).toEqual(
				ids.slice( 0, 2 ).sort()
			);
		} );
	} );
} );
