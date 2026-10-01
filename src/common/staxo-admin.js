/* exported str_admin_init, checkNameSet, linkAdm, ariaChk, hideSel, ccSel, cchSel, checkMinMax */

/*
 * Admin screen scripts. Functions listed above are called from inline handlers output by PHP.
 */

let panels, tabs, cb_types, cc_types, cc_hards;

// For easy reference
const keys = {
	end: 35,
	home: 36,
	left: 37,
	right: 39,
};

// Add or subtract depending on key pressed
const direction = {
	37: -1,
	39: 1,
};

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
		tabs[ i ].addEventListener( 'keyup', keyupEventListener );
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

// Handle keydown on tabs
function keydownEventListener( event ) {
	const key = event.keyCode;
	switch ( key ) {
		case keys.end:
			event.preventDefault();
			// Activate last tab
			activateTab( tabs[ tabs.length - 1 ] );
			break;
		case keys.home:
			event.preventDefault();
			// Activate first tab
			activateTab( tabs[ 0 ] );
			break;
	}
}

// Only left and right arrow function.
function keyupEventListener( event ) {
	const key = event.keyCode;
	if ( key === keys.left || key === keys.right ) {
		for ( let i = 0; i < tabs.length; ++i ) {
			tabs[ i ].addEventListener( 'focus', focusEventHandler );
		}
		if ( direction[ key ] ) {
			const target = event.target;
			if ( target.index !== undefined ) {
				if ( tabs[ target.index + direction[ key ] ] ) {
					tabs[ target.index + direction[ key ] ].focus();
				} else if ( key === keys.left ) {
					tabs[ tabs.length - 1 ].focus();
				} else if ( key === keys.right ) {
					tabs[ 0 ].focus();
				}
			}
		}
	}
}

// Activates any given tab panel
function activateTab( tab, setFocus = true ) {
	// Deactivate all other tabs
	deactivateTabs();

	// Remove tabindex attribute
	tab.removeAttribute( 'tabindex' );

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
		tabs[ t ].removeEventListener( 'focus', focusEventHandler );
	}

	for ( let p = 0; p < panels.length; p++ ) {
		panels[ p ].classList.add( 'is-hidden' );
	}
}

function focusEventHandler( event ) {
	const target = event.target;

	setTimeout( checkTabFocus, 250, target );
}

// Only activate tab on focus if it still has focus after the delay
function checkTabFocus( target ) {
	const focused = target.ownerDocument.activeElement;

	if ( target === focused ) {
		activateTab( target, false );
	}
}

function checkNameSet( evt ) {
	document.getElementById( 'submit' ).disabled =
		evt.currentTarget.value.length === 0;
	evt.stopPropagation();
}

function linkAdm( evt, objNo ) {
	evt.currentTarget.setAttribute( 'aria-checked', evt.currentTarget.checked );
	document.getElementById( 'admlist' + objNo ).disabled =
		evt.currentTarget.checked === false;
	if ( evt.currentTarget.checked === false ) {
		document.getElementById( 'admlist' + objNo ).checked = false;
		document
			.getElementById( 'admlist' + objNo )
			.setAttribute( 'aria-checked', 'false' );
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
			.setAttribute( 'aria-checked', 'false' );
		document
			.getElementById( 'cclist' + objNo )
			.removeAttribute( 'checked' );
	}
	evt.stopPropagation();
}

function ariaChk( evt ) {
	evt.currentTarget.setAttribute( 'aria-checked', evt.currentTarget.checked );
	if ( evt.currentTarget.checked ) {
		evt.currentTarget.setAttribute( 'checked', 'checked' );
	} else {
		evt.currentTarget.removeAttribute( 'checked' );
	}
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
	const tab_visible =
		document.getElementById( 'st_update_count_callback' ).value.length ===
		0;
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
		document.getElementById( 'cb_sel' ).checked = false;
		document
			.getElementById( 'cb_sel' )
			.setAttribute( 'aria-selected', 'false' );
		document.getElementById( 'cb_any' ).checked = false;
		document
			.getElementById( 'cb_any' )
			.setAttribute( 'aria-selected', 'false' );
		document.getElementById( 'cb_std' ).checked = true;
		document
			.getElementById( 'cb_std' )
			.setAttribute( 'aria-selected', 'false' );
		hideSel( evt, 0 );
	}
	evt.stopPropagation();
}

function hideSel( evt, objNo ) {
	if ( ! ( objNo in [ 0, 1, 2 ] ) ) {
		objNo = 0;
	}
	for ( let i = 0; i < cb_types.length; i++ ) {
		cb_types[ i ].setAttribute( 'tabindex', '-1' );
		cb_types[ i ].setAttribute( 'aria-selected', 'false' );
		cb_types[ i ].removeAttribute( 'checked' );
	}
	cb_types[ objNo ].setAttribute( 'tabindex', '0' );
	cb_types[ objNo ].setAttribute( 'aria-selected', 'true' );
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
		cc_types[ i ].setAttribute( 'tabindex', '-1' );
		cc_types[ i ].removeAttribute( 'checked' );
	}
	cc_types[ objNo ].setAttribute( 'tabindex', '0' );
	cc_types[ objNo ].setAttribute( 'checked', 'checked' );
	evt.stopPropagation();
}

function cchSel( evt, objNo ) {
	for ( let i = 0; i < cc_hards.length; i++ ) {
		cc_hards[ i ].setAttribute( 'tabindex', '-1' );
		cc_hards[ i ].removeAttribute( 'checked' );
	}
	cc_hards[ objNo ].setAttribute( 'tabindex', '0' );
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
