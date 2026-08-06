import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { Spinner, PanelBody, RadioControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';

/* global staxo_post */
export default function Edit( { context, attributes, setAttributes } ) {
	const { postType_c, postId } = context;
	const blockProps = useBlockProps();

	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	)

	const taxonomies = useSelect(
		( select ) => {
			if ( ! postType ) {
				return null; // still resolving postType itself
			}
			return select( coreStore ).getTaxonomies( { type: postType, per_page: -1 } );
		},
		[ postType ]
	);

	// still loading (postType not known yet, or REST fetch not resolved)
	if ( ! postType || taxonomies === null ) {
		return createElement( Spinner );
	}

	// build the set of valid taxonomy slugs for this post type
	const validSlugs = new Set( taxonomies.map( ( tax ) => tax.slug ) );

	const filteredStaxoPost = Object.fromEntries(
	    Object.entries( staxo_post ).filter( ( [ slug ] ) => validSlugs.has( slug ) )
	);

	const opts = [ { label: __( 'All Custom', 'simple-taxonomy-refreshed' ), value: '' } ];
	for ( const slug in filteredStaxoPost ) {
		opts.push( { label: filteredStaxoPost[ slug ], value: slug } );
	}

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="simple-taxonomy-refreshed/staxo-terms"
				attributes={ attributes }
			/>
			<InspectorControls>
				<PanelBody title={ __( 'Post Terms', 'simple-taxonomy-refreshed' ) } initialOpen={ true }>
					<RadioControl
						label={ __( 'Taxonomy', 'simple-taxonomy-refreshed' ) }
						selected={ attributes.tax }
						options={ opts }
						onChange={ ( val ) => setAttributes( { tax: val } ) }
					/>
				</PanelBody>
			</InspectorControls>
		</div>
	);
}
