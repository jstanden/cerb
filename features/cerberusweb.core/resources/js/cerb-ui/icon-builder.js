/*
 * CerbUI.IconBuilder — a design surface for Cerb's `cerb-icons` SCSS glyphs (Setup → Developers → Icon Builder).
 *
 * A vertical SplitPane: the top pane previews the current icon everywhere it appears in the UI (a size ramp, on
 * mock buttons, inside a mock panel, and with the animation utilities); the bottom pane is a ScriptingEditor
 * holding ONLY the inner SVG geometry (the 24×24 viewBox + outer stroke/fill wrapper is enforced by the icon
 * pipeline, so authors never write it). An IconPicker seeds the editor from any existing glyph, and a copy-out
 * emits the two paste-ready source lines (the `$icons` SCSS map entry + the getCerbIcons() name entry).
 *
 * The preview matches the real render path exactly: `.cerb-icon-<name>` is just a mask-image data URI tinted by
 * currentColor, so every preview glyph is a `.cerb-icons.cib-glyph` whose mask comes from one CSS custom
 * property (`--cib-mask`) set on the root — one style write updates them all.
 *
 * Usage:  new CerbUI.IconBuilder(document.getElementById('mount'), {});
 *
 * Requires: CerbUI.SplitPane, CerbUI.ScriptingEditor, CerbUI.IconPicker, the cerb-icons CSS.
 */
CerbUI.IconBuilder = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.IconBuilder._instances.get(el); }

	// The fixed outer <svg> wrapper — SOURCE OF TRUTH is `$cerb-icon-svg-tag` in cerb-icons.scss (keep in sync).
	// Authors write only the inner geometry; this wraps it for the live mask, exactly as the SCSS compile does.
	static SVG_OPEN = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>";

	// The latest fragment rides sessionStorage so a reload / click-away keeps it as the new default (session-scoped,
	// not server-persisted). MAX_REVISIONS caps the in-memory history so it can't grow unbounded.
	static STORAGE_KEY = 'cerb-icon-builder-last-geometry';
	static MAX_REVISIONS = 40;

	// Read an existing glyph's INNER geometry straight from its compiled `.cerb-icon-<name>` mask — the same
	// probe trick CerbUI.ImageEditor uses (setFromIcon). No server round-trip, and always in sync with the CSS.
	// The mask is `url("data:image/svg+xml;utf8,<svg …>GEOMETRY</svg>")`; we strip the wrapper and return only
	// the inner markup so it drops straight into the editor. Returns '' if the icon/mask isn't resolvable.
	static geometryForIcon(name) {
		if(!name) return '';
		const probe = document.createElement('span');
		probe.className = 'cerb-icons cerb-icon-' + name;
		probe.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
		document.body.appendChild(probe);
		const cs = getComputedStyle(probe);
		let mask = (cs.maskImage && cs.maskImage !== 'none') ? cs.maskImage : cs.webkitMaskImage;
		document.body.removeChild(probe);
		if(!mask || mask === 'none') return '';

		let start = mask.indexOf('url(');
		if(start === -1) return '';
		let inner = mask.slice(start + 4);
		const end = inner.lastIndexOf(')');
		if(end !== -1) inner = inner.slice(0, end);
		inner = inner.trim();
		if(inner.length >= 2 && ((inner[0] === '"' && inner.endsWith('"')) || (inner[0] === "'" && inner.endsWith("'"))))
			inner = inner.slice(1, -1);

		const comma = inner.indexOf(',');
		if(comma === -1) return '';
		let payload = inner.slice(comma + 1);
		if(/%3C/i.test(payload)) { try { payload = decodeURIComponent(payload); } catch(_) {} } // some browsers %-encode
		const gt = payload.indexOf('>');
		const close = payload.lastIndexOf('</svg>');
		if(gt === -1 || close === -1 || close < gt) return '';
		return payload.slice(gt + 1, close).trim();
	}

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.opts = Object.assign({ seed: 'mail', agentToolbarHtml: '' }, opts);

		this._renderTimer = null;
		this._render = this._render.bind(this);

		// Local revision history (in-memory): a snapshot of the geometry each time it's REPLACED wholesale (a
		// set_geometry command, the picker, or the seed) — navigable to compare, with the SVG code + preview riding
		// along. Typing between snapshots edits the current slot in place, so no keystroke spawns a revision.
		this.revisions = [];
		this.revIndex = -1;
		this._loadingRevision = false;

		this._buildDom();
		this._enhance();

		CerbUI.IconBuilder._instances.set(this.el, this);
	}

	// ── DOM ─────────────────────────────────────────────────────────────────────
	_buildDom() {
		this.el.classList.add('cerb-icon-builder');

		const SIZES = [16, 24, 32, 48, 64];
		const sizeRow = SIZES.map((px) =>
			`<span class="cib-size"><span class="cerb-icons cib-glyph" style="width:${px}px;height:${px}px"></span><span class="cib-size--label">${px}px</span></span>`
		).join('');

		const anims = [
			['cerb-u-anim-spin', 'spin'],
			['cerb-u-anim-pulse', 'pulse'],
			['cerb-u-anim-ping', 'ping'],
			['cerb-u-anim-shake', 'shake'],
			['cerb-u-anim-magic-sweep', 'magic-sweep'],
		];
		const animRow = anims.map(([cls, label]) =>
			`<span class="cib-anim"><span class="cerb-icons cib-glyph ${cls}"></span><span class="cib-anim--label">${label}</span></span>`
		).join('');

		this.el.innerHTML = `
			<div class="cerb-icon-builder--split">
				<div class="cerb-icon-builder--top">
					<div class="cerb-icon-builder--toolbar">
						<div class="cib-history" title="Icon revision history — flip between geometry snapshots">
							<button type="button" class="cerb-ui-button cerb-ui-button--transparent cib-history-prev" title="Previous revision"><span class="cerb-icons cerb-icon-chevron-left"></span></button>
							<span class="cib-history-label">1 / 1</span>
							<button type="button" class="cerb-ui-button cerb-ui-button--transparent cib-history-next" title="Next revision"><span class="cerb-icons cerb-icon-chevron-right"></span></button>
						</div>
					</div>

					<div class="cerb-icon-builder--surfaces">
						<div class="cerb-ui-panel cerb-ui-panel--spaced">
							<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--label">Sizes</div></div>
							<div class="cib-sizes">${sizeRow}</div>
						</div>

						<div class="cerb-ui-panel cerb-ui-panel--spaced">
							<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--label">On buttons</div></div>
							<div class="cib-buttons">
								<button type="button" class="cerb-ui-button"><span class="cerb-icons cib-glyph"></span> Primary</button>
								<button type="button" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cib-glyph"></span> Subtle</button>
								<button type="button" class="cerb-ui-button cerb-ui-button--outline"><span class="cerb-icons cib-glyph"></span> Outline</button>
								<button type="button" class="cerb-ui-button cerb-ui-button--transparent" title="Icon only"><span class="cerb-icons cib-glyph"></span></button>
							</div>
						</div>

						<div class="cerb-ui-panel cerb-ui-panel--spaced">
							<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
								<div class="cerb-ui-header--title-sm"><span class="cerb-icons cib-glyph"></span> Card-style title with your icon</div>
							</div>
							<div>A panel head pairs the glyph with a title — the most common place an icon appears.</div>
						</div>

						<div class="cerb-ui-panel cerb-ui-panel--spaced">
							<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--label">Animation utilities</div></div>
							<div class="cib-anims">${animRow}</div>
						</div>
					</div>
				</div>

				<div class="cerb-icon-builder--bottom">
					<div class="cerb-icon-builder--fields cerb-ui-form">
						<div class="cerb-ui-form--row">
							<div class="cerb-ui-form--field">
								<label class="cerb-ui-form--label">Start from <span class="cerb-ui-form--hint">optional</span></label>
								<input type="text" class="cib-picker" placeholder="an existing icon…">
							</div>
							<div class="cerb-ui-form--field">
								<label class="cerb-ui-form--label">Name</label>
								<input type="text" class="cib-name" placeholder="my-new-icon" spellcheck="false">
							</div>
						</div>
					</div>

					<div class="cerb-ui-header cerb-ui-header--tight">
						<div class="cerb-ui-header--label">Inner SVG geometry</div>
						<div class="cerb-ui-header--summary">24×24 viewBox &middot; <code>stroke='black' stroke-width='2'</code>, <code>fill='none'</code>, round caps — the wrapper is added for you</div>
					</div>
					<textarea class="cib-source" spellcheck="false"></textarea>

					<div class="cerb-icon-builder--output">
						<div class="cib-out-row">
							<span class="cib-out-label">SCSS <code>$icons</code> map</span>
							<code class="cib-out-code cib-out-scss"></code>
							<button type="button" class="cerb-ui-button cerb-ui-button--subtle cib-copy" data-copy="scss"><span class="cerb-icons cerb-icon-copy"></span> Copy</button>
						</div>
						<div class="cib-out-row">
							<span class="cib-out-label"><code>getCerbIcons()</code> name</span>
							<code class="cib-out-code cib-out-name"></code>
							<button type="button" class="cerb-ui-button cerb-ui-button--subtle cib-copy" data-copy="name"><span class="cerb-icons cerb-icon-copy"></span> Copy</button>
						</div>
					</div>
				</div>
			</div>
		`;

		this.splitEl   = this.el.querySelector('.cerb-icon-builder--split');
		this.pickerEl  = this.el.querySelector('.cib-picker');
		this.nameEl    = this.el.querySelector('.cib-name');
		this.sourceEl  = this.el.querySelector('.cib-source');
		this.outScss   = this.el.querySelector('.cib-out-scss');
		this.outName   = this.el.querySelector('.cib-out-name');
		this.historyPrevEl  = this.el.querySelector('.cib-history-prev');
		this.historyNextEl  = this.el.querySelector('.cib-history-next');
		this.historyLabelEl = this.el.querySelector('.cib-history-label');
	}

	_enhance() {
		// No inner split — the preview (top) takes its natural height and the editor sits directly below it; the
		// whole left pane scrolls if needed. (The outer left/right split — builder vs agent chat — stays.)
		this.editor = new CerbUI.ScriptingEditor(this.sourceEl, {
			minLines: 6,
			maxLines: 16,
			gutter: true,
		});
		this.editor.onChange(() => {
			this._scheduleRender();
			// Live typing edits the current revision slot in place (a wholesale replace spawns a new one).
			if(!this._loadingRevision) this._syncCurrentRevision();
		});

		this.picker = new CerbUI.IconPicker(this.pickerEl, {
			allowClear: true,
			onChange: (name) => this._loadFromIcon(name),
		});

		this.el.querySelectorAll('.cib-copy').forEach((btn) => {
			btn.addEventListener('click', () => this._copy(btn));
		});
		this.nameEl.addEventListener('input', () => this._renderOutput());

		if(this.historyPrevEl) this.historyPrevEl.addEventListener('click', () => this._gotoRevision(-1));
		if(this.historyNextEl) this.historyNextEl.addEventListener('click', () => this._gotoRevision(1));

		// The collapsible agent chat sidebar. AgentPane wraps this.el's content in an outer horizontal split and
		// hosts the agent.pane toolbar as "New Agent Chat" tiles; each launches an interaction inline, carrying
		// the get/set-geometry command bridge into the live editor. Its toggle button rides the toolbar row.
		this.agentPane = new CerbUI.AgentPane(this.el, {
			component: 'icon',
			capabilities: 'get_geometry,set_geometry,get_icon_geometry',
			mutatingCommands: 'set_geometry',   // writes the editor → guard against accidental navigation loss
			toolbarHtml: this.opts.agentToolbarHtml,
			storageKey: 'cerb-icon-builder-chat',
			toggleInto: this.el.querySelector('.cerb-icon-builder--toolbar'),
			runCommand: (name, params) => this._runAgentCommand(name, params),
		});

		// Seed the editor as the first revision: keep the last fragment across a reload/navigation (sessionStorage),
		// else a friendly starter icon. Pushing it records revision 1 and renders the preview.
		let seedGeom = this.editor.getValue().trim();
		if(!seedGeom) {
			try { seedGeom = sessionStorage.getItem(CerbUI.IconBuilder.STORAGE_KEY) || ''; } catch(_) {}
		}
		if(!seedGeom) seedGeom = CerbUI.IconBuilder.geometryForIcon(this.opts.seed) || '';
		this._pushRevision(seedGeom);
	}

	// ── Behavior ────────────────────────────────────────────────────────────────
	_loadFromIcon(name) {
		if(!name) return;
		const geometry = CerbUI.IconBuilder.geometryForIcon(name);
		if(geometry) this._pushRevision(geometry); // a wholesale replace → new revision + preview

		// "Start from" is a momentary action (seed the editor from an existing glyph), not a stored value —
		// reset the picker to its placeholder. setValue('') re-enters here with an empty name, which no-ops.
		if(this.picker) this.picker.setValue('');
	}

	// ── Revision history ────────────────────────────────────────────────────────
	// Append a wholesale-replacement snapshot (set_geometry / picker / seed), point at it, and load it. Older
	// snapshots stay navigable; typing edits the current one (see _syncCurrentRevision).
	_pushRevision(geom) {
		this.revisions.push(geom || '');
		if(this.revisions.length > CerbUI.IconBuilder.MAX_REVISIONS)
			this.revisions.shift();
		this.revIndex = this.revisions.length - 1;
		this._loadRevisionIntoEditor();
		this._updateHistoryUI();
	}

	_gotoRevision(delta) {
		const next = this.revIndex + delta;
		if(next < 0 || next >= this.revisions.length) return;
		this.revIndex = next;
		this._loadRevisionIntoEditor();
		this._updateHistoryUI();
	}

	// Load the current revision into the editor WITHOUT recording it as a new edit (the guard makes onChange skip
	// the slot-sync). The editor change still refreshes the preview + copy-out and persists the fragment.
	_loadRevisionIntoEditor() {
		if(this.revIndex < 0) return;
		this._loadingRevision = true;
		this.editor.setValue(this.revisions[this.revIndex]);
		this._loadingRevision = false;
	}

	// Live typing keeps the current revision slot in sync (a revision is an editable snapshot; only a wholesale
	// replacement spawns a new one).
	_syncCurrentRevision() {
		if(this.revIndex >= 0)
			this.revisions[this.revIndex] = this.editor.getValue();
	}

	_updateHistoryUI() {
		const n = this.revisions.length;
		if(this.historyLabelEl) this.historyLabelEl.textContent = (n ? this.revIndex + 1 : 0) + ' / ' + n;
		if(this.historyPrevEl) this.historyPrevEl.disabled = this.revIndex <= 0;
		if(this.historyNextEl) this.historyNextEl.disabled = this.revIndex >= n - 1;
	}

	// Persist the current fragment so a reload / click-away keeps it as the new default (session-scoped).
	_saveLast() {
		try { sessionStorage.setItem(CerbUI.IconBuilder.STORAGE_KEY, this.editor.getValue()); } catch(_) {}
	}

	// ── Agent panel ───────────────────────────────────────────────────────────
	// The UI-command bridge target: an interaction's `uiCommand` await routes here (via CerbUI.AgentPane's
	// runCommand) to read/write the live editor.
	_runAgentCommand(name, params) {
		params = params || {};
		switch(name) {
			case 'get_geometry':      return this.editor.getValue();
			case 'set_geometry':      this._pushRevision(params.geometry || ''); return 'ok'; // new revision → live preview
			case 'get_icon_geometry': return CerbUI.IconBuilder.geometryForIcon(params.name) || '';
		}
		return '';
	}

	_scheduleRender() {
		if(this._renderTimer) clearTimeout(this._renderTimer);
		this._renderTimer = setTimeout(this._render, 120);
	}

	// Build the mask data URI from the current geometry and push it to every preview glyph via one CSS var.
	_maskFor(inner) {
		const svg = CerbUI.IconBuilder.SVG_OPEN + (inner || '') + '</svg>';
		return 'url("data:image/svg+xml,' + encodeURIComponent(svg) + '")';
	}

	_render() {
		this._renderTimer = null;
		this.el.style.setProperty('--cib-mask', this._maskFor(this.editor.getValue()));
		this._renderOutput();
		this._saveLast(); // debounced with the preview → persist the current fragment as the reload default
	}

	_renderOutput() {
		const name = (this.nameEl.value || 'my-new-icon').trim();
		const geometry = this.editor.getValue().replace(/\s+/g, ' ').trim();
		this.outScss.textContent = '  ' + name + ':  "' + geometry + '",';
		this.outName.textContent = "'" + name + "',";
	}

	_copy(btn) {
		const text = (btn.getAttribute('data-copy') === 'scss') ? this.outScss.textContent : this.outName.textContent;
		const done = () => {
			const label = btn.querySelector('.cerb-icons');
			if(label) { label.className = 'cerb-icons cerb-icon-check'; setTimeout(() => { label.className = 'cerb-icons cerb-icon-copy'; }, 1200); }
		};
		if(navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(done, () => {});
		} else {
			const ta = document.createElement('textarea');
			ta.value = text; document.body.appendChild(ta); ta.select();
			try { document.execCommand('copy'); done(); } catch(_) {}
			ta.remove();
		}
	}

	destroy() {
		if(this._renderTimer) clearTimeout(this._renderTimer);
		if(this.picker) this.picker.destroy();
		if(this.agentPane && this.agentPane.destroy) this.agentPane.destroy();
		CerbUI.IconBuilder._instances.delete(this.el);
	}
};
