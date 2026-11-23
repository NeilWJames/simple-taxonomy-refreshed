/* exported str_admin_init, staxo_sortable, checkNameSet, linkAdm, hideSel, ccSel, cchSel, checkMinMax */

/*
 * Admin screen scripts. Functions listed above are called from inline handlers output by PHP.
 */

let panels, tabs, cb_types, cc_types, cc_hards;

/**
 * Initialise the admin screen once the DOM is loaded.
 *
 * Also used by the Taxonomy List Order page, which has none of the taxonomy form fields,
 * so every lookup must allow for missing elements.
 *
 * @param {Event} [evt] The DOMContentLoaded event (optional).
 */
function str_admin_init( evt ) {
	panels = document.querySelectorAll( '[role="tabpanel"]' );
	tabs = document.querySelectorAll( '[role="tab"]' );
	cb_types = document.getElementsByName( 'st_cb_type' );
	cc_types = document.getElementsByName( 'st_cc_type' );
	cc_hards = document.getElementsByName( 'st_cc_hard' );

	// Bind listeners
	for ( let i = 0; i < tabs.length; ++i ) {
		tabs[ i ].addEventListener( 'click', clickEventListener );
		tabs[ i ].addEventListener( 'keydown', keydownEventListener );
		// Build an array with all tabs (<button>s) in it
		tabs[ i ].index = i;
	}

	// Various linkages (only on the taxonomy form).
	const umin = document.getElementById( 'st_cc_umin' );
	const umax = document.getElementById( 'st_cc_umax' );
	if ( umin && umax ) {
		switchMinMax( evt );
		umin.addEventListener( 'change', switchMinMax );
		umax.addEventListener( 'change', switchMinMax );
	}
	const cbName = document.getElementById( 'st_update_count_callback' );
	if ( cbName ) {
		cbName.addEventListener( 'change', hideCnt );
		cbName.addEventListener( 'keydown', function ( e ) {
			if ( e.keyCode === 32 ) {
				e.preventDefault();
			}
		} );
	}

	// Keep the Admin List Filter fields in line with Hierarchical.
	// Only the custom taxonomy form has a select; the external form uses a hidden field.
	const hier = document.getElementById( 'hierarchical' );
	if ( hier && 'SELECT' === hier.tagName ) {
		hier.addEventListener( 'change', linkH );
	}

	// Specific action to display value of one attribute on another.
	const select = document.getElementById( 'show_ui' );
	if ( select ) {
		select.addEventListener( 'change', function ( event ) {
			const newText =
				event.target.options[ event.target.selectedIndex ].text;
			document.getElementById( 'show_ui_2' ).textContent = newText;
		} );
	}
}

// When a tab is clicked, activateTab is fired to activate it
function clickEventListener( event ) {
	const tab = event.target;
	activateTab( tab, false );
}

/**
 * Keyboard use of the tabs (WAI-ARIA tabs pattern, automatic activation).
 *
 * Only the selected tab is in the Tab order (PHP outputs tabindex -1 on the others);
 * Left and Right move to the previous or next tab (wrapping round), Home and End to the
 * first or last, and the tab reached is selected and its panel shown.
 *
 * @param {KeyboardEvent} event The keydown event.
 */
function keydownEventListener( event ) {
	const index = event.currentTarget.index;
	let target = null;
	switch ( event.key ) {
		case 'ArrowLeft':
			target = tabs[ ( index - 1 + tabs.length ) % tabs.length ];
			break;
		case 'ArrowRight':
			target = tabs[ ( index + 1 ) % tabs.length ];
			break;
		case 'Home':
			target = tabs[ 0 ];
			break;
		case 'End':
			target = tabs[ tabs.length - 1 ];
			break;
	}
	if ( null !== target ) {
		event.preventDefault();
		activateTab( target );
	}
}

// Activates any given tab panel
function activateTab( tab, setFocus = true ) {
	// Deactivate all other tabs
	deactivateTabs();

	// The selected tab is the one in the Tab order.
	tab.setAttribute( 'tabindex', '0' );

	// Set the tab as selected
	tab.setAttribute( 'aria-selected', 'true' );

	// Get the value of aria-controls (which is an ID)
	const controls = tab.getAttribute( 'aria-controls' );

	// Remove is-hidden class from tab panel to make it visible
	document.getElementById( controls ).classList.remove( 'is-hidden' );

	if ( controls === 'adm_filter' ) {
		const flat =
			0 === Number( document.getElementById( 'hierarchical' ).value );
		document.getElementById( 'st_adm_hier' ).disabled = flat;
		document.getElementById( 'st_adm_depth' ).disabled = flat;
	}

	// Set focus when required
	if ( setFocus ) {
		tab.focus();
	}
}

// Deactivate all tabs and tab panels
function deactivateTabs() {
	for ( let t = 0; t < tabs.length; t++ ) {
		tabs[ t ].setAttribute( 'tabindex', '-1' );
		tabs[ t ].setAttribute( 'aria-selected', 'false' );
	}

	for ( let p = 0; p < panels.length; p++ ) {
		panels[ p ].classList.add( 'is-hidden' );
	}
}

function checkNameSet( evt ) {
	document.getElementById( 'submit' ).disabled =
		evt.currentTarget.value.length === 0;
	evt.stopPropagation();
}

/**
 * A post type ticked or unticked in Objects: its Admin List Filter and Term Control boxes
 * are only available (and only ticked) while it is ticked.
 *
 * @param {Event}  evt   The click event.
 * @param {number} objNo The post type's position in the lists.
 */
function linkAdm( evt, objNo ) {
	document.getElementById( 'admlist' + objNo ).disabled =
		evt.currentTarget.checked === false;
	if ( evt.currentTarget.checked === false ) {
		document.getElementById( 'admlist' + objNo ).checked = false;
		document
			.getElementById( 'admlist' + objNo )
			.removeAttribute( 'checked' );
	}
	document.getElementById( 'cclist' + objNo ).disabled =
		evt.currentTarget.checked === false;
	if ( evt.currentTarget.checked === false ) {
		document.getElementById( 'cclist' + objNo ).checked = false;
		document
			.getElementById( 'cclist' + objNo )
			.removeAttribute( 'checked' );
	}
	evt.stopPropagation();
}

// Hierarchical changed: the hierarchical filter options only apply to hierarchical taxonomies.
function linkH( evt ) {
	const flat = 0 === Number( evt.currentTarget.value );
	const admHier = document.getElementById( 'st_adm_hier' );
	const admDepth = document.getElementById( 'st_adm_depth' );
	admHier.disabled = flat;
	admDepth.disabled = flat;
	if ( flat ) {
		admHier.value = 0;
		admDepth.value = 0;
	}
	evt.stopPropagation();
}

function hideCnt( evt ) {
	// Term Count options apply with no callback, or with WordPress's own
	// _update_post_term_count (it applies the post status filter).
	const callback = document
		.getElementById( 'st_update_count_callback' )
		.value.trim();
	const tab_visible =
		callback.length === 0 || callback === '_update_post_term_count';
	if ( tab_visible ) {
		document.getElementById( 'count_tab_0' ).classList.add( 'is-hidden' );
		document
			.getElementById( 'count_tab_1' )
			.classList.remove( 'is-hidden' );
	} else {
		document
			.getElementById( 'count_tab_0' )
			.classList.remove( 'is-hidden' );
		document.getElementById( 'count_tab_1' ).classList.add( 'is-hidden' );
		// Native radio buttons: checked is their state (aria-selected is not valid on them).
		document.getElementById( 'cb_sel' ).checked = false;
		document.getElementById( 'cb_any' ).checked = false;
		document.getElementById( 'cb_std' ).checked = true;
		hideSel( evt, 0 );
	}
	evt.stopPropagation();
}

function hideSel( evt, objNo ) {
	if ( ! ( objNo in [ 0, 1, 2 ] ) ) {
		objNo = 0;
	}
	// Native radio buttons manage their own focus (arrow keys) and state.
	for ( let i = 0; i < cb_types.length; i++ ) {
		cb_types[ i ].removeAttribute( 'checked' );
	}
	cb_types[ objNo ].setAttribute( 'checked', 'checked' );
	if ( objNo === 2 ) {
		document.getElementById( 'count_sel_0' ).classList.add( 'is-hidden' );
		document
			.getElementById( 'count_sel_1' )
			.classList.remove( 'is-hidden' );
	} else {
		document
			.getElementById( 'count_sel_0' )
			.classList.remove( 'is-hidden' );
		document.getElementById( 'count_sel_1' ).classList.add( 'is-hidden' );
	}
	evt.stopPropagation();
}

function ccSel( evt, objNo ) {
	if ( objNo === 0 ) {
		document
			.getElementById( 'control_tab_0' )
			.classList.remove( 'is-hidden' );
		document.getElementById( 'control_tab_1' ).classList.add( 'is-hidden' );
	} else {
		document.getElementById( 'control_tab_0' ).classList.add( 'is-hidden' );
		document
			.getElementById( 'control_tab_1' )
			.classList.remove( 'is-hidden' );
	}
	for ( let i = 0; i < cc_types.length; i++ ) {
		cc_types[ i ].removeAttribute( 'checked' );
	}
	cc_types[ objNo ].setAttribute( 'checked', 'checked' );
	evt.stopPropagation();
}

function cchSel( evt, objNo ) {
	for ( let i = 0; i < cc_hards.length; i++ ) {
		cc_hards[ i ].removeAttribute( 'checked' );
	}
	cc_hards[ objNo ].setAttribute( 'checked', 'checked' );
	evt.stopPropagation();
}

function switchMinMax( evt ) {
	const umin = 0 === Number( document.getElementById( 'st_cc_umin' ).value );
	const umax = 0 === Number( document.getElementById( 'st_cc_umax' ).value );
	document.getElementById( 'st_cc_min' ).disabled = umin;
	document.getElementById( 'st_cc_max' ).disabled = umax;
	if ( evt ) {
		evt.stopPropagation();
	}
}

function checkMinMax( evt ) {
	// Compare as numbers ("10" > "9" is false as strings).
	const minv = Number( document.getElementById( 'st_cc_min' ).value );
	const maxv = Number( document.getElementById( 'st_cc_max' ).value );
	if ( minv > maxv && evt.currentTarget.id === 'st_cc_min' ) {
		document.getElementById( 'st_cc_max' ).value = minv;
	}
	if ( minv > maxv && evt.currentTarget.id === 'st_cc_max' ) {
		document.getElementById( 'st_cc_min' ).value = maxv;
	}
	evt.stopPropagation();
}

/**
 * Sortable lists (Taxonomy List Order, and the export order on Configuration Export/Import).
 *
 * The list is output by PHP SimpleTaxonomyRefreshed_Admin::sortable_list(). Items can be dragged
 * (jQuery UI Sortable) or moved with their Move up and Move down buttons, so the order can be
 * set with a keyboard too. Each move by button is announced, and focus stays on the item.
 *
 * @param {string} list_id   The list element id.
 * @param {string} arr_id    The hidden field that receives the names, as JSON.
 * @param {string} submit_id The submit button to enable after a change (optional).
 */
function staxo_sortable( list_id, arr_id, submit_id ) {
	const list = document.getElementById( list_id );
	if ( null === list ) {
		return;
	}

	// The first item cannot go up, nor the last one down.
	const set_edges = function () {
		const items = list.children;
		for ( let i = 0; i < items.length; i++ ) {
			for ( const btn of items[ i ].querySelectorAll(
				'button[data-move]'
			) ) {
				const step = Number( btn.dataset.move );
				btn.disabled =
					( step < 0 && 0 === i ) ||
					( step > 0 && items.length - 1 === i );
			}
		}
	};

	const changed = function () {
		const names = [];
		for ( const item of list.children ) {
			names.push( item.dataset.name );
		}
		document.getElementById( arr_id ).value = JSON.stringify( names );
		if ( submit_id ) {
			const submit = document.getElementById( submit_id );
			if ( null !== submit ) {
				submit.disabled = false;
			}
		}
		set_edges();
	};

	list.addEventListener( 'click', function ( event ) {
		const btn = event.target.closest( 'button[data-move]' );
		if ( null === btn || ! list.contains( btn ) ) {
			return;
		}
		event.preventDefault();
		const item = btn.closest( 'li' );
		const step = Number( btn.dataset.move );
		const other =
			step < 0 ? item.previousElementSibling : item.nextElementSibling;
		if ( null === other ) {
			return;
		}
		if ( step < 0 ) {
			list.insertBefore( item, other );
		} else {
			list.insertBefore( other, item );
		}
		changed();

		// Moving the item can drop focus; keep it on the item, on the other button at an end.
		if ( btn.disabled ) {
			const rest = item.querySelector(
				'button[data-move]:not(:disabled)'
			);
			if ( null !== rest ) {
				rest.focus();
			}
		} else {
			btn.focus();
		}

		const position =
			Array.prototype.indexOf.call( list.children, item ) + 1;
		const message = ( list.dataset.moved || '' )
			.replace( '%1$s', item.dataset.name )
			.replace( '%2$s', String( position ) )
			.replace( '%3$s', String( list.children.length ) );
		if ( window.wp && window.wp.a11y && window.wp.a11y.speak ) {
			window.wp.a11y.speak( message );
		}
	} );

	// Dragging, for pointer users. Buttons do not start a drag (jQuery UI's default "cancel").
	if ( window.jQuery && window.jQuery.fn.sortable ) {
		window.jQuery( list ).sortable( {
			placeholder: 'ui-sortable-placeholder',
			update: changed,
		} );
	}

	set_edges();
}
