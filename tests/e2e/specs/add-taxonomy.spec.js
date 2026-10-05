/**
 * Add Taxonomy screen (Taxonomies > Add Taxonomy).
 *
 * Adds a custom taxonomy through the form and checks that WordPress then registers it.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { ADD_URL, resetStaxo, taxonomyForm, enterSlug } from '../helpers';

test.describe( 'Add Taxonomy', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await resetStaxo( requestUtils );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await resetStaxo( requestUtils );
	} );

	test( 'submit is enabled only once a name is entered', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( ADD_URL );
		const form = taxonomyForm( page );

		await expect(
			page.getByRole( 'heading', {
				name: 'Add Custom Taxonomy',
				exact: true,
			} )
		).toBeVisible();
		await expect( form.addButton ).toBeDisabled();

		await enterSlug( form, 'e2e_genre' );
		await expect( form.addButton ).toBeEnabled();

		await enterSlug( form, '' );
		await expect( form.addButton ).toBeDisabled();
	} );

	test( 'tabs show one panel at a time', async ( { admin, page } ) => {
		await admin.visitAdminPage( ADD_URL );
		const form = taxonomyForm( page );

		await expect( form.tab( 'Main Options' ) ).toHaveAttribute(
			'aria-selected',
			'true'
		);
		await expect( form.label ).toBeHidden();

		await form.tab( 'Labels' ).click();
		await expect( form.tab( 'Labels' ) ).toHaveAttribute(
			'aria-selected',
			'true'
		);
		await expect( form.tab( 'Main Options' ) ).toHaveAttribute(
			'aria-selected',
			'false'
		);
		await expect( form.label ).toBeVisible();
		await expect( form.slug ).toBeHidden();
	} );

	test( 'adds a hierarchical taxonomy for posts', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( ADD_URL );
		const form = taxonomyForm( page );

		await enterSlug( form, 'e2e_genre' );
		await form.hierarchical.selectOption( '1' );
		await form.postTypes
			.getByRole( 'checkbox', { name: 'Posts', exact: true } )
			.check();

		await form.tab( 'Labels' ).click();
		await form.label.fill( 'E2E Genres' );

		await form.addButton.click();

		// Back on the list, with the confirmation and the new row.
		// WordPress removes `message` from the address bar once the page has loaded
		// (removable query args), so check the taxonomy in the URL and the notice.
		await expect( page ).toHaveURL(
			/page=staxo_settings.*&staxo=e2e_genre$/
		);
		await expect(
			page.getByText( 'Taxonomy "e2e_genre" added successfully !' )
		).toBeVisible();
		await expect(
			page.locator( '#the-list-custom' ).getByRole( 'cell', {
				name: 'e2e_genre',
				exact: true,
			} )
		).toBeVisible();

		// WordPress registers it: its terms screen opens under Posts.
		await admin.visitAdminPage(
			'edit-tags.php',
			'taxonomy=e2e_genre&post_type=post'
		);
		await expect(
			page.getByRole( 'heading', { name: 'E2E Genres', level: 1 } )
		).toBeVisible();
		// Hierarchical: the add-term form has a Parent field.
		await expect( page.locator( '#parent' ) ).toBeVisible();
	} );

	test( 'refuses a name already used by another taxonomy', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( ADD_URL );
		const form = taxonomyForm( page );

		await enterSlug( form, 'category' );
		await form.tab( 'Labels' ).click();
		await form.label.fill( 'Duplicate' );
		await form.addButton.click();

		await expect(
			page.getByText(
				'You are trying to add a taxonomy with a name already used by another taxonomy.'
			)
		).toBeVisible();
	} );
} );
