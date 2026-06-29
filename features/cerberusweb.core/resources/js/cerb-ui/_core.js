/*
 * cerb-ui — design-system component behaviors (plain JS, no jQuery).
 *
 * In DEVELOPMENT_MODE these per-component source files load individually for live editing.
 * For release, `composer build-js` concatenates + minifies them into resources/js/cerb-ui.js.
 * Components attach to the shared CerbUI global, e.g. CerbUI.Toggle.
 */
window.CerbUI = window.CerbUI || {};

// Metric data lives under two parallel namespaces, keyed the same way (a key suffix, or none = default):
//   numeric  -> data-value  / data-value-{key}   (e.g. 'size' -> data-value-size)   [used for math]
//   display  -> data-text   / data-text-{key}    (e.g. 'size' -> data-text-size)    [optional formatted text]
// Separate namespaces avoid the data-value-text vs. key="text" collision.
CerbUI._suffix = function(base, key) {
	return key ? (base + key.charAt(0).toUpperCase() + key.slice(1)) : base;
};
CerbUI.valueAttr = function(key) { return CerbUI._suffix('value', key); };
CerbUI.textAttr = function(key) { return CerbUI._suffix('text', key); };

// Small DOM utilities. These replace a few jQuery-UI helpers that were removed with that bundle. Each accepts a
// DOM element or a selector string (NOT a jQuery object).
CerbUI.utils = CerbUI.utils || {};

CerbUI.utils._el = function(el) {
	return (typeof el === 'string') ? document.querySelector(el) : el;
};

// Replaces jQuery-UI's $.fn.disableSelection / .enableSelection (make an element's text un-selectable, e.g. for
// click-to-select rows so a drag doesn't highlight text).
CerbUI.utils.disableSelection = function(el) {
	el = CerbUI.utils._el(el);
	if(!el || !el.style) return;
	el.style.userSelect = 'none';
	el.style.webkitUserSelect = 'none';
	el.style.MozUserSelect = 'none';
	el.style.msUserSelect = 'none';
	el.style.webkitTouchCallout = 'none';
};

CerbUI.utils.enableSelection = function(el) {
	el = CerbUI.utils._el(el);
	if(!el || !el.style) return;
	el.style.userSelect = '';
	el.style.webkitUserSelect = '';
	el.style.MozUserSelect = '';
	el.style.msUserSelect = '';
	el.style.webkitTouchCallout = '';
};

// Replaces jQuery-UI's `:focusable` selector: the visible, focusable descendants of a container, in DOM order.
CerbUI.utils.focusable = function(container) {
	container = CerbUI.utils._el(container);
	if(!container) return [];
	var sel = 'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]),'
		+ ' button:not([disabled]), iframe, object, embed, [tabindex], [contenteditable="true"]';
	return Array.prototype.slice.call(container.querySelectorAll(sel)).filter(function(el) {
		// Visible: has layout boxes (covers display:none ancestors + detached nodes)
		return el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length > 0;
	});
};
