/*
 * CerbUI.RecordChooser — pick record(s) of a single context (large-set safe). A *Chooser*: popup-search
 * based, with inline autocomplete as a time-saver. Reuses Cerb's ACL-filtered record autocomplete + the
 * legacy chooserOpen worklist popup; avatars come from the dictionary _image_url (real pic or monogram).
 *
 *   - Single (default): no value → leading icon + autocomplete input + magnifier (opens chooserOpen).
 *     A value → a chip (click = card peek) with only × to clear; no search until cleared.
 *   - Multiple (`multiple:true`): a tag/token input. Selections (autocomplete OR popup) stack as tiles;
 *     the magnifier stays at the right and the autocomplete input sits at the end. Click empty space to
 *     type; click a tile for its card peek; × removes it.
 *
 * Usage:
 *   new CerbUI.RecordChooser(el, {
 *     context: 'worker', multiple: false,
 *     searchPlaceholder: 'Search workers…',
 *     emptyIcon: 'file',                 // empty-state glyph (cerb-icons name)
 *     name: 'worker_id',                 // hidden input (single) / name[] (multiple) so it posts
 *     value: {id,label,image_url} | [ … ],  // PREFER server-rendered [data-context-id] seed markup over
 *                                           // this JSON option — see _readMarkupValues (the <li> approach)
 *     onSelect: (item) => { ... },       // fired per ADD only (kept as-is; callers rely on it)
 *     onChange: (values) => { ... },     // fired after ANY change — add, remove, or clear
 *     ghosts: [{label, icon_name}],      // display-only tiles — see below
 *   });
 *
 * GHOST TILES are values the field DISPLAYS but does not own: a set that already applies from somewhere else
 * (an inherited default, a group's grant) shown inline with what this field adds to it. They carry no hidden
 * input, so they post nothing, and they have no remove button, because this field is not where they come from.
 * The host manages them -- setGhosts() -- which is what lets them track a live edit somewhere else on the form.
 * They render first, ahead of the real values, and are the only tiles allowed to have no id at all.
 *
 * A ghost carrying a `value` (or an `id`) is also EXCLUDED from selection -- the autocomplete stops offering it
 * and the search popup refuses it -- so the same thing can't be stacked on top of the tile already showing it.
 * One with neither is display-only and blocks nothing, which is correct for a reference that resolved to no
 * record at all.
 *
 * They exist because a placeholder can only describe an inherited set while the field is EMPTY -- it disappears
 * the moment you add anything, which is exactly when you most need to see what you are adding TO.
 *
 * API: rc.getValue(); rc.setValue(item|array|null); rc.setGhosts(list); rc.clear(); rc.openSearch(); rc.destroy().
 * Requires chooserCore + genericAjaxGet + (for the popup/peek) genericAjaxPopup + jQuery.
 */
CerbUI.RecordChooser = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.RecordChooser._instances.get(el); }

	// Attach a record-search popup to a trigger element (e.g. a clickable "ID" label) that, on pick, inserts
	// the chosen record's id into a sibling text field — for DUAL-PURPOSE fields that also accept render-time
	// placeholders ({{...}}), where we only write a literal id when the user explicitly picks one. Replaces the
	// legacy `<a class="cerb-chooser">.cerbChooserTrigger()` + `cerb-chooser-selected` wiring. Reuses chooserCore
	// (its own search box, anchored to the link) + the autocomplete endpoint. The context is read from the
	// link's `data-context` unless `opts.context` is given. Default insert format is `id{# label #}` (a Cerb
	// placeholder comment — the `#}` keeps the human label visible while the value resolves to the id).
	static pickerLink(linkEl, opts = {}) {
		linkEl = (linkEl && linkEl.jquery) ? linkEl[0] : linkEl;
		if(!linkEl || !window.CerbUI || !CerbUI.chooserCore) return null;

		// Read live so a dynamic data-context (e.g. a "Type" <select> driving the search) takes effect per
		// search; static callers (fixed data-context / opts.context) re-read the same value — no change.
		const getContext = () => opts.context || linkEl.getAttribute('data-context') || '';
		const query = opts.query || '';
		let inputEl = opts.input || null;
		if(inputEl && inputEl.jquery) inputEl = inputEl[0];

		const onPick = (typeof opts.onPick === 'function') ? opts.onPick : function(item) {
			if(!inputEl) return;
			const val = item.id + '{# ' + item.label + ' #}';
			// The input may have been enhanced into a CerbUI.ScriptingEditor (e.g. legacy-bot `.placeholders`
			// fields): the original input is then a hidden value-carrier and the visible surface is the editor,
			// which only syncs editor→carrier. Writing inputEl.value alone updates the carrier but not the visible
			// editor (the pick wouldn't appear until reload). Drive the editor instead when present.
			const wrap = inputEl.closest && inputEl.closest('.cerb-ui-scriptingeditor');
			const ed = (wrap && window.CerbUI && CerbUI.ScriptingEditor && CerbUI.ScriptingEditor.from)
				? CerbUI.ScriptingEditor.from(wrap) : null;
			if(ed && typeof ed.setValue === 'function') ed.setValue(val);
			else inputEl.value = val;
		};

		const search = (term) => new Promise((resolve) => {
			if(typeof genericAjaxGet !== 'function') { resolve({ results: [], more: false }); return; }
			const context = getContext();
			const args = 'c=internal&a=invoke&module=records&action=autocomplete'
				+ '&context=' + encodeURIComponent(context)
				+ '&query=' + encodeURIComponent(query)
				+ '&term=' + encodeURIComponent(term || '');
			genericAjaxGet('', args, (json) => {
				const rows = Array.isArray(json) ? json : [];
				resolve({
					results: rows.filter((r) => r.value && r.value !== '0').map((r) => ({
						context:   context,
						id:        r.value,
						label:     r.label,
						image_url: r.icon || '',
						// A record whose identity is a GLYPH rather than a picture (an agent tool, a filesystem)
						// sends `icon_name`; `icon` stays what it has always been, an avatar URL. The chooser's
						// own `itemIcon` stands in for a context whose records all share one glyph, so the
						// DROPDOWN and the chip agree.
						icon_name: r.icon_name || this.opts.itemIcon || '',
						sublabel:  r.meta ? Object.values(r.meta).filter(Boolean).join(' · ') : '',
					})),
					more: false,
				});
			}, { error: () => resolve({ results: [], more: false }) });
		});

		const core = CerbUI.chooserCore.create({
			anchor:        linkEl,
			search:        search,
			closeOnSelect: true,
			onSelect:      (item) => onPick(item),
		});

		linkEl.addEventListener('click', (e) => {
			e.preventDefault();
			e.stopPropagation();
			core.isOpen() ? core.close() : core.open();
		});

		return core;
	}

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;

		this.opts = Object.assign({
			context:           '',
			multiple:          false,
			searchPlaceholder: 'Search…',
			emptyIcon:         'file',
			ghosts:            [],    // display-only tiles (no hidden input, no remove button) -- see setGhosts()
			onChange:          null,  // (values) after ANY change. `onSelect` is add-only by contract, so a host
			                          // deriving anything from the selection needs this one or it goes stale the
			                          // moment a chip is removed.

			itemIcon:          '',    // a cerb-icons name every tile falls back to when the record offers no
			                          // `icon_name` of its own. For a context whose records share one identity
			                          // (every agent filesystem is a folder), where initials say nothing.
			name:              null,
			value:             null,
			onSelect:          null,
			onResults:         null,  // (items, query) after each autocomplete search renders
			exclude:           null, // () => [ids] — extra ids to hide from autocomplete (e.g. an adder whose
			                         // selections live elsewhere, so this.values doesn't reflect them)
			searchButton:      true,  // false = autocomplete only (no magnifying-glass worklist popup). For an
			                          // inline adder where the full-popup jQuery-UI dialog is unwanted.
			query:             '',   // scope query appended to the autocomplete request (e.g. 'group:(id:5)');
			                         // read live, so callers can re-scope at runtime via setQuery()
			create:            false, // opt-in inline create: true | 'if-null'. Renders a "+" that opens the
			                          // context's create peek; the saved record is added. The CALLER opts in
			                          // (gate it on the worker's create permission); the peek save re-checks.
			panelClass:        '',    // extra class on the autocomplete popup, which mounts to document.body --
			                          // the only way a host can style ITS dropdown rows and not everyone's.
		}, opts);

		// Initial value(s): an explicit `value` option (an item object, a "context:id" string, or an array
		// of either), else enhance authored `[data-context-id]` seed markup inside the element (the old
		// <ul><li> idea — server-render the resolved label/avatar into data-* attributes).
		this.values = (this.opts.value != null)
			? this._normalizeValues(this.opts.value)
			: this._readMarkupValues();

		this.el.replaceChildren(); // the component owns the element's content; drop the read seed markup

		this.el.classList.add('cerb-ui-record-chooser');
		if(this.opts.multiple) this.el.classList.add('cerb-ui-record-chooser--multiple');

		// Leading icon (single empty state only)
		this.iconEl = document.createElement('span');
		this.iconEl.className = 'cerb-icons cerb-icon-' + this.opts.emptyIcon + ' cerb-ui-record-chooser--empty-icon';
		this.iconEl.setAttribute('aria-hidden', 'true');

		this.input = document.createElement('input');
		this.input.type = 'text';
		this.input.className = 'cerb-ui-record-chooser--input';
		this.input.setAttribute('autocomplete', 'off');
		this.input.setAttribute('placeholder', this.opts.searchPlaceholder);

		// Tiles flow before the input via display:contents
		this.tilesEl = document.createElement('span');
		this.tilesEl.className = 'cerb-ui-record-chooser--tiles';

		// Wrapping field region: icon + tiles + input (the input trails at the end, so the autocomplete
		// menu anchors there). The search button lives outside it so it stays pinned right as tiles wrap.
		this.fieldEl = document.createElement('span');
		this.fieldEl.className = 'cerb-ui-record-chooser--field';
		this.fieldEl.appendChild(this.iconEl);
		this.fieldEl.appendChild(this.tilesEl);
		this.fieldEl.appendChild(this.input);

		this.searchBtn = document.createElement('button');
		this.searchBtn.type = 'button';
		this.searchBtn.className = 'cerb-ui-record-chooser--search-btn';
		this.searchBtn.setAttribute('aria-label', 'Search');
		this.searchBtn.innerHTML = '<span class="cerb-icons cerb-icon-search" aria-hidden="true"></span>';

		this.el.appendChild(this.fieldEl);
		if(this.opts.searchButton !== false) this.el.appendChild(this.searchBtn);

		// Opt-in "+" create button — opens the context's create peek (context_id 0) via the existing peek
		// system; on save the new record is added as a selection. Caller gates this on create permission.
		this.createBtn = null;
		if(this.opts.create) {
			this.createBtn = document.createElement('button');
			this.createBtn.type = 'button';
			this.createBtn.className = 'cerb-ui-record-chooser--create-btn';
			this.createBtn.setAttribute('aria-label', 'Create');
			this.createBtn.setAttribute('data-context', this._context());
			this.createBtn.setAttribute('data-context-id', '0');
			this.createBtn.innerHTML = '<span class="cerb-icons cerb-icon-circle-plus" aria-hidden="true"></span>';
			this.el.appendChild(this.createBtn);

			// Dismiss the inline autocomplete when the create peek is invoked (mouse OR keyboard Enter fires
			// click), matching the search button's openSearch() → core.close() behavior.
			this.createBtn.addEventListener('click', () => { if(this.core) this.core.close(); });

			if(window.jQuery && jQuery.fn.cerbPeekTrigger) {
				jQuery(this.createBtn).cerbPeekTrigger();
				jQuery(this.createBtn).on('cerb-peek-saved', (e) => {
					e.stopPropagation();
					if(e.id == null || e.id === '') return;
					this._choose({ context: this._context(), id: e.id, label: e.label || ('#' + e.id), image_url: e.record_image_url || '' });
				});
			}
		}

		// Hidden form fields — kept INSIDE the chooser element (display:none, no layout effect) so the
		// passthrough input travels with the component and is unambiguously inside the surrounding form.
		this.hiddenWrap = document.createElement('span');
		this.hiddenWrap.style.display = 'none';
		this.el.appendChild(this.hiddenWrap);

		this.core = CerbUI.chooserCore.create({
			inputEl:       this.input,
			anchor:        this.input,          // anchor results to the input so they stay connected as tiles grow
			closeOnSelect: !this.opts.multiple, // multi: stay open to add several
			search:        (q, page) => this._search(q, page),
			panelClass:    this.opts.panelClass,
			onSelect:      (item) => this._choose(item),
			onResults:     (items, query) => { if(typeof this.opts.onResults === 'function') this.opts.onResults(items, query); },
		});

		this._onFocus = () => { if(this.opts.multiple || !this.values.length) this.core.open(); };
		this._onSearchClick = (e) => { e.stopPropagation(); this.openSearch(); };
		// Clicking empty space (not a tile/×/search) focuses the input to start searching
		this._onElClick = (e) => {
			if(e.target.closest('.cerb-ui-record-chooser--tile')) return;
			if(e.target.closest('.cerb-ui-record-chooser--search-btn')) return;
			if(e.target.closest('.cerb-ui-record-chooser--create-btn')) return;
			if(e.target === this.input) return;
			if(!this.input.hidden) this.input.focus();
		};
		this.input.addEventListener('focus', this._onFocus);
		this.searchBtn.addEventListener('click', this._onSearchClick);
		this.el.addEventListener('click', this._onElClick);

		this._syncState();
		CerbUI.RecordChooser._instances.set(this.el, this);
	}

	// ── Seams (CerbUI.ContextChooser overrides these to go multi-record-type) ──
	_context() { return this.opts.context; }      // the context to search / link within
	_valueKey(item) { return String(item.id); }   // selection identity (dedupe + already-chosen filter)

	/*
	 * What the ghosts already cover, so the same thing can't be added on top of one.
	 *
	 * Matched on an EXPLICIT identity -- a ghost's `value`, falling back to its `id` -- compared against the
	 * same `_valueKey()` the field already dedupes its own values with. Deliberately not the label: two records
	 * can share one, and a ghost's rendered text is a display concern that shouldn't decide what is selectable.
	 *
	 * A ghost with no identity at all blocks nothing, which is right: it stands for something that resolved to
	 * no record (a deleted filesystem, a hand-authored name), and no search can return that to collide with it.
	 */
	_ghostKeys() {
		const keys = new Set();

		(this.opts.ghosts || []).forEach((g) => {
			if(!g) return;
			const id = (g.value != null && g.value !== '') ? g.value : g.id;
			if(id != null && id !== '') keys.add(String(id));
		});

		return keys;
	}

	_isGhosted(item) { return this._ghostKeys().has(this._valueKey(item)); }

	/* Every path that changes the selection ends here. `onSelect` stays ADD-ONLY by contract. */
	_changed() {
		if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getValue());
	}
	_hiddenValue(item) { return item.id; }         // the value posted in the hidden input

	// ── Initial-value normalization (option) + markup enhancement (data-* seed) ──
	_normalizeValues(v) {
		if(v == null) return [];
		return (Array.isArray(v) ? v : [v]).map((item) => this._normalizeValue(item)).filter(Boolean);
	}
	// An item object is used as-is; a scalar is a bare id for this chooser's fixed context.
	// (ContextChooser overrides this to also parse a "context:id" string.)
	_normalizeValue(item) {
		if(item == null) return null;
		return (typeof item === 'object') ? item : { context: this.opts.context, id: item };
	}
	// Read seed values from authored `[data-context-id]` elements inside the chooser (data-context for a
	// multi-type chooser, data-label + data-image for the chip's name/avatar, data-icon-name for a record whose
	// identity is a glyph rather than a picture). Server-render these.
	_readMarkupValues() {
		const out = [];
		this.el.querySelectorAll('[data-context-id]').forEach((node) => {
			out.push({
				context:   node.getAttribute('data-context') || this.opts.context,
				id:        node.getAttribute('data-context-id'),
				label:     node.getAttribute('data-label') || '',
				image_url: node.getAttribute('data-image') || '',
				icon_name: node.getAttribute('data-icon-name') || '',
				eyebrow:   node.getAttribute('data-eyebrow') || '',
			});
		});
		return out;
	}

	// ── Inline autocomplete (ACL-filtered record autocomplete) ──
	_search(query) {
		return new Promise((resolve) => {
			if(typeof genericAjaxGet !== 'function') { resolve({ results: [], more: false }); return; }
			const context = this._context();
			const args = 'c=internal&a=invoke&module=records&action=autocomplete'
				+ '&context=' + encodeURIComponent(context)
				+ '&query=' + encodeURIComponent(this.opts.query || '')
				+ '&term=' + encodeURIComponent(query || '');
			genericAjaxGet('', args, (json) => {
				const rows = Array.isArray(json) ? json : [];
				// Hide already-chosen records: this.values + any ids the caller's exclude() reports (for
				// adder-style usage where selections are held outside the chooser).
				const selected = new Set(this.values.map((v) => this._valueKey(v)));
				if(typeof this.opts.exclude === 'function')
					(this.opts.exclude() || []).forEach((id) => selected.add(this._valueKey({ context: context, id: id })));
				// …and anything a GHOST already covers: adding it would stack a real chip on a tile that is
				// already there, for a value the consumer was going to union away anyway.
				this._ghostKeys().forEach((key) => selected.add(key));
				resolve({
					// Drop the "(no X)" sentinel AND records already chosen (don't render repeats)
					results: rows.filter((r) => r.value && r.value !== '0' && !selected.has(this._valueKey({ context: context, id: r.value }))).map((r) => ({
						context:   context,
						id:        r.value,
						label:     r.label,
						image_url: r.icon || '',
						// A record whose identity is a GLYPH rather than a picture (an agent tool, a filesystem)
						// sends `icon_name`; `icon` stays what it has always been, an avatar URL. `itemIcon`
						// stands in for a context whose records all share one glyph, so the DROPDOWN ROWS and
						// the chips agree -- without this the rows monogram while the chips show glyphs.
						icon_name: r.icon_name || this.opts.itemIcon || '',
						sublabel:  r.meta ? Object.values(r.meta).filter(Boolean).join(' · ') : '',
					})),
					more: false,
				});
			}, { error: () => resolve({ results: [], more: false }) });
		});
	}

	// ── Full search popup (legacy chooserOpen worklist) ──
	openSearch() {
		if(!this.opts.multiple && this.values.length) return; // single: clear first
		this.core.close();
		if(typeof genericAjaxPopup !== 'function' || !window.jQuery) return;
		const context = this._context();
		const uid = 'recordchooser' + (window.Devblocks && Devblocks.uniqueId ? Devblocks.uniqueId() : '');
		const url = 'c=internal&a=invoke&module=records&action=chooserOpen'
			+ '&context=' + encodeURIComponent(context)
			+ '&single=' + (this.opts.multiple ? '0' : '1')
			// Scope the popup worklist to the same `query` as the inline autocomplete (as enforced required
			// params, `qr`, so the user can't broaden to records the chooser shouldn't allow). Read live, so
			// setQuery() re-scopes the popup too (e.g. a coupled "On:" event select).
			+ (this.opts.query ? '&qr=' + encodeURIComponent(this.opts.query) : '');
		const $chooser = genericAjaxPopup(uid, url, null, true, '90%');
		// The legacy chooser is a jQuery-UI dialog (z ~100). If it was spawned from inside a CerbUI.Dialog
		// (z 9000+), lift it above so it isn't hidden behind the dialog.
		if(window.CerbUI && CerbUI.Dialog) {
			const z = (CerbUI.Dialog._zTop || 9000) + 1;
			requestAnimationFrame(() => { $chooser.closest('.ui-dialog').css('z-index', z); });
		}
		$chooser.one('chooser_save', (event) => {
			const vals = event.values || [], labels = event.labels || [], images = event.images || [];
			for(let i = 0; i < vals.length; i++) {
				this._choose({ context: context, id: vals[i], label: labels[i] || ('#' + vals[i]), image_url: images[i] || '' });
				if(!this.opts.multiple) break;
			}
		});
	}

	_choose(item) {
		if(!item || item.id == null || item.id === '') return; // allow id 0 (e.g. the app:0 "Global" actor)

		// The search POPUP lists everything and never sees the autocomplete's filter, so the guard has to be
		// here too -- otherwise a ghosted record is one popup away from being added on top of itself.
		if(this._isGhosted(item)) return;

		if(this.opts.multiple) {
			if(this.values.some((v) => this._valueKey(v) === this._valueKey(item))) return; // dedupe
			this.values.push(item);
			this._syncState();
			this.input.value = '';
			this.core.refresh();              // keep the popup open with a fresh list
			requestAnimationFrame(() => this.input.focus());
		} else {
			this.values = [item];
			this._syncState();
			// Single-filled hides the input + search button, so a keyboard pick (autocomplete OR the search
			// popup, which doesn't restore focus) would drop focus to <body>. Land it on the chip's × instead.
			requestAnimationFrame(() => {
				const tile = this.tilesEl.lastElementChild;
				const clear = tile && tile.querySelector('.cerb-ui-record-chooser--clear');
				if(clear) clear.focus();
			});
		}
		if(typeof this.opts.onSelect === 'function') this.opts.onSelect(item);
		this._changed();
	}

	_removeValue(item) {
		this.values = this.values.filter((v) => this._valueKey(v) !== this._valueKey(item));
		this._syncState();
		this._changed();
		// Keep the field editable: focus the autocomplete input if it's available, else the search button
		requestAnimationFrame(() => {
			if(!this.input.hidden) this.input.focus();
			else if(!this.searchBtn.hidden) this.searchBtn.focus();
		});
	}

	// ── Tile (avatar + peek label + remove); used for the single chip and multi tokens ──
	_buildTile(item, isGhost) {
		const tile = document.createElement('span');
		tile.className = 'cerb-ui-record-chooser--tile' + (isGhost ? ' cerb-ui-record-chooser--tile--ghost' : '');
		if(isGhost && item.title) tile.title = item.title;

		const av = document.createElement('span');
		av.className = 'cerb-ui-chooser--avatar cerb-ui-record-chooser--avatar';

		// A glyph replaces the INITIALS when the record has one -- they say nothing about a tool. On a NEUTRAL
		// disc, not the hashed one: initials earn their color (it's locked to the name, so it identifies), but a
		// column of identical folder glyphs in six colors says only "these are separate rows".
		const glyph = item.icon_name || this.opts.itemIcon;

		if(glyph) {
			av.classList.add('cerb-ui-chooser--avatar-glyph');
			av.innerHTML = '<span class="cerb-icons" aria-hidden="true"></span>';
			av.firstChild.classList.add('cerb-icon-' + glyph);
		} else {
			av.style.backgroundColor = CerbUI.chooserCore.monogramColor((item.context || '') + ':' + item.id);
			av.textContent = CerbUI.chooserCore.initials(item.label);
		}

		if(!glyph && item.image_url) {
			const probe = new Image();
			probe.addEventListener('load', () => {
				av.style.backgroundImage = 'url("' + probe.src + '")';
				av.style.backgroundColor = 'transparent';
				av.textContent = '';
			});
			probe.src = item.image_url;
		}
		tile.appendChild(av);

		// id 0 = a forced/singleton actor (e.g. app:0 "Everyone") — no record to peek, so a plain label
		let name;
		if(String(item.id) === '0') {
			name = document.createElement('span');
			name.className = 'cerb-ui-record-chooser--label';
			name.textContent = item.label;
		} else {
			name = document.createElement('a');
			name.className = 'cerb-peek-trigger no-underline cerb-ui-record-chooser--label';
			name.setAttribute('data-context', item.context);
			name.setAttribute('data-context-id', item.id);
			// The peek-trigger anchor has no href, so it isn't a tab stop and cerbPeekTrigger only binds click.
			// Make the chip keyboard-reachable (it opens the record peek) and map Enter/Space → click.
			name.setAttribute('tabindex', '0');
			name.setAttribute('role', 'button');
			name.textContent = item.label;
			if(window.jQuery && jQuery.fn.cerbPeekTrigger) jQuery(name).cerbPeekTrigger();
			name.addEventListener('keydown', (e) => {
				if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); name.click(); }
			});
		}

		// Optional eyebrow (e.g. a disambiguating parent path) stacks above the label. Seeds pass it as
		// `data-eyebrow`; autocomplete picks inherit it from the result's `sublabel` (joined `meta`).
		const eyebrowText = item.eyebrow || item.sublabel || '';
		if(eyebrowText) {
			const stack = document.createElement('span');
			stack.className = 'cerb-ui-record-chooser--text';
			const eb = document.createElement('span');
			eb.className = 'cerb-ui-record-chooser--eyebrow';
			eb.textContent = eyebrowText;
			eb.title = eyebrowText;
			stack.appendChild(eb);
			stack.appendChild(name);
			tile.appendChild(stack);
		} else {
			tile.appendChild(name);
		}

		// A ghost isn't owned here, so there is nothing to remove.
		if(!isGhost) {
			const clear = document.createElement('button');
			clear.type = 'button';
			clear.className = 'cerb-ui-record-chooser--clear';
			clear.setAttribute('aria-label', 'Remove');
			clear.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove" aria-hidden="true"></span>';
			clear.addEventListener('click', (e) => { e.stopPropagation(); this._removeValue(item); });
			tile.appendChild(clear);
		}

		return tile;
	}

	// ── Render state + hidden fields ──
	_syncState() {
		const filled = this.values.length > 0;
		const multi = this.opts.multiple;

		// Tiles — ghosts lead, since they're what this field is adding TO
		const ghosts = Array.isArray(this.opts.ghosts) ? this.opts.ghosts : [];

		this.tilesEl.replaceChildren();
		ghosts.forEach((item) => this.tilesEl.appendChild(this._buildTile(item, true)));
		this.values.forEach((item) => this.tilesEl.appendChild(this._buildTile(item)));

		// Visibility. `filled` is about VALUES, so a single-value field with a ghost still offers its input --
		// the ghost is what you'd be replacing, not something you have to clear first.
		const hasTiles = filled || ghosts.length > 0;

		this.iconEl.hidden = multi || hasTiles;          // leading icon only for empty
		this.input.hidden = !multi && filled;            // single-filled hides the input
		this.searchBtn.hidden = !multi && filled;        // …and the search button
		if(this.createBtn) this.createBtn.hidden = !multi && filled; // single-filled hides "+"; multiple keeps it
		this.el.classList.toggle('cerb-ui-record-chooser--has-tiles', hasTiles); // collapse the idle input when tiles exist

		// Hidden form fields. Multi → one `name[]` per value (none when empty). Single → ALWAYS one hidden
		// `name` field (so it passes through a form even when cleared): its value, or '' when empty.
		this.hiddenWrap.replaceChildren();
		if(this.opts.name) {
			if(multi) {
				const name = this.opts.name + '[]';
				this.values.forEach((item) => {
					const h = document.createElement('input');
					h.type = 'hidden';
					h.name = name;
					h.value = this._hiddenValue(item);
					this.hiddenWrap.appendChild(h);
				});
			} else {
				const h = document.createElement('input');
				h.type = 'hidden';
				h.name = this.opts.name;
				h.value = this.values.length ? this._hiddenValue(this.values[0]) : '';
				this.hiddenWrap.appendChild(h);
			}
		}
	}

	// ── Public API ──
	getValue() { return this.opts.multiple ? this.values.slice() : (this.values[0] || null); }

	// Replace the display-only tiles. Host-managed on purpose: what they represent lives outside this field,
	// so only the host knows when it changed.
	setGhosts(list) {
		this.opts.ghosts = Array.isArray(list) ? list : (list ? [list] : []);
		this._syncState();
		return this;
	}

	getGhosts() { return (this.opts.ghosts || []).slice(); }

	setValue(value) {
		this.values = value ? (Array.isArray(value) ? value.slice() : [value]) : [];
		this._syncState();
	}

	// focus=true (default) lands the caret back in the field — right for the user clicking × to re-pick. Pass
	// false for programmatic clears (e.g. a coupled "Type" select re-scoping several choosers at once) so they
	// don't each grab focus and pop their autocomplete open.
	clear(focus = true) {
		this.values = [];
		this.input.value = '';
		this._syncState();
		this._changed();
		if(focus) requestAnimationFrame(() => this.input.focus());
	}

	// Re-scope the autocomplete at runtime (e.g. owner scoped to the currently-selected group)
	setQuery(query) { this.opts.query = query || ''; }

	// Re-target the searched context at runtime (e.g. a "Type" select driving a single-type chooser).
	// _context() reads opts.context live, so this re-points both the autocomplete and the search popup.
	setContext(context) { this.opts.context = context || ''; }

	openSearchPopup() { this.openSearch(); }

	// (Re)open the inline autocomplete — e.g. an adder that keeps suggesting after each pick. Deferred a
	// frame so it runs after _choose's own focus handling (single-select refocuses the just-removed tile).
	openAutocomplete() {
		requestAnimationFrame(() => {
			if(this.input.hidden) return;
			this.input.focus();
			this.core.open();
		});
	}

	destroy() {
		CerbUI.RecordChooser._instances.delete(this.el);
		this.core.destroy();
		this.input.removeEventListener('focus', this._onFocus);
		this.searchBtn.removeEventListener('click', this._onSearchClick);
		this.el.removeEventListener('click', this._onElClick);
		this.hiddenWrap.remove();
		this.el.classList.remove('cerb-ui-record-chooser', 'cerb-ui-record-chooser--multiple');
	}
};
