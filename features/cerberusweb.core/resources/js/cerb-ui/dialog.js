/*
 * CerbUI.Dialog — a draggable / resizable / minimizable dialog (zero-dependency).
 *
 * Wraps an existing content element in a floating dialog appended to <body>. The content is restored to
 * its original DOM position on destroy(). Colors are tokens, so dark mode is automatic.
 *
 * Usage:
 *   const dlg = new CerbUI.Dialog(contentEl, { title: 'Ticket', header: 'bar' });
 *   dlg.open(); dlg.close(); dlg.minimize(); dlg.restore(); CerbUI.Dialog.from(contentEl);
 *
 * Minimize: a minimize button (alongside close) — or Shift+Esc on the topmost dialog — docks the dialog
 *   into a single shared tray button (a window
 *   icon + a count, titlebar-blue) fixed at the top-right of the page. Available on any header with controls
 *   — both 'bar' and 'floating' (the buttons inject into the floating cluster / content header) — i.e.
 *   default on except header:'none'. With a single dialog minimized, clicking the tray restores it directly
 *   (no menu). With two or more, clicking the tray opens a menu of the minimized dialogs' titles
 *   (`opts.title`, falling back to "Untitled" for headerless dialogs that don't set one); choosing one
 *   restores it (`restore()`) to the default top-center position. That menu also offers a "Restore all"
 *   item (chevron-down icon) that restores every minimized dialog (cascaded so they fan out instead of
 *   stacking exactly) and a "Close all" item (trash icon) that
 *   closes every minimized dialog via `close()` (so each onClose veto hook still runs). `onMinimize(bool)`
 *   fires true on minimize /
 *   false on restore. Modal dialogs are never minimizable (modal + minimize are mutually exclusive).
 *
 * AJAX popups — CerbUI.Dialog.fromAjax(request, opts):
 *   Builds the dialog DOM procedurally, shows a spinner, fetches HTML, and loads it as the content. The
 *   `request` mirrors the legacy genericAjaxPopup: a string ⇒ GET (ajax args), a FormData ⇒ POST. The
 *   helper fetches with an empty target (so it skips its own fade-out/fade-in over our spinner); the
 *   response is then injected with jQuery .html(), so any <script> in it runs under the page's CSP nonce
 *   — the canonical Cerb path (a raw fetch()+innerHTML would NOT run them).
 *   HTTP errors already raise a toast banner via the helper; the wrapper just closes the dialog (the old
 *   hookError behavior). `opts` are the constructor options below (title, header, namespace, modal,
 *   width, …) plus an optional onLoad(content, html). The popup is throwaway: it self-destroys on close.
 *   Loaded content resolves its own dialog with CerbUI.Dialog.from(anyDescendant) — e.g. a form inside it
 *   — then setTitle()/close() (the genericAjaxPopupFind replacement). Pass a `namespace` to make the popup a
 *   singleton: re-opening that namespace focuses the live dialog instead of fetching/stacking a duplicate
 *   (pass `replace:true` to take over instead). The old "share one shell/position" behavior is now `positionGroup`.
 *
 * Header chrome (`header` option):
 *   'bar'      — the classic Cerb title bar (accent background) with the title + controls; drag by the bar.
 *   'floating' — no bar; the controls dock into the content's own cerb-ui-header (`.cerb-ui-header--right`,
 *                created if absent), so they sit beside the content's chrome instead of overlapping it. With
 *                no content header to dock into, they fall back to a cluster floating top-right over the
 *                content. Drag via `dragHandle` (default [data-cerb-ui-dialog-drag]).
 *   'none'     — no controls at all; the content fully owns its chrome. Drag via `dragHandle`.
 *
 * Options: title, header ('bar'|'floating'|'none'), draggable, resizable, closable, minimizable, modal,
 *   spinner (fromAjax loading variant: 'spark' default | 'arc' | 'dots' | null ring),
 *   width, minWidth, minHeight, position {x,y}, namespace (singleton: re-open focuses the live dialog),
 *   replace (a same-namespace open takes over instead of focusing), positionGroup (siblings share one
 *   shell/position + close each other),
 *   fixed, scrollBody, closeOnEscape, closeWarnOnUnsavedChanges, dragHandle (selector), onOpen,
 *   onClose (return false to veto), onMinimize, onDragged, onResized. Also dispatches `cerb-ui-dialog:open`
 *   / `:close` on the content element.
 *
 * Unsaved-changes guard (`closeWarnOnUnsavedChanges`, default off):
 *   When on, the first time the user actually changes a tracked form control (typing, a toggle, a menu,
 *   a checkbox/radio, a select) flips the dialog "dirty". Any close after that — ESC, the (x) button, or
 *   the tray's Close-all — first asks "Discard changes?" (CerbUI.Confirm) before the onClose hook runs;
 *   Cancel keeps it open, OK proceeds. Merely *having* inputs never warns (unlike the legacy popups), and
 *   pre-filling values programmatically doesn't count. Add `data-cerb-ui-dialog-no-dirty` to a control (or
 *   any ancestor — e.g. a search form) to exclude it from tracking. A save-success path should call
 *   `markClean()` before/instead of `close()` so a successful save never trips the warning; `isDirty()`
 *   reports the current state. A dirty dialog left minimized in the tray also raises the browser's native
 *   leave-page prompt on reload / back-forward / close (custom modals aren't allowed during unload).
 *
 * Placement: opens centered horizontally, near the top (a one-titlebar-height gap), like the legacy
 *   genericAjaxPopup — never vertically centered, so tall/growing content extends downward rather than
 *   opening mid-screen. Pass `position {x,y}` to place it explicitly.
 *
 * Width: `width` defaults to 75% of the viewport, capped at 1100px (the cerb-ui-page max-width). An
 *   explicit `width` is honored as-is (even above the cap). On mobile (viewport ≤ 768px) every dialog is
 *   95% wide and width directives are ignored.
 *
 * Tall / growing dialogs:
 *   By default the dialog grows to fit its content (vertically) and never repositions; wide non-wrapping
 *   content (e.g. <pre> code) scrolls horizontally inside the body rather than spilling out of the frame.
 *   Since it's an out-of-flow absolute box it can't lengthen the document, so while open it bumps `body`'s
 *   min-height to cover its bottom (plus a titlebar-height margin) — the *page* scrolls to reveal it. A
 *   ResizeObserver re-syncs that as the content grows in place (accordion, inline search, async load).
 *   `scrollBody: true` instead caps the dialog to the viewport (a titlebar margin top + bottom, so the
 *   whole frame is visible) and scrolls its body internally. `reflow()` is a manual page re-sync hook.
 */
CerbUI.Dialog = class {
	static _uid = 0;
	static _zTop = 9000; // above .cerb-float (2500) / jQuery dialogs (~100); below tooltips/menus (10000+)
	static _instances = new WeakMap(); // keyed on the content element passed to the constructor
	static _byRoot = new WeakMap();    // keyed on the dialog root (.cerb-ui-dialog) for descendant lookups
	static _namespaces = new Map();    // singleton identity: one open dialog per namespace (focus, don't duplicate)
	static _positionGroups = new Map(); // shared shell/position: siblings hand off position + close each other
	static _pageDialogs = new Set();   // open dialogs that grow + page-scroll (not fixed, not scrollBody)
	static _origBodyMinHeight = null;  // body.style.minHeight before we touched it; restored when the set empties
	static _openDialogs = new Set();   // every open dialog — drives the viewport-resize reflow
	static _loading = null;            // the singleton loading overlay (CerbUI.Dialog.Loading)
	static _resizeTimer = null;        // debounce timer for the window-resize reflow (fires after resize ends)
	static _viewportResizeBound = false; // the window 'resize' listener is attached once, lazily
	static _MAX_WIDTH = 1100;          // default-width cap; mirrors .cerb-ui-page--max-width
	static _MOBILE_MAX = 768;          // mobile breakpoint (cerb-responsive.scss) — dialogs go 95% wide below it
	static _minimized = new Set();     // dialogs docked in the top-right tray
	static _CASCADE_STEP = 28;         // px offset per dialog when "Restore all" fans them out
	static _tray = null;               // the shared tray button (lazily built)
	static _trayMenu = null;           // the open tray menu, if any (so a re-click toggles it shut)
	static _unloadHandler = null;      // beforeunload guard, attached only while a dirty tray popup exists

	// True when any minimized tray popup has unsaved edits (and opted into the close warning) — these would
	// be silently lost on reload / back-forward / close, which the close guard never sees.
	static _anyUnsavedMinimized() {
		for(const d of CerbUI.Dialog._minimized)
			if(d._dirty && d.opts.closeWarnOnUnsavedChanges) return true;
		return false;
	}

	// Bring an already-open dialog to the user's attention (restore from the tray if minimized, else raise).
	// Used for singleton-namespace focus so a repeated open() focuses the live dialog instead of duplicating.
	static _focusExisting(d) {
		if(d.minimized) d.restore();
		else d.bringToFront();
		return d;
	}

	// After a dialog closes/minimizes, raise + focus the next-highest open dialog so keyboard focus (and the
	// Escape-to-close target) move to it — letting Escape cascade down a whole stack (ESC/ESC/ESC). No-op when
	// none remain. `except` skips a dialog mid-teardown (its _open may not be cleared yet).
	static _focusTopmost(except) {
		let top = null;
		for(const d of CerbUI.Dialog._openDialogs) {
			if(d === except || !d._open || d.minimized) continue;
			if(!top || parseInt(d.el.style.zIndex || '0', 10) > parseInt(top.el.style.zIndex || '0', 10))
				top = d;
		}
		if(top) { top.bringToFront(); top._focus(); }
	}

	// Keep a beforeunload listener attached only while such a popup exists. A permanently-registered
	// beforeunload disables the back-forward cache, so detach it the moment nothing qualifies.
	static _syncUnloadGuard() {
		const need = CerbUI.Dialog._anyUnsavedMinimized();
		if(need && !CerbUI.Dialog._unloadHandler) {
			CerbUI.Dialog._unloadHandler = (e) => {
				if(!CerbUI.Dialog._anyUnsavedMinimized()) return; // re-check at fire time
				// Custom modals are forbidden during unload — only the browser's generic prompt can show.
				e.preventDefault();
				e.returnValue = '';
			};
			window.addEventListener('beforeunload', CerbUI.Dialog._unloadHandler);
		} else if(!need && CerbUI.Dialog._unloadHandler) {
			window.removeEventListener('beforeunload', CerbUI.Dialog._unloadHandler);
			CerbUI.Dialog._unloadHandler = null;
		}
	}

	// Attach the window-resize listener once (lazily, on the first open). It debounces so the reflow runs after
	// the viewport stops resizing — not on every intermediate frame.
	static _ensureViewportResize() {
		if(CerbUI.Dialog._viewportResizeBound) return;
		CerbUI.Dialog._viewportResizeBound = true;
		window.addEventListener('resize', () => {
			clearTimeout(CerbUI.Dialog._resizeTimer);
			CerbUI.Dialog._resizeTimer = setTimeout(CerbUI.Dialog._onViewportResize, 150);
		});
	}

	// Reflow open dialogs after the viewport settles: re-resolve relative/default widths (leaving any the user
	// manually resized), then re-center untouched dialogs / clamp dragged ones back on-screen.
	static _onViewportResize() {
		for(const dlg of CerbUI.Dialog._openDialogs) {
			if(!dlg._open || dlg.minimized) continue;
			if(!dlg._userSizedW) {
				dlg.w = dlg._computeWidth();
				dlg.el.style.width = dlg.w + 'px';
			}
			// Re-center only dialogs still at the default placement; clamp ones the user dragged or that opened
			// at an explicit position (anchored / reuse popups) so we don't yank them to center.
			if(dlg._userMoved || dlg.opts.position) dlg._clampIntoView();
			else                                    dlg._positionDefault();
		}
		CerbUI.Dialog._syncPageHeight();
	}

	// Keep the top-right tray button in sync with the minimized set: hidden at 0, else a window icon + count.
	static _syncTray() {
		const n = CerbUI.Dialog._minimized.size;
		let tray = CerbUI.Dialog._tray;
		if(n === 0) { if(tray) tray.style.display = 'none'; return; }
		if(!tray) {
			tray = document.createElement('button');
			tray.type = 'button';
			tray.className = 'cerb-ui-dialog-tray';
			tray.setAttribute('aria-label', 'Minimized windows');
			const icon = document.createElement('span');
			icon.className = 'cerb-icons cerb-icon-window-top';
			icon.setAttribute('aria-hidden', 'true');
			const count = document.createElement('span');
			count.className = 'cerb-ui-dialog-tray--count';
			tray.appendChild(icon);
			tray.appendChild(count);
			tray.addEventListener('click', () => CerbUI.Dialog._openTrayMenu());
			document.body.appendChild(tray);
			CerbUI.Dialog._tray = tray;
		}
		tray.style.display = '';
		tray.querySelector('.cerb-ui-dialog-tray--count').textContent = String(n);
	}

	// Open a CerbUI.Menu of the minimized dialogs' titles, anchored to the tray; selecting one restores it.
	// Re-clicking the tray toggles the menu shut (the menu ignores clicks on its anchor, so we toggle here).
	static _openTrayMenu() {
		if(CerbUI.Dialog._trayMenu) { CerbUI.Dialog._trayMenu.close(); return; } // onClose nulls the ref
		if(!(window.CerbUI && CerbUI.Menu) || !CerbUI.Dialog._minimized.size) return;
		// A single minimized window has no menu — clicking the tray just restores it.
		if(CerbUI.Dialog._minimized.size === 1) {
			for(const d of CerbUI.Dialog._minimized) { d.restore(); break; }
			return;
		}
		const ul = document.createElement('ul');
		for(const d of CerbUI.Dialog._minimized) {
			const li = document.createElement('li');
			li.textContent = d.opts.title || 'Untitled'; // textContent = the security boundary
			li.dataset.uid = String(d.uid);
			ul.appendChild(li);
		}
		// With more than one, offer bulk actions: separator (empty <li>) + "Restore all" + "Close all".
		if(CerbUI.Dialog._minimized.size > 1) {
			const sep = document.createElement('li');
			sep.textContent = ''; // empty → renders as a menu separator
			ul.appendChild(sep);
			const restoreAll = document.createElement('li');
			restoreAll.textContent = 'Restore all';
			restoreAll.dataset.action = 'restore-all';
			ul.appendChild(restoreAll);
			const closeAll = document.createElement('li');
			closeAll.textContent = 'Close all';
			closeAll.dataset.action = 'close-all';
			ul.appendChild(closeAll);
		}
		const menu = new CerbUI.Menu(ul, {
			fixed: true,
			onClose: function() { CerbUI.Dialog._trayMenu = null; },
			onRenderItem: function(rendered, source) {
				// Icons aren't in markup — inject by action (chevron-down restore / trash close).
				const iconClass = { 'restore-all': 'cerb-icon-chevron-down', 'close-all': 'cerb-icon-trash' }[source.dataset.action];
				if(iconClass) {
					const icon = document.createElement('span');
					icon.className = 'cerb-icons ' + iconClass;
					icon.setAttribute('aria-hidden', 'true');
					icon.style.marginRight = '0.5em'; // space the icon off the label (matches the selectmenu icon)
					rendered.insertBefore(icon, rendered.firstChild);
				}
			},
			onSelect: function(rendered, source) {
				if(source.dataset.action === 'restore-all') {
					// Snapshot first — restore() mutates _minimized mid-iteration. Cascade (i) so they fan
					// out instead of stacking exactly on each other.
					[...CerbUI.Dialog._minimized].forEach((d, i) => d.restore(i));
					return;
				}
				if(source.dataset.action === 'close-all') {
					// Snapshot first — close() mutates _minimized mid-iteration; each runs its onClose hook.
					for(const d of [...CerbUI.Dialog._minimized]) d.close();
					return;
				}
				const uid = parseInt(source.dataset.uid, 10);
				for(const d of CerbUI.Dialog._minimized) { if(d.uid === uid) { d.restore(); break; } }
			},
		});
		CerbUI.Dialog._trayMenu = menu;
		menu.open(CerbUI.Dialog._tray);
	}

	// A floating absolute dialog is out of flow, so it doesn't lengthen the document — a dialog taller than
	// the viewport would be unreachable by page scroll. While such dialogs are open, grow <body> to cover the
	// lowest one so the window can scroll down to it (min-height only ever grows; real content is unaffected).
	static _syncPageHeight() {
		let maxBottom = 0;
		for(const d of CerbUI.Dialog._pageDialogs)
			// Leave a couple of titlebar-heights of margin below the dialog so the page scrolls comfortably
			// past its bottom (even when the dialog is dragged to the very end of the page).
			maxBottom = Math.max(maxBottom, d.el.getBoundingClientRect().bottom + window.scrollY + d._topMargin * 2);
		if(CerbUI.Dialog._origBodyMinHeight === null)
			CerbUI.Dialog._origBodyMinHeight = document.body.style.minHeight || '';
		document.body.style.minHeight = (CerbUI.Dialog._pageDialogs.size && maxBottom > window.innerHeight)
			? Math.ceil(maxBottom) + 'px'
			: CerbUI.Dialog._origBodyMinHeight;
	}

	// Resolve the dialog from its content element (exact) or any descendant of it (climbs to the root).
	// The descendant path is what lets AJAX-loaded content find its own dialog (replaces genericAjaxPopupFind).
	static from(el) {
		if(!el) return undefined;
		const direct = CerbUI.Dialog._instances.get(el);
		if(direct) return direct;
		const root = el.closest ? el.closest('.cerb-ui-dialog') : null;
		return root ? CerbUI.Dialog._byRoot.get(root) : undefined;
	}

	// Build a dialog around freshly-fetched HTML: spinner while loading, then the response as content.
	// `request`: a string ⇒ GET (ajax args) or a FormData ⇒ POST. `opts`: constructor options + onLoad.
	static fromAjax(request, opts = {}) {
		// Singleton: if a dialog is already open under this namespace, focus it (no second fetch / shell) —
		// unless `replace`, which lets the new one take over. Mirrors open()'s namespace handling, but earlier
		// so we never spin up the spinner + request for a duplicate.
		if(opts.namespace != null && !opts.replace) {
			const existing = CerbUI.Dialog._namespaces.get(opts.namespace);
			if(existing && existing._open) return CerbUI.Dialog._focusExisting(existing);
		}

		const content = document.createElement('div'); // detached → origParent null → destroy() removes it all
		const dlg = new CerbUI.Dialog(content, opts);

		const loading = document.createElement('div');
		loading.className = 'cerb-ui-dialog--loading';
		loading.appendChild((window.CerbUI && CerbUI.Spinner) ? CerbUI.Spinner.create(dlg.opts.spinner) : document.createElement('span'));
		content.appendChild(loading);

		// Throwaway popup: tear the DOM down once it closes (keeps hidden dialogs from piling up).
		content.addEventListener('cerb-ui-dialog:close', () => dlg.destroy(), { once: true });
		dlg.open(); // spinner shows immediately, centered

		const onError = () => dlg.close(); // the helper already toasted the HTTP error; just close (legacy hookError)
		const onDone  = (html) => {
			// Inject the response ourselves: a single write with no fade. Passing `content` as the helper's
			// target div would trigger its fadeTo(0.2)->html()->fadeTo(1.0) cycle on top of our spinner — a
			// visible double-blink. jQuery .html() still runs the response's <script nonce> under the page CSP.
			jQuery(content).html(html);
			dlg.reflow(); // the response grew the dialog — re-pin its top + lengthen the page to reach it
			if(typeof opts.onLoad === 'function') opts.onLoad(content, html);
		};

		if(typeof genericAjaxGet !== 'function' || typeof genericAjaxPost !== 'function' || !window.jQuery) {
			if(window.console) console.warn('CerbUI.Dialog.fromAjax requires genericAjaxGet/genericAjaxPost + jQuery');
			return dlg;
		}

		// Fetch with an EMPTY target so the helper does the request + HTTP-error toasts but NOT its built-in
		// fadeTo(0.2)->fadeTo(1.0) cycle (which would double-blink over our spinner); we inject in onDone.
		if(request instanceof FormData)
			genericAjaxPost(request, '', '', onDone, { error: onError });
		else
			genericAjaxGet('', request, onDone, { error: onError });

		return dlg;
	}

	// Singleton "Loading, please wait…" overlay — the showLoadingPanel/hideLoadingPanel replacement. A modal,
	// chrome-less dialog (no titlebar / close / drag / resize, Esc-locked) holding a spinner + message; one at a
	// time (a second show() refreshes the message on the existing one).
	static Loading = {
		show(message) {
			const text = (message != null) ? message : 'Loading, please wait...';

			if(CerbUI.Dialog._loading) {
				const m = CerbUI.Dialog._loading.innerContent.querySelector('[data-cerb-loading-msg]');
				if(m) m.textContent = text;
				return CerbUI.Dialog._loading;
			}

			const content = document.createElement('div');
			content.style.padding = '1.5em 2em';
			content.style.textAlign = 'center';

			const spin = document.createElement('div');
			if(window.CerbUI && CerbUI.Spinner) spin.appendChild(CerbUI.Spinner.create('spark'));
			content.appendChild(spin);

			const msg = document.createElement('div');
			msg.setAttribute('data-cerb-loading-msg', '');
			msg.style.marginTop = '0.75em';
			msg.style.fontWeight = 'bold';
			msg.textContent = text;
			content.appendChild(msg);

			const dlg = new CerbUI.Dialog(content, {
				header: 'none', modal: true, closable: false, draggable: false,
				resizable: false, closeOnEscape: false, width: 300,
			});
			dlg.open();
			CerbUI.Dialog._loading = dlg;
			return dlg;
		},

		hide() {
			if(CerbUI.Dialog._loading) {
				CerbUI.Dialog._loading.destroy();
				CerbUI.Dialog._loading = null;
			}
		},
	};

	constructor(contentEl, opts = {}) {
		this.uid = ++CerbUI.Dialog._uid;
		this.opts = Object.assign({
			title:      '',
			header:     'bar',
			draggable:  true,
			resizable:  true,
			closable:   true,
			minimizable: null, // resolved below: default true for any header with controls ('bar' / 'floating')
			modal:      false,
			closeWarnOnUnsavedChanges: false, // warn before closing once a tracked form control is actually changed
			scrollBody: false, // true = cap to the viewport and scroll the body; default grows + page scrolls
			autoHeight: false, // true = an n/s resize snaps height back to content on release (keeps the new width)
			spinner:    'spark', // CerbUI.Spinner variant for fromAjax's loading state: 'spark' (default) | 'arc' | 'dots' | null (plain ring)
			width:      null,  // null = 75% (capped at _MAX_WIDTH); a number = fixed px; an '<n>%' string = relative + reflows
			widthCap:   null,  // optional px cap on a relative width (the null default uses _MAX_WIDTH unless overridden)
			minWidth:   200,
			minHeight:  80,
			position:   null,
			namespace:  null,  // singleton identity: re-opening this namespace focuses the live dialog (see `replace`)
			replace:    false, // true = a same-namespace open closes the existing dialog and opens this one instead
			positionGroup: null, // shared shell: siblings in this group hand off position + close each other on open
			fixed:      false,
			closeOnEscape: true,
			closeOnBackdrop: false, // modal only: a click on the dimmed backdrop closes the dialog
			dragHandle: '[data-cerb-ui-dialog-drag]',
			onOpen:     null,
			onClose:    null,
			onMinimize: null,
			onDragged:  null,
			onResized:  null,
		}, opts);
		if(this.opts.namespace == null) this.opts.namespace = String(this.uid);
		if(this.opts.minimizable == null) this.opts.minimizable = (this.opts.header !== 'none'); // 'bar' + 'floating'
		if(this.opts.modal) this.opts.minimizable = false; // modal + minimize are mutually exclusive

		// Width spec → mobile (<= _MOBILE_MAX) is always 95%; otherwise an '<n>%' string is relative (reflows with
		// the viewport via _computeWidth), null defaults to 75% capped at _MAX_WIDTH (also relative), and a number
		// is fixed px. _userSizedW/_userMoved gate the viewport reflow so it never fights a manual resize/drag.
		this._widthPct  = null; // non-null => relative width that reflows with the viewport
		this._widthCap  = null; // optional px cap on the relative width (the default's _MAX_WIDTH)
		this._widthPx   = null; // explicit fixed px width, if one was given
		this._userSizedW = false; // set once the user e/w-resizes — reflow then leaves the width alone
		this._userMoved  = false; // set once the user drags — reflow then clamps instead of re-centering
		if(typeof this.opts.width === 'string' && this.opts.width.trim().endsWith('%')) {
			this._widthPct = parseFloat(this.opts.width);
			this._widthCap = this.opts.widthCap; // null => uncapped
		} else if(this.opts.width == null) {
			this._widthPct = 75;
			this._widthCap = (this.opts.widthCap != null) ? this.opts.widthCap : CerbUI.Dialog._MAX_WIDTH;
		} else {
			this._widthPx = this.opts.width;
		}
		this.w = this._computeWidth();
		this.h = null; // null = auto height until the first n/s resize
		this.x = 0;
		this.y = 0;
		this.minimized = false;
		this._open = false;
		this._dirty = false;              // set true once the user actually changes a tracked form control
		this.docKeydown = null;
		this.backdrop = null;
		this.minimizeBtn = null;
		this.titleEl = null;
		this._injectedControls = null;   // floating controls docked into the content's header
		this._createdRightToolbar = null; // a .cerb-ui-header--right we created to dock them into
		this._topMargin = 0;              // measured titlebar height at open (top gap + page-scroll bottom margin)

		this.innerContent = contentEl;
		this.origParent = contentEl.parentNode;
		this.origNextSibling = contentEl.nextSibling;

		this.onPointerDown = this.onPointerDown.bind(this);

		// Flip _dirty the first time the user actually changes a tracked control (typing, toggle, menu,
		// checkbox/radio, select). Native events bubble — CerbUI Toggle/SelectMenu dispatch `change` too.
		this._onDirty = (e) => {
			if(this._dirty) return; // already dirty — nothing more to track
			const t = e.target;
			if(!t || !t.matches || !t.matches('input, select, textarea')) return;
			if(t.type === 'hidden') return;                           // hidden inputs aren't user-editable
			if(t.closest('[data-cerb-ui-dialog-no-dirty]')) return;   // opted-out control / subtree
			this._dirty = true;
			CerbUI.Dialog._syncUnloadGuard(); // editing an already-minimized dialog now guards page unload
		};

		const titleId = `cerb-ui-dialog-${this.uid}-title`;

		const dlg = document.createElement('div');
		dlg.className =
			'cerb-ui-dialog cerb-ui-dialog--hidden'
			+ (this.opts.fixed      ? ' cerb-ui-dialog--fixed'     : '')
			+ (this.opts.draggable  ? ' cerb-ui-dialog--draggable' : '')
			+ (this.opts.resizable  ? ' cerb-ui-dialog--resizable' : '')
			+ (this.opts.scrollBody ? ' cerb-ui-dialog--scroll'    : '')
			+ ' cerb-ui-dialog--header-' + this.opts.header;
		dlg.setAttribute('role', 'dialog');
		dlg.setAttribute('aria-modal', this.opts.modal ? 'true' : 'false');
		dlg.setAttribute('aria-labelledby', titleId);
		dlg.style.width = this.w + 'px';
		this.el = dlg;

		// ── Header chrome ─────────────────────────────────────────────────
		if(this.opts.header === 'bar') {
			const bar = document.createElement('div');
			bar.className = 'cerb-ui-dialog--titlebar';

			const title = document.createElement('span');
			title.className = 'cerb-ui-dialog--title';
			title.id = titleId;
			title.textContent = this.opts.title;
			this.titleEl = title;
			bar.appendChild(title);

			bar.appendChild(this._buildControls());
			dlg.appendChild(bar);
		} else if(this.opts.header === 'floating') {
			const controls = this._buildControls();

			// Dock the controls into the content's own header toolbar so they sit beside its chrome
			// instead of floating over it. Fall back to a floating cluster if there's no header.
			const header = contentEl.matches('.cerb-ui-header')
				? contentEl
				: contentEl.querySelector('.cerb-ui-header');
			if(header) {
				let right = header.querySelector(':scope > .cerb-ui-header--right');
				if(!right) {
					right = document.createElement('div');
					right.className = 'cerb-ui-header--right';
					header.appendChild(right);
					this._createdRightToolbar = right; // remove on destroy only if we created it empty
				}
				right.appendChild(controls);
				this._injectedControls = controls; // lives inside the content; clean up on destroy
			} else {
				controls.classList.add('cerb-ui-dialog--floating-controls');
				dlg.appendChild(controls);
			}
		}
		// header 'none': no chrome — content owns everything.

		// ── Content ───────────────────────────────────────────────────────
		const contentWrap = document.createElement('div');
		contentWrap.className = 'cerb-ui-dialog--content';
		contentWrap.appendChild(contentEl);
		dlg.appendChild(contentWrap);

		// ── Resize handles ────────────────────────────────────────────────
		if(this.opts.resizable) {
			for(const dir of ['n', 'ne', 'e', 'se', 's', 'sw', 'w', 'nw']) {
				const handle = document.createElement('div');
				handle.className = 'cerb-ui-dialog--resize';
				handle.dataset['dir'] = dir;
				handle.setAttribute('aria-hidden', 'true');
				dlg.appendChild(handle);
			}
		}

		// Single capture-phase listener routes drag, resize, and z-index focus.
		dlg.addEventListener('pointerdown', this.onPointerDown, true);

		// Contain keyboard events at the dialog boundary so keystrokes typed inside never reach page-level
		// (bubble-phase document) shortcut handlers — e.g. typing in a dialog field shouldn't fire a worklist
		// hotkey behind it. Capture-phase document listeners (CerbUI.Menu with captureKeys, the editor
		// autocompletes / find) run BEFORE the event reaches us, so they're unaffected; a bubble-phase
		// component inside a dialog should use capture (as CerbUI.SelectMenu does) to keep working. Escape is
		// closed here too (so it works with focus inside the dialog) and then contained; the document-level
		// docKeydown still handles Escape when focus is OUTSIDE the dialog.
		this._containKeys = (e) => {
			if(e.type === 'keydown' && e.key === 'Escape'
				&& this.el.style.zIndex === String(CerbUI.Dialog._zTop)) {
				// Shift+Esc docks to the tray; plain Esc closes. The shift guard keeps close from also firing.
				if(e.shiftKey) {
					if(this.opts.minimizable) { e.preventDefault(); this.minimize(); }
				} else if(this.opts.closeOnEscape) {
					e.preventDefault();
					this.close();
				}
			}
			e.stopPropagation();
		};
		dlg.addEventListener('keydown',  this._containKeys);
		dlg.addEventListener('keypress', this._containKeys);
		dlg.addEventListener('keyup',    this._containKeys);

		document.body.appendChild(dlg);
		CerbUI.Dialog._instances.set(contentEl, this);
		CerbUI.Dialog._byRoot.set(dlg, this);

		// Dirty-tracking: one delegated listener in capture phase (so we still see events a child handler
		// might stopPropagation on the bubble). innerContent is stable across fromAjax content loads.
		this.innerContent.addEventListener('input',  this._onDirty, true);
		this.innerContent.addEventListener('change', this._onDirty, true);

		// When the dialog's own size changes while open (accordion expand, inline search, async load),
		// re-extend the page so the new bottom stays reachable — without ever repositioning the dialog.
		this._resizeObs = window.ResizeObserver ? new ResizeObserver(() => CerbUI.Dialog._syncPageHeight()) : null;
	}

	// A control cluster (minimize? + close?) shared by the bar and the floating header.
	_buildControls() {
		const controls = document.createElement('div');
		controls.className = 'cerb-ui-dialog--controls';

		if(this.opts.minimizable) {
			this.minimizeBtn = this._makeBtn('cerb-icon-chevron-up', 'Minimize (Shift+Esc)', () => this.minimize());
			controls.appendChild(this.minimizeBtn);
		}
		if(this.opts.closable) {
			controls.appendChild(this._makeBtn('cerb-icon-remove', 'Close', () => this.close()));
		}
		return controls;
	}

	_makeBtn(iconClass, label, handler) {
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'cerb-ui-dialog--btn';
		btn.setAttribute('aria-label', label);
		const icon = document.createElement('span');
		icon.className = 'cerb-icons ' + iconClass;
		icon.setAttribute('aria-hidden', 'true');
		btn.appendChild(icon);
		btn.addEventListener('click', handler);
		return btn;
	}

	// ── Public API ──────────────────────────────────────────────────────

	open() {
		if(this._open) return true;

		// Singleton by namespace: a dialog already open under this namespace wins — focus it instead of
		// stacking a duplicate. `replace:true` instead closes the existing one and opens this in its place.
		const ns = CerbUI.Dialog._namespaces.get(this.opts.namespace);
		if(ns && ns !== this && ns._open) {
			if(!this.opts.replace) { CerbUI.Dialog._focusExisting(ns); return false; }
			if(!ns.close()) return false; // existing onClose vetoed — abort
		}

		// Position group: siblings sharing a group hand off their position and close each other on open.
		let inheritedPos = null;
		if(this.opts.positionGroup != null) {
			const sib = CerbUI.Dialog._positionGroups.get(this.opts.positionGroup);
			if(sib && sib !== this && sib._open) {
				inheritedPos = { x: parseInt(sib.el.style.left, 10), y: parseInt(sib.el.style.top, 10) };
				if(!sib.close()) return false; // sibling's onClose blocked it — abort
			}
			CerbUI.Dialog._positionGroups.set(this.opts.positionGroup, this);
		}

		CerbUI.Dialog._namespaces.set(this.opts.namespace, this);

		this._open = true;
		CerbUI.Dialog._openDialogs.add(this);
		CerbUI.Dialog._ensureViewportResize();

		if(this.opts.modal) this._addBackdrop();

		// Measure while invisible to determine centering position.
		this.el.style.visibility = 'hidden';
		this.el.classList.remove('cerb-ui-dialog--hidden');

		const vw = window.innerWidth;
		const w  = this.el.offsetWidth;
		const bar = this.el.querySelector('.cerb-ui-dialog--titlebar');
		this._topMargin = bar ? bar.offsetHeight : 35; // ~one titlebar height (legacy "top+35")

		if(this.opts.position) {
			this.x = this.opts.position.x;
			this.y = this.opts.position.y;
		} else if(inheritedPos) {
			this.x = inheritedPos.x;
			this.y = inheritedPos.y;
		} else {
			this._positionDefault(); // centered horizontally, near the top (the default / restore position)
		}

		// scrollBody: cap to the viewport so the whole dialog is visible (its body scrolls internally via
		// content overflow:auto). Leave a titlebar gap at the top (the open position) and two at the bottom
		// so the frame clears the viewport edge. Short content stays shorter (no scroll).
		if(this.opts.scrollBody)
			this.el.style.maxHeight = Math.max(this.opts.minHeight, window.innerHeight - 3 * this._topMargin) + 'px';

		this.el.style.left = this.x + 'px';
		this.el.style.top  = this.y + 'px';
		this.el.style.visibility = '';

		this.bringToFront();

		this.docKeydown = (e) => {
			// Only the topmost dialog responds to Escape.
			if(e.key !== 'Escape' || this.el.style.zIndex !== String(CerbUI.Dialog._zTop)) return;
			// Shift+Esc docks to the tray; plain Esc closes. The shift guard keeps close from also firing.
			if(e.shiftKey) {
				if(this.opts.minimizable) this.minimize();
			} else if(this.opts.closeOnEscape) {
				this.close();
			}
		};
		document.addEventListener('keydown', this.docKeydown);

		// A growing (non-fixed, non-scrollBody) dialog lengthens the page so it stays reachable by scroll;
		// the ResizeObserver re-syncs that as content grows in place.
		if(!this.opts.fixed && !this.opts.scrollBody) {
			CerbUI.Dialog._pageDialogs.add(this);
			CerbUI.Dialog._syncPageHeight();
		}
		if(this._resizeObs) this._resizeObs.observe(this.el);

		if(this.opts.onOpen) this.opts.onOpen();
		this.innerContent.dispatchEvent(new CustomEvent('cerb-ui-dialog:open', { bubbles: true }));

		// Move keyboard focus into the dialog so Escape works immediately (no manual click first). Prefer an
		// explicit [autofocus] control; otherwise focus the dialog root. Async content (e.g. fromAjax, still a
		// spinner here) focuses itself once loaded — that runs later, so it wins over this baseline.
		const autofocusEl = this.innerContent.querySelector('[autofocus]');
		if(autofocusEl && typeof autofocusEl.focus === 'function') {
			try { autofocusEl.focus({ preventScroll: true }); } catch(e) { autofocusEl.focus(); }
		} else {
			this._focus();
		}
		return true;
	}

	close() {
		if(!this._open) return false;

		// Unsaved-changes guard — runs before the user onClose hook. CerbUI.Confirm is async, so abort this
		// attempt and re-enter close() from the onConfirm callback (clears the flag → proceeds to onClose).
		// Covers every close path (ESC, the (x) button, Close-all) since they all route through close().
		if(this.opts.closeWarnOnUnsavedChanges && this._dirty && window.CerbUI && CerbUI.Confirm) {
			CerbUI.Confirm.open({
				title: 'Discard changes',
				body:  'Are you sure you want to close this popup without saving?',
				onConfirm: () => { this._dirty = false; this.close(); },
			});
			return false;
		}

		if(this.opts.onClose && this.opts.onClose() === false) return false;

		this._open = false;
		this.el.classList.add('cerb-ui-dialog--hidden');
		this._removeBackdrop();

		if(this.minimized) {
			this.minimized = false;
			this.el.classList.remove('cerb-ui-dialog--minimized');
			CerbUI.Dialog._minimized.delete(this);
			CerbUI.Dialog._syncTray();
			CerbUI.Dialog._syncUnloadGuard();
		}

		if(this.docKeydown) {
			document.removeEventListener('keydown', this.docKeydown);
			this.docKeydown = null;
		}

		if(CerbUI.Dialog._namespaces.get(this.opts.namespace) === this) {
			CerbUI.Dialog._namespaces.delete(this.opts.namespace);
		}
		if(this.opts.positionGroup != null && CerbUI.Dialog._positionGroups.get(this.opts.positionGroup) === this) {
			CerbUI.Dialog._positionGroups.delete(this.opts.positionGroup);
		}

		if(this._resizeObs) this._resizeObs.disconnect();
		CerbUI.Dialog._openDialogs.delete(this);
		if(CerbUI.Dialog._pageDialogs.delete(this)) CerbUI.Dialog._syncPageHeight();

		this.innerContent.dispatchEvent(new CustomEvent('cerb-ui-dialog:close', { bubbles: true }));

		// Hand focus to the next dialog down the stack so Escape can cascade through the whole stack.
		CerbUI.Dialog._focusTopmost(this);
		return true;
	}

	isOpen() {
		return this._open;
	}

	// Unsaved-changes tracking. A save-success handler should markClean() before (or instead of) close()
	// so a successful save never trips the discard warning (every close path guards on _dirty).
	isDirty()   { return this._dirty; }
	markClean() { this._dirty = false; CerbUI.Dialog._syncUnloadGuard(); }

	setTitle(title) {
		this.opts.title = title;
		if(this.titleEl) this.titleEl.textContent = title;
	}

	// Re-extend the page after the content's size changes (no reposition — the dialog stays put). The
	// ResizeObserver does this automatically; this is the manual hook for callers that mutate content.
	reflow() {
		if(this._open) CerbUI.Dialog._syncPageHeight();
	}

	destroy() {
		CerbUI.Dialog._instances.delete(this.innerContent);
		CerbUI.Dialog._byRoot.delete(this.el);
		if(CerbUI.Dialog._namespaces.get(this.opts.namespace) === this) {
			CerbUI.Dialog._namespaces.delete(this.opts.namespace);
		}
		if(this.opts.positionGroup != null && CerbUI.Dialog._positionGroups.get(this.opts.positionGroup) === this) {
			CerbUI.Dialog._positionGroups.delete(this.opts.positionGroup);
		}
		if(this._resizeObs) this._resizeObs.disconnect();
		CerbUI.Dialog._openDialogs.delete(this);
		if(CerbUI.Dialog._minimized.delete(this)) CerbUI.Dialog._syncTray();
		CerbUI.Dialog._syncUnloadGuard();
		if(CerbUI.Dialog._pageDialogs.delete(this)) CerbUI.Dialog._syncPageHeight();

		// Teardown without invoking onClose — destroy is always forceful.
		if(this._open) {
			this._open = false;
			if(this.docKeydown) {
				document.removeEventListener('keydown', this.docKeydown);
				this.docKeydown = null;
			}
		}
		this._removeBackdrop();

		this.el.removeEventListener('pointerdown', this.onPointerDown, true);
		this.el.removeEventListener('keydown',  this._containKeys);
		this.el.removeEventListener('keypress', this._containKeys);
		this.el.removeEventListener('keyup',    this._containKeys);
		this.innerContent.removeEventListener('input',  this._onDirty, true);
		this.innerContent.removeEventListener('change', this._onDirty, true);

		// Controls docked into the content's header live inside innerContent — pull them out before
		// restoring it, and drop a --right toolbar we created if it's now empty.
		if(this._injectedControls) {
			this._injectedControls.remove();
			this._injectedControls = null;
		}
		if(this._createdRightToolbar) {
			if(!this._createdRightToolbar.childElementCount) this._createdRightToolbar.remove();
			this._createdRightToolbar = null;
		}

		if(this.origParent) {
			this.origParent.insertBefore(this.innerContent, this.origNextSibling);
		}

		this.el.remove();
	}

	// ── Internal ────────────────────────────────────────────────────────

	bringToFront() {
		CerbUI.Dialog._zTop++;
		this.el.style.zIndex = String(CerbUI.Dialog._zTop);
		if(this.backdrop) this.backdrop.style.zIndex = String(CerbUI.Dialog._zTop - 1);
	}

	// Put keyboard focus on the dialog root (tabindex -1) so Escape routes through this.el's keydown handler —
	// without selecting/scrolling to an inner field. Used when focus hands off to a newly-topmost dialog.
	_focus() {
		this.el.setAttribute('tabindex', '-1');
		try { this.el.focus({ preventScroll: true }); } catch(e) { this.el.focus(); }
	}

	_addBackdrop() {
		this.backdrop = document.createElement('div');
		this.backdrop.className = 'cerb-ui-dialog--backdrop';
		this.backdrop.setAttribute('aria-hidden', 'true');
		// A click outside the dialog (on the backdrop) closes it, when opted in.
		if(this.opts.closeOnBackdrop)
			this.backdrop.addEventListener('click', () => this.close());
		document.body.appendChild(this.backdrop);
	}

	_removeBackdrop() {
		if(this.backdrop) { this.backdrop.remove(); this.backdrop = null; }
	}

	// Resolve the current pixel width from the spec: mobile forces 95%; a relative %/default scales with the
	// viewport (optionally capped, never wider than the viewport); a fixed px width is returned as-is.
	_computeWidth() {
		const vw = window.innerWidth;
		if(vw <= CerbUI.Dialog._MOBILE_MAX) return Math.round(vw * 0.95);
		if(this._widthPct == null) return this._widthPx;
		let w = Math.round(vw * this._widthPct / 100);
		if(this._widthCap != null) w = Math.min(w, this._widthCap);
		return Math.min(w, vw - 20);
	}

	// Keep a dragged dialog horizontally within the viewport after a resize (we don't re-center moved dialogs).
	_clampIntoView() {
		const vw   = window.innerWidth;
		const w    = this.el.offsetWidth;
		const ox   = this.opts.fixed ? 0 : window.scrollX;
		const minX = ox + 10;
		const maxX = Math.max(minX, ox + vw - w - 10);
		this.x = Math.min(Math.max(this.x, minX), maxX);
		this.el.style.left = this.x + 'px';
	}

	// The default open / restore position: centered horizontally, near the top (one-titlebar gap), at the
	// current scroll. Top placement (not centered) lets tall/growing content extend down + page-scroll.
	_positionDefault() {
		const vw = window.innerWidth;
		const w  = this.el.offsetWidth;
		const ox = this.opts.fixed ? 0 : window.scrollX;
		const oy = this.opts.fixed ? 0 : window.scrollY;
		this.x = Math.max(0, ox + Math.round((vw - w) / 2));
		this.y = oy + this._topMargin;
		this.el.style.left = this.x + 'px';
		this.el.style.top  = this.y + 'px';
	}

	// Dock into the top-right tray (one-way; restore from the tray menu).
	minimize() {
		if(this.minimized || !this._open) return;
		this.minimized = true;
		this.el.classList.add('cerb-ui-dialog--minimized'); // display:none — the tray represents it now
		CerbUI.Dialog._minimized.add(this);
		CerbUI.Dialog._syncTray();
		CerbUI.Dialog._syncUnloadGuard(); // a dirty dialog docked in the tray now guards page unload
		CerbUI.Dialog._syncPageHeight(); // hidden → drops out of the page-height calc
		CerbUI.Dialog._focusTopmost(this); // hand focus/Escape to the next dialog down the stack
		if(this.opts.onMinimize) this.opts.onMinimize(true);
	}

	// Restore from the tray back to the default top-center position, on top. `cascade` offsets the placement
	// by N steps so "Restore all" fans dialogs out instead of stacking them exactly.
	restore(cascade = 0) {
		if(!this.minimized) return;
		this.minimized = false;
		this.el.classList.remove('cerb-ui-dialog--minimized');
		CerbUI.Dialog._minimized.delete(this);
		CerbUI.Dialog._syncTray();
		CerbUI.Dialog._syncUnloadGuard(); // no longer in the tray → may drop the unload guard
		this._positionDefault();
		if(cascade > 0) {
			const offset = cascade * CerbUI.Dialog._CASCADE_STEP;
			this.x += offset;
			this.y += offset;
			this.el.style.left = this.x + 'px';
			this.el.style.top  = this.y + 'px';
		}
		this.bringToFront();
		CerbUI.Dialog._syncPageHeight();
		if(this.opts.onMinimize) this.opts.onMinimize(false);
	}

	// Whether a pointerdown target should start a drag (in the drag region, outside the controls).
	_isDragTarget(target) {
		if(target.closest('.cerb-ui-dialog--controls')) return false;
		if(this.opts.header === 'bar') {
			// Only THIS dialog's own titlebar (a direct child of its root) is a drag handle — never a NESTED
			// `.cerb-ui-dialog--titlebar` (e.g. a static dialog-facsimile rendered inside the content, like the
			// Form Builder preview), which `closest()` would otherwise match and let hijack the parent's drag.
			const bar = target.closest('.cerb-ui-dialog--titlebar');
			return !!bar && bar.parentNode === this.el;
		}
		return !!target.closest(this.opts.dragHandle);
	}

	onPointerDown(e) {
		this.bringToFront();

		const target = e.target;

		const handle = target.closest('.cerb-ui-dialog--resize');
		if(handle) {
			if(this.opts.resizable && !this.minimized) {
				e.preventDefault();
				this.startResize(e, handle.dataset['dir'] ?? '');
			}
			return;
		}

		// Drag by the header/handle works in both normal and minimized states.
		if(this.opts.draggable && this._isDragTarget(target)) {
			e.preventDefault();
			this.startDrag(e);
		}
	}

	startDrag(startEvent) {
		// For position:absolute the stored x/y are document-relative; clientX/Y are viewport-relative, so
		// we add scrollX/Y to convert before computing the offset.
		const sx = this.opts.fixed ? 0 : window.scrollX;
		const sy = this.opts.fixed ? 0 : window.scrollY;
		const offsetX = (startEvent.clientX + sx) - this.x;
		const offsetY = (startEvent.clientY + sy) - this.y;

		const onMove = (e) => {
			this._userMoved = true; // a moved dialog is clamped (not re-centered) on viewport resize
			const mx = this.opts.fixed ? 0 : window.scrollX;
			const my = this.opts.fixed ? 0 : window.scrollY;
			this.x = (e.clientX + mx) - offsetX;
			this.y = (e.clientY + my) - offsetY;
			this.el.style.left = this.x + 'px';
			this.el.style.top  = this.y + 'px';
		};

		const onUp = () => {
			document.removeEventListener('pointermove', onMove);
			document.removeEventListener('pointerup', onUp);
			CerbUI.Dialog._syncPageHeight(); // a dialog dragged lower may need more page height to reach
			if(this.opts.onDragged) this.opts.onDragged(this.x, this.y);
		};

		document.addEventListener('pointermove', onMove);
		document.addEventListener('pointerup', onUp);
	}

	startResize(startEvent, dir) {
		const startX = startEvent.clientX;
		const startY = startEvent.clientY;
		const startW = this.el.offsetWidth;
		const startLeft = this.x;
		const startTop  = this.y;

		const affectsH = dir.includes('n') || dir.includes('s');
		const affectsW = dir.includes('e') || dir.includes('w');
		if(affectsW) this._userSizedW = true; // a hand-sized width is left alone by the viewport reflow
		if(affectsH && this.h === null) this.h = this.el.offsetHeight;
		const startH = this.h ?? 0;

		const onMove = (e) => {
			const dx = e.clientX - startX;
			const dy = e.clientY - startY;

			let newW    = startW;
			let newH    = startH;
			let newLeft = startLeft;
			let newTop  = startTop;

			if(dir.includes('e')) newW = Math.max(this.opts.minWidth, startW + dx);
			if(dir.includes('w')) {
				newW    = Math.max(this.opts.minWidth, startW - dx);
				newLeft = startLeft + (startW - newW);
			}
			if(dir.includes('s')) newH = Math.max(this.opts.minHeight, startH + dy);
			if(dir.includes('n')) {
				newH   = Math.max(this.opts.minHeight, startH - dy);
				newTop = startTop + (startH - newH);
			}

			this.w = newW;
			this.x = newLeft;
			this.y = newTop;

			this.el.style.width = newW + 'px';
			this.el.style.left  = newLeft + 'px';
			this.el.style.top   = newTop + 'px';

			if(affectsH) {
				this.h = newH;
				this.el.style.height = newH + 'px';
			}
		};

		const onUp = () => {
			document.removeEventListener('pointermove', onMove);
			document.removeEventListener('pointerup', onUp);
			// autoHeight: drop the dragged height so the body refits to content (the new width persists) — the
			// jQuery-UI resizeStop behavior. Tall content still grows + page-scrolls via _syncPageHeight().
			if(this.opts.autoHeight && affectsH) { this.h = null; this.el.style.height = ''; }
			CerbUI.Dialog._syncPageHeight(); // a dialog resized taller may need more page height to reach
			if(this.opts.onResized) this.opts.onResized(this.w, this.h);
		};

		document.addEventListener('pointermove', onMove);
		document.addEventListener('pointerup', onUp);
	}
};
