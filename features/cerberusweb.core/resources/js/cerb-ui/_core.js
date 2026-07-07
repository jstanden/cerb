/*
 * cerb-ui — design-system component behaviors (plain JS, no jQuery).
 *
 * In DEVELOPMENT_MODE these per-component source files load individually for live editing.
 * For release, `composer build-js` concatenates + minifies them into resources/js/cerb-ui.js.
 * Components attach to the shared CerbUI global, e.g. CerbUI.Toggle.
 */
window.CerbUI = window.CerbUI || {};

// The browser fires a benign, spec-defined ErrorEvent ("ResizeObserver loop completed with
// undelivered notifications." / "...loop limit exceeded") when an observer callback dirties
// layout mid-frame. It self-recovers and renders correctly — the message is pure console noise.
// It's dispatched to window (not thrown), so only a global error listener can silence it.
if(!CerbUI._resizeObserverErrorSuppressed) {
	CerbUI._resizeObserverErrorSuppressed = true;
	window.addEventListener('error', function(e) {
		const msg = e && e.message ? e.message : '';
		if(/ResizeObserver loop (completed with undelivered notifications|limit exceeded)/.test(msg)) {
			e.stopImmediatePropagation();
			e.preventDefault();
			return false;
		}
	});
}

// Metric data lives under two parallel namespaces, keyed the same way (a key suffix, or none = default):
//   numeric  -> data-value  / data-value-{key}   (e.g. 'size' -> data-value-size)   [used for math]
//   display  -> data-text   / data-text-{key}    (e.g. 'size' -> data-text-size)    [optional formatted text]
// Separate namespaces avoid the data-value-text vs. key="text" collision.
CerbUI._suffix = function(base, key) {
	return key ? (base + key.charAt(0).toUpperCase() + key.slice(1)) : base;
};
CerbUI.valueAttr = function(key) { return CerbUI._suffix('value', key); };
CerbUI.textAttr = function(key) { return CerbUI._suffix('text', key); };

// CerbUI.placeholders — bridges the widget-config placeholder strip (the peek's dashboard/toolbar.tpl) to modern
// editor components (SearchQuery, DataQuery, KataEditor, …). A peek marks a container with the static attribute
// [data-cerb-placeholders] and registers a provider on that element; a component whose wrapper is tagged
// `.placeholders` inside that scope floats the FULL strip (placeholders + test + help) beside it on focus.
// Outside a marked scope there's no provider, so nothing attaches. The provider is resolved lazily at focus time
// (registration may lag component construction). Tagging `.placeholders` is the ONLY opt-in — there's deliberately
// no auto-surfaced inline button just for living in a scope.
//   provider = { attach(hostEl, fieldEl, opts) }   // floats the strip beside hostEl, bound to fieldEl
CerbUI.placeholders = {
	_providers: new WeakMap(),

	// The nearest marked container above `el`, or null.
	scopeEl: function(el) {
		return (el && el.closest) ? el.closest('[data-cerb-placeholders]') : null;
	},

	// True when `el` lives inside a placeholder scope (decides whether a component shows its button).
	hasScope: function(el) {
		return !!CerbUI.placeholders.scopeEl(el);
	},

	// Register a provider for a marked container (called once per peek by the toolbar strip).
	register: function(containerEl, provider) {
		if(containerEl) CerbUI.placeholders._providers.set(containerEl, provider);
	},

	// Resolve the provider for `el` at call time.
	provider: function(el) {
		const scope = CerbUI.placeholders.scopeEl(el);
		return scope ? (CerbUI.placeholders._providers.get(scope) || null) : null;
	},

	// Attach the FULL floating strip (placeholders + test + help + tester) to a component. A wrapper tagged
	// `.placeholders` inside a scope calls this on focus; the provider floats its strip beside `hostEl` and
	// binds it to `fieldEl` (the component's named textarea — the tester reads its name + value).
	//   opts = { placement }  // 'auto'|'top'|'bottom'|'left'|'right'
	attach: function(hostEl, fieldEl, opts) {
		const p = CerbUI.placeholders.provider(hostEl);
		if(p && typeof p.attach === 'function') p.attach(hostEl, fieldEl, opts || {});
	},

	// Position a floating strip `panelEl` beside `hostEl` per a simple placement enum (default 'auto' =
	// below, flipping above when there's no room). Writes position:absolute offsets relative to panelEl's
	// offsetParent so the strip stays inside the <form> (the tester serializes the enclosing form).
	_floatPanel: function(panelEl, hostEl, placement) {
		if(!panelEl || !hostEl) return;
		placement = placement || 'auto';

		panelEl.classList.add('cerb-placeholder-menu--floating'); // solid surface + shadow (see _toolbar.scss)
		panelEl.style.position = 'absolute';
		panelEl.style.zIndex = panelEl.style.zIndex || '5';

		// Measure against the viewport, then convert to offsets within the positioned offsetParent.
		const host = hostEl.getBoundingClientRect();
		const pw = panelEl.offsetWidth || 320;
		const ph = panelEl.offsetHeight || 40;
		const vw = document.documentElement.clientWidth;
		const vh = document.documentElement.clientHeight;
		const gap = 4;

		const roomBelow = vh - host.bottom;
		const roomAbove = host.top;
		const roomRight = vw - host.right;
		const roomLeft = host.left;

		let side = placement;
		if(side === 'auto')
			side = (roomBelow >= ph + gap || roomBelow >= roomAbove) ? 'bottom' : 'top';
		else if(side === 'bottom' && roomBelow < ph + gap && roomAbove > roomBelow) side = 'top';
		else if(side === 'top' && roomAbove < ph + gap && roomBelow > roomAbove) side = 'bottom';
		else if(side === 'right' && roomRight < pw + gap && roomLeft > roomRight) side = 'left';
		else if(side === 'left' && roomLeft < pw + gap && roomRight > roomLeft) side = 'right';

		// Viewport-space top/left for the chosen side, then clamp on-screen.
		let vTop, vLeft;
		if(side === 'top')          { vTop = host.top - ph - gap;    vLeft = host.left; }
		else if(side === 'left')    { vTop = host.top;               vLeft = host.left - pw - gap; }
		else if(side === 'right')   { vTop = host.top;               vLeft = host.right + gap; }
		else /* bottom */           { vTop = host.bottom + gap;      vLeft = host.left; }

		vLeft = Math.max(gap, Math.min(vLeft, vw - pw - gap));
		vTop = Math.max(gap, Math.min(vTop, vh - ph - gap));

		// Convert viewport coords → offset within the positioned offsetParent (or the document).
		const parent = panelEl.offsetParent;
		if(parent && parent !== document.body && parent !== document.documentElement) {
			const pr = parent.getBoundingClientRect();
			panelEl.style.top = (vTop - pr.top + parent.scrollTop) + 'px';
			panelEl.style.left = (vLeft - pr.left + parent.scrollLeft) + 'px';
		} else {
			panelEl.style.top = (vTop + window.scrollY) + 'px';
			panelEl.style.left = (vLeft + window.scrollX) + 'px';
		}
	}
};

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
