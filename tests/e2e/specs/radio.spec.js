/**
 * Radio buttons for a taxonomy that allows one term (review finding H3).
 *
 * Fixture staxo-config-e2e-radio.json: e2e_kind ("Kinds"), hierarchical, for posts,
 * REST base "kinds", Terms Control on any saved post with exactly one term
 * (minimum 1, maximum 1, checked when the post is saved). With a maximum of 1 the
 * plugin shows the terms as radio buttons:
 *  - block editor: the plugin's radio term selector (src/editor, built to build/editor
 *    with `npm run build:editor`) replaces WordPress's checkboxes through the
 *    editor.PostTaxonomyType filter, with its own "Add new term" form;
 *  - classic editor: dom_radio_client() changes the checkboxes into radio inputs once
 *    the page is ready (H3: it used to be called before it was registered);
 *  - Quick Edit: dom_qe_radio_client() does the same when the row opens.
 *
 * The draft is created before the fixture is loaded, so the control does not stop it
 * being created without a term.
 *
 * Fixture staxo-config-e2e-radio-notify.json: the same taxonomy with "How Control is
 * applied" set to notification only and no minimum. Radio buttons are shown at every
 * control level, with a "No term" choice when there is no minimum.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	resetStaxo,
	loadConfig,
	createTerms,
	getPost,
	editPostClassic,
	openQuickEdit,
	collectPageErrors,
} from '../helpers';

test.describe( 'Radio buttons for a one-term taxonomy', () => {
	let postId;
	let rock;
	let jazz;

	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
		const post = await requestUtils.createPost( {
			title: 'E2E radio draft',
			status: 'draft',
		} );
		postId = post.id;
		await loadConfig( requestUtils, 'staxo-config-e2e-radio.json' );
		[ rock, jazz ] = await createTerms( requestUtils, 'kinds', [
			'Rock',
			'Jazz',
		] );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
	} );

	test( 'block editor: the terms are radio buttons, with Add new term', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		const errors = collectPageErrors( page );
		await admin.editPost( postId );
		await editor.openDocumentSettingsSidebar();

		const panel = page.getByRole( 'button', {
			name: 'Kinds',
			exact: true,
		} );
		if ( 'false' === ( await panel.getAttribute( 'aria-expanded' ) ) ) {
			await panel.click();
		}
		const group = page.getByRole( 'radiogroup', {
			name: 'Kinds',
			exact: true,
		} );
		const radio = ( name ) =>
			group.getByRole( 'radio', { name, exact: true } );

		// Radio buttons, no checkboxes; a term is required, so no "No term" choice.
		await expect( group.getByRole( 'radio' ) ).toHaveCount( 2 );
		await expect( group.getByRole( 'checkbox' ) ).toHaveCount( 0 );

		await radio( 'Rock' ).check();
		await expect( radio( 'Rock' ) ).toBeChecked();

		// Choosing another term replaces it.
		await radio( 'Jazz' ).check();
		await expect( radio( 'Jazz' ) ).toBeChecked();
		await expect( radio( 'Rock' ) ).not.toBeChecked();

		// Add a new term: it is created and chosen.
		await page
			.getByRole( 'button', { name: 'Add New Category', exact: true } )
			.click();
		const form = page.locator( 'form.staxo-radio-terms__add' );
		await form
			.getByLabel( 'New Category Name', { exact: true } )
			.fill( 'Blues' );
		await form
			.getByRole( 'button', { name: 'Add New Category', exact: true } )
			.click();
		await expect( radio( 'Blues' ) ).toBeChecked();
		await expect( radio( 'Jazz' ) ).not.toBeChecked();

		await editor.saveDraft();
		const [ blues ] = await requestUtils.rest( {
			path: '/wp/v2/kinds',
			params: { search: 'Blues' },
		} );
		const saved = await getPost( requestUtils, postId );
		expect( saved.kinds ).toEqual( [ blues.id ] );
		expect( errors ).toEqual( [] );
	} );

	test( 'classic editor: the terms are radio buttons', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const errors = collectPageErrors( page );
		await editPostClassic( admin, postId );

		const all = page.locator( '#e2e_kind-all' );
		await expect( all.locator( 'input[type="radio"]' ) ).toHaveCount( 2 );
		await expect(
			page.locator( '#taxonomy-e2e_kind input[type="checkbox"]' )
		).toHaveCount( 0 );

		await all.getByLabel( 'Jazz', { exact: true } ).check();
		await all.getByLabel( 'Rock', { exact: true } ).check();
		await expect(
			all.getByLabel( 'Jazz', { exact: true } )
		).not.toBeChecked();

		await page.locator( '#save-post' ).click();
		await expect( page.getByText( 'Post draft updated.' ) ).toBeVisible();

		const saved = await getPost( requestUtils, postId );
		expect( saved.kinds ).toEqual( [ rock ] );
		expect( errors ).toEqual( [] );
	} );

	test( 'Quick Edit: the terms are radio buttons', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const errors = collectPageErrors( page );
		const row = await openQuickEdit( admin, page, postId );

		const list = row.locator( '.e2e_kind-checklist' );
		await expect( list.locator( 'input[type="radio"]' ) ).toHaveCount( 2 );
		await expect( list ).toHaveAttribute( 'role', 'radiogroup' );

		await list.getByLabel( 'Jazz', { exact: true } ).check();
		await row
			.getByRole( 'button', { name: 'Update', exact: true } )
			.click();
		// The row closes and the list shows the post again.
		await expect( row ).toBeHidden();

		const saved = await getPost( requestUtils, postId );
		expect( saved.kinds ).toEqual( [ jazz ] );
		expect( errors ).toEqual( [] );
	} );
} );

test.describe( 'Radio buttons with notification-only Terms Control', () => {
	let postId;
	let jazz;

	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
		const post = await requestUtils.createPost( {
			title: 'E2E radio notification draft',
			status: 'draft',
		} );
		postId = post.id;
		await loadConfig( requestUtils, 'staxo-config-e2e-radio-notify.json' );
		[ , jazz ] = await createTerms( requestUtils, 'kinds', [
			'Rock',
			'Jazz',
		] );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
		await resetStaxo( requestUtils );
	} );

	test( 'block editor: radio buttons with No term', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		const errors = collectPageErrors( page );
		await admin.editPost( postId );
		await editor.openDocumentSettingsSidebar();

		const panel = page.getByRole( 'button', {
			name: 'Kinds',
			exact: true,
		} );
		if ( 'false' === ( await panel.getAttribute( 'aria-expanded' ) ) ) {
			await panel.click();
		}
		const group = page.getByRole( 'radiogroup', {
			name: 'Kinds',
			exact: true,
		} );
		const radio = ( name ) =>
			group.getByRole( 'radio', { name, exact: true } );

		// No minimum: "No term" and the two terms, no checkboxes.
		await expect( group.getByRole( 'radio' ) ).toHaveCount( 3 );
		await expect( group.getByRole( 'checkbox' ) ).toHaveCount( 0 );
		await expect( radio( 'No term' ) ).toBeChecked();

		await radio( 'Jazz' ).check();
		await expect( radio( 'No term' ) ).not.toBeChecked();
		await editor.saveDraft();
		expect( ( await getPost( requestUtils, postId ) ).kinds ).toEqual( [
			jazz,
		] );

		// Back to no term.
		await radio( 'No term' ).check();
		await editor.saveDraft();
		expect( ( await getPost( requestUtils, postId ) ).kinds ).toEqual(
			[]
		);
		expect( errors ).toEqual( [] );
	} );

	test( 'classic editor: the terms are radio buttons', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		const errors = collectPageErrors( page );
		await editPostClassic( admin, postId );

		const all = page.locator( '#e2e_kind-all' );
		await expect(
			all.getByLabel( 'Jazz', { exact: true } )
		).toHaveAttribute( 'type', 'radio' );
		await expect(
			page.locator( '#taxonomy-e2e_kind input[type="checkbox"]' )
		).toHaveCount( 0 );

		await all.getByLabel( 'Jazz', { exact: true } ).check();
		await page.locator( '#save-post' ).click();
		await expect( page.getByText( 'Post draft updated.' ) ).toBeVisible();

		expect( ( await getPost( requestUtils, postId ) ).kinds ).toEqual( [
			jazz,
		] );
		expect( errors ).toEqual( [] );
	} );
} );
