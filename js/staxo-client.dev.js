/**
 * Placeholder for control options and error/warning message texts.
 */

 var tax_cntl = [];

/**
 * Routine to retrieve the row of tax_cntl being processed.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 * @return string[] List of applicable parameters.
 */
 function get_cntl( tax_slug ) {
	for ( const cntl of tax_cntl ) {
		if ( cntl[0] === tax_slug ) {
			return cntl;
		}
	}
}

/**
 * Functions to support No term option.
 */

/**
 * Add No Term element.
 * 
 * @param HTML_Collection tax   The parent element of the taxonomy term list.
 * @param bool            terms_found Existing terms found ( so No Terms not checked).
 */
function add_nt_element( tax, terms_found ) {
	// check if already added.
	if ( tax.classList.contains("NoTerm") ) {
		return true;
	}
	let inp = tax.getElementsByTagName("li");
	// create clone.
	let no_term     = inp[0].cloneNode( true );
	let no_term_id  = no_term.getAttribute( "id" );
	let no_term_inp = no_term.getElementsByTagName( "input" )[0];
	if ( undefined === no_term_inp ) {
		return false;
	}
	// remove any children terms.
	let child_list = no_term.getElementsByTagName( "ul" );
	for ( let child_ul of child_list ){
		no_term.removeChild( child_ul );
	}
	// reset id to a unique value.
	let no_term_val = no_term_inp.value;
	no_term.id     = no_term_id.replace( "-"+no_term_val, "--1" );
	no_term_inp.id = no_term_inp.id.replace( "-"+no_term_val, "--1" );
	no_term_inp.checked = ! terms_found;
	no_term_inp.value = -1;
	let no_term_lbl = no_term.getElementsByTagName( "label" )[0];
	let no_term_txt = no_term_lbl.lastChild.data.replace( /[\n\r\t]/g, "");
	no_term_lbl.lastChild.data = no_term_lbl.lastChild.data.replace( no_term_txt, " No Term" );
	inp[0].parentNode.insertBefore( no_term, inp[0] );
	// add class to denote treated.
	tax.classList.add("NoTerm"); 
	return true;
}

/**
 * Process No Term.
 * 
 * @param string tax_slug    The taxonomy slug for the taxonomy.
 * @param bool   terms_found Existing terms found ( so No Terms not checked).
 */
function process_no_term( tax_id, terms_found ) {
	// minimum is set to 0, add no term.
	let tax = document.getElementById( tax_id );
	if ( null === tax ) {
		return false;
	}

	return add_nt_element( tax, terms_found );
}

/**
 * Add No Term.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 */
function add_no_term( tax_slug ) {
	cntl = get_cntl( tax_slug );
	// bypass for non-zero minimum or non-hierarchical.
	if ( 0 !== cntl[2] || 0 === cntl[6] ) {
		return;
	}

	// are there existing terms.
	terms_count = false;
	let terms = document.getElementsByName( "tax_input["+tax_slug+"][]" );
	for ( let item of terms ) {
		if ( item.checked ) {
			terms_count = true;
		}
	}
	
	// minimum is set to 0, add no term. Could either one list or twp (popular and all).
	process_no_term( tax_slug+"-pop", terms_count );
	process_no_term( tax_slug+"-all", terms_count );
	process_no_term( "taxonomy-"+tax_slug, terms_count );
}

/**
 * Functions for converting checkbox to radio buttons in post edit screens.
 */

/**
 * Converts checkbox to radio buttons.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 */
function chk_radio_client( tax_slug ) {
		let tax = document.getElementById( "taxonomy-"+tax_slug );
		let inp = tax.getElementsByTagName("input");
		let changed = false;
		for ( let item of inp ) {
				// avoid updating hidden one.
				if ( item.type === "checkbox" ) {
						item.type = "radio";
						item.setAttribute('role', 'radio');
						item.addEventListener('click', event => {
								adj_radio_client( tax_slug, item.value );
						});
						item.addEventListener('keypress', event => {
								adj_radio_client( tax_slug, item.value );
						});
						changed = true;
				}
		}
		if ( changed ) {
			// we would have got here initially or if one was added.
			hier_cntl_check( tax_slug );
		}
}

/**
 * Keeps the popular and the all sub-panels in line.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 * @param int    val      The term id being selected.
 */
function adj_radio_client( tax_slug, val) {
		// act over both lists.
		let tax = document.getElementById( "taxonomy-"+tax_slug );
		let inp = tax.getElementsByTagName("input");
		for ( let i in inp ) {
				inp[i].checked = false;
				if ( inp[i].value === val ) {
						inp[i].checked = true;
				}
		}
}

/**
 * Function to add the event listeners on document load.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 */
function dom_radio_client( tax_slug ) {
	add_no_term( tax_slug );
	chk_radio_client( tax_slug );

	let tax_pop = document.getElementById( tax_slug+"-pop" );
	tax_pop.setAttribute('role', 'radiogroup');//
	let tax_all = document.getElementById( tax_slug+"-all" );
	tax_all.setAttribute('role', 'radiogroup');//

	let sub = document.getElementById( tax_slug+"-add-submit" );
	sub.addEventListener('click', event => {
		adj_radio_client( tax_slug, -1);
	});
	sub.addEventListener('keypress', event => {
		adj_radio_client( tax_slug, -1);
	});

	// Select the node that will be observed for mutations
	const targetNode = document.getElementById( tax_slug+"-all" );

	// Options for the observer (which mutations to observe)
	const config = { childList: true, subtree: true };

	// Callback function to execute when mutations are observed
	const callback = function(mutationsList, observer) {
		// on any change call the check code once.
		chk_radio_client( tax_slug );
	};

	// Create an observer instance linked to the callback function
	const observer = new MutationObserver(callback);

	// Start observing the target node for configured mutations
	observer.observe(targetNode, config);
}

/**
 * Functions for converting checkbox to radio buttons in quick edit screens.
 * 
 * Note that the standard processes work by looking for checkboxes so all interactions 
 * need to convert back to checkbox before standard processing and reconvert before display.
 */

/**
 * Writes an error message in the quick edit part..
 * 
 * @param HTML_Collection item The list item output.
 * @param string          tax  The taxonomy involved.
 * @param string          txt  The message text to output.
 */
function qe_error_set_msg( item, tax, txt ) {
	// find error item. if hidden then unhide it.
	let err = item.getElementsByClassName( "notice-error" );
	if ( err ) {
		for( let line of err ) {
			// Is it mine.
			if ( line.classList.contains( tax+"-err" ) ) {
				// remove hidden if there.
				if ( line.classList.contains( "hidden" ) ) {
					line.classList.remove( "hidden" );
				}
				let msg = line.getElementsByTagName( "p" );
				msg[0].innerHTML = txt;
				item.getElementsByClassName( "save" )[0].setAttribute("disabled", 'disabled');
				return;
			}
		}
		// WP creates one which we don't use, add a new one.
		let note_div = document.createElement( "div" );
		note_div.classList.add( "notice", "notice-error", "notice-alt", "inline", "staxo", tax+"-err" );
		let note_p = document.createElement( "p" );
		note_p.classList.add( "error" );
		note_p.innerHTML = txt;
		note_div.appendChild( note_p );
		// find the submit element - only one expected.
		let sub = item.getElementsByClassName( "submit" );
		sub[0].appendChild( note_div );
		item.getElementsByClassName( "save" )[0].setAttribute("disabled", 'disabled');
	}
}

/**
 * Clears an error message in the quick edit part..
 * 
 * @param HTML_Collection item The list item output.
 * @param string          tag  The taxonomy involved.
 */
function qe_error_clear_msg( item, tag ) {
	// find error item. if hidden then unhide it.
	let err = item.getElementsByClassName( "notice-error" );
	if ( err ) {
		// any staxo not hidden.
		all_hidden = true;
		for( let line of err ) {
			// Is it mine.
			if ( line.classList.contains( tag+"-err" ) ) {
				// add hidden if not there.
				if ( ! line.classList.contains( "hidden" ) ) {
					line.classList.add( "hidden" );
				}
				let msg = line.getElementsByTagName( "p" );
				msg[0].innerHTML = "";
			} else {
				if ( line.classList.contains( "staxo" ) && ! line.classList.contains( "hidden" ) ) {
					all_hidden = false;
				}	
			}
		}
		if ( all_hidden ) {
			item.getElementsByClassName( "save" )[0].removeAttribute("disabled");
		}
	}
}

/**
 * Functions for converting checkbox to radio buttons in quick edit screens.
 * 
 * Note that the standard processes work by looking for checkboxes so all interactions 
 * need to convert back to checkbox before standard processing and reconvert before display.
 */

/**
 * Converts checkbox to radio buttons.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 */
 function chk_qe_radio_client( tax_slug ) {
	let tag = event.target.tagName.toLowerCase();
	let cls = event.target.classList;
	// Choose the events to check.
	// Button is on QuickEdit Edit entry and Save exit.
	// Option is for Post status change.
	// Input is the taxonomy input elements for hierarchical taxonomies.
	if ( tag === "button" && ! cls.contains( "editinline" ) && ! cls.contains( "save" ) ) {
		return;
	} else if ( tag === "option" && event.target.parentNode.name !== "_status" ) {
		return;
	} else if ( tag === "input" && event.target.name !== "tax_input["+tax_slug+"][]" ) {
		return;
	}
	// open rows, look to validate the qe row.
	let open = document.getElementsByClassName( "inline-edit-row" );
	for( let item of open ) {
		if ( item.id.substring(0,5) !== 'edit-' ) {
			continue;
		}
		// get post to see taxonomies initial state only).
		let post = document.getElementById( "post-" + item.id.substring(5) );
		let taxs = post.getElementsByClassName( "taxonomy-"+tax_slug );

		// more than one on entry means we can't change to radio.
		let chg = taxs[0].getElementsByTagName("a").length;
		let cntl = get_cntl( tax_slug );
		// If initial state more than one, then not radio,. Now need to look at actual taxonomy.
		let lst = item.getElementsByClassName( tax_slug+"-checklist" );
		if ( chg < 2 ) {
			lst[0].setAttribute('role', 'radiogroup');
		}
		// process no term.
		if ( 0 === cntl[2] ) {
			add_nt_element( lst[0], (chg > 0) );
		}
		let inp = lst[0].getElementsByTagName("input");
		let multi = 0;
		for ( let tax of inp ) {
			if ( tax.checked ) {
				multi++;
			}
			// change to radio if not in error.
			if ( chg < 2 && tax.type === "checkbox" ) {
				tax.type = "radio";
				tax.setAttribute('role', 'radio');
			}
		}

		// check post_status.
		var stat;
		if ( tag === "option" ) {
			stat = event.target.value;
			// clear any error message but it may be put back.
			qe_error_clear_msg( item, tax_slug );
		} else {
			const cfix = item.getElementsByClassName( "inline-edit-status" )[0];
			stat = cfix.getElementsByTagName("select")[0].value;
		}
		if ( "new" === stat || "auto-draft" === stat || "trash" === stat ) {
			return;
		}
		if ( 1 === cntl[1] ) {
			// check published status only.
			if ( "publish" !== stat && "future" !== stat ) { 
				return;
			}
		}

		if ( multi > 1 ) { 
			// cannot convert. Output message.
			qe_error_set_msg( item, tax_slug, cntl[5] );
		} else if ( multi === 0 ) { 
			// Need to assign a value. Output message.
			qe_error_set_msg( item, tax_slug, cntl[3] );
		} else {
			// OK.
			qe_error_clear_msg( item, tax_slug );
			return;
		}
	}
}

/**
 * Reverts the radio to a checkbox.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 * @param int    id       The term id being selected.
 */
function rst_qe_radio_client( tax_slug, id ) {
	if ( event.target.tagName.toLowerCase() !== "button") {
		return;
	}
	// open rows, look to change radio back to checkbox as standard processing assumes it is.
	let open = document.getElementsByClassName( "inline-edit-row" );
	for( let item of open ) {
		if ( item.id.substring(0,5) !== 'edit-' ) {
			continue;
		}
		// should we put out a message.
		chk_qe_radio_client( tax_slug );
		let lst = item.getElementsByClassName( tax_slug+"-checklist" );
		lst[0].removeAttribute('role');//
		let inp = lst[0].getElementsByTagName("input");
		for ( let tax of inp ) {
			if ( tax.type === "radio" ) {
				tax.type = "checkbox";
				tax.removeAttribute('role');
			}
		}
	}
}

/**
 * Function to add the event listeners on document load.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 */
function dom_qe_radio_client( tax_slug ) {
	const inp = document.getElementById("the-list");
	// add to parent to ensure elements attached to this are executed first.
	inp.parentElement.addEventListener('click', event => {
		chk_qe_radio_client( tax_slug );
	});
	inp.parentElement.addEventListener('keypress', event => {
		chk_qe_radio_client( tax_slug );
	});
	const rows = inp.getElementsByTagName("tr");
	const re = /[0-9]+$/g;
	for( let item of rows ) {
		let postId = item.id.match(re)[0];
			item.addEventListener('click', event => {
				rst_qe_radio_client( tax_slug, postId );
			});
	}
}

/**
 * Functions for processing term control.
 * 
 */

/**
 * Hierarchical controls.
 */

 /**
 * Function to count the number of taxonomy items checked on the post.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 * @return int Number of checked items for given taxonomy.
 */
function hier_tax_count( tax_slug ) {
	let tax = document.getElementById( "taxonomy-"+tax_slug );
	let inp = tax.getElementsByTagName("input");
	let i, v, arr = [];
	for ( i in inp ) {
		if ( inp[i].checked ) {
			v = inp[i].value;
			if ( v > 0 && ! arr.includes( v )) {
				arr.splice(0, 0, v);
			}
		}
	}
	return arr.length;
}

 /**
 * Function to count the number of taxonomy items checked on the post.
 * 
 * @param string  tax_slug The taxonomy slug for the taxonomy.
 * @param boolean bail     Whether to stop processing if outside bounds.
 */
function hier_cntl_check( tax_slug, bail = false ) {
	const cntl = get_cntl( tax_slug );
	// check post_status.
	const stat = document.getElementById("post_status").value;
	if ( "new" === stat || "auto-draft" === stat || "trash" === stat ) {
		return;
	}
	if ( 1 === cntl[1] ) {
		// check published status only.
		if ( "publish" !== stat && "future" !== stat ) { 
			return;
		}
	}
	let cnt = hier_tax_count( tax_slug );
	let err = false;

	// if minimum defined, check value.
	if ( null !== cntl[2] ) {
		if ( cnt < cntl[2] ) {
			set_errblock( tax_slug, cntl[3] );
			err = true;
		}
	}

	// if maximum defined, check value.
	if ( null !== cntl[4] ) {
		if ( cnt > cntl[4] ) {
			set_errblock( tax_slug, cntl[5] );
			err = true;
		}
	}

	if (! err ) {
		clear_errblock( tax_slug );
	}

	if (err && bail) {
		event.stopPropagation();
		event.preventDefault();
	}
}

/**
 * Function to add the event listeners on document load.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 */
function dom_hier_cntl_check( tax_slug ) {
	let tax = document.getElementById( "taxonomy-"+tax_slug );
	let inp = tax.getElementsByTagName("input");
	for( let item of inp) {
		item.addEventListener('click', event => {
			hier_cntl_check( tax_slug );
		});
		item.addEventListener('blur', event => {
			hier_cntl_check( tax_slug );
		});
	}
	document.getElementById("publish").addEventListener('click', event => {
		hier_cntl_check( tax_slug, true);
	});
	var sp = document.getElementById("save-post");
	if (sp) {
		sp.addEventListener('click', event => {
			hier_cntl_check( tax_slug, true);
		});
		sp.addEventListener('keypress', event => {
			hier_cntl_check( tax_slug, true);
		});
	}
}

/**
 * Function to count the number of taxonomy items checked on the post.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 * @return int Number of checked items for given taxonomy.
 */
function tag_tax_count( tax_slug ) {
	// find taxonomy section.
	const sect = document.getElementById( tax_slug );
	const list = sect.getElementsByTagName('ul')[0];
	return list.getElementsByTagName('li').length;
}

 /**
 * Function to count the number of taxonomy items checked on the post.
 * 
 * @param string  tax_slug The taxonomy slug for the taxonomy.
 * @param boolean special  Whether to force stop processing if outside bounds.
 */
  function tag_cntl_check( tax_slug, special = false ) {
	// check post_status.
	var stat = document.getElementById("post_status").value;
	if ( "new" === stat || "auto-draft" === stat || "trash" === stat ) {
		return;
	}
	const cntl = get_cntl( tax_slug );
	if ( 1 === cntl[1] ) {
		// check published status only.
		if ( "publish" !== stat && "future" !== stat ) { 
			return;
		}
	}

	// Ensure tag add readonly attribute remove, unless explicitly wanted (cloud may not exist).
	document.getElementById( "new-tag-"+tax_slug ).removeAttribute("readonly");
	document.getElementById( "link-"+tax_slug ).removeAttribute("disabled");
	let cloud = document.getElementById( "tagcloud-"+tax_slug );
	if ( null !== cloud ) {
		cloud.removeAttribute("disabled");
	}

	let cnt = tag_tax_count( tax_slug );
	let err = false;

	// if minimum defined, check value.
	if ( null !== cntl[2] ) {
		if ( cnt < cntl[2] ) {
			set_errblock( tax_slug, cntl[3] );
			err = true;
		}
	}

	// if maximum defined, check value.
	if ( null !== cntl[4] ) {
		if ( cnt >= cntl[4] ) {
			document.getElementById( "new-tag-"+tax_slug ).setAttribute("readonly", 'readonly');
			document.getElementById( "link-"+tax_slug ).setAttribute("disabled", 'disabled');
			if ( null !== cloud ) {
				cloud.setAttribute("disabled", 'disabled');
			}
		}
		if ( cnt > cntl[4] ) {
			set_errblock( tax_slug, cntl[5] );
			err = true;
		}
	}

	// remove any original error notice.
	if (! err ) {
		clear_errblock( tax_slug );
	}

	// special case when min = 1 and max = 1, then otherwise cannot change tag.
	if ( 1 === cntl[2] && 1 === cntl[4] && cnt >= 0 && cnt <= 2 && ! special ) {
		return;
	}

	if ( err ) {
		event.stopPropagation();
		event.preventDefault();
	}
}

/**
 * Function to add the event listeners on document load.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 */
function dom_tag_cntl_check( tax_slug ) {
	// call these with special = true to force error processing.
	let elt = document.getElementById("publish");
	if ( null !== elt ) {
		elt.addEventListener('click', event => { tag_cntl_check( tax_slug, true ); });
	}
	elt = document.getElementById("save-post");
	if ( null !== elt ) {
		elt.addEventListener('click', event => { tag_cntl_check( tax_slug, true ); });
		elt.addEventListener('keypress', event => { tag_cntl_check( tax_slug, true ); });
	}
	// Select the node that will be observed for mutations
	let tag = document.getElementById( tax_slug );
	const targetNode = tag.getElementsByTagName('ul')[0];

	// Options for the observer (which mutations to observe)
	const config = { childList: true, subtree: true };

	// Callback function to execute when mutations are observed
	const callback = function(mutationsList, observer) {
		tag_cntl_check( tax_slug );
	};

	// Create an observer instance linked to the callback function
	const observer = new MutationObserver(callback);

	// Start observing the target node for configured mutations
	observer.observe(targetNode, config);
}

/**
 * Anonymous function to process limit for a taxonomy in block screens.
 * 
 * @param object wp   The wp object from the window.
 * @param string slug The taxonomy slug for the taxonomy.
 */
 let block_limit = ( function( wpx, slug ) { 
	const wp = wpx;
	const { select, dispatch, subscribe } = wp.data;
	const tax_slug = slug;
	const cntl = get_cntl( tax_slug );
	console.log(cntl);

	let locked = false;
	let nopubl = false;

	function block_taxonomy( event ) {
		if ( event.target.tagName.toLowerCase() !== 'button' ) {
			return;
		}
		var cn = event.target.className;
		var isPublish = cn.includes('editor-post-publish-button__button');
		if ( isPublish && ( nopubl || locked ) ) {
			alert('Cannot Publish due to taxonomy restrictions');
			event.stopPropagation();
			event.preventDefault();
			event.target.disabled = true;
		}
	}
	function check_taxonomy( stat, cnt ) {
		let check = ( 1 === cntl[1] ? [ 'publish', 'future' ].includes(stat) : ! [ 'new', 'auto-draft', 'trash' ].includes(stat) );
		let publishd = ['publish', 'future'].includes(stat);
		let err_text = '';
		// if minimum defined, check value.
		if ( null !== cntl[2] ) {
			if ( cnt < cntl[2] ) {
				err_text = cntl[3];
			}
		}
			// if maximum defined, check value.
		if ( null !== cntl[4] ) {
			if ( cnt > cntl[4] ) {
				err_text = cntl[5];
			}
		}

		if ( check && err_text ) {
			// no save/switch draft.
			var save = document.getElementsByClassName('editor-post-save-draft');
			if (save.length > 0) save[0].disabled = true;
			save = document.getElementsByClassName('editor-post-switch-to-draft');
			if (save.length > 0) save[0].disabled = true;
			// show notice.
			dispatch( 'core/notices' ).createNotice(
				'error',
				err_text,
				{
					id: 'str_notice_'+tax_slug,
					isDismissible: false,
				}
			);
	
			if ( ! publishd ) {
				if ( ! locked ) {
					// Make sure post cannot be saved, by adding a save lock.
					locked = true;
					dispatch( 'core/editor' ).lockPostSaving( 'str_'+tax_slug+'_lock' );
				}
			}
	
			if ( ! nopubl ) {
				nopubl = true;
				var sub = document.getElementsByClassName('editor-post-publish-button__button');
				if (sub.length > 0) sub[0].disabled = true;
				dispatch( 'core/edit-post' ).disablePublishSidebar;
				dispatch( 'core/editor' ).isPublishable = false;
			}
		} else {
			// no save draft.
			var save = document.getElementsByClassName('editor-post-save-draft');
			if (save.length > 0) save[0].disabled = false;
			save = document.getElementsByClassName('editor-post-switch-to-draft');
			if (save.length > 0) save[0].disabled = false;
			// remove notice.
			dispatch( 'core/notices' ).removeNotice( 'str_notice_'+tax_slug );
			// remove save lock.
			if ( locked ) {
				locked = false;
				dispatch( 'core/editor' ).unlockPostSaving( 'str_'+tax_slug+'_lock' );
			}
			// remove publish block.
			if ( nopubl ) {
				nopubl = false;
				var sub = document.getElementsByClassName('editor-post-publish-button__button');
				if (sub.length > 0) sub[0].disabled = false;
				dispatch( 'core/edit-post' ).enablePublishSidebar;
			}
		}
	}
	
	const getstat = () => select( 'core/editor' ).getEditedPostAttribute( 'status' );
	const gettax = () => select( 'core/editor' ).getEditedPostAttribute( tax_slug );	

	var btn = document.getElementById('editor');
	btn.addEventListener('click', event => {
		block_taxonomy( event );
	}, true);
	// get initial values from the initial call.
	let stat = cntl[6];
	let tax = cntl[2];
	check_taxonomy( stat, tax );
	subscribe( () => {
		const newstat = getstat();
		const statChanged = ( newstat !== stat );
		stat = newstat;
		const newtax = gettax();
		// takes a bit of time for the stores to be correctly populated.
		if ( undefined !== newtax ) {
			const taxChanged = ( newtax.length !== tax );
			tax = newtax.length;
			if ( taxChanged || statChanged ) check_taxonomy( stat, tax );
		}
	} );
} );


/**
 * Function to hide and clear the post taxonomy error block.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 * @return void.
 */
function clear_errblock( tax_slug ) {
	let errblock = document.getElementById( "err-"+tax_slug );
	if ( null !== errblock ) {
		// Hide and clear if block was showing.
		if ( ! errblock.classList.contains( "hidden" ) ) {
			errblock.classList.add( "hidden" );
			errblock.getElementsByTagName( "p" )[0].innerHTML = "";
		}
	}
}

/**
 * Function to set the post taxonomy error block.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 * @param string message  The error message.
 * @return void.
 */
 function set_errblock( tax_slug, message ) {
	let errblock = document.getElementById( "err-"+tax_slug );
	if ( null !== errblock ) {
		// Show it if hidden.
		if ( errblock.classList.contains( "hidden" ) ) {
			errblock.classList.remove( "hidden" );
		}
		// set message.
		errblock.getElementsByTagName( "p" )[0].innerHTML = message;
	}
}

/**
 *  Functions to process terms control on Quick Edit screens. 
 */

/**
 * Counts the taxonomy items and manages the error message.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 * @param int    hier     Taxonomy is hierarchical.
 */
 function proc_qe_tax_cntl( tax_slug, hier ) {
	let tag = event.target.tagName.toLowerCase();
	let cls = event.target.classList;
	// Choose the events to check.
	// Button is on QuickEdit Edit entry and Save exit.
	// Option is for Post status change.
	// Input is the taxonomy input elements for hierarchical taxonomies.
	// Textarea is the taxonomy box for tag taxonomies.
	if ( tag === "button" && ! cls.contains( "editinline" ) && ! cls.contains( "save" ) ) {
		return;
	} else if ( tag === "option" && event.target.parentNode.name !== "_status" ) {
		return;
	} else if ( tag === "input" && event.target.name !== "tax_input["+tax_slug+"][]" ) {
		return;
	} else if ( tag === "textarea" && ! cls.contains( "tax_input_"+tax_slug ) ) {
		return;
	}
	// open rows, look to validate the qe row.
	let open = document.getElementsByClassName( "inline-edit-row" );
	for( let item of open ) {
		if ( item.id.substring(0,5) !== 'edit-' ) {
			continue;
		}
		const cntl = get_cntl( tax_slug );
		// check post_status.
		var stat;
		if ( tag === "option" ) {
			stat = event.target.value;
			// clear any error message but it may be put back.
			qe_error_clear_msg( item, tax_slug );
		} else {
			const cfix = item.getElementsByClassName( "inline-edit-status" )[0];
			stat = cfix.getElementsByTagName("select")[0].value;
		}
		if ( "new" === stat || "auto-draft" === stat || "trash" === stat ) {
			return;
		}
		if ( 1 === cntl[1] ) {
			// check published status only.
			if ( "publish" !== stat && "future" !== stat ) { 
				return;
			}
		}

		// count items (different ways for hierarchical or not).
		let cnt = 0;
		if ( hier ) {
			let lst = item.getElementsByClassName( tax_slug+"-checklist" );
			let inp = lst[0].getElementsByTagName("input");
			for ( let tax of inp ) {
				if ( tax.checked ) {
					cnt++;
				}
			}
		} else {
			let lst = item.getElementsByClassName( "tax_input_"+tax_slug );
			let inp = lst[0].value;
			if ( inp.length === 0 ) {
				cnt = 0;
			} else {
				cnt = ( inp.match( /,/g) || [] ).length + 1;
			}
		}
		let err = false;
	
		// if minimum defined, check value.
		if ( null !== cntl[2] ) {
			if ( cnt < cntl[2] ) {
				qe_error_set_msg( item, tax_slug, cntl[3] );
				err = true;
			}
		}
	
		// if maximum defined, check value.
		if ( null !== cntl[4] ) {
			if ( cnt > cntl[4] ) {
				qe_error_set_msg( item, tax_slug, cntl[5] );
				err = true;
			}
		}
	
		if ( ! err ) {
			// no error, clear message.
			qe_error_clear_msg( item, tax_slug );
		}
	}
}

/**
 * Function to add the event listeners on quick edit load.
 * 
 * @param string tax_slug The taxonomy slug for the taxonomy.
 * @param int    hier     Taxonomy is hierarchical.
 */
function dom_qe_cntl_check( tax_slug, hier ) {
	const inp = document.getElementById("the-list");
	// add to parent to ensure elements attached to this are executed first.
	inp.parentElement.addEventListener('click', event => {
		proc_qe_tax_cntl( tax_slug, hier );
	});
	inp.parentElement.addEventListener('focusout', event => {
		proc_qe_tax_cntl( tax_slug, hier );
	});
}
