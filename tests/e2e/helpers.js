/**
 * Shared helpers for the Simple Taxonomy Refreshed end-to-end tests.
 */
import { expect } from '@wordpress/e2e-test-utils-playwright';

/** Admin URL of the taxonomy list (Taxonomies > All Taxonomies). */
export const LIST_URL = 'admin.php?page=staxo_settings';

/** Admin URL of the Add Taxonomy form. */
export const ADD_URL = 'admin.php?page=staxo_settings&action=add';

/**
 * Admin URL of the form for an existing taxonomy (custom or external).
 *
 * @param {string} name Taxonomy name (slug).
 * @return {string} Admin URL.
 */
export const editUrl = ( name ) =>
	`admin.php?page=staxo_settings&action=edit&taxonomy_name=${ name }`;

/**
 * Delete the STR settings and the terms of the taxonomies they define
 * (helper must-use plugin, route staxo-e2e/v1/reset).
 *
 * @param {Object} requestUtils RequestUtils fixture.
 */
export async function resetStaxo( requestUtils ) {
	await requestUtils.rest( {
		method: 'POST',
		path: '/staxo-e2e/v1/reset',
	} );
}

/**
 * Load a configuration fixture from tests/files as the STR settings
 * (route staxo-e2e/v1/config). The taxonomies are registered from the next request.
 *
 * @param {Object} requestUtils RequestUtils fixture.
 * @param {string} file         Fixture file name, e.g. staxo-config-suite.json.
 */
export async function loadConfig( requestUtils, file ) {
	await requestUtils.rest( {
		method: 'POST',
		path: '/staxo-e2e/v1/config',
		data: { file },
	} );
}

/**
 * Locators for the taxonomy form (add and edit).
 *
 * Some labels are used on more than one tab ("Hierarchical ?" and "Post types" each
 * label three fields), so those fields are looked up inside their tab panel.
 *
 * @param {import('@playwright/test').Page} page Current page.
 */
export function taxonomyForm( page ) {
	const panel = ( id ) => page.locator( `#${ id }` );
	return {
		panel,
		slug: page.getByLabel( 'Name (slug)', { exact: true } ),
		hierarchical: panel( 'mainopts' ).getByLabel( 'Hierarchical ?', {
			exact: true,
		} ),
		postTypes: panel( 'mainopts' ).getByRole( 'group', {
			name: 'Post types',
			exact: true,
		} ),
		label: page.getByLabel( 'Name (label)', { exact: true } ),
		singularLabel: page.getByLabel( 'Singular Name', { exact: true } ),
		addButton: page.getByRole( 'button', {
			name: 'Add taxonomy',
			exact: true,
		} ),
		updateButton: page.getByRole( 'button', {
			name: 'Update taxonomy',
			exact: true,
		} ),
		tab: ( name ) => page.getByRole( 'tab', { name, exact: true } ),
	};
}

/**
 * Enter the slug. The form checks it on change, so leave the field afterwards.
 *
 * @param {Object} form Locators from taxonomyForm().
 * @param {string} slug Taxonomy name (slug).
 */
export async function enterSlug( form, slug ) {
	await form.slug.fill( slug );
	await form.slug.press( 'Tab' );
}

/**
 * Create terms through the REST API.
 *
 * @param {Object}   requestUtils RequestUtils fixture.
 * @param {string}   restBase     Taxonomy REST base.
 * @param {string[]} names        Term names.
 * @return {Promise<number[]>} Term IDs, in the same order.
 */
export async function createTerms( requestUtils, restBase, names ) {
	const ids = [];
	for ( const name of names ) {
		const term = await requestUtils.rest( {
			method: 'POST',
			path: `/wp/v2/${ restBase }`,
			data: { name },
		} );
		ids.push( term.id );
	}
	return ids;
}

/**
 * Read a post through the REST API (edit context, so all fields are present).
 *
 * @param {Object} requestUtils RequestUtils fixture.
 * @param {number} id           Post ID.
 * @return {Promise<Object>} Post.
 */
export function getPost( requestUtils, id ) {
	return requestUtils.rest( {
		path: `/wp/v2/posts/${ id }`,
		params: { context: 'edit' },
	} );
}

/**
 * Open a post in the classic editor (the helper plugin switches it with
 * staxo_e2e_classic=1).
 *
 * @param {Object} admin Admin fixture.
 * @param {number} id    Post ID.
 */
export async function editPostClassic( admin, id ) {
	await admin.visitAdminPage(
		'post.php',
		`post=${ id }&action=edit&staxo_e2e_classic=1`
	);
}

/**
 * Open Quick Edit for a post on the Posts list.
 *
 * @param {Object}                          admin Admin fixture.
 * @param {import('@playwright/test').Page} page  Current page.
 * @param {number}                          id    Post ID.
 * @return {Promise<import('@playwright/test').Locator>} The Quick Edit row.
 */
export async function openQuickEdit( admin, page, id ) {
	await admin.visitAdminPage( 'edit.php' );
	const row = page.locator( `#post-${ id }` );
	// The row actions appear on hover.
	await row.hover();
	await row.locator( 'button.editinline' ).click();
	const edit = page.locator( `#edit-${ id }` );
	await expect( edit ).toBeVisible();
	return edit;
}

/**
 * Collect uncaught script errors on the page.
 *
 * @param {import('@playwright/test').Page} page Current page.
 * @return {string[]} Messages, filled as errors happen.
 */
export function collectPageErrors( page ) {
	const errors = [];
	page.on( 'pageerror', ( error ) => errors.push( error.message ) );
	return errors;
}
