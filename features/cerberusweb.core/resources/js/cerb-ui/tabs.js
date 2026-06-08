/*
 * CerbUI.Tabs — a lightweight tab strip that enhances a <ul> of <li><a> items:
 *
 *   Anchor tabs  — <a href="#panel-id">  — toggles a user-authored content div (a sibling of the <ul>).
 *   Dynamic tabs — <a href="/some/url">  — fetches HTML on first activation; manages its own panel div.
 *
 * Dynamic panels show a spinner while loading and cache their content; call refresh(index) to force a reload.
 * They load through Cerb's genericAjaxGet, which injects the fragment with jQuery — so any <script> tags in
 * the returned HTML execute under the page's CSP nonce (the canonical Cerb way; a raw fetch()+innerHTML would
 * NOT run them). A dynamic tab's href carries the ajax args (the query string after ajax.php?).
 *
 * Usage:
 *   const tabs = new CerbUI.Tabs(ul, { remember: 'ticketProfile', onTabSelected: (i, tab) => {...} });
 *   tabs.select(1);  tabs.refresh();  tabs.active;  tabs.activeTab;  CerbUI.Tabs.from(ul);
 *
 * CSS lives in cerb.css (.cerb-ui-tabs--*) — this component never injects styles.
 */
CerbUI.Tabs = class {
	static _uid = 0;
	static _instances = new WeakMap();

	static _DEFAULTS = {
		active: undefined,       // initial 0-based index (overrides `remember`); undefined = use remember/0
		storagePrefix: 'cerb-tabs', // localStorage key prefix; key = `${storagePrefix}[${remember}]`
		onTabSelected: null,     // (index, tab) after a tab is shown
		onBeforeTabLoad: null,   // (index, tab) before activation; return false to cancel
		onAfterTabLoad: null,    // (index, tab) when the panel is ready (now for static/cached, post-fetch for dynamic)
		onTabLoadError: null,    // (index, tab, status) on dynamic fetch failure; return false to suppress the message
	};

	static from(el) {
		return CerbUI.Tabs._instances.get(el);
	}

	constructor(ul, opts = {}) {
		this.ul = ul;
		this.opts = Object.assign({}, CerbUI.Tabs._DEFAULTS, opts);
		this.tabs = [];
		this.activeIndex = -1;
		this.storageKey = null;
		this.uid = ++CerbUI.Tabs._uid;

		if(opts.remember) this.storageKey = this.opts.storagePrefix + '[' + opts.remember + ']';

		// Bind the delegated handlers once so add/removeEventListener share a stable reference
		this._onUlClick = this._onUlClick.bind(this);
		this._onUlKeydown = this._onUlKeydown.bind(this);

		this._init(opts.active);
		this.ul.addEventListener('click', this._onUlClick);
		this.ul.addEventListener('keydown', this._onUlKeydown);
		CerbUI.Tabs._instances.set(ul, this);
	}

	// ── Public API ──────────────────────────────────────────────────────────

	get active() {
		return this.activeIndex;
	}

	get el() {
		return this.ul;
	}

	get allTabs() {
		return this.tabs.map((tab, i) => this._makeInfo(i, tab));
	}

	get activeTab() {
		const tab = this.tabs[this.activeIndex];
		return tab ? this._makeInfo(this.activeIndex, tab) : null;
	}

	// Activate a tab by 0-based index. Fires onBeforeTabLoad / onTabSelected.
	select(index) {
		this._activateTab(index);
	}

	// Re-fetch a dynamic tab's content (ignores cache). Reloads immediately if active, else on next select.
	// Omit index to refresh the active tab. Does not fire onTabSelected.
	refresh(index = this.activeIndex) {
		const tab = this.tabs[index];
		if(!tab || !tab.isDynamic) return;
		tab.loaded = false;
		if(this.activeIndex === index) this._loadPanel(tab);
	}

	// Re-parse the <ul> to pick up <li> items added or removed since construction.
	sync() {
		const activeLi = this.activeIndex >= 0 ? (this.tabs[this.activeIndex] && this.tabs[this.activeIndex].li) : null;
		const lis = Array.from(this.ul.querySelectorAll(':scope > li'));
		const newTabs = [];

		for(let i = 0; i < lis.length; i++) {
			const li = lis[i];
			const existing = this.tabs.find(t => t.li === li);
			if(existing) {
				newTabs.push(existing);
			} else {
				const tab = this._buildTabState(li, i);
				if(tab) newTabs.push(tab);
			}
		}

		// Remove auto-created panels for tabs that no longer exist.
		for(const tab of this.tabs) {
			if(tab.isDynamic && !newTabs.includes(tab)) tab.panel.remove();
		}

		this.tabs = newTabs;
		this._applyAttrs();

		// Try to keep the previously active tab; fall back to tab 0.
		const next = activeLi ? Math.max(0, this.tabs.findIndex(t => t.li === activeLi)) : 0;

		this.activeIndex = -1; // force re-activation after _applyAttrs reset everything
		if(this.tabs.length > 0) this._activateTab(next, false);
	}

	destroy() {
		CerbUI.Tabs._instances.delete(this.ul);
		this.ul.removeEventListener('click', this._onUlClick);
		this.ul.removeEventListener('keydown', this._onUlKeydown);
		this.ul.removeAttribute('role');
		this.ul.classList.remove('cerb-ui-tabs');

		for(const tab of this.tabs) {
			tab.li.classList.remove('cerb-ui-tabs--tab', 'cerb-ui-tabs--tab-active');
			tab.li.removeAttribute('role');
			tab.a.removeAttribute('role');
			tab.a.removeAttribute('id');
			tab.a.removeAttribute('aria-selected');
			tab.a.removeAttribute('aria-controls');
			tab.a.removeAttribute('tabindex');
			tab.a.removeAttribute('data-label');
			tab.panel.removeAttribute('role');
			tab.panel.removeAttribute('aria-labelledby');
			tab.panel.removeAttribute('tabindex');
			tab.panel.classList.remove('cerb-ui-tabs--panel', 'cerb-ui-tabs--panel-active');
			if(tab.isDynamic) tab.panel.remove();
		}
	}

	// ── Init ─────────────────────────────────────────────────────────────────

	_init(explicitActive) {
		this.ul.setAttribute('role', 'tablist');
		this.ul.classList.add('cerb-ui-tabs');

		const lis = Array.from(this.ul.querySelectorAll(':scope > li'));
		for(let i = 0; i < lis.length; i++) {
			const tab = this._buildTabState(lis[i], i);
			if(tab) this.tabs.push(tab);
		}

		this._applyAttrs();

		if(this.tabs.length > 0) {
			let initial;
			if(explicitActive !== undefined) {
				initial = Math.max(0, Math.min(explicitActive, this.tabs.length - 1));
			} else if(this.storageKey) {
				try {
					const stored = parseInt(localStorage.getItem(this.storageKey) ?? '', 10);
					initial = (!isNaN(stored) && stored >= 0 && stored < this.tabs.length) ? stored : 0;
				} catch(e) { initial = 0; }
			} else {
				initial = 0;
			}
			this._activateTab(initial, false);
		}
	}

	// Build a tab record from a <li>. Returns null if no <a>, or an #anchor href points to a missing div.
	_buildTabState(li, index) {
		const a = li.querySelector(':scope > a');
		if(!a) return null;

		const href = a.getAttribute('href') ?? '';
		const isDynamic = href !== '' && !href.startsWith('#');
		let panel;

		if(isDynamic) {
			panel = document.createElement('div');
			panel.id = 'cerb-ui-tabs-panel-' + this.uid + '-' + index;
			// Insert directly after the last known panel (or after the <ul>).
			this._lastPanelRef().insertAdjacentElement('afterend', panel);
		} else {
			const id = href.slice(1);
			const found = id ? document.getElementById(id) : null;
			if(!found) return null;
			panel = found;
		}

		return { li, a, isDynamic, href, panel, loaded: false };
	}

	// The element after which the next dynamic panel should be inserted.
	_lastPanelRef() {
		let ref = this.ul;
		for(const tab of this.tabs) {
			if(ref.compareDocumentPosition(tab.panel) & Node.DOCUMENT_POSITION_FOLLOWING)
				ref = tab.panel;
		}
		return ref;
	}

	// Write ARIA + classes to every tab/panel in their inactive state; _activateTab promotes one afterwards.
	_applyAttrs() {
		for(let i = 0; i < this.tabs.length; i++) {
			const { li, a, panel } = this.tabs[i];
			const tabId = 'cerb-ui-tabs-tab-' + this.uid + '-' + i;

			li.classList.add('cerb-ui-tabs--tab');
			li.setAttribute('role', 'presentation');

			a.setAttribute('role', 'tab');
			a.setAttribute('id', tabId);
			a.setAttribute('aria-controls', panel.id);
			a.setAttribute('aria-selected', 'false');
			a.setAttribute('tabindex', '-1');
			a.setAttribute('data-label', a.textContent ?? '');

			panel.setAttribute('role', 'tabpanel');
			panel.setAttribute('aria-labelledby', tabId);
			panel.setAttribute('tabindex', '-1');
			panel.classList.add('cerb-ui-tabs--panel');
			panel.classList.remove('cerb-ui-tabs--panel-active');
		}
	}

	// ── Activation ────────────────────────────────────────────────────────────

	_activateTab(index, fireCallbacks = true) {
		if(index < 0 || index >= this.tabs.length) return;
		if(index === this.activeIndex) return;

		const tab = this.tabs[index];
		const info = this._makeInfo(index, tab);

		if(fireCallbacks && typeof this.opts.onBeforeTabLoad === 'function'
			&& this.opts.onBeforeTabLoad(index, info) === false) return;

		// Deactivate the current tab (skipped on first call when activeIndex is -1).
		if(this.activeIndex >= 0 && this.activeIndex < this.tabs.length) {
			const prev = this.tabs[this.activeIndex];
			prev.li.classList.remove('cerb-ui-tabs--tab-active');
			prev.a.setAttribute('aria-selected', 'false');
			prev.a.setAttribute('tabindex', '-1');
			prev.panel.classList.remove('cerb-ui-tabs--panel-active');
			prev.panel.setAttribute('tabindex', '-1');
		}

		// Activate the new tab.
		tab.li.classList.add('cerb-ui-tabs--tab-active');
		tab.a.setAttribute('aria-selected', 'true');
		tab.a.setAttribute('tabindex', '0');
		tab.panel.classList.add('cerb-ui-tabs--panel-active');
		tab.panel.setAttribute('tabindex', '0');
		this.activeIndex = index;
		if(this.storageKey) {
			try { localStorage.setItem(this.storageKey, String(index)); } catch(e) { /* quota / private browsing */ }
		}

		if(tab.isDynamic && !tab.loaded) {
			this._loadPanel(tab);
		} else if(typeof this.opts.onAfterTabLoad === 'function') {
			this.opts.onAfterTabLoad(index, info);
		}

		if(fireCallbacks && typeof this.opts.onTabSelected === 'function')
			this.opts.onTabSelected(index, info);
	}

	// ── Async content loading ─────────────────────────────────────────────────

	// Load a dynamic tab's panel via Cerb's genericAjaxGet, which injects the fetched fragment with jQuery —
	// so its <script> tags run under the page's CSP nonce (the canonical Cerb path). tab.href is the ajax
	// args (the query string after ajax.php?). Falls back to a non-script fetch only if the helper is absent.
	_loadPanel(tab) {
		tab.panel.innerHTML = '';
		const wrap = document.createElement('div');
		wrap.className = 'cerb-ui-tabs--panel-loading';
		wrap.appendChild((window.CerbUI && CerbUI.Spinner) ? CerbUI.Spinner.create() : document.createElement('span'));
		tab.panel.appendChild(wrap);

		const onDone = () => {
			tab.loaded = true;
			const i = this.tabs.indexOf(tab);
			if(i >= 0 && typeof this.opts.onAfterTabLoad === 'function')
				this.opts.onAfterTabLoad(i, this._makeInfo(i, tab));
		};
		const onError = (status) => {
			const i = this.tabs.indexOf(tab);
			// loaded stays false so refresh() / the next select() retries.
			return (i >= 0 && typeof this.opts.onTabLoadError === 'function')
				? this.opts.onTabLoadError(i, this._makeInfo(i, tab), status) : undefined;
		};

		if(typeof genericAjaxGet === 'function' && window.jQuery) {
			// genericAjaxGet injects with jQuery (runs fragment scripts under the page nonce) + handles
			// the loading fade and session/error alerts.
			genericAjaxGet(jQuery(tab.panel), tab.href, function() { onDone(); }, {
				error: function(xhr) { onError(xhr && typeof xhr.status === 'number' ? xhr.status : null); }
			});
			return;
		}

		// Degraded fallback when Cerb's AJAX helper isn't present: fetch text + inject WITHOUT running scripts.
		fetch(tab.href).then(res => {
			if(!res.ok) throw res.status;
			return res.text();
		}).then(html => {
			tab.panel.innerHTML = html;
			onDone();
		}).catch(status => {
			tab.panel.innerHTML = '';
			if(onError(typeof status === 'number' ? status : null) !== false) {
				const errEl = document.createElement('p');
				errEl.className = 'cerb-ui-tabs--panel-error';
				errEl.textContent = 'Failed to load content.';
				tab.panel.appendChild(errEl);
			}
		});
	}

	// ── Events ────────────────────────────────────────────────────────────────

	_onUlClick(e) {
		e.preventDefault();
		const li = e.target.closest('li.cerb-ui-tabs--tab');
		if(!li) return;
		const index = this.tabs.findIndex(t => t.li === li);
		if(index >= 0) this._activateTab(index);
	}

	_onUlKeydown(e) {
		const active = document.activeElement;
		const a = active ? active.closest('.cerb-ui-tabs--tab > a') : null;
		if(!a) return;
		const index = this.tabs.findIndex(t => t.a === a);
		if(index < 0) return;

		const last = this.tabs.length - 1;

		switch(e.key) {
			case 'ArrowLeft':
			case 'ArrowRight': {
				e.preventDefault();
				const dir = e.key === 'ArrowRight' ? 1 : -1;
				const next = (index + dir + this.tabs.length) % this.tabs.length;
				this._activateTab(next);
				this.tabs[next].a.focus();
				break;
			}
			case 'Home':
				e.preventDefault();
				this._activateTab(0);
				this.tabs[0].a.focus();
				break;
			case 'End':
				e.preventDefault();
				this._activateTab(last);
				this.tabs[last].a.focus();
				break;
		}
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	_makeInfo(index, tab) {
		return { index, isDynamic: tab.isDynamic, href: tab.href, li: tab.li, panel: tab.panel };
	}
};
