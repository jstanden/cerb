/*
 * CerbUI.ContextChooser — RecordChooser, but you can switch the record TYPE on the fly via a leading
 * chip-head dropdown, then autocomplete within that type. Selections can span types. Single or multiple.
 *
 * A context entry with `fixedId` (e.g. the app:0 "Global" actor) is a DIRECT pick — choosing it from the
 * type menu selects {context, fixedId} immediately, with no search step.
 *
 * Context aliases are the FULL context ids (e.g. 'cerberusweb.contexts.worker') — getByAlias resolves them,
 * and the posted value is "context:id" (single → one hidden input; multiple → name[]).
 *
 * Usage:
 *   new CerbUI.ContextChooser(el, {
 *     contexts: [
 *       { alias:'cerberusweb.contexts.app',    label:'Everyone', icon:'globe', fixedId:0 },
 *       { alias:'cerberusweb.contexts.role',   label:'Role',   icon:'shield' },
 *       { alias:'cerberusweb.contexts.group',  label:'Group',  icon:'users' },
 *       { alias:'cerberusweb.contexts.worker', label:'Worker', icon:'user' },
 *     ],
 *     multiple: false,
 *     name: 'owner',
 *     defaultContext: 'cerberusweb.contexts.role',  // initial searchable type (does NOT reorder the menu)
 *     value: { context, id, label, image_url } | [ … ],  // PREFER server-rendered [data-context-id] seed
 *                                                         // markup over this JSON (the <li> approach; see
 *                                                         // RecordChooser._readMarkupValues + .Owner doc below)
 *     onSelect: (item) => {},
 *   });
 *
 * Inherits RecordChooser's API + UX. Requires CerbUI.Menu in addition to RecordChooser's deps.
 */
CerbUI.ContextChooser = class extends CerbUI.RecordChooser {
	constructor(el, opts = {}) {
		super(el, opts);
		if(!this.el) return;

		this.el.classList.add('cerb-ui-context-chooser');
		this.contexts = Array.isArray(opts.contexts) ? opts.contexts.filter(Boolean) : [];
		this._ctxByAlias = {};
		this.contexts.forEach((c) => { this._ctxByAlias[c.alias] = c; });

		// Active (searchable) context: a loaded value's type wins; else opts.defaultContext (when it names a
		// searchable context); else the first searchable. This never changes the menu order.
		const firstSearchable = this.contexts.find((c) => c.fixedId == null);
		const valueCtx = this.values[0] && this._ctxByAlias[this.values[0].context];
		const defaultCtx = opts.defaultContext && this._ctxByAlias[opts.defaultContext];
		if(valueCtx && valueCtx.fixedId == null)
			this.activeContext = this.values[0].context;
		else if(defaultCtx && defaultCtx.fixedId == null)
			this.activeContext = opts.defaultContext;
		else
			this.activeContext = firstSearchable ? firstSearchable.alias : (this.contexts[0] ? this.contexts[0].alias : '');

		// A per-context `query` scopes the record search within that type (e.g. only chart-kata widgets). The
		// active type's query wins; switching types swaps it (see _setContext).
		this._applyContextQuery();

		// The type-switcher head replaces the leading empty-state icon
		if(this.iconEl) this.iconEl.remove();
		this._buildHead();

		this._syncState();
	}

	// Point opts.query at the active context's scope query (empty when it has none) so the inherited search uses it.
	_applyContextQuery() {
		const c = this._ctxByAlias[this.activeContext];
		this.setQuery((c && c.query) || '');
	}

	// ── Seam overrides: search/identify/post by context AND id ──
	_context() { return this.activeContext; }
	_valueKey(item) { return (item.context || '') + ':' + item.id; }
	_hiddenValue(item) { return (item.context || '') + ':' + item.id; }

	// A bare "context:id" string is a valid `value` (the format you already have from a record). The colon
	// before the id is the separator (context ids have dots, not colons). Pass label/image_url too (or
	// server-render them into seed markup) for the chip's name + avatar.
	_normalizeValue(item) {
		if(item == null) return null;
		if(typeof item === 'object') return item;
		const s = String(item), i = s.lastIndexOf(':');
		return (i < 0) ? { context: '', id: s } : { context: s.slice(0, i), id: s.slice(i + 1) };
	}

	_ctxConfig(alias) { return this._ctxByAlias[alias] || { alias: alias, label: alias }; }

	// ── Chip-head type switcher ──
	_buildHead() {
		this.headEl = document.createElement('button');
		this.headEl.type = 'button';
		this.headEl.className = 'cerb-ui-record-chooser--ctxhead';
		this.headEl.innerHTML =
			'<span class="cerb-ui-record-chooser--ctxhead-icon cerb-icons" aria-hidden="true"></span>' +
			'<span class="cerb-ui-record-chooser--ctxhead-label"></span>' +
			'<span class="cerb-icons cerb-icon-chevron-down cerb-ui-record-chooser--ctxhead-caret" aria-hidden="true"></span>';
		this.fieldEl.insertBefore(this.headEl, this.fieldEl.firstChild);

		// A hidden <ul> that CerbUI.Menu enhances into the type dropdown
		this._menuUl = document.createElement('ul');
		this._menuUl.style.display = 'none';
		// Key menu items by index (not alias) so two entries can share an alias — e.g. "Me"
		// (worker:<id>, a direct pick) alongside a searchable "Worker".
		this.contexts.forEach((c, i) => {
			const li = document.createElement('li');
			li.setAttribute('data-ctx-index', String(i));
			if(c.icon) li.setAttribute('data-icon', c.icon);
			li.textContent = c.label || c.alias;
			this._menuUl.appendChild(li);
		});
		this.el.appendChild(this._menuUl);

		this._onHeadClick = (e) => { e.stopPropagation(); this._openTypeMenu(); };
		this.headEl.addEventListener('click', this._onHeadClick);

		this._updateHead();
	}

	_openTypeMenu() {
		if(!(window.CerbUI && CerbUI.Menu)) return;
		if(!this._menu) {
			this._menu = new CerbUI.Menu(this._menuUl, {
				onRenderItem: (renderedLi, sourceLi) => {
					const icon = sourceLi.getAttribute('data-icon');
					if(!icon) return;
					const g = document.createElement('span');
					g.className = 'cerb-icons cerb-icon-' + icon + ' cerb-ui-record-chooser--ctxmenu-icon';
					renderedLi.insertBefore(g, renderedLi.firstChild);
				},
				onSelect: (renderedLi, sourceLi) => {
					const i = parseInt(sourceLi.getAttribute('data-ctx-index'), 10);
					this._pickType(this.contexts[i]);
				},
			});
		}
		this._menu.open(this.headEl);
	}

	// A fixedId entry picks its record directly (no search); a normal type switches + opens the
	// autocomplete. Takes the exact context (not just an alias), so "Me" can share the worker alias.
	// `pickLabel` (optional) is the chosen value's label when it differs from the menu label — e.g. the
	// "Me" menu item resolves to the worker's actual name once selected.
	_pickType(ctx) {
		if(!ctx) return;
		if(ctx.fixedId != null) {
			this.core.close();
			this._choose({ context: ctx.alias, id: ctx.fixedId, label: ctx.pickLabel || ctx.label || ctx.alias, image_url: ctx.image_url || '' });
			return;
		}
		this._setContext(ctx.alias);
		this.core.open();
	}

	_setContext(alias) {
		this.activeContext = alias;
		this._applyContextQuery();
		this._updateHead();
		if(this.core.isOpen()) this.core.refresh(); // re-search in the new type
		if(!this.input.hidden) requestAnimationFrame(() => this.input.focus());
	}

	_updateHead() {
		if(!this.headEl) return;
		const c = this._ctxConfig(this.activeContext);
		const iconEl = this.headEl.querySelector('.cerb-ui-record-chooser--ctxhead-icon');
		const labelEl = this.headEl.querySelector('.cerb-ui-record-chooser--ctxhead-label');
		if(iconEl) iconEl.className = 'cerb-ui-record-chooser--ctxhead-icon cerb-icons' + (c.icon ? ' cerb-icon-' + c.icon : '');
		if(labelEl) labelEl.textContent = c.label || '';
		if(this.input) this.input.setAttribute('placeholder', 'Search ' + (c.label || '') + '…');
	}

	// Hide the head when single-filled (chip only), mirroring the input
	_syncState() {
		super._syncState();
		if(this.headEl) {
			const filled = this.values.length > 0;
			this.headEl.hidden = !this.opts.multiple && filled;
			this._updateHead();
		}
	}

	destroy() {
		if(this._menu) this._menu.destroy();
		if(this.headEl && this._onHeadClick) this.headEl.removeEventListener('click', this._onHeadClick);
		if(this._menuUl) this._menuUl.remove();
		super.destroy();
		this.el.classList.remove('cerb-ui-context-chooser');
	}
};

/*
 * CerbUI.ContextChooser.Owner — factory for the common actor/owner field. Merges your overrides over the
 * defaults (multiple:false, name:'owner', and the Me / Everyone / Role / Group / Worker contexts), posting
 * the single combined `name="owner"` value "context:id" (the legacy save contract).
 *
 * "Me" (worker:<meWorkerId>) and "Everyone" (app:0) are direct picks (no search) whose avatars are looked up
 * server-side. "Me" appears when meWorkerId is given (and shows the worker's name once selected, via meLabel);
 * "Everyone" is gated to superusers via allowApp (no peek, id 0).
 *
 * Config can come from opts OR from the element's data-* (so a peek can be pure markup + a one-line init;
 * opts win when both are present). data-allow-app / data-me-worker-id / data-me-label / data-me-image /
 * data-app-image. Seed the current value as [data-context-id] markup inside the element (or pass value).
 *
 *   CerbUI.ContextChooser.Owner(el, { meWorkerId: 5, meLabel: 'Jane Doe', meImageUrl: '…', allowApp: true, appImageUrl: '…' });
 *   CerbUI.ContextChooser.Owner(el);  // ← reads the above from data-* attributes
 */
CerbUI.ContextChooser.Owner = function(el, opts = {}) {
	el = (typeof el === 'string') ? document.querySelector(el) : el;
	opts = opts || {};
	const ds = (el && el.dataset) || {};
	const pick = (o, d) => (o != null ? o : d);

	const meWorkerId  = pick(opts.meWorkerId,  ds.meWorkerId ? parseInt(ds.meWorkerId, 10) : 0);
	const meLabel     = pick(opts.meLabel,     ds.meLabel || '');
	const meImageUrl  = pick(opts.meImageUrl,  ds.meImage || '');
	const allowApp    = pick(opts.allowApp,    ds.allowApp === '1' || ds.allowApp === 'true');
	const appImageUrl = pick(opts.appImageUrl, ds.appImage || '');

	const contexts = [];
	if(meWorkerId)
		contexts.push({ alias: 'cerberusweb.contexts.worker', label: 'Me', pickLabel: meLabel, icon: 'user', fixedId: meWorkerId, image_url: meImageUrl });
	if(allowApp)
		contexts.push({ alias: 'cerberusweb.contexts.app', label: 'Everyone', icon: 'globe', fixedId: 0, image_url: appImageUrl });
	contexts.push({ alias: 'cerberusweb.contexts.role',   label: 'Role',   icon: 'shield' });
	contexts.push({ alias: 'cerberusweb.contexts.group',  label: 'Group',  icon: 'users' });
	contexts.push({ alias: 'cerberusweb.contexts.worker', label: 'Worker', icon: 'user' });

	return new CerbUI.ContextChooser(el, Object.assign({ multiple: false, name: 'owner' }, opts, { contexts: contexts }));
};
