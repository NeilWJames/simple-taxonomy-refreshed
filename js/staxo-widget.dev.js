( function( blocks, element, blockEditor, components, serverSideRender, i18n, data ) {
const { registerBlockType, createBlock } = wp.blocks; //Blocks API
const { createElement } = wp.element; //React.createElement
const { InspectorControls } = wp.blockEditor; //Block inspector wrapper
const { PanelBody, RadioControl, RangeControl, SelectControl, TextControl, ToggleControl } = wp.components; //WordPress form inputs
const { __ } = wp.i18n; //translation functions
const { useSelect } = wp.data; //data functions

registerBlockType( 'simple-taxonomy-refreshed/cloud-widget', {
	title: __( 'Taxonomy Cloud', 'simple-taxonomy-refreshed' ), // Block title.
	description: __( 'Display a Taxonomy Cloud.', 'simple-taxonomy-refreshed' ),
	icon: 'admin-page',
	attributes:  {
		title : {
			type : 'string'
		},
		taxonomy : {
			type : 'string'
		},
		disptype : {
			type : 'string',
			default : 'cloud'
		},
		small : {
			type : 'number',
			default : 50
		},
		big : {
			type : 'number',
			default : 150
		},
		alignment : {
			type : 'string',
			default : 'justify'
		},
		orderby : {
			type : 'string',
			default : 'name'
		},
		ordering : {
			type : 'string',
			default : 'ASC'
		},
		showcount : {
			type : 'boolean',
			default: false
		},
		numdisp : {
			type : 'number',
			default : 0
		},
		minposts : {
			type : 'number',
			default : 0
		},
		align: {
			type: 'string'
		},
		backgroundColor: {
			type: 'string'
		},
		linkColor: {
			type: 'string'
		},
		textColor: {
			type: 'string'
		},
		gradient: {
			type: 'string'
		},
		fontSize: {
			type: 'string'
		},
		style: {
				type: 'object'
		}
	},
	supports: {
		align: true,
		color: {
			gradients: true,
			link: true
		},
		spacing: {
			margin: true,
			padding: true
    },
		typography: {
			fontSize: true,
			lineHeight: true
    }
	},
	//display the settings
	edit( props ){
		const attributes =  props.attributes;
		const setAttributes =  props.setAttributes;

		var opts = [];
		for ( key in staxo_data ) {
			opts.push( { label: staxo_data[key], value: key } );
		}
		
    //Display block preview and UI
		return createElement('div', {},
			[
				//Preview a block with a PHP render callback
				createElement( serverSideRender, {
					block: 'simple-taxonomy-refreshed/cloud-widget',
					attributes: attributes
					}
				),
				//Block inspector
				createElement( InspectorControls, {},
					[
						createElement( PanelBody, { title: __( 'Taxonomy Cloud Settings', 'simple-taxonomy-refreshed' ), initialOpen: true },
							[
								// A simple text control for Title
								createElement( TextControl, {
									value: attributes.title,
									label: __( 'Title', 'simple-taxonomy-refreshed' ),
									onChange: function( val ) {
										setAttributes( { title: val } );
									}
								}),
								// A simple text control for Taxonomy
								createElement( RadioControl, {
									label: __( 'Taxonomy', 'simple-taxonomy-refreshed' ),
									selected: attributes.taxonomy,
									options: opts,
									onChange: function( val ) {
										setAttributes( { taxonomy: val } );
										reset_excludes(val);
									}
								}),
								// Radio control for alignment options. .
								createElement(RadioControl, {
									label: __( 'Display Type', 'simple-taxonomy-refreshed' ),
								  selected: attributes.disptype,
								  options: [
										{ label: __( 'Cloud', 'simple-taxonomy-refreshed' ), value: 'cloud' },
										{ label: __( 'List', 'simple-taxonomy-refreshed' ), value: 'list' },
								  ],
									onChange: function( val ) {
										setAttributes( { disptype: val } );
									}
								}),
								//Select smallest fontsize (%)
								createElement( RangeControl, {
									value: attributes.small,
									label: __( 'Tag size - Smallest', 'simple-taxonomy-refreshed' ),
									onChange: function( val ) {
										setAttributes( { small: parseInt( val ) } );
									},
									min: 40,
									max: 100
								}),
								//Select largest fontsize (%)
								createElement( RangeControl, {
									value: attributes.big,
									label: __( 'Tag size - Largest', 'simple-taxonomy-refreshed' ),
									onChange: function( val ) {
										setAttributes( { big: parseInt( val ) } );
									},
									min: 100,
									max: 160
								}),
								// Radio control for alignment options. .
								createElement( RadioControl, {
									label: __( 'Text Alignment', 'simple-taxonomy-refreshed' ),
								  selected: attributes.alignment,
								  options: [
										{ label: __( 'Centre', 'simple-taxonomy-refreshed' ), value: 'center' },
										{ label: __( 'Left', 'simple-taxonomy-refreshed' ), value: 'left' },
										{ label: __( 'Right', 'simple-taxonomy-refreshed' ), value: 'right' },
										{ label: __( 'Justify', 'simple-taxonomy-refreshed' ), value: 'justify' },
								  ],
									onChange: function( val ) {
										setAttributes( { alignment: val } );
									}
								}),
								// Radio control for order choice .
								createElement( RadioControl, {
									label: __( 'Order choice', 'simple-taxonomy-refreshed' ),
								  selected: attributes.orderby,
								  options: [
										{ label: __( 'Name', 'simple-taxonomy-refreshed' ), value: 'name' },
										{ label: __( 'Count', 'simple-taxonomy-refreshed' ), value: 'count' },
								  ],
									onChange: function( val ) {
										setAttributes( { orderby: val } );
									}
								}),
								// Radio control for ordering.
								createElement( RadioControl, {
									label: __( 'Order sequence', 'simple-taxonomy-refreshed' ),
								  selected: attributes.ordering,
								  options: [
										{ label: __( 'Ascending', 'simple-taxonomy-refreshed' ), value: 'ASC' },
										{ label: __( 'Descending', 'simple-taxonomy-refreshed' ), value: 'DESC' },
										{ label: __( 'Random', 'simple-taxonomy-refreshed' ), value: 'RAND' },
								  ],
									onChange: function( val ) {
										setAttributes( { ordering: val } );
									}
								}),
								// Toggle for show counts
								createElement( ToggleControl, {
									type: 'boolean',
									checked: attributes.showcount,
									label: __( 'Show the number of posts for each term?', 'wp-document-revisions' ),
									help: __( 'Setting this on will give the number of posts linked to each term.', 'simple-taxonomy-refreshed' ),
									onChange: function( val ) {
										setAttributes( { showcount: val } )
									}
								}),
								//Select minimum threshold
								createElement( RangeControl, {
									value: attributes.numdisp,
									label: __( 'Maximum number of terms to display', 'simple-taxonomy-refreshed' ),
									onChange: function( val ) {
										setAttributes( { numdisp: parseInt( val ) } );
									},
									min: 1,
									max: 100
								}),
								//Select minimum threshold
								createElement( RangeControl, {
									value: attributes.minposts,
									label: __( 'Minimum count of posts for term to be shown', 'simple-taxonomy-refreshed' ),
									help: __( 'Set to 1 to remove empty terms.', 'simple-taxonomy-refreshed' ),
									onChange: function( val ) {
										setAttributes( { minposts: parseInt( val ) } );
									},
									min: 0
								}),
								// Select control for exclude terms.
								// Show featured image
							]
						)
					]
				)
			]
		);
	},
	save(){
		return null; //save has to exist. This all we need.
	},
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/legacy-widget' ],
				isMatch: ( { idBase, instance } ) => {
					if ( ! instance?.raw ) {
						// Can't transform if raw instance is not shown in REST API.
						return false;
					}
					return idBase === 'staxonomy';
				},
				transform: ( { instance } ) => {
					return createBlock( 'simple-taxonomy-refreshed/cloud-widget', {
						name: instance.raw.name,
					} );
				},
			},
		]
	},
} );
}
(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender,
	window.wp.i18n,
	window.wp.data
) );

