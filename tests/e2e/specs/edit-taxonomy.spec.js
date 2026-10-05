/**
 * Edit Taxonomy screen (Taxonomies > All Taxonomies > Modify / Extra Functions).
 *
 * The four taxonomies of tests/files/staxo-config-suite.json are loaded before each
 * test. The form must show the saved settings on every tab, and saving must store
 * them: for a custom taxonomy (test_hier) and for an external one (category).
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	LIST_URL,
	editUrl,
	resetStaxo,
	loadConfig,
	taxonomyForm,
} from '../helpers';

test.describe( 'Edit Taxonomy', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await resetStaxo( requestUtils );
		await loadConfig( requestUtils, 'staxo-config-suite.json' );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await resetStaxo( requestUtils );
	} );

	test( 'the list links to the form of each taxonomy', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( LIST_URL );

		const list = page.locator( '#the-list-custom' );
		for ( const label of [
			'Test Terms',
			'Flat Terms',
			'Control Terms',
			'Count Terms',
		] ) {
			await expect(
				list
					.getByRole( 'link', { name: `Modify - ${ label }` } )
					.first()
			).toBeVisible();
		}

		await list
			.getByRole( 'link', { name: 'Modify - Test Terms' } )
			.first()
			.click();

		const form = taxonomyForm( page );
		await expect(
			page.getByRole( 'heading', {
				name: 'Custom Taxonomy : Test Terms',
				exact: true,
			} )
		).toBeVisible();
		// The name cannot be changed here (Rename Slug does that).
		await expect( form.slug ).toHaveValue( 'test_hier' );
		await expect( form.slug ).toHaveAttribute( 'readonly', 'readonly' );
		await expect( form.updateButton ).toBeEnabled();
	} );

	test( 'shows the saved main options and labels', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( editUrl( 'test_hier' ) );
		const form = taxonomyForm( page );

		await expect( form.hierarchical ).toHaveValue( '1' );
		await expect(
			form.postTypes.getByRole( 'checkbox', {
				name: 'Posts',
				exact: true,
			} )
		).toBeChecked();
		await expect(
			form.postTypes.getByRole( 'checkbox', {
				name: 'Pages',
				exact: true,
			} )
		).not.toBeChecked();
		await expect(
			page.getByLabel( 'Display Terms Before text', { exact: true } )
		).toHaveValue( 'Test Terms:' );

		await form.tab( 'Labels' ).click();
		await expect( form.label ).toHaveValue( 'Test Terms' );
		await expect( form.singularLabel ).toHaveValue( 'Test Term' );
	} );

	test( 'shows the saved REST base and default term', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( editUrl( 'test_flat' ) );
		const form = taxonomyForm( page );

		await expect( form.hierarchical ).toHaveValue( '0' );
		for ( const type of [ 'Posts', 'Pages' ] ) {
			await expect(
				form.postTypes.getByRole( 'checkbox', {
					name: type,
					exact: true,
				} )
			).toBeChecked();
		}

		await form.tab( 'REST' ).click();
		await expect(
			page.getByLabel( 'REST Base', { exact: true } )
		).toHaveValue( 'flat-terms' );

		await form.tab( 'Other' ).click();
		await expect(
			page.getByLabel( 'Default Term Name', { exact: true } )
		).toHaveValue( 'Unsorted' );
	} );

	test( 'shows the saved Term Count settings', async ( { admin, page } ) => {
		await admin.visitAdminPage( editUrl( 'test_count' ) );
		const form = taxonomyForm( page );

		await form.tab( 'Term Count' ).click();
		await expect(
			page
				.getByRole( 'radiogroup', {
					name: 'Count Options',
					exact: true,
				} )
				.getByRole( 'radio', { name: 'Selection', exact: true } )
		).toBeChecked();

		const statuses = page.getByRole( 'group', {
			name: 'Status Selection',
			exact: true,
		} );
		await expect( statuses ).toBeVisible();
		for ( const [ status, counted ] of [
			[ 'Publish', true ],
			[ 'Future', false ],
			[ 'Draft', true ],
			[ 'Pending', false ],
			[ 'Private', false ],
			[ 'Trash', false ],
		] ) {
			const box = statuses.getByRole( 'checkbox', {
				name: status,
				exact: true,
			} );
			await expect( box ).toBeChecked( { checked: counted } );
		}
	} );

	test( 'shows the saved Term Control settings', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( editUrl( 'test_cntl' ) );
		const form = taxonomyForm( page );
		const control = form.panel( 'countt' );

		await form.tab( 'Term Control' ).click();
		await expect(
			control
				.getByRole( 'radiogroup', { name: 'Post status', exact: true } )
				.getByRole( 'radio', {
					name: 'Any (Except Trash)',
					exact: true,
				} )
		).toBeChecked();
		await expect(
			control
				.getByRole( 'radiogroup', {
					name: 'How Control is applied',
					exact: true,
				} )
				.getByRole( 'radio', {
					name: 'When the post is saved',
					exact: true,
				} )
		).toBeChecked();
		await expect(
			control.getByLabel( 'Use minimum number of terms', {
				exact: true,
			} )
		).toHaveValue( '1' );
		await expect(
			control.getByLabel( 'Minimum number of Terms', { exact: true } )
		).toHaveValue( '1' );
		await expect(
			control.getByLabel( 'Maximum number of Terms', { exact: true } )
		).toHaveValue( '2' );
	} );

	test( 'saves changed labels of a custom taxonomy', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( editUrl( 'test_hier' ) );
		const form = taxonomyForm( page );

		await form.tab( 'Labels' ).click();
		await form.label.fill( 'Genres' );
		await form.singularLabel.fill( 'Genre' );
		await form.updateButton.click();

		// WordPress removes `message` from the address bar once the page has loaded
		// (removable query args), so check the taxonomy in the URL and the notice.
		await expect( page ).toHaveURL(
			/page=staxo_settings.*&staxo=test_hier$/
		);
		await expect(
			page.getByText( 'Taxonomy "test_hier" updated successfully !' )
		).toBeVisible();
		await expect(
			page
				.locator( '#the-list-custom' )
				.getByRole( 'link', { name: 'Modify - Genres' } )
				.first()
		).toBeVisible();

		// The form shows the new labels, and WordPress uses them.
		await admin.visitAdminPage( editUrl( 'test_hier' ) );
		await form.tab( 'Labels' ).click();
		await expect( form.label ).toHaveValue( 'Genres' );
		await expect( form.singularLabel ).toHaveValue( 'Genre' );

		await admin.visitAdminPage(
			'edit-tags.php',
			'taxonomy=test_hier&post_type=post'
		);
		await expect(
			page.getByRole( 'heading', { name: 'Genres', level: 1 } )
		).toBeVisible();
	} );

	test( 'saves Term Control settings for an external taxonomy', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage( editUrl( 'category' ) );
		const form = taxonomyForm( page );
		const control = form.panel( 'countt' );

		await expect(
			page.getByRole( 'heading', {
				name: 'External Taxonomy : Categories',
				exact: true,
			} )
		).toBeVisible();
		// Only the integration tabs are offered for a taxonomy defined elsewhere.
		await expect( form.tab( 'Main Options' ) ).toHaveCount( 0 );

		await form.tab( 'Term Control' ).click();
		await control
			.getByRole( 'radiogroup', { name: 'Post status', exact: true } )
			.getByRole( 'radio', { name: 'Any (Except Trash)', exact: true } )
			.check();
		await control
			.getByRole( 'radiogroup', {
				name: 'How Control is applied',
				exact: true,
			} )
			.getByRole( 'radio', {
				name: 'When the post is saved',
				exact: true,
			} )
			.check();
		await control
			.getByLabel( 'Use minimum number of terms', { exact: true } )
			.selectOption( '1' );
		const minimum = control.getByLabel( 'Minimum number of Terms', {
			exact: true,
		} );
		// Enabled once "Use minimum" is True.
		await expect( minimum ).toBeEnabled();
		await minimum.fill( '1' );
		await minimum.press( 'Tab' );

		await form.updateButton.click();
		// WordPress removes `message` from the address bar once the page has loaded
		// (removable query args), so check the taxonomy in the URL and the notice.
		await expect( page ).toHaveURL(
			/page=staxo_settings.*&staxo=category$/
		);
		await expect(
			page.getByText( 'Taxonomy "category" updated successfully !' )
		).toBeVisible();

		// Saved: the form shows the settings again.
		await admin.visitAdminPage( editUrl( 'category' ) );
		await form.tab( 'Term Control' ).click();
		await expect(
			control
				.getByRole( 'radiogroup', { name: 'Post status', exact: true } )
				.getByRole( 'radio', {
					name: 'Any (Except Trash)',
					exact: true,
				} )
		).toBeChecked();
		await expect(
			control
				.getByRole( 'radiogroup', {
					name: 'How Control is applied',
					exact: true,
				} )
				.getByRole( 'radio', {
					name: 'When the post is saved',
					exact: true,
				} )
		).toBeChecked();
		await expect(
			control.getByLabel( 'Use minimum number of terms', {
				exact: true,
			} )
		).toHaveValue( '1' );
		await expect( minimum ).toHaveValue( '1' );
	} );
} );
