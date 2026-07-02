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

// Async flow control — the small surface we used from the caolan/async library (now retired). A "task" is a
// node-style function `task(callback)` that eventually calls `callback(err, result)`; iteratees may call
// `callback()` with no args (treated as success). Both runners aggregate results in original order and stop
// on the first error.

// apply(fn, ...args) => a task `callback => fn(...args, callback)`. Handy for building task arrays.
CerbUI.utils.apply = function(fn) {
	const boundArgs = Array.prototype.slice.call(arguments, 1);
	return function(callback) { fn.apply(this, boundArgs.concat(callback)); };
};

// series(tasks, done): run tasks strictly one at a time; done(err, results).
CerbUI.utils.series = function(tasks, done) {
	done = done || function() {};
	const results = [];
	let i = 0;
	const next = function() {
		if(i >= tasks.length) return done(null, results);
		const idx = i++;
		tasks[idx](function(err, result) {
			if(err) return done(err, results);
			results[idx] = result;
			next();
		});
	};
	next();
};

// parallelLimit(tasks, limit, done): run tasks with at most `limit` in flight; done(err, results).
// The `inPump` guard keeps a synchronously-completing task (e.g. an early-exit that calls callback() inline)
// from re-entering the launch loop and recursing — the active loop just picks up the freed slot instead.
CerbUI.utils.parallelLimit = function(tasks, limit, done) {
	done = done || function() {};
	const results = [];
	let nextIndex = 0, running = 0, finished = false, inPump = false;
	if(!tasks.length) return done(null, results);
	const pump = function() {
		if(inPump) return;
		inPump = true;
		while(!finished && running < limit && nextIndex < tasks.length) {
			const idx = nextIndex++;
			running++;
			tasks[idx](function(err, result) {
				running--;
				if(finished) return;
				if(err) { finished = true; return done(err, results); }
				results[idx] = result;
				if(nextIndex >= tasks.length && running === 0) return done(null, results);
				pump();
			});
		}
		inPump = false;
	};
	pump();
};
