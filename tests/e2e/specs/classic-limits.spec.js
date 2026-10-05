/**
 * Terms Control checked as terms are changed, in the classic editor and Quick Edit
 * (review finding H3: the dom_*() handlers used to run before the page was ready).
 *
 * Fixture staxo-config-e2e-hard.json: e2e_hard ("Editor's Terms"), flat, for posts,
 * 1 or 2 terms on any saved post, checked as terms are changed.
 *  - Classic editor: dom_tag_cntl_check() shows the reason, stops Save Draft while
 *    the count is outside the limits, and stops more tags being added at the maximum.
 *  - Quick Edit: dom_qe_cntl_check() shows the reason and disables Update.
 *
 * The draft is created before the fixture is loaded.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	resetStaxo,
	loadConfig,
	getPost,
	editPostClassic,
	openQuickEdit,
	collectPageErrors,
} from '../helpers';

const BELOW = "The number of terms for taxonomy (Editor's Terms) is less than";

test.describe( 'Terms Control in the classic editor and Quick Edit', () => {
	let postId;

	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
		const post = await requestUtils.createPost( {
			title: 'E2E classic draft without terms',
			status: 'draft',
		} );
		postId = post.id;
		await loadConfig( requestUtils, 'staxo-config-e2e-hard.json' );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
	} );

	test( 'classic editor: saving waits for the terms to be within the limits', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const errors = collectPageErrors( page );
		await editPostClassic( admin, postId );

		const error = page.locator( '#err-e2e_hard' );
		await expect( error ).toContainText( BELOW );

		// Save Draft is stopped: still on the editor, nothing saved.
		await page.locator( '#save-post' ).click();
		await expect( error ).toContainText( BELOW );
		await expect( page.getByText( 'Post draft updated.' ) ).toHaveCount(
			0
		);

		// One tag: within the limits.
		const box = page.locator( '#e2e_hard' );
		const input = box.locator( '#new-tag-e2e_hard' );
		await input.fill( 'One' );
		await box.locator( 'input.tagadd' ).click();
		await expect( error ).toBeHidden();

		// Two tags: the maximum, so no more can be added.
		await input.fill( 'Two' );
		await box.locator( 'input.tagadd' ).click();
		await expect( input ).toHaveAttribute( 'readonly', 'readonly' );

		await page.locator( '#save-post' ).click();
		await expect( page.getByText( 'Post draft updated.' ) ).toBeVisible();

		const saved = await getPost( requestUtils, postId );
		expect( saved.e2e_hard ).toHaveLength( 2 );
		expect( errors ).toEqual( [] );
	} );

	test( 'Quick Edit: Update waits for the terms to be within the limits', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const errors = collectPageErrors( page );
		const row = await openQuickEdit( admin, page, postId );

		const update = row.getByRole( 'button', {
			name: 'Update',
			exact: true,
		} );
		await expect( row.locator( '.e2e_hard-err' ) ).toContainText( BELOW );
		await expect( update ).toBeDisabled();

		const tags = row.locator( 'textarea.tax_input_e2e_hard' );
		await tags.fill( 'One' );
		// The check runs when the field is left.
		await tags.press( 'Tab' );
		await expect( row.locator( '.e2e_hard-err' ) ).toBeHidden();
		await expect( update ).toBeEnabled();

		await update.click();
		await expect( row ).toBeHidden();

		const saved = await getPost( requestUtils, postId );
		expect( saved.e2e_hard ).toHaveLength( 1 );
		expect( errors ).toEqual( [] );
	} );
} );
