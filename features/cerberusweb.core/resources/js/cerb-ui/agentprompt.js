/*
 * CerbUI.AgentPrompt — an agentic chat input (a fourth member of the editor-core family, alongside
 * SearchQuery / KataEditor / MarkdownEditor). A plain <textarea> with grow-as-you-type, a caret-anchored
 * autocomplete menu (for `@`-context mentions and automation-defined `/`-slash commands), image paste,
 * up-arrow prompt history, a footer with model selection + a pre-compaction context progress bar + a Send
 * button, Shift+Enter for newlines and Enter to submit.
 *
 * It has NO particular syntax (it's a prompt, not markdown): the highlight mirror only tints inserted
 * reference tokens (`@record_type:id`, `worker:id`, `[record_type #id]`) and a leading `/command`.
 *
 * The component owns the INPUT behaviors; the host supplies CONTENT via callbacks:
 *   - onAutocomplete(ctx) — items for `@` (record types / worker mentions / a RecordChooser panel) and `/`
 *     (automation-declared commands). ctx.path[0] is '@' or '/'.
 *   - onSubmit({text, model_id, attachments, mentions}) — the turn.
 *   - onModelChange(model, {providerChanged}) — host auto-forks on a provider change.
 *   - onImage(info) — a pasted image was uploaded (host tracks the attachment).
 *
 * Markup (mirrors MarkdownEditor; the shell self-builds around a bare <textarea>):
 *   <textarea class="cerb-ui-agentprompt--input" name="…"></textarea>
 *
 * CSS lives in cerb.css (.cerb-ui-agentprompt--*) — this component never injects styles.
 */
CerbUI.AgentPrompt = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.AgentPrompt._instances.get(el); }
	static _NS = 'agentprompt';

	static enhance(field, opts = {}) { return CerbUI.editorCore.enhanceEditor(this, field, opts, { gutter: false }); }

	static _DEFAULTS = {
		placeholder: 'Message the agent…',
		minHeight: 60,
		maxHeight: 320,
		models: [],              // [{ id, label, provider, model, icon, disabled, vision, context_window, context_ratio, effort_choices, default_effort }]
		defaultModel: null,      // model id; falls back to the first enabled model
		defaultEffort: '',       // persisted effort level; falls back to the model's default_effort (fixed `effort:`)
		contextTokens: 0,        // current estimated tokens in the session (host updates via setContextUsage)
		cacheElapsed: null,      // seconds since the last agent turn (null = no prior turn → no cache ring)
		images: true,            // allow image paste (further gated by the selected model's `vision`)
		autofocus: false,        // focus the input once built, unless something else is already focused
		autocompleteDelay: 150,
		onBeforeSubmit: null,    // (text) => text — rewrite the message before it's serialized (`/command` expansion)
		onSubmit: null,
		onAutocomplete: null,
		onModelChange: null,
		onCommand: null,
		onImage: null,
	};

	constructor(el, opts = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		el = CerbUI.editorCore.resolveEditorEl(el, CerbUI.AgentPrompt._NS, { gutter: false });
		if(!el) return;

		this.el = el;
		this.opts = Object.assign({}, CerbUI.AgentPrompt._DEFAULTS, opts);

		this.textarea = el.querySelector('.cerb-ui-agentprompt--input');
		this.field = el.querySelector('.cerb-ui-agentprompt--field');
		this.highlight = el.querySelector('.cerb-ui-agentprompt--highlight');
		this.caretAnchor = el.querySelector('.cerb-ui-agentprompt--caret-anchor');
		if(!this.textarea || !this.field || !this.highlight || !this.caretAnchor) return;

		this.textarea.setAttribute('autocomplete', 'off');
		this.textarea.setAttribute('autocorrect', 'off');
		if(this.opts.placeholder != null) this.textarea.placeholder = this.opts.placeholder;

		this._attachments = [];       // {file_id, file_name, url}
		this._history = [];           // submitted prompts (recall via ArrowUp)
		this._historyIndex = -1;      // -1 = editing a fresh prompt
		this._draftBeforeHistory = null;

		// Resolve the active model (defaultModel id, else first enabled) + its effort (persisted, else default).
		this._model = this._resolveModel(this.opts.defaultModel);
		this._effort = this._resolveEffort(this._model, this.opts.defaultEffort);

		// Prompt-cache countdown anchor: server-reported seconds since the last agent turn, plus our own elapsed
		// since this render (skew-free — no shared clock). null = no prior turn → the cache ring stays hidden.
		this._cacheElapsedBase = (this.opts.cacheElapsed == null) ? null : Math.max(0, +this.opts.cacheElapsed || 0);
		this._cacheT0 = Date.now();

		this._ac = new CerbUI.editorCore.Autocomplete({
			textarea: this.textarea,
			caretAnchor: this.caretAnchor,
			editor: this,
			delay: this.opts.autocompleteDelay,
			onScope: (text, caret) => this._scopePathAt(text, caret),
			onItems: (ctx) => (typeof this.opts.onAutocomplete === 'function') ? this.opts.onAutocomplete(ctx) : [],
			onAfterApply: () => { this._renderHighlight(); this._autosize(); },
		});

		CerbUI.AgentPrompt._instances.set(el, this);

		this._onInput = () => this._handleInput();
		this._onKeydown = (e) => this._handleKeydown(e);
		this._onScroll = () => this._syncScroll();
		this._onBlur = () => { this._ac.clearTimer(); };
		this._onPaste = (e) => this._handlePaste(e);

		this.textarea.addEventListener('input', this._onInput);
		this.textarea.addEventListener('keydown', this._onKeydown);
		this.textarea.addEventListener('scroll', this._onScroll, { passive: true });
		this.textarea.addEventListener('blur', this._onBlur);
		this.textarea.addEventListener('paste', this._onPaste);

		this._buildFooter();
		this._renderHighlight();
		this._autosize();
		this.setContextUsage(this.opts.contextTokens);

		// The host's generic post-render focus targets the bare <textarea> before this shell is built, so
		// the rebuild drops it. Reclaim focus here — but only when nothing else was intentionally focused
		// (activeElement is body/null, or already inside this prompt), so a multi-element form's first
		// field still wins.
		if(this.opts.autofocus) {
			const active = document.activeElement;
			if(!active || active === document.body || this.el.contains(active))
				this.focus();
		}
	}

	// ── Public API ──────────────────────────────────────────────────────

	getValue() { return this.textarea ? this.textarea.value : ''; }

	setValue(str) {
		if(!this.textarea) return this;
		this.textarea.value = str ?? '';
		this._renderHighlight();
		this._autosize();
		return this;
	}

	focus() { if(this.textarea) this.textarea.focus(); return this; }

	getModel() { return this._model; }

	// Seed the input (e.g. a fork's returned `draft`) and focus, ready to edit + resubmit.
	seedDraft(text) { this.setValue(text || ''); this.focus(); this.setSelection(this.textarea.value.length, this.textarea.value.length); return this; }

	getSelectionBounds() { return { start: this.textarea.selectionStart, end: this.textarea.selectionEnd }; }
	setSelection(start, end) { this.textarea.selectionStart = start; this.textarea.selectionEnd = end; }

	insertText(text) {
		const b = this.getSelectionBounds();
		this._replaceRange(b.start, b.end, String(text == null ? '' : text));
	}

	// Update the context progress bar (tokens used vs the selected model's window).
	setContextUsage(tokens) {
		this._contextTokens = Math.max(0, tokens | 0);
		this._renderProgress();
		return this;
	}

	destroy() {
		this._ac.destroy();
		if(this._cacheTimer) { clearInterval(this._cacheTimer); this._cacheTimer = null; }
		if(this._modelMenu) { this._modelMenu.destroy(); this._modelMenu = null; }
		CerbUI.AgentPrompt._instances.delete(this.el);
		if(this.textarea) {
			this.textarea.removeEventListener('input', this._onInput);
			this.textarea.removeEventListener('keydown', this._onKeydown);
			this.textarea.removeEventListener('scroll', this._onScroll);
			this.textarea.removeEventListener('blur', this._onBlur);
			this.textarea.removeEventListener('paste', this._onPaste);
		}
	}

	// ── Submit ──────────────────────────────────────────────────────────

	submit() { return this._submit(); }

	_submit() {
		// No enabled model (e.g. every model disabled by a rate-limit/budget gate) — nothing to send to.
		if(!this._model) return this;

		const typed = this.getValue();
		if(typed.trim() === '' && this._attachments.length === 0) return this;

		// Sending re-arms the transcript's stick-to-bottom. A reader who scrolled up to re-read something stays
		// put while the agent works (that's the point of the flag), but the moment they send they've asked for
		// what comes next -- leaving them detached would hide their own message and the reply.
		if(window.CerbUI && CerbUI.AgentTranscript)
			CerbUI.AgentTranscript.resetStick(this.el);

		// The host may rewrite the text before it's serialized (`/command` expansion). It happens HERE rather
		// than in onSubmit so mentions are extracted from what's actually SENT — an expansion carrying
		// `@volume/path` has to resolve like a typed one, and the miss would be silent.
		let text = typed;

		if(typeof this.opts.onBeforeSubmit === 'function') {
			const rewritten = this.opts.onBeforeSubmit(text);
			if(typeof rewritten === 'string') text = rewritten;
		}

		const payload = {
			text: text,
			model_id: this._model ? this._model.id : null,
			effort: this._effort || '',
			attachments: this._attachments.slice(),
			mentions: this._extractMentions(text),
		};

		// History recalls what was TYPED — ↑ after `/flatten` should give back `/flatten`, not its expansion.
		if(typed.trim() !== '') {
			this._history.push(typed);
			if(this._history.length > 100) this._history.shift();
		}
		this._historyIndex = -1;
		this._draftBeforeHistory = null;

		if(typeof this.opts.onSubmit === 'function') this.opts.onSubmit(payload);

		// Clear for the next turn.
		this.setValue('');
		this._attachments = [];
		this._renderAttachments();
		return this;
	}

	// Pull the `@` tokens out of the text (the `@` prefix is always kept): `@type:id` for records /
	// resources (`@automation_resource:<token>`, id may be a number or a uuid/token) and a bare `@handle`
	// for workers by name (`@jeff`, `@cerb-dev`). The automation resolves these to context at submit.
	// The name class allows dashes so a hyphenated worker mention is one token, not `@cerb`-dev.
	// Scans `text` when given (the about-to-be-sent text, post-expansion), else the live input.
	_extractMentions(text) {
		const src = (text == null) ? this.getValue() : String(text);
		const out = [];
		// Three forms, longest-first so a file reference isn't clipped to its volume:
		//   @<volume>/<path>  a file in an agent filesystem — slashes and dots, so `@cerb-dev/guides/setup.md`
		//                     is ONE token (the server resolves it to a record; an unknown path just drops out)
		//   @<type>:<id>      an explicit record pair
		//   @<handle>         a worker
		const rx = /(?:^|\s)@([a-z0-9_.-]+(?:\/[a-z0-9_./-]*)+)|(?:^|\s)@([a-z0-9_.-]+):([a-z0-9-]+)|(?:^|\s)@([a-z0-9_.-]+)/gi;
		let m;
		while((m = rx.exec(src)) !== null) {
			if(m[1]) out.push({ path: m[1] });
			else if(m[2]) out.push({ context: m[2], id: m[3] });
			else out.push({ handle: m[4] });
		}
		return out;
	}

	// ── Model selection + footer ────────────────────────────────────────

	_enabledModels() { return (this.opts.models || []).filter(m => m && !m.disabled); }

	_resolveModel(id) {
		const enabled = this._enabledModels();
		if(!enabled.length) return null;
		return enabled.find(m => m.id === id) || enabled[0];
	}

	// The effort levels a model offers in its submenu (author-declared catalog `effort_choices`), or [].
	_effortChoices(m) { return (m && Array.isArray(m.effort_choices)) ? m.effort_choices : []; }

	// The full set of pickable effort levels for a model = its choices PLUS its fixed default (`effort:`).
	_allowedEfforts(m) {
		const out = this._effortChoices(m).slice();
		const def = (m && m.default_effort) || '';
		if(def && out.indexOf(def) === -1) out.push(def);
		return out;
	}

	// Resolve a model's active effort: a preferred level if the model allows it, else its fixed default_effort,
	// else '' (provider default).
	_resolveEffort(m, preferred) {
		if(preferred && this._allowedEfforts(m).indexOf(preferred) !== -1) return preferred;
		return (m && m.default_effort) || '';
	}

	getEffort() { return this._effort || ''; }

	setEffort(level) {
		if(this._allowedEfforts(this._model).indexOf(level) === -1) return this;
		this._effort = level;
		this._renderPickerLabel();
		return this;
	}

	setModel(id) { return this._pickSelection(id, null); }

	// Apply a picker choice: a model id and an optional explicit effort level (a submenu leaf). A null effort
	// (clicking the model row itself) resolves to the model's default. Re-gates images/progress and repaints
	// the trigger. No-ops if the id isn't an enabled model.
	_pickSelection(id, effort) {
		const next = (this.opts.models || []).find(m => m && m.id === id && !m.disabled);
		if(!next) return this;

		const providerChanged = !this._model || (next.provider !== this._model.provider);
		const modelChanged = !this._model || next.id !== this._model.id;
		this._model = next;
		// Leaf → that level (if allowed); parent/model row → the model's default.
		this._effort = (effort != null && effort !== '')
			? (this._allowedEfforts(next).indexOf(effort) !== -1 ? effort : (next.default_effort || ''))
			: (next.default_effort || '');

		this._renderPickerLabel();
		this._syncImageControls();
		this._renderProgress();
		this._renderCacheRing();   // TTL/visibility follows the newly-selected model

		if(modelChanged && typeof this.opts.onModelChange === 'function')
			this.opts.onModelChange(next, { providerChanged });
		return this;
	}

	_buildFooter() {
		const footer = document.createElement('div');
		footer.className = 'cerb-ui-agentprompt--footer';

		// Attachment chips (above the footer row).
		this._attachmentsEl = document.createElement('div');
		this._attachmentsEl.className = 'cerb-ui-agentprompt--attachments';
		this.el.appendChild(this._attachmentsEl);

		// Model selector — a combobox trigger opening a CerbUI.Menu. Models are top-level rows; a model that
		// offers effort_choices gets a cascading effort SUBMENU (selectableParents: clicking the model row picks
		// it with its default effort, a submenu leaf picks model+level). One control, no second dropdown.
		this._buildModelPicker(footer);

		// Context progress bar — a CerbUI.Distbar (used / available), no legend.
		this._progressEl = document.createElement('div');
		this._progressEl.className = 'cerb-ui-distbar cerb-ui-agentprompt--progress';
		this._progressUsed = document.createElement('span');
		this._progressUsed.setAttribute('data-value', '0');
		this._progressAvail = document.createElement('span');
		this._progressAvail.setAttribute('data-value', '1');
		this._progressEl.appendChild(this._progressUsed);
		this._progressEl.appendChild(this._progressAvail);
		footer.appendChild(this._progressEl);
		if(window.CerbUI && CerbUI.Distbar)
			this._distbar = new CerbUI.Distbar(this._progressEl);

		// Auto-compaction threshold tick — appended AFTER Distbar reads its segments (:scope > span at
		// construction), so it isn't counted as one; positioned absolutely at the model's context_ratio.
		this._progressThreshold = document.createElement('span');
		this._progressThreshold.className = 'cerb-ui-agentprompt--threshold';
		this._progressThreshold.style.display = 'none';
		this._progressEl.appendChild(this._progressThreshold);

		// Prompt-cache countdown ring — soft guidance on how long until the cache tail lapses (a pause past it
		// re-parses recent turns at full price). Shown only when the model caches AND a prior turn exists;
		// _renderCacheRing decides. A dumb TimeRing (no internal clock), so drive it on an interval.
		this._cacheRingWrap = document.createElement('span');
		this._cacheRingWrap.className = 'cerb-ui-agentprompt--cache-ring';
		this._cacheRingWrap.style.display = 'none';
		footer.appendChild(this._cacheRingWrap);
		if(window.CerbUI && CerbUI.TimeRing) {
			this._cacheRing = new CerbUI.TimeRing(this._cacheRingWrap, { size: 30, stroke: 3 });
			this._cacheTimer = setInterval(() => this._renderCacheRing(), 1000);
		}

		// Attach image — opens a file picker; same upload path as paste (vision-gated).
		if(this.opts.images !== false) {
			this._fileInput = document.createElement('input');
			this._fileInput.type = 'file';
			this._fileInput.accept = 'image/*';
			this._fileInput.multiple = true;
			this._fileInput.style.display = 'none';
			this._fileInput.addEventListener('change', () => {
				const files = this._fileInput.files || [];
				for(const f of files) if(f.type.lastIndexOf('image/', 0) === 0) this._uploadImage(f);
				this._fileInput.value = '';
			});
			this.el.appendChild(this._fileInput);

			this._attachBtn = document.createElement('button');
			this._attachBtn.type = 'button';
			this._attachBtn.className = 'cerb-ui-button cerb-ui-button--subtle';
			this._attachBtn.title = 'Attach image';
			this._attachBtn.innerHTML = '<span class="cerb-icons cerb-icon-paperclip"></span>';
			this._attachBtn.addEventListener('click', () => { if(this._fileInput) this._fileInput.click(); });
			footer.appendChild(this._attachBtn);
		}

		// Send — a CerbUI button.
		this._sendBtn = document.createElement('button');
		this._sendBtn.type = 'button';
		this._sendBtn.className = 'cerb-ui-button cerb-ui-button--subtle';
		this._sendBtn.innerHTML = '<span class="cerb-icons cerb-icon-send"></span>';
		this._sendBtn.title = 'Send';
		this._sendBtn.addEventListener('click', () => {
			this._submit();
			this.focus();
		});
		// No enabled model → nothing to send to; disable Send (the picker is also empty in this state).
		if(!this._model) {
			this._sendBtn.disabled = true;
			this._sendBtn.title = 'No models available';
		}
		footer.appendChild(this._sendBtn);

		this.el.appendChild(footer);
		this._renderAttachments();
		this._syncImageControls();
		this._renderProgress();
		this._renderCacheRing();
	}

	// Build the combobox trigger + its CerbUI.Menu (models, each with an optional effort submenu). Reuses the
	// SelectMenu trigger chrome (.cerb-ui-selectmenu) for visual parity with other pickers.
	_buildModelPicker(footer) {
		this._pickerTrigger = document.createElement('div');
		this._pickerTrigger.className = 'cerb-ui-selectmenu cerb-ui-agentprompt--model';
		this._pickerTrigger.setAttribute('role', 'combobox');
		this._pickerTrigger.setAttribute('aria-haspopup', 'menu');
		this._pickerTrigger.setAttribute('aria-expanded', 'false');
		this._pickerTrigger.setAttribute('tabindex', '0');

		this._pickerValue = document.createElement('span');
		this._pickerValue.className = 'cerb-ui-selectmenu--value';
		this._pickerTrigger.appendChild(this._pickerValue);

		const chevron = document.createElement('span');
		chevron.className = 'cerb-icons cerb-icon-chevron-down cerb-ui-selectmenu--chevron';
		chevron.setAttribute('aria-hidden', 'true');
		this._pickerTrigger.appendChild(chevron);
		footer.appendChild(this._pickerTrigger);

		const enabled = this._enabledModels();
		if(!enabled.length || !(window.CerbUI && CerbUI.Menu)) {
			this._renderPickerLabel();
			return;
		}

		// Source <ul>: each model = a top-level <li> (data-value=<id>, icon via data-cerb-ui-icon); a model
		// with effort_choices gets a child <ul> of per-level leaves (data-value=<id>\t<level>).
		const ul = document.createElement('ul');
		enabled.forEach(m => {
			const li = document.createElement('li');
			li.dataset.value = m.id;
			if(m.icon) li.dataset.cerbUiIcon = m.icon;
			// Shown only when true, matching the worklist and the model editor -- an absent mark reads faster
			// than a struck-through one, and a bare row is exactly the signal that a model can't do this.
			if(m.vision) li.dataset.cerbVision = '1';
			if(m.thinking) li.dataset.cerbThinking = '1';
			if(Array.isArray(m.ratings) && m.ratings.length) li.dataset.cerbRatings = JSON.stringify(m.ratings);
			li.appendChild(document.createTextNode(m.label || m.model || m.id));

			const choices = this._effortChoices(m);
			if(choices.length) {
				const sub = document.createElement('ul');
				choices.forEach(level => {
					const leaf = document.createElement('li');
					leaf.dataset.value = m.id + '\t' + level;
					leaf.appendChild(document.createTextNode(level));
					sub.appendChild(leaf);
				});
				li.appendChild(sub);
			}
			ul.appendChild(li);
		});

		// `ratings:` on the element can narrow the meters or switch them off entirely, and with none the rows
		// collapse back to one line. itemHeight MUST track the CSS height either way -- the virtual-scroll
		// math positions rows by that number rather than measuring them. Uniform per picker, since the
		// setting is per-element rather than per-model.
		const stacked = enabled.some(m => Array.isArray(m.ratings) && m.ratings.length);

		this._modelMenu = new CerbUI.Menu(ul, {
			selectableParents: true,   // clicking a model row selects it (default effort); hover opens its submenu
			clearActiveOnLeave: true,  // move off the menu and the row un-highlights and its effort submenu folds
			                           // back up, rather than sitting lit until the next click
			panelClass: 'cerb-ui-agentprompt--model-menu',
			itemHeight: stacked ? 44 : 28,
			onRenderItem: (renderedLi, sourceLi) => {
				const icon = sourceLi.dataset.cerbUiIcon;
				if(icon) {
					const el = document.createElement('span');
					el.className = 'cerb-icons cerb-icon-' + icon + ' cerb-ui-selectmenu--icon';
					el.setAttribute('aria-hidden', 'true');
					renderedLi.insertBefore(el, renderedLi.firstChild);
				}

				// Ratings: a second line of fixed-width meters. They are wrapped in a column block rather than
				// appended to the row, because the whole point is scanning DOWN a column -- meters that flowed
				// after a variable-length label would sit at a different x on every row and compare nothing.
				// The submenu arrow is appended after this hook, so it stays a sibling and centers alongside.
				let meters = [];
				try { meters = JSON.parse(sourceLi.dataset.cerbRatings || '[]'); } catch(e) { meters = []; }

				// The brand mark stays OUTSIDE the column block, in its own left gutter, so the name and any
				// meters below it share one left edge. Both lines then start at the same x and the rows scan
				// as a single column of models rather than two ragged ones.
				const brand = renderedLi.querySelector('.cerb-ui-selectmenu--icon');
				if(brand) brand.remove();

				const entry = document.createElement('span');
				entry.className = 'cerb-ui-agentprompt--model-entry';

				const line1 = document.createElement('span');
				line1.className = 'cerb-ui-agentprompt--model-line';
				while(renderedLi.firstChild) line1.appendChild(renderedLi.firstChild);

				// Trailing the NAME, not right-aligned: anchored to the label they can never collide with the
				// submenu arrow, so the position no longer depends on whether a model has one. Ragged x is
				// fine here -- these answer "can it?", and only the meters are meant to compare down the list.
				// Rendered independently of the meters, so switching ratings off never hides a capability.
				const caps = [
					{ on: sourceLi.dataset.cerbVision, icon: 'eye-open', label: 'Accepts images' },
					{ on: sourceLi.dataset.cerbThinking, icon: 'brain', label: 'Extended thinking' }
				].filter(c => c.on);

				if(caps.length) {
					const capWrap = document.createElement('span');
					capWrap.className = 'cerb-ui-agentprompt--model-caps';

					caps.forEach(c => {
						const g = document.createElement('span');
						g.className = 'cerb-icons cerb-icon-' + c.icon;
						g.title = c.label;
						capWrap.appendChild(g);
					});

					line1.appendChild(capWrap);
				}

				entry.appendChild(line1);

				// Ratings: a second line of fixed-width meters, in the element's configured order. Wrapped in
				// the column block rather than appended to the row, because the point is scanning DOWN a
				// column -- meters that flowed after a variable-length label would sit at a different x on
				// every row and compare nothing.
				if(meters.length) {
					entry.classList.add('cerb-ui-agentprompt--model-entry-stacked');

					const row = document.createElement('span');
					row.className = 'cerb-ui-agentprompt--meters';

					const glyphs = { intelligence: 'brain', speed: 'zap', privacy: 'lock', cost: 'coins' };

					meters.forEach(r => {
						const meter = document.createElement('span');
						meter.className = 'cerb-ui-agentprompt--meter cerb-ui-agentprompt--meter-' + r.key;
						meter.title = r.key.charAt(0).toUpperCase() + r.key.slice(1)
							+ ': ' + (r.label || 'unrated') + ' (as configured)';

						const g = document.createElement('span');
						g.className = 'cerb-icons cerb-icon-' + (glyphs[r.key] || 'circle');
						meter.appendChild(g);

						// Discrete blocks, not a bar: the scale HAS four steps and nothing between them, and
						// 3-of-4 vs 4-of-4 is a 6px length difference at this size but an obvious count. `of`
						// comes from the server, so a fifth tier grows every meter with no client change.
						const blocks = document.createElement('span');
						blocks.className = 'cerb-ui-agentprompt--meter-blocks';

						for(let i = 1; i <= (r.of || 0); i++) {
							const b = document.createElement('span');
							b.className = 'cerb-ui-agentprompt--meter-block'
								+ (i <= r.level ? ' cerb-ui-agentprompt--meter-block-on' : '');
							blocks.appendChild(b);
						}

						meter.appendChild(blocks);
						row.appendChild(meter);
					});

					entry.appendChild(row);
				}

				if(brand) renderedLi.appendChild(brand);
				renderedLi.appendChild(entry);
			},
			onSelect: (renderedLi, sourceLi) => {
				const parts = String(sourceLi.dataset.value || '').split('\t');
				this._pickSelection(parts[0], parts.length > 1 ? parts[1] : null);
				this._pickerTrigger.focus();
			},
			onClose: () => {
				this._pickerTrigger.setAttribute('aria-expanded', 'false');
				this._pickerTrigger.classList.remove('cerb-ui-selectmenu--open');
			},
		});

		const open = () => {
			this._pickerTrigger.setAttribute('aria-expanded', 'true');
			this._pickerTrigger.classList.add('cerb-ui-selectmenu--open');
			this._modelMenu.open(this._pickerTrigger);
		};
		this._pickerTrigger.addEventListener('click', () => {
			if(this._modelMenu.isOpen()) this._modelMenu.close(); else open();
		});
		this._pickerTrigger.addEventListener('keydown', (e) => {
			if(this._modelMenu.isOpen()) return;
			if(e.key === ' ' || e.key === 'Enter' || e.key === 'ArrowDown' || e.key === 'ArrowUp') {
				e.preventDefault();
				open();
			}
		});

		this._renderPickerLabel();
	}

	// Paint the trigger with the current model's label + icon, and an effort suffix when a non-empty level is
	// active (e.g. "Sonnet (anthropic) · high").
	_renderPickerLabel() {
		if(!this._pickerValue) return;
		this._pickerValue.replaceChildren();

		if(!this._model) {
			const t = document.createElement('span');
			t.className = 'cerb-ui-selectmenu--text cerb-ui-selectmenu--text-placeholder';
			t.textContent = 'No models available';
			this._pickerValue.appendChild(t);
			return;
		}

		if(this._model.icon) {
			const icon = document.createElement('span');
			icon.className = 'cerb-icons cerb-icon-' + this._model.icon + ' cerb-ui-selectmenu--icon';
			icon.setAttribute('aria-hidden', 'true');
			this._pickerValue.appendChild(icon);
		}

		const text = document.createElement('span');
		text.className = 'cerb-ui-selectmenu--text';
		let label = this._model.label || this._model.model || this._model.id;
		if(this._effort) label += ' · ' + this._effort;
		text.textContent = label;
		this._pickerValue.appendChild(text);
	}

	// Image controls follow the selected model's vision capability. Runs at first render (_buildFooter) and on
	// every model change (setModel), so switching models re-gates live — no server round-trip. Paste is gated
	// separately in _handlePaste; here we hide the attach button (not just disable it) so a non-vision model
	// offers no upload affordance at all.
	_syncImageControls() {
		const noVision = !!(this._model && this._model.vision === false);

		if(this._attachBtn) this._attachBtn.style.display = noVision ? 'none' : '';

		// Switching to a non-vision model drops any staged images (the server vision-gates them anyway) so the
		// chips don't imply an image will be sent.
		if(noVision && this._attachments.length) {
			this._attachments = [];
			this._renderAttachments();
		}
	}

	_renderProgress() {
		if(!this._progressEl) return;
		const win = (this._model && this._model.context_window) ? this._model.context_window : 0;
		const used = this._contextTokens || 0;
		const usedClamped = win > 0 ? Math.min(used, win) : 0;
		const avail = win > 0 ? Math.max(0, win - usedClamped) : 1;
		const pct = win > 0 ? Math.round((usedClamped / win) * 100) : 0;

		this._progressUsed.setAttribute('data-value', usedClamped);
		this._progressAvail.setAttribute('data-value', avail);
		// Distbar colored segments by palette; recolor to the grays. USED = bright (high contrast), the
		// remaining/AVAILABLE track = a subtle dark fill (low contrast). Warn/full accents on the used part.
		this._progressUsed.style.backgroundColor = (pct >= 90)
			? 'var(--cerb-color-accent-red, var(--cerb-color-background-contrast-240))'
			: ((pct >= 75) ? 'var(--cerb-color-accent-orange, var(--cerb-color-background-contrast-220))'
				: 'var(--cerb-color-background-contrast-150)');
		this._progressAvail.style.backgroundColor = 'var(--cerb-color-background-contrast-70)';
		if(this._distbar) this._distbar.render();
		this._progressEl.title = win > 0 ? ('Context: ~' + used + ' / ' + win + ' tokens (' + pct + '%)') : 'Context usage';

		// Auto-compaction threshold tick at the model's context_ratio (default hidden when unknown or ≥100%).
		if(this._progressThreshold) {
			const ratio = (this._model && this._model.context_ratio) ? this._model.context_ratio : 0;
			if(win > 0 && ratio > 0 && ratio < 1) {
				this._progressThreshold.style.left = (ratio * 100) + '%';
				this._progressThreshold.style.display = '';
				this._progressThreshold.title = 'Auto-compaction ~' + Math.round(ratio * 100) + '%';
			} else {
				this._progressThreshold.style.display = 'none';
			}
		}
	}

	// The prompt-cache countdown ring: wall-clock elapsed since the last agent turn vs the selected model's
	// cache-tail TTL. Fills toward "expired = full" (like the context bar), muted → amber → red as it closes;
	// center shows time remaining. Hidden unless the model caches (cache_ttl) AND a prior turn set the anchor.
	// Driven on a 1s interval — TimeRing has no clock of its own.
	_renderCacheRing() {
		if(!this._cacheRing || !this._cacheRingWrap) return;

		const ttl = (this._model && this._model.cache_ttl) ? this._model.cache_ttl : 0;

		if(!ttl || this._cacheElapsedBase == null) {
			this._cacheRingWrap.style.display = 'none';
			return;
		}
		this._cacheRingWrap.style.display = '';

		const elapsed = this._cacheElapsedBase + (Date.now() - this._cacheT0) / 1000;
		const frac = Math.max(0, Math.min(1, elapsed / ttl));
		const rem = Math.max(0, ttl - elapsed);

		this._cacheRing.setFraction(frac);
		this._cacheRing.setLabel(this._fmtRemaining(rem), 'cache');

		// Recolor the arc (currentColor) to match the context bar's escalation.
		this._cacheRingWrap.style.color = (frac >= 1)
			? 'var(--cerb-color-accent-red, var(--cerb-color-background-contrast-240))'
			: ((frac >= 0.75) ? 'var(--cerb-color-accent-orange, var(--cerb-color-background-contrast-220))'
				: 'var(--cerb-color-background-contrast-150)');

		const ttlLabel = (ttl % 3600 === 0) ? (ttl / 3600) + 'h' : Math.round(ttl / 60) + 'm';
		this._cacheRingWrap.title = (frac >= 1)
			? ('Prompt cache tail (' + ttlLabel + ') has lapsed — the next turn re-parses recent turns')
			: ('Prompt cache tail (' + ttlLabel + ') lapses in ~' + this._fmtRemaining(rem));
	}

	// Seconds → one condensed unit for the ring's center: hours ≥1h, then minutes ≥1m, then seconds
	// (1h → 59m → … → 1m → 59s → … → 0s). Whole-unit granularity keeps the tiny center readable at a glance.
	_fmtRemaining(s) {
		s = Math.max(0, Math.floor(s));
		if(s >= 3600) return Math.floor(s / 3600) + 'h';
		if(s >= 60) return Math.floor(s / 60) + 'm';
		return s + 's';
	}

	// ── Image paste → attachments ───────────────────────────────────────

	_handlePaste(e) {
		if(this.opts.images === false || (this._model && this._model.vision === false)) return;
		const files = e.clipboardData && e.clipboardData.files;
		if(!files || files.length === 0) return;
		let hasImage = false;
		for(const f of files) if(f.type.lastIndexOf('image/', 0) === 0) hasImage = true;
		if(!hasImage) return;
		e.preventDefault();
		e.stopPropagation();
		for(const f of files) if(f.type.lastIndexOf('image/', 0) === 0) this._uploadImage(f);
	}

	_uploadImage(f) {
		const xhr = new XMLHttpRequest();
		if(!xhr.upload) return;
		xhr.open('POST', DevblocksAppPath + 'ajax.php?c=internal&a=invoke&module=records&action=chooserOpenFileAjaxUpload', true);
		xhr.setRequestHeader('X-File-Name', encodeURIComponent(f.name));
		xhr.setRequestHeader('X-File-Type', f.type);
		xhr.setRequestHeader('X-File-Size', f.size);
		// No asResource header: land the upload as a durable attachment (the endpoint's default), returning an
		// int `id`. Images are owned by the transcript via attachment_link and reaped when it's deleted (or by
		// the orphan sweep if never submitted) — no 1-hour resource TTL to expire mid-conversation.
		xhr.setRequestHeader('X-CSRF-Token', $('meta[name="_csrf_token"]').attr('content'));
		xhr.onreadystatechange = () => {
			if(xhr.readyState !== 4 || xhr.status !== 200) return;
			let json;
			try { json = JSON.parse(xhr.responseText); } catch(err) { return; }
			if(!json || !json.id) return;
			// Just the durable attachment uri — the backend looks up the attachment for its authoritative
			// mime_type + validation (a client-supplied mime is spoofable) and builds the `<name>__images` shape.
			const info = {
				uri: json.uri || ('cerb:attachment:' + json.id),
				file_name: json.name || f.name,
			};
			this._attachments.push(info);
			this._renderAttachments();
			if(typeof this.opts.onImage === 'function') this.opts.onImage(info);
		};
		xhr.send(f);
	}

	_renderAttachments() {
		if(!this._attachmentsEl) return;
		this._attachmentsEl.innerHTML = '';
		this._attachmentsEl.style.display = this._attachments.length ? '' : 'none';
		this._attachments.forEach((a, i) => {
			const chip = document.createElement('span');
			chip.className = 'cerb-ui-pill';

			const ic = document.createElement('span');
			ic.className = 'cerb-icons cerb-icon-picture';

			const nm = document.createElement('span');
			nm.textContent = a.file_name || 'image';

			// A <span> (not <button>) so the global button:has(cerb-icons):hover doesn't hijack it.
			const x = document.createElement('span');
			x.className = 'cerb-icons cerb-icon-circle-remove';
			x.setAttribute('role', 'button');
			x.title = 'Remove';
			x.style.cursor = 'pointer';
			x.addEventListener('click', () => { this._attachments.splice(i, 1); this._renderAttachments(); });

			chip.appendChild(ic);
			chip.appendChild(nm);
			chip.appendChild(x);
			this._attachmentsEl.appendChild(chip);
		});
	}

	// ── Input / keyboard ────────────────────────────────────────────────

	_handleInput() {
		this._renderHighlight();
		this._autosize();
		this._ac.clearTimer();
		const sp = this._scopePathAt(this.textarea.value, this.textarea.selectionStart);
		if(sp.trigger && typeof this.opts.onAutocomplete === 'function') this._ac.schedule();
		else this._ac.close();
	}

	_handleKeydown(e) {
		const menuOpen = this._ac.isOpen();

		if(e.code === 'Space' && (e.ctrlKey || e.metaKey)) {
			e.preventDefault();
			if(menuOpen) e.stopPropagation();
			this._ac.trigger();
			return;
		}

		if(menuOpen) {
			// Drive the suggestion menu directly (popup key-containment blocks its own document listener).
			if((e.key === 'ArrowDown' || e.key === 'ArrowUp') && !(e.metaKey || e.ctrlKey || e.altKey)) {
				e.preventDefault(); e.stopPropagation();
				this._ac.moveSelection(e.key === 'ArrowDown' ? +1 : -1);
				return;
			}
			if(e.key === 'ArrowLeft' || e.key === 'ArrowRight') { e.stopPropagation(); this._ac.close(); return; }
			if(e.key === 'Enter') {
				if(this._ac.navigated || this._ac.pointerInMenu()) {
					e.preventDefault(); e.stopPropagation(); this._ac.acceptSelection(e); return;
				}
				e.stopPropagation(); this._ac.close(); return;
			}
			if(e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); this._ac.close(); return; }
			return;
		}

		// Menu closed:
		// Enter submits; Shift+Enter is a newline (native).
		if(e.key === 'Enter' && !e.shiftKey && !e.metaKey && !e.ctrlKey && !e.altKey) {
			e.preventDefault();
			this._submit();
			return;
		}

		// Up/Down recall prompt history at the first/last line.
		if(e.key === 'ArrowUp' && !e.shiftKey && !e.metaKey && !e.ctrlKey && !e.altKey && this._caretOnFirstLine()) {
			if(this._history.length && this._recallHistory(-1)) { e.preventDefault(); return; }
		}
		if(e.key === 'ArrowDown' && !e.shiftKey && !e.metaKey && !e.ctrlKey && !e.altKey && this._caretOnLastLine()) {
			if(this._historyIndex !== -1 && this._recallHistory(+1)) { e.preventDefault(); return; }
		}
	}

	_caretOnFirstLine() { return this.textarea.value.lastIndexOf('\n', this.textarea.selectionStart - 1) === -1; }
	_caretOnLastLine() { return this.textarea.value.indexOf('\n', this.textarea.selectionEnd) === -1; }

	// dir -1 = older, +1 = newer. Returns true if it moved.
	_recallHistory(dir) {
		if(this._historyIndex === -1) {
			if(dir !== -1) return false;
			this._draftBeforeHistory = this.getValue();
			this._historyIndex = this._history.length - 1;
		} else {
			this._historyIndex += dir;
		}

		if(this._historyIndex < 0) { this._historyIndex = 0; }

		if(this._historyIndex >= this._history.length) {
			// Past the newest — restore the in-progress draft.
			this._historyIndex = -1;
			this.setValue(this._draftBeforeHistory || '');
		} else {
			this.setValue(this._history[this._historyIndex]);
		}
		this.setSelection(this.textarea.value.length, this.textarea.value.length);
		return true;
	}

	// ── Highlight (tint reference tokens only; no markdown) ──────────────

	_renderHighlight() {
		CerbUI.editorCore.renderTokens(this.highlight, this._tokenize(this.textarea.value), CerbUI.AgentPrompt._TOK_CLASS);
		this._syncScroll();
	}

	_syncScroll() { CerbUI.editorCore.syncScroll(this.textarea, this.highlight); }

	_autosize() {
		const ta = this.textarea;
		ta.style.height = 'auto';
		const h = Math.min(Math.max(ta.scrollHeight, this.opts.minHeight), this.opts.maxHeight);
		ta.style.height = h + 'px';
		ta.style.overflowY = (ta.scrollHeight > this.opts.maxHeight) ? 'auto' : 'hidden';
		this.highlight.style.height = ta.style.height;
	}

	// Covers every char (incl. newlines). Tints `@handle` / `@type:id` mentions (@ kept) and a leading `/command`.
	// The name class allows dashes (`@cerb-dev`) so a hyphenated mention tints as one token.
	_tokenize(text) {
		const toks = [];
		// Matches the three mention forms (see _extractMentions), so an `@volume/path/file.md` reference tints
		// as one token instead of just its `@volume` head. The word-boundary guard below keeps `foo@bar.com/x`
		// from tinting.
		const RX = /@[a-z0-9_.-]+(?:\/[a-z0-9_./-]*)+|@[a-z0-9_.-]+(?::[a-z0-9-]+)?/gi;
		const lines = text.split('\n');
		for(let li = 0; li < lines.length; li++) {
			if(li > 0) toks.push({ type: 'text', value: '\n' });
			let line = lines[li], last = 0, m;
			// A slash command occupies the whole first line when it leads with `/`.
			if(li === 0 && /^\/[a-z0-9_.-]*/i.test(line)) {
				const cmd = line.match(/^\/[a-z0-9_.-]*/i)[0];
				toks.push({ type: 'command', value: cmd });
				last = cmd.length;
			}
			RX.lastIndex = last;
			while((m = RX.exec(line)) !== null) {
				// Only at a word boundary (so emails like foo@bar don't tint).
				if(m.index !== 0 && !/\s/.test(line[m.index - 1])) continue;
				if(m.index > last) toks.push({ type: 'text', value: line.slice(last, m.index) });
				toks.push({ type: 'mention', value: m[0] });
				last = m.index + m[0].length;
			}
			if(last < line.length) toks.push({ type: 'text', value: line.slice(last) });
		}
		return toks;
	}

	// ── Scope at the caret (for autocomplete) ───────────────────────────
	// Two triggers: an `@…` word (mention/context) and a leading `/…` (automation command).

	_scopePathAt(text, caret) {
		const lineStart = text.lastIndexOf('\n', caret - 1) + 1;
		const line = text.slice(lineStart, caret);

		// A `/command` only triggers at the very start of the prompt's line.
		if(/^\/[a-z0-9_.-]*$/i.test(line))
			return { trigger: 'command', path: ['/'], prefix: line.slice(1), prefixRaw: line, line, caret };

		let s = caret;
		while(s > 0 && !/\s/.test(text[s - 1])) s--;
		const word = text.slice(s, caret);

		if(word.charAt(0) === '@')
			return { trigger: 'mention', path: ['@'], prefix: word.slice(1), prefixRaw: word, line, caret };

		return { trigger: null, path: [], prefix: '', prefixRaw: '', line, caret };
	}

	// The single mutation primitive — undo-safe via execCommand (falls back to setRangeText).
	_replaceRange(start, end, text) {
		const ta = this.textarea;
		text = String(text == null ? '' : text).replace(/\r/g, '');
		ta.focus();
		ta.setSelectionRange(start, end);
		let ok = false;
		try { ok = document.execCommand('insertText', false, text); } catch(e) { ok = false; }
		if(!ok) {
			ta.setRangeText(text, start, end, 'end');
			this._renderHighlight(); this._autosize();
		}
	}
};

// Token type -> CSS class (metric-safe: color only). `mention` = accent, `command` = accent.
CerbUI.AgentPrompt._TOK_CLASS = {
	mention: 'cerb-ui-agentprompt--tok-mention',
	command: 'cerb-ui-agentprompt--tok-command',
};

/*
 * CerbUI.AgentPrompt.ModelPicker — the design-time editor for the model list an AgentPrompt offers. Each row
 * REFERENCES a first-class agent_model record by name (the record supplies provider / model / authentication /
 * vision / context window); a row may override a few per-offer knobs. "+ Model" lists the configured
 * agent_models; drag to reorder. It doesn't run a chat — it SEEDS an agentPrompt's `models:` block (used by the
 * Automation Builder wizard and the Form Builder). onChange(models) fires on any edit.
 *
 *   new CerbUI.AgentPrompt.ModelPicker(el, { agentModels, models, onChange });
 *   agentModels: [{ name, provider, model, icon, vision, context_window }]  (the records to offer)
 *   getModels() -> [{ name, context_window?, effort_choices?, disabled? }]   (only overrides that are set)
 */
CerbUI.AgentPrompt.ModelPicker = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.AgentPrompt.ModelPicker._instances.get(el); }

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;

		this.opts = Object.assign({ agentModels: [], models: null, onChange: null }, opts);
		this.agentModels = this.opts.agentModels || [];

		this._recordByName = {};
		this.agentModels.forEach(m => { if(m && m.name) this._recordByName[m.name] = m; });

		// Start empty — the first "+ Model" is the user's default. A caller may seed models (editing an
		// existing agentPrompt).
		this.models = Array.isArray(this.opts.models) ? this.opts.models.map(m => this._normalize(m)) : [];

		CerbUI.AgentPrompt.ModelPicker._instances.set(this.el, this);
		this.render();
	}

	// The overrides for each referenced model, in KATA order. Only non-empty overrides are emitted — a bare
	// reference (`models: <name>:`) inherits everything from the record.
	getModels() {
		return this.models.filter(m => m.name).map(m => {
			const out = { name: m.name };
			if(m.context_window !== '' && m.context_window != null) out.context_window = parseInt(m.context_window, 10) || 0;
			if(m.effort_choices) out.effort_choices = m.effort_choices;
			if(m.disabled) out.disabled = m.disabled;
			return out;
		});
	}

	destroy() {
		if(this._sortable) { this._sortable.destroy(); this._sortable = null; }
		CerbUI.AgentPrompt.ModelPicker._instances.delete(this.el);
	}

	// No async choosers to pull from anymore (rows are plain fields); kept for API parity with callers that
	// call sync() before reading `.models` directly.
	sync() { return this; }

	_normalize(m) {
		m = m || {};
		const rec = this._recordByName[m.name] || {};
		return {
			name: m.name || '',
			context_window: (m.context_window != null) ? m.context_window : '',
			effort_choices: m.effort_choices || '',
			disabled: m.disabled || '',
			_provider: rec.provider || m._provider || '',
			_icon: rec.icon || m._icon || 'bot-message',
			_model: rec.model || m._model || '',
			_cwDefault: (rec.context_window != null) ? rec.context_window : '',
		};
	}

	_emit() { if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getModels()); }

	_append(m) {
		this.models.push(this._normalize(m));
		this.render();
		this._emit();
	}

	render() {
		this.el.classList.add('cerb-ui-agent-model-picker');
		this.el.textContent = '';

		const head = document.createElement('div');
		head.className = 'cerb-ui-agent-model-picker--head cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1';
		head.textContent = 'Models (drag to reorder)';
		this.el.appendChild(head);

		const list = document.createElement('div');
		list.className = 'cerb-ui-agent-model-picker--list';
		this._listEl = list;
		this.models.forEach((m, i) => list.appendChild(this._buildCard(m, i)));
		this.el.appendChild(list);

		this._initSortable();

		const add = document.createElement('button');
		add.type = 'button';
		add.className = 'cerb-ui-button cerb-ui-button--subtle cerb-ui-agent-model-picker--add';
		add.innerHTML = '<span class="cerb-icons cerb-icon-plus"></span> Model';
		add.addEventListener('click', (e) => this._openModelMenu(e.currentTarget));
		this.el.appendChild(add);
	}

	_initSortable() {
		if(this._sortable) { this._sortable.destroy(); this._sortable = null; }
		if(!(window.CerbUI && CerbUI.Sortable)) return;
		this._sortable = new CerbUI.Sortable(this._listEl, {
			items: '.cerb-ui-agent-model-picker--card',
			handle: '.cerb-ui-agent-model-picker--card-head',
			onEnd: (info) => {
				const from = info.fromIndex, to = info.toIndex;
				if(from == null || to == null || from === to) return;
				const moved = this.models.splice(from, 1)[0];
				this.models.splice(to, 0, moved);
				this._emit();
				setTimeout(() => this.render(), 0);
			},
		});
	}

	// "+ Model" — the configured agent_models not already chosen (by name).
	_openModelMenu(anchor) {
		const chosen = {};
		this.models.forEach(m => { if(m.name) chosen[m.name] = true; });
		const available = this.agentModels.filter(m => m && m.name && !chosen[m.name]);

		if(!(window.CerbUI && CerbUI.Menu)) {
			if(available[0]) this._append({ name: available[0].name });
			return;
		}

		const ul = document.createElement('ul');

		if(!available.length) {
			const li = document.createElement('li');
			li.textContent = this.agentModels.length ? 'All models added' : 'No agent models configured';
			li.dataset.disabled = '1';
			ul.appendChild(li);
		}

		available.forEach(m => {
			const li = document.createElement('li');
			li.textContent = m.name + (m.model ? ' — ' + m.model : '');
			li.dataset.name = m.name;
			if(m.icon) li.dataset.icon = m.icon;
			ul.appendChild(li);
		});

		const menu = new CerbUI.Menu(ul, {
			onRenderItem: (renderedLi, srcLi) => {
				const icon = srcLi.dataset ? srcLi.dataset.icon : '';
				if(icon) {
					const ico = document.createElement('span');
					ico.className = 'cerb-icons cerb-icon-' + icon + ' cerb-u-mr-1';
					renderedLi.insertBefore(ico, renderedLi.firstChild);
				}
			},
			onSelect: (li, src) => {
				if(src.dataset && src.dataset.name)
					this._append({ name: src.dataset.name });
			},
		});
		menu.open(anchor);
	}

	_field(label) {
		const field = document.createElement('div');
		field.className = 'cerb-ui-form--field';
		const lab = document.createElement('label');
		lab.className = 'cerb-ui-form--label';
		lab.textContent = label;
		field.appendChild(lab);
		return field;
	}

	_textField(card, label, value, placeholder, onInput) {
		const field = this._field(label);
		const input = document.createElement('input');
		input.type = 'text';
		input.value = (value == null) ? '' : value;
		if(placeholder) input.setAttribute('placeholder', placeholder);
		input.addEventListener('input', () => onInput(input.value, input));
		field.appendChild(input);
		card.appendChild(field);
		return input;
	}

	_buildCard(m, index) {
		const card = document.createElement('div');
		card.className = 'cerb-ui-agent-model-picker--card';

		// Header: the referenced record's provider icon + name (read-only — a reference, not an inline model).
		const header = document.createElement('div');
		header.className = 'cerb-ui-agent-model-picker--card-head';
		const title = document.createElement('span');
		title.className = 'cerb-ui-agent-model-picker--card-title';
		title.innerHTML = '<span class="cerb-icons cerb-icon-' + (m._icon || 'bot-message') + '"></span> <b></b>';
		title.querySelector('b').textContent = m.name || 'model';
		if(m._model) {
			const sub = document.createElement('span');
			sub.className = 'cerb-u-text-muted cerb-u-ml-1';
			sub.textContent = m._model;
			title.appendChild(sub);
		}
		header.appendChild(title);
		const del = document.createElement('button');
		del.type = 'button';
		del.className = 'cerb-ui-button cerb-ui-button--subtle';
		del.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove"></span>';
		del.addEventListener('click', () => { this.models.splice(index, 1); this.render(); this._emit(); });
		header.appendChild(del);
		card.appendChild(header);

		const form = document.createElement('div');
		form.className = 'cerb-ui-form';
		card.appendChild(form);

		// Optional per-offer overrides — blank inherits the record's own value.
		const row = document.createElement('div');
		row.className = 'cerb-ui-form--row';
		form.appendChild(row);

		const cwField = this._field('Context window');
		const cw = document.createElement('input');
		cw.type = 'number';
		cw.value = (m.context_window !== '' && m.context_window != null) ? m.context_window : '';
		if(m._cwDefault) cw.setAttribute('placeholder', String(m._cwDefault));
		cw.addEventListener('input', () => { m.context_window = cw.value; this._emit(); });
		cwField.appendChild(cw);
		row.appendChild(cwField);

		this._textField(row, 'Effort choices', m.effort_choices, 'e.g. medium,high,xhigh', (v) => { m.effort_choices = v; this._emit(); });

		this._textField(form, 'Disable when (expression)', m.disabled, 'e.g. worker_over_budget', (v) => { m.disabled = v; this._emit(); });

		return card;
	}
};

/*
 * CerbUI.AgentPrompt.ToolPicker — pick the tools an agent may call and configure each. A CerbUI.RecordChooser
 * (scoped by a query, e.g. `trigger:cerb.trigger.llm.tool`) adds tools; each becomes a card where you name it
 * (the function name the model sees, e.g. `docs_search`) and give its transcript labels. getTools() returns
 * [{ id, alias, label_summary, label_active }] — the caller resolves id -> automation name/uri.
 */
CerbUI.AgentPrompt.ToolPicker = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.AgentPrompt.ToolPicker._instances.get(el); }

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;

		this.opts = Object.assign({ context: 'cerb.contexts.automation', query: '', tools: null, onChange: null }, opts);
		this.tools = Array.isArray(this.opts.tools) ? this.opts.tools.map(t => this._normalize(t)) : [];

		CerbUI.AgentPrompt.ToolPicker._instances.set(this.el, this);
		this.render();
	}

	getTools() {
		return this.tools.filter(t => t.id).map(t => ({
			id: t.id,
			alias: this._sanitizeAlias(t.alias) || this._sanitizeAlias(t.name) || 'tool',
			label_summary: t.label_summary || '',
			label_active: t.label_active || '',
		}));
	}

	destroy() {
		if(this._rc && this._rc.destroy) { try { this._rc.destroy(); } catch(e) {} }
		CerbUI.AgentPrompt.ToolPicker._instances.delete(this.el);
	}

	_normalize(t) {
		return Object.assign({ id: 0, name: '', alias: '', label_summary: '', label_active: '' }, t || {});
	}

	_sanitizeAlias(v) {
		return String(v || '').toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '');
	}

	_emit() { if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getTools()); }

	_add(item) {
		this.tools.push(this._normalize({
			id: item.id,
			name: item.label || '',
			alias: this._sanitizeAlias(item.label || ''),
			// Default to the transcript's own generic verbs, so what's baked matches the UI default.
			label_active: 'Working...',
			label_summary: 'Worked',
		}));
		this.render();
		this._emit();
	}

	render() {
		this.el.classList.add('cerb-ui-agent-tool-picker');
		this.el.textContent = '';

		const head = document.createElement('div');
		head.className = 'cerb-ui-agent-tool-picker--head cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1';
		head.textContent = 'Tools';
		this.el.appendChild(head);

		const list = document.createElement('div');
		list.className = 'cerb-ui-agent-tool-picker--list';
		this._listEl = list;
		this.tools.forEach((t, i) => list.appendChild(this._buildCard(t, i)));
		this.el.appendChild(list);

		// The "add a tool" chooser sits BELOW the cards (consistent with the models "+ Model").
		const adder = document.createElement('div');
		adder.className = 'cerb-ui-agent-tool-picker--adder';
		this.el.appendChild(adder);

		if(window.CerbUI && CerbUI.RecordChooser) {
			try {
				const rc = new CerbUI.RecordChooser(adder, {
					context: this.opts.context,
					query: this.opts.query,
					emptyIcon: 'wrench',
					searchPlaceholder: 'Add a tool…',
					exclude: () => this.tools.map(t => t.id).filter(Boolean),
					onSelect: (item) => {
						if(!item || !item.id) return;
						this._add(item);
						if(rc.clear) rc.clear();
					},
				});
				this._rc = rc;
			} catch(e) {}
		}
	}

	_field(card, label, value, placeholder, onInput) {
		const field = document.createElement('div');
		field.className = 'cerb-ui-form--field';
		const lab = document.createElement('label');
		lab.className = 'cerb-ui-form--label';
		lab.textContent = label;
		const input = document.createElement('input');
		input.type = 'text';
		input.value = (value == null) ? '' : value;
		if(placeholder) input.setAttribute('placeholder', placeholder);
		input.addEventListener('input', () => onInput(input.value));
		field.appendChild(lab);
		field.appendChild(input);
		card.appendChild(field);
		return input;
	}

	_buildCard(t, index) {
		const card = document.createElement('div');
		card.className = 'cerb-ui-agent-tool-picker--card';

		const header = document.createElement('div');
		header.className = 'cerb-ui-agent-tool-picker--card-head';
		const title = document.createElement('span');
		title.className = 'cerb-ui-agent-tool-picker--card-title';
		title.innerHTML = '<span class="cerb-icons cerb-icon-wrench"></span> <b></b>';
		title.querySelector('b').textContent = t.name || ('#' + t.id);
		header.appendChild(title);
		const del = document.createElement('button');
		del.type = 'button';
		del.className = 'cerb-ui-button cerb-ui-button--subtle';
		del.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove"></span>';
		del.addEventListener('click', () => { this.tools.splice(index, 1); this.render(); this._emit(); });
		header.appendChild(del);
		card.appendChild(header);

		const form = document.createElement('div');
		form.className = 'cerb-ui-form';
		card.appendChild(form);

		this._field(form, 'Name (the agent calls this)', t.alias, 'e.g. docs_search', (v) => { t.alias = v; this._emit(); });
		this._field(form, 'Active label', t.label_active, 'e.g. Working...', (v) => { t.label_active = v; this._emit(); });
		this._field(form, 'Summary label', t.label_summary, 'e.g. Worked', (v) => { t.label_summary = v; this._emit(); });

		return card;
	}
};
