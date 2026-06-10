/*
 * CerbUI.Dialog — a draggable / resizable / minimizable dialog (zero-dependency).
 *
 * Wraps an existing content element in a floating dialog appended to <body>. The content is restored to
 * its original DOM position on destroy(). Colors are tokens, so dark mode is automatic.
 *
 * Usage:
 *   const dlg = new CerbUI.Dialog(contentEl, { title: 'Ticket', header: 'bar' });
 *   dlg.open(); dlg.close(); CerbUI.Dialog.from(contentEl);
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
 *   fixed, closeOnEscape, dragHandle (selector), onOpen, onClose (return false to veto), onMinimize,
 *   onDragged, onResized. Also dispatches `cerb-ui-dialog:open` / `:close` on the content element.
 */
CerbUI.Dialog = class {
	static _uid = 0;
	static _zTop = 9000; // above .cerb-float (2500) / jQuery dialogs (~100); below tooltips/menus (10000+)
	static _instances = new WeakMap();
	static _namespaces = new Map();

	static from(el) { return CerbUI.Dialog._instances.get(el); }

	constructor(contentEl, opts = {}) {
		this.uid = ++CerbUI.Dialog._uid;
		this.opts = Object.assign({
			title:      '',
			header:     'bar',
			draggable:  true,
			resizable:  true,
			closable:   true,
			minimizable: null, // resolved below: default true only for the 'bar' header
			modal:      false,
			width:      400,
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
		if(this.opts.minimizable == null) this.opts.minimizable = (this.opts.header === 'bar');

		this.w = this.opts.width;
		this.h = null; // null = auto height until the first n/s resize
		this.x = 0;
		this.y = 0;
		this.minimized = false;
		this._open = false;
		this.docKeydown = null;
		this.backdrop = null;
		this.minimizeBtn = null;
		this.titleEl = null;
		this._injectedControls = null;   // floating controls docked into the content's header
		this._createdRightToolbar = null; // a .cerb-ui-header--right we created to dock them into

		this.innerContent = contentEl;
		this.origParent = contentEl.parentNode;
		this.origNextSibling = contentEl.nextSibling;

		this.onPointerDown = this.onPointerDown.bind(this);

		const titleId = `cerb-ui-dialog-${this.uid}-title`;

		const dlg = document.createElement('div');
		dlg.className =
			'cerb-ui-dialog cerb-ui-dialog--hidden'
			+ (this.opts.fixed     ? ' cerb-ui-dialog--fixed'     : '')
			+ (this.opts.draggable ? ' cerb-ui-dialog--draggable' : '')
			+ (this.opts.resizable ? ' cerb-ui-dialog--resizable' : '')
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
	}

	// A control cluster (minimize? + close?) shared by the bar and the floating header.
	_buildControls() {
		const controls = document.createElement('div');
		controls.className = 'cerb-ui-dialog--controls';

		if(this.opts.minimizable) {
			this.minimizeBtn = this._makeBtn('cerb-icon-chevron-up', 'Minimize', () => this._toggleMinimize());
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
		const vh = window.innerHeight;
		const w  = this.el.offsetWidth;
		const h  = this.el.offsetHeight;

		if(this.opts.position) {
			this.x = this.opts.position.x;
			this.y = this.opts.position.y;
		} else if(inheritedPos) {
			this.x = inheritedPos.x;
			this.y = inheritedPos.y;
		} else {
			const ox = this.opts.fixed ? 0 : window.scrollX;
			const oy = this.opts.fixed ? 0 : window.scrollY;
			this.x = Math.max(0, ox + Math.round((vw - w) / 2));
			this.y = Math.max(0, oy + Math.round((vh - h) / 3));
		}

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

		if(this.opts.onOpen) this.opts.onOpen();
		this.innerContent.dispatchEvent(new CustomEvent('cerb-ui-dialog:open', { bubbles: true }));
	}

	close() {
		if(!this._open) return false;
		if(this.opts.onClose && this.opts.onClose() === false) return false;

		this._open = false;
		this.el.classList.add('cerb-ui-dialog--hidden');
		this._removeBackdrop();

		if(this.minimized) {
			this.minimized = false;
			this.el.classList.remove('cerb-ui-dialog--minimized');
			if(this.minimizeBtn) this._setMinimizeIcon(false);
			this.el.style.height = (this.h !== null ? this.h + 'px' : '');
		}

		if(this.docKeydown) {
			document.removeEventListener('keydown', this.docKeydown);
			this.docKeydown = null;
		}

		if(CerbUI.Dialog._namespaces.get(this.opts.namespace) === this) {
			CerbUI.Dialog._namespaces.delete(this.opts.namespace);
		}

		this.innerContent.dispatchEvent(new CustomEvent('cerb-ui-dialog:close', { bubbles: true }));
		return true;
	}

	isOpen() {
		return this._open;
	}

	setTitle(title) {
		this.opts.title = title;
		if(this.titleEl) this.titleEl.textContent = title;
	}

	destroy() {
		CerbUI.Dialog._instances.delete(this.innerContent);
		if(CerbUI.Dialog._namespaces.get(this.opts.namespace) === this) {
			CerbUI.Dialog._namespaces.delete(this.opts.namespace);
		}

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

	_setMinimizeIcon(minimized) {
		const icon = this.minimizeBtn.querySelector('.cerb-icons');
		if(icon) icon.className = 'cerb-icons ' + (minimized ? 'cerb-icon-chevron-down' : 'cerb-icon-chevron-up');
		this.minimizeBtn.setAttribute('aria-label', minimized ? 'Restore' : 'Minimize');
	}

	_toggleMinimize() {
		this.minimized = !this.minimized;
		this.el.classList.toggle('cerb-ui-dialog--minimized', this.minimized);
		// Clear explicit height so the dialog collapses to the header; restore on un-minimize.
		this.el.style.height = this.minimized ? '' : (this.h !== null ? this.h + 'px' : '');
		this._setMinimizeIcon(this.minimized);
		if(this.opts.onMinimize) this.opts.onMinimize(this.minimized);
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
			if(this.opts.onResized) this.opts.onResized(this.w, this.h);
		};

		document.addEventListener('pointermove', onMove);
		document.addEventListener('pointerup', onUp);
	}
};
