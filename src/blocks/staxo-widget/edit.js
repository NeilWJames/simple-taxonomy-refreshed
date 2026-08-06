import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RadioControl,
	RangeControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

/* global staxo_data */

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	const opts = [];
	for ( const key in staxo_data ) {
		opts.push( { label: staxo_data[ key ], value: key } );
	}

	return (
		<div { ...blockProps }>
			{ /* Preview the block using its PHP render callback */ }
			<ServerSideRender
				block="simple-taxonomy-refreshed/cloud-widget"
				attributes={ attributes }
			/>

			<InspectorControls>
				<PanelBody
					title={ __( 'Taxonomy Cloud Settings', 'simple-taxonomy-refreshed' ) }
					initialOpen={ true }
				>
					<TextControl
						value={ attributes.title }
						label={ __( 'Title', 'simple-taxonomy-refreshed' ) }
						onChange={ ( val ) => setAttributes( { title: val } ) }
					/>

					<RadioControl
						label={ __( 'Taxonomy', 'simple-taxonomy-refreshed' ) }
						selected={ attributes.taxonomy }
						options={ opts }
						onChange={ ( val ) => setAttributes( { taxonomy: val } ) }
					/>

					<RadioControl
						label={ __( 'Display Type', 'simple-taxonomy-refreshed' ) }
						selected={ attributes.disptype }
						options={ [
							{ label: __( 'Cloud', 'simple-taxonomy-refreshed' ), value: 'cloud' },
							{ label: __( 'List', 'simple-taxonomy-refreshed' ), value: 'list' },
						] }
						onChange={ ( val ) => setAttributes( { disptype: val } ) }
					/>

					<RangeControl
						value={ attributes.small }
						label={ __( 'Tag size - Smallest', 'simple-taxonomy-refreshed' ) }
						onChange={ ( val ) => setAttributes( { small: parseInt( val, 10 ) } ) }
						min={ 40 }
						max={ 100 }
					/>

					<RangeControl
						value={ attributes.big }
						label={ __( 'Tag size - Largest', 'simple-taxonomy-refreshed' ) }
						onChange={ ( val ) => setAttributes( { big: parseInt( val, 10 ) } ) }
						min={ 100 }
						max={ 160 }
					/>

					<RadioControl
						label={ __( 'Text Alignment', 'simple-taxonomy-refreshed' ) }
						selected={ attributes.alignment }
						options={ [
							{ label: __( 'Centre', 'simple-taxonomy-refreshed' ), value: 'center' },
							{ label: __( 'Left', 'simple-taxonomy-refreshed' ), value: 'left' },
							{ label: __( 'Right', 'simple-taxonomy-refreshed' ), value: 'right' },
							{ label: __( 'Justify', 'simple-taxonomy-refreshed' ), value: 'justify' },
						] }
						onChange={ ( val ) => setAttributes( { alignment: val } ) }
					/>

					<RadioControl
						label={ __( 'Order choice', 'simple-taxonomy-refreshed' ) }
						selected={ attributes.orderby }
						options={ [
							{ label: __( 'Name', 'simple-taxonomy-refreshed' ), value: 'name' },
							{ label: __( 'Count', 'simple-taxonomy-refreshed' ), value: 'count' },
						] }
						onChange={ ( val ) => setAttributes( { orderby: val } ) }
					/>

					<RadioControl
						label={ __( 'Order sequence', 'simple-taxonomy-refreshed' ) }
						selected={ attributes.ordering }
						options={ [
							{ label: __( 'Ascending', 'simple-taxonomy-refreshed' ), value: 'ASC' },
							{ label: __( 'Descending', 'simple-taxonomy-refreshed' ), value: 'DESC' },
							{ label: __( 'Random', 'simple-taxonomy-refreshed' ), value: 'RAND' },
						] }
						onChange={ ( val ) => setAttributes( { ordering: val } ) }
					/>

					<ToggleControl
						checked={ attributes.showcount }
						label={ __( 'Show the number of posts for each term?', 'simple-taxonomy-refreshed' ) }
						help={ __( 'Setting this on will give the number of posts linked to each term.', 'simple-taxonomy-refreshed' ) }
						onChange={ ( val ) => setAttributes( { showcount: val } ) }
					/>

					<RangeControl
						value={ attributes.numdisp }
						label={ __( 'Maximum number of terms to display', 'simple-taxonomy-refreshed' ) }
						onChange={ ( val ) => setAttributes( { numdisp: parseInt( val, 10 ) } ) }
						min={ 1 }
						max={ 100 }
					/>

					<RangeControl
						value={ attributes.minposts }
						label={ __( 'Minimum count of posts for term to be shown', 'simple-taxonomy-refreshed' ) }
						help={ __( 'Set to 1 to remove empty terms.', 'simple-taxonomy-refreshed' ) }
						onChange={ ( val ) => setAttributes( { minposts: parseInt( val, 10 ) } ) }
						min={ 0 }
					/>
				</PanelBody>
			</InspectorControls>
		</div>
	);
}