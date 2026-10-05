/**
 * Block editor: radio buttons for taxonomies limited to one term.
 *
 * WordPress draws each taxonomy panel with a term selector wrapped in the
 * `editor.PostTaxonomyType` filter. For a taxonomy listed in window.staxo_radio
 * (set by PHP, SimpleTaxonomyRefreshed_Admin::script_radio(): hierarchical, Terms
 * Control maximum 1, fewer than two terms on the post) the plugin's radio selector is
 * used instead; every other taxonomy keeps WordPress's selector.
 */

/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import RadioTermSelector from './radio-term-selector';
import './style.css';

/**
 * Settings of the radio taxonomies on this screen, keyed by slug.
 *
 * @param {string} slug Taxonomy slug.
 * @return {Object|undefined} { noTerm: string|null }.
 */
function radioSettings( slug ) {
	const settings = window.staxo_radio || {};
	return Object.prototype.hasOwnProperty.call( settings, slug )
		? settings[ slug ]
		: undefined;
}

addFilter(
	'editor.PostTaxonomyType',
	'simple-taxonomy-refreshed/radio-term-selector',
	( OriginalComponent ) =>
		function StaxoTermSelector( props ) {
			const settings = radioSettings( props.slug );
			if ( undefined === settings ) {
				return <OriginalComponent { ...props } />;
			}
			return (
				<RadioTermSelector
					slug={ props.slug }
					noTerm={ settings.noTerm ?? null }
				/>
			);
		}
);
