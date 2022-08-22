var i, tablist, vertical, panels, tabs, cb_types, cc_types, cc_hards;

// For easy reference
var keys = {
	end: 35,
	home: 36,
	left: 37,
	right: 39,
};

// Add or subtract depending on key pressed
var direction = {
	37: -1,
	39: 1,
};

function str_admin_init() {
	tablist  = document.querySelectorAll('[role="tablist"]');
	vertical = 'getAttribute' in tablist && tablist.getAttribute('aria-orientation') == 'vertical';
	panels   = document.querySelectorAll('[role="tabpanel"]');
	tabs     = document.querySelectorAll('[role="tab"]');
	cb_types = document.getElementsByName('st_cb_type');
	cc_types = document.getElementsByName('st_cc_type');
	cc_hards = document.getElementsByName('st_cc_hard');

	// Bind listeners
	for (var i = 0; i < tabs.length; ++i) {
		tabs[i].addEventListener('click', clickEventListener);
		tabs[i].addEventListener('keydown', keydownEventListener);
		tabs[i].addEventListener('keyup', keyupEventListener);
		// Build an array with all tabs (<button>s) in it
		tabs[i].index = i;
	}
}

// When a tab is clicked, activateTab is fired to activate it
function clickEventListener(event) {
	var tab = event.target;
	activateTab(tab, false);
}

// Handle keydown on tabs
function keydownEventListener(event) {
	var key = event.keyCode;
		switch (key) {
		case keys.end:
			event.preventDefault();
			// Activate last tab
			activateTab(tabs[tabs.length - 1]);
			break;
		case keys.home:
			event.preventDefault();
			// Activate first tab
			activateTab(tabs[0]);
			break;
	}
}

// Only left and right arrow function.
function keyupEventListener(event) {
	var key = event.keyCode;
	if (key === keys.left || key === keys.right) {
		for (var i = 0; i < tabs.length; ++i) {
			tabs[i].addEventListener('focus', focusEventHandler);
		}
		if (direction[key]) {
			var target = event.target;
			if (target.index !== undefined) {
				if (tabs[target.index + direction[key]]) {
					tabs[target.index + direction[key]].focus();
				} else if (key === keys.left) {
					tabs[tabs.length - 1].focus();
				} else if (key === keys.right) {
					tabs[0].focus();
				}
			}
		}
	}
}

// Activates any given tab panel
function activateTab(tab, setFocus) {
	setFocus = setFocus || true;
	// Deactivate all other tabs
	deactivateTabs();

    // Remove tabindex attribute
	tab.removeAttribute('tabindex');

	// Set the tab as selected
	tab.setAttribute('aria-selected', 'true');

	// Get the value of aria-controls (which is an ID)
	var controls = tab.getAttribute('aria-controls');

	// Remove is-hidden class from tab panel to make it visible
	document.getElementById(controls).classList.remove('is-hidden');

	if (controls == "adm_filter") {
		var i = document.getElementById("hierarchical").value;
		document.getElementById("st_adm_hier").disabled = ( i == 0 );
		document.getElementById("st_adm_depth").disabled = ( i == 0 );
	}

	// Set focus when required
	if (setFocus) {
		tab.focus();
	}
}

// Deactivate all tabs and tab panels
function deactivateTabs() {
	for (var t = 0; t < tabs.length; t++) {
		tabs[t].setAttribute('tabindex', '-1');
		tabs[t].setAttribute('aria-selected', 'false');
		tabs[t].removeEventListener('focus', focusEventHandler);
	}

	for (var p = 0; p < panels.length; p++) {
		panels[p].classList.add('is-hidden');
	}
}

function focusEventHandler(event) {
	var target = event.target;

	setTimeout(checkTabFocus, 250, target);
}

// Only activate tab on focus if it still has focus after the delay
function checkTabFocus(target) {
	var focused = document.activeElement;

    if (target === focused) {
		activateTab(target, false);
	}
}

function openTab(evt, tabName) {
	for (i = 0; i < panels.length; i++) {
		panels[i].style.display = "none";
	}
	for (i = 0; i < tabs.length; i++) {
		tabs[i].setAttribute('aria-selected', 'false');
	}

    document.getElementById(tabName).style.display = "block";
	if (tabName == "adm_filter") {
		var i = document.getElementById("hierarchical").value;
		document.getElementById("st_adm_hier").disabled = ( i == 0 );
		document.getElementById("st_adm_depth").disabled = ( i == 0 );
	}
	evt.currentTarget.setAttribute('aria-selected', 'true');
	evt.stopPropagation();
}

function checkNameSet(evt) {
	document.getElementById("submit").disabled = ( evt.currentTarget.value.length === 0 );
	evt.stopPropagation();
}

function linkAdm(evt, objNo) {
	evt.currentTarget.setAttribute('aria-checked', ( evt.currentTarget.checked ) );
	document.getElementById("admlist" + objNo).disabled = ( evt.currentTarget.checked === false );
	if (evt.currentTarget.checked === false) {
		document.getElementById("admlist" + objNo).checked = false;
		document.getElementById("admlist" + objNo).setAttribute('aria-checked', 'false');
		document.getElementById("admlist" + objNo).removeAttribute( 'checked' );
	}
	evt.stopPropagation();
}

function ariaChk(evt) {
	evt.currentTarget.setAttribute('aria-checked', ( evt.currentTarget.checked ) );
	if ( evt.currentTarget.checked ) {
		evt.currentTarget.setAttribute( 'checked', 'checked' );
	} else {
		evt.currentTarget.removeAttribute( 'checked' );
	}
}

function linkH(evt, objNo) {
	document.getElementById("st_adm_hier").disabled = (objNo === 0);
	document.getElementById("st_adm_depth").disabled = (objNo === 0);
	if (objNo === 0) {
		document.getElementById("st_adm_hier").value = 0;
		document.getElementById("st_adm_depth").value = 0;
	}
	evt.stopPropagation();
}

function hideCnt(evt) {
	var tab_visible = (document.getElementById("st_update_count_callback").value.length == 0);
	if (tab_visible) {
		document.getElementById("count_tab_0").classList.add('is-hidden');
		document.getElementById("count_tab_1").classList.remove('is-hidden');
	} else {
		document.getElementById("count_tab_0").classList.remove('is-hidden');
		document.getElementById("count_tab_1").classList.add('is-hidden');
		document.getElementById("cb_sel").checked = false;
		document.getElementById("cb_sel").setAttribute('aria-selected', 'false');
		document.getElementById("cb_any").checked = false;
		document.getElementById("cb_any").setAttribute('aria-selected', 'false');
		document.getElementById("cb_std").checked = true;
		document.getElementById("cb_std").setAttribute('aria-selected', 'false');
		hideSel(evt, 0);
	}
	evt.stopPropagation();
}

function hideSel(evt, objNo) {
	for (var i = 0; i < cb_types.length; i++) {
		cb_types[i].setAttribute('tabindex', '-1');
		cb_types[i].setAttribute('aria-selected', 'false');
		cb_types[i].removeAttribute('checked');
	}
	cb_types[objNo].setAttribute('tabindex', '0');
	cb_types[objNo].setAttribute('aria-selected', 'true');
	cb_types[objNo].setAttribute('checked', 'checked');
	if (objNo === 2) {
		document.getElementById("count_sel_0").classList.add('is-hidden');
		document.getElementById("count_sel_1").classList.remove('is-hidden');
	} else {
		document.getElementById("count_sel_0").classList.remove('is-hidden');
		document.getElementById("count_sel_1").classList.add('is-hidden');
	}
	evt.stopPropagation();
}

function ccSel(evt, objNo) {
	if (objNo === 0) {
		document.getElementById("control_tab_0").classList.remove('is-hidden');
		document.getElementById("control_tab_1").classList.add('is-hidden');
	} else {
		document.getElementById("control_tab_0").classList.add('is-hidden');
		document.getElementById("control_tab_1").classList.remove('is-hidden');
	}
	for (var i = 0; i < cc_types.length; i++) {
		cc_types[i].setAttribute('tabindex', '-1');
		cc_types[i].removeAttribute('checked');
	}
	cc_types[objNo].setAttribute('tabindex', '0');
	cc_types[objNo].setAttribute('checked', 'checked');
	evt.stopPropagation();
}

function cchSel(evt, objNo) {
	for (var i = 0; i < cc_hards.length; i++) {
		cc_hards[i].setAttribute('tabindex', '-1');
		cc_hards[i].removeAttribute('checked');
	}
	cc_hards[objNo].setAttribute('tabindex', '0');
	cc_hards[objNo].setAttribute('checked', 'checked');
	evt.stopPropagation();
}

function switchMinMax(evt) {
	var umin = (document.getElementById("st_cc_umin").value == 0);
	var umax = (document.getElementById("st_cc_umax").value == 0);
	document.getElementById("st_cc_min").disabled = umin;
	document.getElementById("st_cc_max").disabled = umax;
	evt.stopPropagation();
}

function checkMinMax(evt) {
	var minv = document.getElementById("st_cc_min").value;
	var maxv = document.getElementById("st_cc_max").value;
	if (minv > maxv && evt.currentTarget.id === "st_cc_min") {
		document.getElementById("st_cc_max").value = minv;
	}
    if (minv > maxv && evt.currentTarget.id === "st_cc_max") {
		document.getElementById("st_cc_min").value = maxv;
	}
	evt.stopPropagation();
}
