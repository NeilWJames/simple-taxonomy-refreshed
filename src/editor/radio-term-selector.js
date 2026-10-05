/**
 * Radio term selector for the block editor.
 *
 * Used in place of WordPress's hierarchical term selector for a taxonomy whose
 * Terms Control allows one term at most (see index.js). Adapted from
 * HierarchicalTermSelector in `@wordpress/editor` (GPL-2.0-or-later): the same
 * term list, search and "Add new term" form, but each term is a radio button, so
 * choosing a term replaces the one chosen before. When the taxonomy does not require
 * a term, a "No term" choice comes first.
 *
 * Only stable WordPress APIs are used, so it works from WordPress 6.9.
 */

/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useEffect, useMemo, useState } from '@wordpress/element';
import {
	Button,
	SearchControl,
	Spinner,
	TextControl,
	TreeSelect,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';
import { speak } from '@wordpress/a11y';
import { decodeEntities } from '@wordpress/html-entities';

const DEFAULT_QUERY = {
	per_page: -1,
	orderby: 'name',
	order: 'asc',
	_fields: 'id,name,parent',
	context: 'view',
};
const MIN_TERMS_COUNT_FOR_FILTER = 8;
const EMPTY_ARRAY = [];

/**
 * Build the term tree from a flat list (children under their parent).
 *
 * @param {Object[]} flatTerms Terms with id, name and parent.
 * @return {Object[]} Top-level terms, each with children.
 */
export function buildTermsTree( flatTerms ) {
	const byParent = {};
	for ( const term of flatTerms ) {
		const parent = term.parent || 0;
		byParent[ parent ] = byParent[ parent ] || [];
		byParent[ parent ].push( { ...term, children: [] } );
	}
	const known = new Set( flatTerms.map( ( term ) => term.id ) );
	const attach = ( terms ) =>
		terms.map( ( term ) => ( {
			...term,
			children: attach( byParent[ term.id ] || [] ),
		} ) );
	// Terms whose parent is not in the list are shown at the top level.
	const roots = flatTerms
		.filter( ( term ) => ! term.parent || ! known.has( term.parent ) )
		.map( ( term ) => ( { ...term, children: [] } ) );
	return attach( roots );
}

/**
 * Keep the terms (and their parents) whose name contains the search text.
 *
 * @param {Object[]} tree   Term tree.
 * @param {string}   search Search text.
 * @return {Object[]} Filtered tree.
 */
export function filterTermsTree( tree, search ) {
	if ( '' === search ) {
		return tree;
	}
	const needle = search.toLowerCase();
	const match = ( term ) => {
		const children = term.children.map( match ).filter( Boolean );
		if (
			decodeEntities( term.name ).toLowerCase().includes( needle ) ||
			children.length > 0
		) {
			return { ...term, children };
		}
		return null;
	};
	return tree.map( match ).filter( Boolean );
}

/**
 * Find a term by parent and name (case-insensitive).
 *
 * @param {Object[]}      terms  Terms.
 * @param {number|string} parent Parent id ('' or 0 for top level).
 * @param {string}        name   Term name.
 * @return {Object|undefined} Term.
 */
export function findTerm( terms, parent, name ) {
	return terms.find(
		( term ) =>
			( ( ! term.parent && ! parent ) ||
				parseInt( term.parent, 10 ) === parseInt( parent, 10 ) ) &&
			term.name.toLowerCase() === name.toLowerCase()
	);
}

/**
 * Count the terms in a tree.
 *
 * @param {Object[]} tree Term tree.
 * @return {number} Number of terms.
 */
function countTerms( tree ) {
	return tree.reduce(
		( count, term ) => count + 1 + countTerms( term.children ),
		0
	);
}

/**
 * One radio button.
 *
 * @param {Object}                  props          Props.
 * @param {string}                  props.group    Input name shared by the group.
 * @param {number}                  props.value    Term id (0 for "No term").
 * @param {string}                  props.label    Label text.
 * @param {number}                  props.level    Depth in the tree.
 * @param {boolean}                 props.checked  Chosen.
 * @param {(value: number) => void} props.onChoose Called with the value when chosen.
 */
function TermRadio( { group, value, label, level, checked, onChoose } ) {
	const id = `${ group }-${ value }`;
	return (
		<div
			className="staxo-radio-terms__choice"
			style={ { paddingInlineStart: `${ level * 16 }px` } }
		>
			<input
				type="radio"
				id={ id }
				name={ group }
				value={ value }
				checked={ checked }
				onChange={ () => onChoose( value ) }
			/>
			<label htmlFor={ id }>{ label }</label>
		</div>
	);
}

/**
 * Radio buttons for a term tree.
 *
 * @param {Object}                  props          Props.
 * @param {Object[]}                props.terms    Term tree.
 * @param {string}                  props.group    Input name.
 * @param {number}                  props.chosen   Chosen term id (0 for none).
 * @param {(value: number) => void} props.onChoose Called with the chosen id.
 * @param {number}                  props.level    Depth.
 */
function TermRadios( { terms, group, chosen, onChoose, level = 0 } ) {
	return terms.map( ( term ) => (
		<div key={ term.id }>
			<TermRadio
				group={ group }
				value={ term.id }
				label={ decodeEntities( term.name ) }
				level={ level }
				checked={ chosen === term.id }
				onChoose={ onChoose }
			/>
			{ term.children.length > 0 && (
				<TermRadios
					terms={ term.children }
					group={ group }
					chosen={ chosen }
					onChoose={ onChoose }
					level={ level + 1 }
				/>
			) }
		</div>
	) );
}

/**
 * Radio term selector.
 *
 * @param {Object}      props        Props.
 * @param {string}      props.slug   Taxonomy slug.
 * @param {string|null} props.noTerm Label of the "No term" choice, or null when a term is required.
 * @return {Element|null} Selector.
 */
export default function RadioTermSelector( { slug, noTerm } ) {
	const [ adding, setAdding ] = useState( false );
	const [ formName, setFormName ] = useState( '' );
	const [ formParent, setFormParent ] = useState( '' );
	const [ showForm, setShowForm ] = useState( false );
	const [ filterValue, setFilterValue ] = useState( '' );

	const {
		hasCreateAction,
		hasAssignAction,
		terms,
		loading,
		availableTerms,
		taxonomy,
	} = useSelect(
		( select ) => {
			const { getCurrentPost, getEditedPostAttribute } =
				select( editorStore );
			const { getEntityRecord, getEntityRecords, isResolving } =
				select( coreStore );
			const _taxonomy = getEntityRecord( 'root', 'taxonomy', slug );
			const post = getCurrentPost();
			return {
				hasCreateAction: _taxonomy
					? !! post._links?.[
							'wp:action-create-' + _taxonomy.rest_base
						]
					: false,
				hasAssignAction: _taxonomy
					? !! post._links?.[
							'wp:action-assign-' + _taxonomy.rest_base
						]
					: false,
				terms: _taxonomy
					? ( getEditedPostAttribute( _taxonomy.rest_base ) ??
						EMPTY_ARRAY )
					: EMPTY_ARRAY,
				loading: isResolving( 'getEntityRecords', [
					'taxonomy',
					slug,
					DEFAULT_QUERY,
				] ),
				availableTerms:
					getEntityRecords( 'taxonomy', slug, DEFAULT_QUERY ) ||
					EMPTY_ARRAY,
				taxonomy: _taxonomy,
			};
		},
		[ slug ]
	);

	const { editPost } = useDispatch( editorStore );
	const { saveEntityRecord } = useDispatch( coreStore );
	const { createErrorNotice } = useDispatch( noticesStore );

	const termsTree = useMemo(
		() => buildTermsTree( availableTerms ),
		[ availableTerms ]
	);
	const shownTerms = useMemo(
		() => filterTermsTree( termsTree, filterValue ),
		[ termsTree, filterValue ]
	);

	useEffect( () => {
		if ( '' === filterValue ) {
			return;
		}
		const count = countTerms( shownTerms );
		speak(
			sprintf(
				/* translators: %d: number of results. */
				_n(
					'%d result found.',
					'%d results found.',
					count,
					'simple-taxonomy-refreshed'
				),
				count
			),
			'polite'
		);
	}, [ shownTerms, filterValue ] );

	if ( ! hasAssignAction ) {
		return null;
	}

	const chosen = terms.length > 0 ? terms[ 0 ] : 0;
	const choose = ( termId ) =>
		editPost( {
			[ taxonomy.rest_base ]: termId ? [ termId ] : [],
		} );

	const onAddTerm = async ( event ) => {
		event.preventDefault();
		if ( '' === formName || adding ) {
			return;
		}
		const existing = findTerm( availableTerms, formParent, formName );
		if ( existing ) {
			choose( existing.id );
			setFormName( '' );
			setFormParent( '' );
			return;
		}
		setAdding( true );
		let newTerm;
		try {
			newTerm = await saveEntityRecord(
				'taxonomy',
				slug,
				{ name: formName, parent: formParent ? formParent : undefined },
				{ throwOnError: true }
			);
		} catch ( error ) {
			createErrorNotice( error.message, { type: 'snackbar' } );
			setAdding( false );
			return;
		}
		speak(
			sprintf(
				/* translators: %s: term name (the taxonomy's singular label). */
				__( '%s added', 'simple-taxonomy-refreshed' ),
				taxonomy?.labels?.singular_name ??
					__( 'Term', 'simple-taxonomy-refreshed' )
			),
			'assertive'
		);
		setAdding( false );
		setFormName( '' );
		setFormParent( '' );
		choose( newTerm.id );
	};

	const label = ( key, fallback ) => taxonomy?.labels?.[ key ] ?? fallback;
	const addLabel = label(
		'add_new_item',
		__( 'Add Term', 'simple-taxonomy-refreshed' )
	);
	const nameLabel = label(
		'new_item_name',
		__( 'New Term Name', 'simple-taxonomy-refreshed' )
	);
	const parentLabel = label(
		'parent_item',
		__( 'Parent Term', 'simple-taxonomy-refreshed' )
	);
	const searchLabel = label(
		'search_items',
		__( 'Search Terms', 'simple-taxonomy-refreshed' )
	);
	const groupLabel =
		taxonomy?.name ?? __( 'Terms', 'simple-taxonomy-refreshed' );
	const group = `staxo-radio-${ slug }`;

	return (
		<div className="staxo-radio-terms">
			{ availableTerms.length >= MIN_TERMS_COUNT_FOR_FILTER &&
				! loading && (
					<SearchControl
						__nextHasNoMarginBottom
						label={ searchLabel }
						placeholder={ searchLabel }
						value={ filterValue }
						onChange={ setFilterValue }
					/>
				) }
			{ loading && <Spinner /> }
			<div
				className="editor-post-taxonomies__hierarchical-terms-list staxo-radio-terms__list"
				role="radiogroup"
				aria-label={ groupLabel }
			>
				{ null !== noTerm && '' === filterValue && (
					<TermRadio
						group={ group }
						value={ 0 }
						label={ noTerm }
						level={ 0 }
						checked={ 0 === chosen }
						onChoose={ choose }
					/>
				) }
				<TermRadios
					terms={ shownTerms }
					group={ group }
					chosen={ chosen }
					onChoose={ choose }
				/>
			</div>
			{ ! loading && hasCreateAction && (
				<Button
					__next40pxDefaultSize
					variant="link"
					className="editor-post-taxonomies__hierarchical-terms-add"
					aria-expanded={ showForm }
					onClick={ () => setShowForm( ! showForm ) }
				>
					{ addLabel }
				</Button>
			) }
			{ showForm && (
				<form className="staxo-radio-terms__add" onSubmit={ onAddTerm }>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ nameLabel }
						value={ formName }
						onChange={ setFormName }
						required
					/>
					{ availableTerms.length > 0 && (
						<TreeSelect
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ parentLabel }
							noOptionLabel={ `— ${ parentLabel } —` }
							onChange={ setFormParent }
							selectedId={ formParent }
							tree={ termsTree }
						/>
					) }
					<Button
						__next40pxDefaultSize
						variant="secondary"
						type="submit"
						isBusy={ adding }
					>
						{ addLabel }
					</Button>
				</form>
			) }
		</div>
	);
}
