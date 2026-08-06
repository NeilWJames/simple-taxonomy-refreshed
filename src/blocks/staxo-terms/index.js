import { createBlock, registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit.js';

registerBlockType( metadata, {
	edit: Edit,
	save: () => null,
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
					return createBlock( 'simple-taxonomy-refreshed/staxo-terms', {
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
});
