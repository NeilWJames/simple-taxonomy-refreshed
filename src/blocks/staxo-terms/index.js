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
				blocks: [ 'core/shortcode' ],
				isMatch( { text } ) {
					return /^\[?staxo_post_terms\b\s*/.test( text );
				},
				transform: ( { text } ) => {
					// default.
					let stax = '';

					// prepare text string.
					let iput = text.toLowerCase();
					if ( iput.indexOf( '[' ) === 0 ) {
						iput = iput.slice( 1, iput.length - 1 );
					}
					const args = iput.split( ' ' );
					args.shift();

					let i;
					for ( i of args ) {
						if ( i.length === 0 ) {
							continue;
						}
						const parm = i.split( '=' );
						if (
							parm.length > 1 &&
							( parm[ 1 ].indexOf( "'" ) === 0 ||
								parm[ 1 ].indexOf( '"' ) === 0 )
						) {
							parm[ 1 ] = parm[ 1 ].slice(
								1,
								parm[ 1 ].length - 1
							);
						}
						if ( parm[ 0 ] === 'tax' ) {
							stax = parm[ 1 ];
						}
					}
					return createBlock(
						'simple-taxonomy-refreshed/staxo-terms',
						{
							tax: stax,
						}
					);
				},
			},
		],
		to: [
			{
				type: 'block',
				blocks: [ 'core/shortcode' ],
				transform: ( attributes ) => {
					let sel = '';
					if (
						'' === attributes.tax ||
						undefined === attributes.tax
					) {
						sel = " tax=''";
					} else {
						sel = ' tax=' + attributes.tax;
					}
					const content = '[staxo_post_terms' + sel + ']';
					return createBlock( 'core/shortcode', {
						text: content,
					} );
				},
			},
		],
	},
} );
