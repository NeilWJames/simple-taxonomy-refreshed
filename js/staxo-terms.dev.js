( function( blocks, element, blockEditor, components, serverSideRender, i18n, data ) {
const { registerBlockType, createBlock } = wp.blocks; //Blocks API
const { createElement } = wp.element; //React.createElement
const { InspectorControls } = wp.blockEditor; //Block inspector wrapper
const { PanelBody, RadioControl, RangeControl, SelectControl, TextControl, ToggleControl } = wp.components; //WordPress form inputs
const { __ } = wp.i18n; //translation functions
const { useSelect } = wp.data; //data functions

registerBlockType( 'simple-taxonomy-refreshed/post-terms', {
	title: __( 'Display Post Terms', 'simple-taxonomy-refreshed' ), // Block title.
	description: __( 'Display the Post Terms for Added Taxonomies.', 'simple-taxonomy-refreshed' ),
	category: 'widgets',
	icon: 'admin-page',
	attributes:  {
		tax : {
			type : 'string'
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
		opts.push( { label: "All Custom", value: "" } );
		for ( key in staxo_own_data ) {
			opts.push( { label: staxo_data[key], value: key } );
		}
		
    	//Display block preview and UI
		return createElement('div', {},
			[
				//Preview a block with a PHP render callback
				createElement( serverSideRender, {
					block: 'simple-taxonomy-refreshed/post-terms',
					attributes: attributes
					}
				),
				//Block inspector
				createElement( InspectorControls, {},
					[
						createElement( PanelBody, { title: __( 'Post Terms', 'simple-taxonomy-refreshed' ), initialOpen: true },
							[
								// A simple text control for Taxonomy
								createElement( RadioControl, {
									label: __( 'Taxonomy', 'simple-taxonomy-refreshed' ),
									selected: attributes.tax,
									options: opts,
									onChange: function( val ) {
										setAttributes( { tax: val } );
									}
								}),
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
				blocks: ['core/shortcode'],
				isMatch: function( {text} ) {
					return /^\[?staxo_post_terms\b\s*/.test(text);
				},
				transform: ( { text } ) => {
					// default.
					var stax = '';

					// prepare text string.
					var iput = text.toLowerCase();
					if ( iput.indexOf("[") == 0 ) {
						iput = iput.slice(1, iput.length-1);
					}
					var args = iput.split(" ");
					args.shift();

					var i;
					for (i of args) {
						if (i.length === 0 ) {
							continue;
						}
						var used = false;
						var parm = i.split("=");
						if ( parm.length > 1 && ( parm[1].indexOf("'") === 0 || parm[1].indexOf('"') === 0 ) ) {
							parm[1] = parm[1].slice(1, parm[1].length-1);
						}
						if ( parm[0] === 'tax' ) {
							stax = parm[1];
						}
					}
					return createBlock( 'simple-taxonomy-refreshed/post-terms', {
						tax: stax
					} );
				},
			},
		],
		to: [
			{
				type: 'block',
				blocks: [ 'core/shortcode' ],
				transform: ( attributes ) => {
					var sel = "";
					if ("" === attributes.tax || undefined === attributes.tax) {
						sel = " tax=''";
					} else {
						sel = " tax=" + attributes.tax;
					}
				var content = "[staxo_post_terms" + sel + "]";
					return createBlock( 'core/shortcode', {
						text : content
					} );
				}
			}
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

