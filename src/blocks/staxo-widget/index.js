import { createBlock, registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import Edit from './edit.js';
import './index.css';

registerBlockType( metadata, {
	edit: Edit,
	save: () => null,
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
});