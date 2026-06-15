/*
 * CerbUI.PriorityPicker — a collapsed→expanded control that multi-selects a set of items AND orders them by
 * priority via drag. Selection and ordering are independent: set an overall priority once, then toggle today's
 * foci on/off without losing the order. Collapsed, it summarizes the selected items (colored pip + label, in
 * priority order). Expanded, it drops a floating panel listing every item with a drag handle, a toggle, and an
 * optional per-row action area. Composes CerbUI.Sortable (reorder) + CerbUI.Toggle (select); requires both.
 *
 * Usage (progressive enhancement — parse a <ul><li> in `el`, or pass `items`):
 *   <div id="my-projects"><ul hidden>
 *     <li data-id="cerb" data-color="green" data-selected="true">Cerb</li>
 *     <li data-id="wgm"  data-color="blue"  data-selected="true">WGM</li>
 *     <li data-id="research" data-color="gray">Research</li>
 *   </ul></div>
 *
 *   new CerbUI.PriorityPicker('#my-projects', {
 *     headerLabel: 'Projects',
 *     onChange: (state) => { ... },   // after Apply commits — persistence + linked-component hook
 *   });
 *
 * `state` = { selected: [ids in priority order], order: [all ids], items: [full model] }.
 *
 * Edits live in a working copy; the footer's Apply/Cancel appears once dirty. Apply commits + fires onChange;
 * Cancel / click-outside / Escape discard. Item CRUD (new/edit/delete) is hooks-only: wire it via the
 * `headerActions` / `itemActions` slots and call setItems() to re-render while selection + order are preserved.
 */
CerbUI.PriorityPicker = class {
	static _uid = 0;
	static _instances = new WeakMap();
	static from(el) { return CerbUI.PriorityPicker._instances.get(el); }

	// data-color names that map to palette tokens; anything else is treated as a raw CSS color
	static _TAG_COLORS = ['blue', 'gray', 'green', 'orange', 'purple', 'red'];

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.uid = ++CerbUI.PriorityPicker._uid;
		this.opts = Object.assign({
			items:           null,        // [{id,label,color,icon,selected,...}]; else parsed from <ul><li> in el
			icon:            '',          // optional leading cerb-icon name for the collapsed trigger
			onChange:        null,        // (state) after Apply commits
			onRender:        null,        // (state) whenever the collapsed summary (re)renders
			onRenderItem:    null,        // (rowLi, item) customize a popover row
			onRenderSummary: null,        // (item) -> node; customize a collapsed summary entry
			itemActions:     null,        // (item) -> node; per-row --right slot (edit/delete)
			headerActions:   null,        // node | html for the popover header --right slot (e.g. "New project")
			headerLabel:     'Items',
			headerSubtitle:  '',
			emptyText:       'None selected',
			maxHeight:       380,
			applyText:       'Apply Changes',
			cancelText:      'Cancel',
		}, opts);

		// committed model (source of truth); `working` is a clone made while the panel is open
		this.items   = this.opts.items ? this._normalize(this.opts.items) : this._parseSourceUl();
		this.working  = null;
		this.panel    = null;
		this.sortable = null;
		this.toggles  = [];

		this._onTriggerClick   = this._onTriggerClick.bind(this);
		this._onTriggerKey     = this._onTriggerKey.bind(this);
		this._onDocPointerDown = this._onDocPointerDown.bind(this);
		this._onDocKey         = this._onDocKey.bind(this);

		this.el.classList.add('cerb-ui-priority-picker');
		this._buildTrigger();
		this._renderSummary();

		CerbUI.PriorityPicker._instances.set(this.el, this);
	}

	// ── Model ───────────────────────────────────────────────────────────────

	_parseSourceUl() {
		const ul = this.el.querySelector('ul');
		if(!ul) return [];
		const items = Array.from(ul.querySelectorAll('li')).map((li, i) => ({
			id:       li.dataset.id || `pp${this.uid}-${i}`,
			label:    li.textContent.trim(),
			color:    li.dataset.color || '',
			icon:     li.dataset.icon || '',
			selected: li.dataset.selected === 'true' || li.hasAttribute('selected'),
		}));
		ul.remove(); // scaffolding only — the component renders its own markup
		return items;
	}

	_normalize(list) {
		return (list || []).map((it, i) => Object.assign({}, it, {
			id:       (it.id != null) ? String(it.id) : `pp${this.uid}-${i}`,
			label:    it.label != null ? String(it.label) : '',
			color:    it.color || '',
			icon:     it.icon || '',
			selected: !!it.selected,
		}));
	}

	_clone(list) {
		return list.map(it => Object.assign({}, it));
	}

	// The live model: the working draft while the panel is open, else the committed model.
	_active() {
		return this.working || this.items;
	}

	_stateOf(list) {
		return {
			selected: list.filter(it => it.selected).map(it => it.id),
			order:    list.map(it => it.id),
			items:    this._clone(list),
		};
	}

	_resolveColor(c) {
		return CerbUI.PriorityPicker._TAG_COLORS.includes(c) ? `var(--cerb-color-tag-${c})` : c;
	}

	// A leading adornment for an item: a cerb-icon if `icon` is set, else a colored pip.
	_adornment(item) {
		if(item.icon) {
			const ic = document.createElement('span');
			ic.className = 'cerb-icons cerb-icon-' + item.icon + ' cerb-ui-priority-picker--icon';
			ic.setAttribute('aria-hidden', 'true');
			return ic;
		}
		const pip = document.createElement('span');
		pip.className = 'cerb-ui-pip';
		if(item.color) pip.style.color = this._resolveColor(item.color);
		return pip;
	}

	// ── Trigger (collapsed) ───────────────────────────────────────────────────

	_buildTrigger() {
		this.trigger = document.createElement('button');
		this.trigger.type = 'button';
		this.trigger.className = 'cerb-ui-priority-picker--trigger';
		this.trigger.setAttribute('aria-haspopup', 'dialog');
		this.trigger.setAttribute('aria-expanded', 'false');

		if(this.opts.icon) {
			const lead = document.createElement('span');
			lead.className = 'cerb-icons cerb-icon-' + this.opts.icon + ' cerb-ui-priority-picker--lead';
			lead.setAttribute('aria-hidden', 'true');
			this.trigger.appendChild(lead);
		}

		this.summary = document.createElement('span');
		this.summary.className = 'cerb-ui-priority-picker--summary';
		this.trigger.appendChild(this.summary);

		const chevron = document.createElement('span');
		chevron.className = 'cerb-icons cerb-icon-chevron-down cerb-ui-priority-picker--chevron';
		chevron.setAttribute('aria-hidden', 'true');
		this.trigger.appendChild(chevron);

		this.trigger.addEventListener('click', this._onTriggerClick);
		this.trigger.addEventListener('keydown', this._onTriggerKey);
		this.el.appendChild(this.trigger);
	}

	// Render the collapsed summary from the live model — so toggle/drag edits preview here while open, and
	// revert on cancel (close() re-renders from the committed model once the draft is discarded).
	_renderSummary() {
		const model = this._active();
		this.summary.replaceChildren();
		const selected = model.filter(it => it.selected);

		if(!selected.length) {
			const empty = document.createElement('span');
			empty.className = 'cerb-ui-priority-picker--empty';
			empty.textContent = this.opts.emptyText;
			this.summary.appendChild(empty);
		} else {
			for(const item of selected) {
				if(typeof this.opts.onRenderSummary === 'function') {
					const node = this.opts.onRenderSummary(item);
					if(node) this.summary.appendChild(node);
					continue;
				}
				const chip = document.createElement('span');
				chip.className = 'cerb-ui-priority-picker--summary-item';
				chip.appendChild(this._adornment(item));
				const lbl = document.createElement('span');
				lbl.className = 'cerb-ui-priority-picker--summary-label';
				lbl.textContent = item.label; // textContent — never innerHTML for item data
				chip.appendChild(lbl);
				this.summary.appendChild(chip);
			}
		}

		if(typeof this.opts.onRender === 'function') this.opts.onRender(this._stateOf(model));
	}

	_onTriggerClick() {
		this.toggle();
	}

	_onTriggerKey(e) {
		if(this.isOpen()) return;
		if(e.key === ' ' || e.key === 'Enter' || e.key === 'ArrowDown') {
			e.preventDefault();
			this.open();
		}
	}

	// ── Panel (expanded) ──────────────────────────────────────────────────────

	open() {
		if(this.isOpen()) return;
		this.working = this._clone(this.items);
		this._buildPanel();
		document.body.appendChild(this.panel);
		this._place();
		this.trigger.setAttribute('aria-expanded', 'true');
		this.el.classList.add('cerb-ui-priority-picker--open');

		// Defer doc listeners so the opening click doesn't immediately close the panel.
		requestAnimationFrame(() => {
			document.addEventListener('pointerdown', this._onDocPointerDown, { capture: true });
			document.addEventListener('keydown', this._onDocKey);
		});
	}

	close() {
		if(!this.isOpen()) return;
		document.removeEventListener('pointerdown', this._onDocPointerDown, { capture: true });
		document.removeEventListener('keydown', this._onDocKey);
		if(this.sortable) { this.sortable.destroy(); this.sortable = null; }
		for(const t of this.toggles) t.destroy();
		this.toggles = [];
		this.panel.remove();
		this.panel = null;
		this.working = null;
		this.trigger.setAttribute('aria-expanded', 'false');
		this.el.classList.remove('cerb-ui-priority-picker--open');
		this._renderSummary(); // discard the draft preview, revert to the committed model
	}

	toggle() {
		if(this.isOpen()) this.close();
		else this.open();
	}

	isOpen() {
		return this.panel !== null;
	}

	_buildPanel() {
		this.panel = document.createElement('div');
		this.panel.className = 'cerb-ui-priority-picker--panel';
		this.panel.setAttribute('role', 'dialog');

		// Header: label/subtitle on the left, an optional actions slot on the right (cerb-ui-header chrome).
		const header = document.createElement('div');
		header.className = 'cerb-ui-header cerb-ui-header--tight cerb-ui-header--center';
		const headLeft = document.createElement('div');
		const label = document.createElement('div');
		label.className = 'cerb-ui-header--label';
		label.textContent = this.opts.headerLabel;
		headLeft.appendChild(label);
		if(this.opts.headerSubtitle) {
			const sub = document.createElement('div');
			sub.className = 'cerb-ui-priority-picker--subtitle';
			sub.textContent = this.opts.headerSubtitle;
			headLeft.appendChild(sub);
		}
		header.appendChild(headLeft);
		if(this.opts.headerActions) {
			const right = document.createElement('div');
			right.className = 'cerb-ui-header--right';
			this._fillSlot(right, this.opts.headerActions);
			header.appendChild(right);
		}
		this.panel.appendChild(header);

		// Item list (the Sortable container) — populated by _renderRows() below.
		this.list = document.createElement('ul');
		this.list.className = 'cerb-ui-priority-picker--list';
		this.list.style.maxHeight = this.opts.maxHeight + 'px';
		this.panel.appendChild(this.list);

		// Footer (Apply / Cancel) — hidden until dirty.
		this.footer = document.createElement('div');
		this.footer.className = 'cerb-ui-priority-picker--footer';
		this.footer.hidden = true;
		const cancelBtn = document.createElement('button');
		cancelBtn.type = 'button';
		cancelBtn.className = 'cerb-ui-button cerb-ui-button--subtle cerb-ui-priority-picker--cancel';
		cancelBtn.textContent = this.opts.cancelText;
		cancelBtn.addEventListener('click', () => this.close());
		const applyBtn = document.createElement('button');
		applyBtn.type = 'button';
		applyBtn.className = 'cerb-ui-button cerb-ui-priority-picker--apply';
		applyBtn.textContent = this.opts.applyText;
		applyBtn.addEventListener('click', () => this._apply());
		this.footer.appendChild(cancelBtn);
		this.footer.appendChild(applyBtn);
		this.panel.appendChild(this.footer);

		this._renderRows();
	}

	// (Re)populate the panel's list from the working draft and wire reorder. Does NOT mutate the draft.
	_renderRows() {
		if(this.sortable) { this.sortable.destroy(); this.sortable = null; }
		for(const t of this.toggles) t.destroy();
		this.toggles = [];
		this.list.replaceChildren();
		for(const item of this.working) this.list.appendChild(this._buildRow(item));
		// Reorder: drag only from the handle; reflect the new order + preview in the draft.
		this.sortable = new CerbUI.Sortable(this.list, {
			handle: '.cerb-ui-priority-picker--handle',
			onSorted: () => { this._syncOrderFromDom(); this._updateFooter(); this._renderSummary(); },
		});
	}

	_buildRow(item) {
		const li = document.createElement('li');
		li.className = 'cerb-ui-priority-picker--item';
		li.dataset.id = item.id;
		if(item.selected) li.classList.add('cerb-ui-priority-picker--item-selected');

		const handle = document.createElement('span');
		handle.className = 'cerb-ui-priority-picker--handle';
		handle.setAttribute('aria-hidden', 'true');
		li.appendChild(handle);

		li.appendChild(this._adornment(item));

		const lbl = document.createElement('span');
		lbl.className = 'cerb-ui-priority-picker--label';
		lbl.textContent = item.label;
		li.appendChild(lbl);

		// Selection toggle on the right (label's flex-grow pushes it past the content)
		const toggleLabel = document.createElement('label');
		toggleLabel.className = 'cerb-ui-toggle';
		const input = document.createElement('input');
		input.type = 'checkbox';
		const slider = document.createElement('span');
		slider.className = 'cerb-ui-toggle--slider';
		toggleLabel.appendChild(input);
		toggleLabel.appendChild(slider);
		li.appendChild(toggleLabel);

		if(typeof this.opts.itemActions === 'function') {
			const actions = document.createElement('span');
			actions.className = 'cerb-ui-priority-picker--actions';
			const node = this.opts.itemActions(item);
			if(node) actions.appendChild(node);
			li.appendChild(actions);
		}

		const toggle = new CerbUI.Toggle(toggleLabel, {
			checked: item.selected,
			onChange: (checked) => {
				item.selected = checked;
				li.classList.toggle('cerb-ui-priority-picker--item-selected', checked);
				this._updateFooter();
				this._renderSummary(); // preview the new selection on the collapsed trigger
			},
		});
		this.toggles.push(toggle);

		if(typeof this.opts.onRenderItem === 'function') this.opts.onRenderItem(li, item);

		return li;
	}

	// Reorder this.working to match the current DOM order of rows.
	_syncOrderFromDom() {
		const order = Array.from(this.list.querySelectorAll('.cerb-ui-priority-picker--item'))
			.map(li => li.dataset.id);
		this.working.sort((a, b) => order.indexOf(a.id) - order.indexOf(b.id));
	}

	// ── Dirty / apply / cancel ────────────────────────────────────────────────

	// A signature over (id, selected) in order — captures both reorder and selection changes.
	_signature(list) {
		return list.map(it => it.id + ':' + (it.selected ? '1' : '0')).join(',');
	}

	_isDirty() {
		return this.working && this._signature(this.working) !== this._signature(this.items);
	}

	_updateFooter() {
		if(this.footer) this.footer.hidden = !this._isDirty();
	}

	_apply() {
		this.items = this._clone(this.working);
		this.close(); // clears the draft + re-renders the summary from the now-committed model
		if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getState());
	}

	// ── Positioning ─────────────────────────────────────────────────────────

	_place() {
		const el = this.panel;
		const vw = document.documentElement.clientWidth;
		const vh = document.documentElement.clientHeight;

		el.style.left = '-9999px';
		el.style.top  = '0px';
		const pw = el.offsetWidth  || 360;
		const ph = el.offsetHeight || 200;

		const r = this.trigger.getBoundingClientRect();
		let x = r.left;
		// Flip above the trigger when there isn't room below.
		let y = (r.bottom + 4 + ph > vh && r.top - ph - 4 > 0) ? (r.top - ph - 4) : (r.bottom + 4);
		y = Math.max(4, Math.min(y, vh - ph - 4));
		if(x + pw > vw) x = vw - pw - 4;
		x = Math.max(4, x);

		el.style.left = (x + window.scrollX) + 'px';
		el.style.top  = (y + window.scrollY) + 'px';
	}

	// ── Document listeners ────────────────────────────────────────────────────

	_onDocPointerDown(e) {
		if(this.panel && this.panel.contains(e.target)) return;
		if(this.trigger.contains(e.target)) return; // let the trigger's own click toggle
		this.close(); // click-outside = cancel
	}

	_onDocKey(e) {
		if(e.key === 'Escape') {
			e.preventDefault();
			this.close();
			this.trigger.focus();
		}
	}

	// ── Slots ─────────────────────────────────────────────────────────────────

	_fillSlot(container, content) {
		if(content instanceof Node) container.appendChild(content);
		else if(typeof content === 'string') container.innerHTML = content; // caller-owned trusted markup
	}

	// ── Public API ──────────────────────────────────────────────────────────

	// State/items reflect the live draft while open (so consumer CRUD round-trips see in-progress edits), or
	// the committed model once closed.
	getState() {
		return this._stateOf(this._active());
	}

	getItems() {
		return this._clone(this._active());
	}

	getSelected() {
		return this._active().filter(it => it.selected).map(it => it.id);
	}

	setSelected(ids) {
		const want = new Set((ids || []).map(String));
		this._applyModel(this._active().map(it => Object.assign({}, it, { selected: want.has(it.id) })));
		return this;
	}

	setOrder(ids) {
		const order = (ids || []).map(String);
		this._applyModel(this._active().slice().sort((a, b) => {
			const ia = order.indexOf(a.id), ib = order.indexOf(b.id);
			return (ia === -1 ? Infinity : ia) - (ib === -1 ? Infinity : ib);
		}));
		return this;
	}

	// Swap the item list while preserving selection + priority order of survivors (matched by id). New items
	// are appended at the end; missing items are dropped. Merges against the live draft when open, so
	// in-progress toggles/reorders survive (e.g. adding a project while editing keeps your current selection).
	setItems(items, options = {}) {
		const preserve = options.preserveSelection !== false;
		const incoming = this._normalize(items);
		const base = this._active();

		if(preserve) {
			const incomingIds = new Set(incoming.map(it => it.id));
			const baseById = new Map(base.map(it => [it.id, it]));
			// Survivors keep their draft order + selection; new items follow in incoming order.
			const survivors = base
				.filter(it => incomingIds.has(it.id))
				.map(it => Object.assign({}, incoming.find(n => n.id === it.id), { selected: it.selected }));
			const added = incoming.filter(it => !baseById.has(it.id));
			this._applyModel(survivors.concat(added));
		} else {
			this._applyModel(incoming);
		}
		return this;
	}

	// Apply a new model to whichever copy is live (the draft when open, else committed), then refresh the UI.
	_applyModel(newList) {
		if(this.working) this.working = newList;
		else this.items = newList;
		if(this.isOpen()) {
			this._renderRows();
			this._updateFooter();
			this._place();
		}
		this._renderSummary();
	}

	destroy() {
		this.close();
		CerbUI.PriorityPicker._instances.delete(this.el);
		this.trigger.removeEventListener('click', this._onTriggerClick);
		this.trigger.removeEventListener('keydown', this._onTriggerKey);
		this.trigger.remove();
		this.el.classList.remove('cerb-ui-priority-picker');
	}
};
