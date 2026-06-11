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
 * Minimize: a minimize button (alongside close) docks the dialog into a single shared tray button (a window
 *   icon + a count, titlebar-blue) fixed at the top-right of the page. Available on any header with controls
 *   — both 'bar' and 'floating' (the buttons inject into the floating cluster / content header) — i.e.
 *   default on except header:'none'. Clicking the tray opens a menu of the minimized dialogs' titles
 *   (`opts.title`, falling back to "Untitled" for headerless dialogs that don't set one); choosing one
 *   restores it (`restore()`) to the default top-center position. With more than one minimized, the menu
 *   also offers a "Close all" item (trash icon) that closes every minimized dialog via `close()` (so each
 *   onClose veto hook still runs). `onMinimize(bool)` fires true on minimize /
 *   false on restore. Modal dialogs are never minimizable (modal + minimize are mutually exclusive).
 *
 * AJAX popups — CerbUI.Dialog.fromAjax(request, opts):
 *   Builds the dialog DOM procedurally, shows a spinner, fetches HTML, and loads it as the content. The
 *   `request` mirrors the legacy genericAjaxPopup: a string ⇒ GET (ajax args), a FormData ⇒ POST. The
 *   response is injected through Cerb's genericAjaxGet/Post (jQuery .html()), so any <script> in it runs
 *   under the page's CSP nonce — the canonical Cerb path (a raw fetch()+innerHTML would NOT run them).
 *   HTTP errors already raise a toast banner via the helper; the wrapper just closes the dialog (the old
 *   hookError behavior). `opts` are the constructor options below (title, header, namespace, modal,
 *   width, …) plus an optional onLoad(content, html). The popup is throwaway: it self-destroys on close.
 *   Loaded content resolves its own dialog with CerbUI.Dialog.from(anyDescendant) — e.g. a form inside it
 *   — then setTitle()/close() (the genericAjaxPopupFind replacement). `namespace` supersedes the old
 *   `layer`/`reuse`: siblings sharing a namespace inherit each other's position and auto-close on open().
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
 *   width, minWidth, minHeight, position {x,y}, namespace (siblings share position + close each other),
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
 *   reports the current state.
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
	static _namespaces = new Map();
	static _pageDialogs = new Set();   // open dialogs that grow + page-scroll (not fixed, not scrollBody)
	static _origBodyMinHeight = null;  // body.style.minHeight before we touched it; restored when the set empties
	static _MAX_WIDTH = 1100;          // default-width cap; mirrors .cerb-ui-page--max-width
	static _MOBILE_MAX = 768;          // mobile breakpoint (cerb-responsive.scss) — dialogs go 95% wide below it
	static _minimized = new Set();     // dialogs docked in the top-right tray
	static _tray = null;               // the shared tray button (lazily built)
	static _trayMenu = null;           // the open tray menu, if any (so a re-click toggles it shut)

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
		const ul = document.createElement('ul');
		for(const d of CerbUI.Dialog._minimized) {
			const li = document.createElement('li');
			li.textContent = d.opts.title || 'Untitled'; // textContent = the security boundary
			li.dataset.uid = String(d.uid);
			ul.appendChild(li);
		}
		// With more than one, offer a bulk dismiss: separator (empty <li>) + a trash-iconed "Close all".
		if(CerbUI.Dialog._minimized.size > 1) {
			const sep = document.createElement('li');
			sep.textContent = ''; // empty → renders as a menu separator
			ul.appendChild(sep);
			const closeAll = document.createElement('li');
			closeAll.textContent = 'Close all';
			closeAll.dataset.action = 'close-all';
			ul.appendChild(closeAll);
		}
		const menu = new CerbUI.Menu(ul, {
			fixed: true,
			onClose: function() { CerbUI.Dialog._trayMenu = null; },
			onRenderItem: function(rendered, source) {
				if(source.dataset.action === 'close-all') { // icons aren't in markup — inject here
					const icon = document.createElement('span');
					icon.className = 'cerb-icons cerb-icon-trash';
					icon.setAttribute('aria-hidden', 'true');
					icon.style.marginRight = '0.5em'; // space the icon off the label (matches the selectmenu icon)
					rendered.insertBefore(icon, rendered.firstChild);
				}
			},
			onSelect: function(rendered, source) {
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
		const content = document.createElement('div'); // detached → origParent null → destroy() removes it all
		const loading = document.createElement('div');
		loading.className = 'cerb-ui-dialog--loading';
		loading.appendChild((window.CerbUI && CerbUI.Spinner) ? CerbUI.Spinner.create() : document.createElement('span'));
		content.appendChild(loading);

		const dlg = new CerbUI.Dialog(content, opts);
		// Throwaway popup: tear the DOM down once it closes (keeps hidden dialogs from piling up).
		content.addEventListener('cerb-ui-dialog:close', () => dlg.destroy(), { once: true });
		dlg.open(); // spinner shows immediately, centered

		const onError = () => dlg.close(); // the helper already toasted the HTTP error; just close (legacy hookError)
		const onDone  = (html) => {
			dlg.reflow(); // the response grew the dialog — re-pin its top + lengthen the page to reach it
			if(typeof opts.onLoad === 'function') opts.onLoad(content, html);
		};

		if(typeof genericAjaxGet !== 'function' || typeof genericAjaxPost !== 'function' || !window.jQuery) {
			if(window.console) console.warn('CerbUI.Dialog.fromAjax requires genericAjaxGet/genericAjaxPost + jQuery');
			return dlg;
		}

		// genericAjaxGet/Post inject the fragment with jQuery (response <script> runs under the page nonce)
		// and surface HTTP errors as toast banners; they replace our spinner via .html() on success.
		if(request instanceof FormData)
			genericAjaxPost(request, jQuery(content), '', onDone, { error: onError });
		else
			genericAjaxGet(jQuery(content), request, onDone, { error: onError });

		return dlg;
	}

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
			width:      null,  // null = 75% of the viewport capped at _MAX_WIDTH (mobile: always 95%)
			minWidth:   200,
			minHeight:  80,
			position:   null,
			namespace:  null,
			fixed:      false,
			closeOnEscape: true,
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

		// Width: mobile is always 95% (ignores any width directive); otherwise an explicit width is honored
		// as-is, else default to 75% of the viewport capped at the cerb-ui-page max-width.
		const vw = window.innerWidth;
		this.w = (vw <= CerbUI.Dialog._MOBILE_MAX)
			? Math.round(vw * 0.95)
			: (this.opts.width != null
				? this.opts.width
				: Math.min(Math.round(vw * 0.75), CerbUI.Dialog._MAX_WIDTH));
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
			this.minimizeBtn = this._makeBtn('cerb-icon-chevron-up', 'Minimize', () => this.minimize());
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
		if(this._open) return;

		// Namespace: close any sibling currently open in the same group, inheriting its position.
		const existing = CerbUI.Dialog._namespaces.get(this.opts.namespace);
		const inheritedPos = (existing && existing !== this)
			? { x: parseInt(existing.el.style.left, 10), y: parseInt(existing.el.style.top, 10) }
			: null;
		if(existing && existing !== this) {
			if(!existing.close()) return; // sibling's onClose blocked it — abort
		}
		CerbUI.Dialog._namespaces.set(this.opts.namespace, this);

		this._open = true;

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
			if(e.key === 'Escape' && this.opts.closeOnEscape && this.el.style.zIndex === String(CerbUI.Dialog._zTop)) {
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
		}

		if(this.docKeydown) {
			document.removeEventListener('keydown', this.docKeydown);
			this.docKeydown = null;
		}

		if(CerbUI.Dialog._namespaces.get(this.opts.namespace) === this) {
			CerbUI.Dialog._namespaces.delete(this.opts.namespace);
		}

		if(this._resizeObs) this._resizeObs.disconnect();
		if(CerbUI.Dialog._pageDialogs.delete(this)) CerbUI.Dialog._syncPageHeight();

		this.innerContent.dispatchEvent(new CustomEvent('cerb-ui-dialog:close', { bubbles: true }));
		return true;
	}

	isOpen() {
		return this._open;
	}

	// Unsaved-changes tracking. A save-success handler should markClean() before (or instead of) close()
	// so a successful save never trips the discard warning (every close path guards on _dirty).
	isDirty()   { return this._dirty; }
	markClean() { this._dirty = false; }

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
		if(this._resizeObs) this._resizeObs.disconnect();
		if(CerbUI.Dialog._minimized.delete(this)) CerbUI.Dialog._syncTray();
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

	_addBackdrop() {
		this.backdrop = document.createElement('div');
		this.backdrop.className = 'cerb-ui-dialog--backdrop';
		this.backdrop.setAttribute('aria-hidden', 'true');
		document.body.appendChild(this.backdrop);
	}

	_removeBackdrop() {
		if(this.backdrop) { this.backdrop.remove(); this.backdrop = null; }
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
		CerbUI.Dialog._syncPageHeight(); // hidden → drops out of the page-height calc
		if(this.opts.onMinimize) this.opts.onMinimize(true);
	}

	// Restore from the tray back to the default top-center position, on top.
	restore() {
		if(!this.minimized) return;
		this.minimized = false;
		this.el.classList.remove('cerb-ui-dialog--minimized');
		CerbUI.Dialog._minimized.delete(this);
		CerbUI.Dialog._syncTray();
		this._positionDefault();
		this.bringToFront();
		CerbUI.Dialog._syncPageHeight();
		if(this.opts.onMinimize) this.opts.onMinimize(false);
	}

	// Whether a pointerdown target should start a drag (in the drag region, outside the controls).
	_isDragTarget(target) {
		if(target.closest('.cerb-ui-dialog--controls')) return false;
		if(this.opts.header === 'bar') return !!target.closest('.cerb-ui-dialog--titlebar');
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
			CerbUI.Dialog._syncPageHeight(); // a dialog resized taller may need more page height to reach
			if(this.opts.onResized) this.opts.onResized(this.w, this.h);
		};

		document.addEventListener('pointermove', onMove);
		document.addEventListener('pointerup', onUp);
	}
};
