/*
 * CerbUI.AgentConfig -- the AI tab on a worker peek: what an agent brings to a turn, and where it runs.
 *
 * DELIBERATELY NOT IN THE BUNDLE. This directory is the CerbUI namespace; `composer.json`'s `build-js` is the
 * subset that loads on EVERY page, and a component driving one surface doesn't belong in it -- shipping it
 * everywhere to use it here is what bloats the library. `workers/peek_edit.tpl` pulls this file and its
 * stylesheet, `resources/css/cerb-ui/agent-config.css` (same name, mirrored tree), through
 * `Devblocks.loadResources()`, and only once the Type switcher says the worker is an agent. Add it to
 * `build-js` and you've undone that.
 *
 * It owns the PARSED `agent.config_kata` tree (handed over as JSON so nothing has to parse KATA in the
 * browser), renders controls over it, and writes the whole tree back to a hidden input as JSON on every
 * change. That makes the form the single writer, which is the only way the panels and the stored config can't
 * disagree.
 *
 * KATA is emitted SERVER-side, by `kata()->emit()` -- the save path and the preview endpoint both go through
 * it. Emitting here instead would mean a second implementation of rules that are easy to get subtly wrong
 * (when `@text:` is needed, that a `#` mid-value is ordinary text, that an empty object is a childless key),
 * and a preview of something other than what gets stored.
 *
 * The schema has more in it than this form does -- `commands:`, a mount's `at:`/`mode:`/`create:`, a tool's
 * `icon:`/`labels:`/`description:` -- and all of it is carried through untouched. Visiting this tab must never
 * be a way to lose config written through the API or an automation. What is NOT recoverable is comments and
 * any key order beyond the object's own, which is why the KATA pane here is a preview and not an editor.
 *
 * One field-set builder serves both the defaults panel and every surface's override panel. Two builders would
 * be two descriptions of one form, and they would drift the first time a field was added.
 */
CerbUI.AgentConfig = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.AgentConfig._instances.get(el) || null; }

	/*
	 * el   the container holding [data-cerb-agent-defaults], [data-cerb-agent-surfaces], and the KATA pane
	 * opts {
	 *   input:      the hidden <input> the serialized model is written to as JSON (required -- it's what posts)
	 *   config:     the parsed config tree
	 *   surfaces:   { key: {label, icon, description} } from Cerb\Agent\Pane\Components::getSurfaceCatalog()
	 *   namespaces: { name: summary } from Cerb\Agent\Cli::getNamespaces()
	 *   refs:       { filesystems: {name:{id,label}}, automations: {uri:{id,label}} } -- resolved server-side so
	 *               the chooser chips can render without one lookup request per reference
	 *   isNew:      this agent has no stored config yet, so defaults may be seeded
	 *   skillsVolume: the bundled skills volume's name (FilesystemAssets::VOLUME_SKILLS), mounted by default
	 * }
	 */
	constructor(el, opts) {
		if(!el) return;

		this.el = el;
		this.opts = Object.assign({ input: null, config: {}, surfaces: {}, namespaces: {}, refs: {}, isNew: false, skillsVolume: '' }, opts || {});

		this.model = (this.opts.config && typeof this.opts.config === 'object' && !Array.isArray(this.opts.config))
			? this.opts.config : {};

		// A brand-new agent starts with the skills volume mounted: it's how the per-surface skills reach the
		// model at all, and an agent without it silently ignores every convention Cerb ships. It's an ordinary
		// mount, so it can be removed -- this only decides the starting point, and only for an agent that has
		// never been saved (`isNew`), so removing it doesn't come back on the next visit.
		if(this.opts.isNew && this.opts.skillsVolume && !this.model.mounts) {
			this.model.mounts = {};
			this.model.mounts[this.opts.skillsVolume] = {};
		}

		// The tab is rendered inside a popup that may not be visible yet; nothing here measures, so that's fine.
		this._renderDefaults();
		this._renderSurfaces();
		this._bindKataPane();
		this._sync();

		CerbUI.AgentConfig._instances.set(el, this);
	}

	// ---- model -------------------------------------------------------------------------------------------

	/* The object a scope's controls read and write: the root for defaults, `components[<key>]` for a surface. */
	_scope(surface) {
		if(!surface) return this.model;

		this.model.components = this.model.components || {};

		if(!this.model.components[surface] || typeof this.model.components[surface] !== 'object' || Array.isArray(this.model.components[surface]))
			this.model.components[surface] = {};

		return this.model.components[surface];
	}

	_isEnabled(surface) {
		const components = this.model.components;

		if(!components || typeof components !== 'object' || !Object.prototype.hasOwnProperty.call(components, surface))
			return false;

		const block = components[surface];

		// Mirrors DevblocksUiEventHandler::isBlockEnabled(): the KEY is the opt-in, the flag only turns it off.
		return !(block && typeof block === 'object' && block.disabled === true);
	}

	_setEnabled(surface, on) {
		if(on) {
			// Enabling drops the flag rather than writing `disabled@bool: no` -- absent already means enabled,
			// so a `no` would be noise in every config.
			const block = this._scope(surface);
			delete block.disabled;
		} else {
			// Disabling KEEPS the block, which is the whole reason the flag exists: a surface switched off in
			// the UI must not lose the prompt, tools, and model query authored for it.
			this._scope(surface).disabled = true;
		}

		this._sync();
	}

	/* Write one key on a scope, deleting it when the value is empty so a blank field leaves no trace. */
	_set(surface, key, value) {
		const scope = this._scope(surface);

		if(value === null || value === undefined || value === '' || (typeof value === 'object' && !Object.keys(value).length))
			delete scope[key];
		else
			scope[key] = value;

		// An emptied surface block is NOT removed: the key's presence is what enables the surface, so deleting
		// it here would silently switch the agent off the moment you cleared its last override.
		this._sync();
	}

	// ---- serialization -----------------------------------------------------------------------------------

	/*
	 * The model as a plain object, in the schema's own key order so a diff between two saves is legible.
	 *
	 * This is what POSTS. KATA is emitted SERVER-side by `kata()->emit()` -- the one canonical emitter, which
	 * already knows that a single-line value needs no `@text:` while an empty one does, that a `#` mid-value is
	 * ordinary text (comments are whole lines only), and that an empty object is a childless key. A second
	 * emitter in here would be a second set of those rules to keep right.
	 *
	 * Anything the form doesn't render rides along untouched -- `commands:`, a mount's `at:`/`mode:`, a tool's
	 * `icon:`/`labels:` -- so visiting this tab can't lose config written through the API or an automation.
	 */
	serializeModel() {
		const order = ['system_prompt', 'models_query', 'automation', 'mounts', 'terminal', 'tools', 'commands'];

		const scope = (src) => {
			const out = {};

			order.forEach((key) => {
				if(this._isSet(src[key])) out[key] = src[key];
			});

			Object.keys(src).forEach((key) => {
				if(key !== 'components' && order.indexOf(key) < 0 && this._isSet(src[key])) out[key] = src[key];
			});

			return out;
		};

		const model = scope(this.model);
		const components = this.model.components || {};

		if(Object.keys(components).length) {
			model.components = {};

			Object.keys(components).forEach((surface) => {
				const block = components[surface] || {};
				const out = {};

				// Leads, because it's the one key that changes what the whole block MEANS.
				if(block.disabled === true) out.disabled = true;

				model.components[surface] = Object.assign(out, scope(block));
			});
		}

		return model;
	}

	/* Empty string, empty object, null -- none of which should reach the emitter as a key. */
	_isSet(v) {
		if(v === null || v === undefined || v === '') return false;
		if(typeof v === 'object') return !!Object.keys(v).length;
		return true;
	}

	_sync() {
		const model = this.serializeModel();

		// JSON on the wire; the server emits the KATA it stores.
		if(this.opts.input) this.opts.input.value = JSON.stringify(model);

		Object.keys(this._pillHosts || {}).forEach((surface) => this._renderPills(surface));

		this._syncPreview();
	}

	/*
	 * Repaint the KATA pane, but only while it's open.
	 *
	 * The text comes from the server, because the server is what emits it -- a local approximation would be a
	 * preview of something other than what gets saved, which is worse than no preview.
	 *
	 * The editor instance is held rather than looked up: `KataEditor._instances` is keyed on the wrapper the
	 * editor builds, not the textarea it was given, so `KataEditor.from(textarea)` returns nothing. That
	 * silently fell through to writing `textarea.value`, which the projection ignores -- the pane showed
	 * whatever it was built with and a stale config could be copied out of it.
	 */
	_syncPreview() {
		if(!this._kataEditor) return;

		const pane = this.el.querySelector('[data-cerb-agent-kata-preview]');
		if(pane && pane.hidden) return;

		clearTimeout(this._previewTimer);

		this._previewTimer = setTimeout(() => this._fetchPreview(), 300);
	}

	_fetchPreview() {
		if(!this._kataEditor || typeof genericAjaxPost !== 'function') return;

		// Only the newest request may paint: a slow one landing after a fast one would show older text.
		const seq = (this._previewSeq = (this._previewSeq || 0) + 1);

		genericAjaxPost({
			c: 'ui',
			a: 'agentConfigKata',
			config_json: this.opts.input ? this.opts.input.value : JSON.stringify(this.serializeModel())
		}, null, '', (json) => {
			if(seq !== this._previewSeq || !this._kataEditor) return;
			if(json && typeof json.kata === 'string') this._kataEditor.setValue(json.kata);
		});
	}

	// ---- rendering ---------------------------------------------------------------------------------------

	_renderDefaults() {
		const host = this.el.querySelector('[data-cerb-agent-defaults]');
		if(host) host.appendChild(this._buildScopeFields(''));
	}

	_renderSurfaces() {
		const host = this.el.querySelector('[data-cerb-agent-surfaces]');
		if(!host) return;

		const surfaces = this.opts.surfaces || {};

		Object.keys(surfaces).forEach((key) => {
			host.appendChild(this._buildSurfaceRow(key, surfaces[key] || {}));
		});
	}

	_buildSurfaceRow(surface, meta) {
		const row = document.createElement('div');
		row.className = 'cerb-agent-config--surface';
		row.setAttribute('data-cerb-agent-surface', surface);

		const head = document.createElement('div');
		head.className = 'cerb-ui-header cerb-ui-header--tight cerb-ui-header--center';

		const titles = document.createElement('div');
		titles.className = 'cerb-agent-config--surface-titles';

		// The icon is its OWN grid cell rather than living inside the title, so the pills below can start at the
		// title's text. Padding them by a guessed icon width would drift -- `.cerb-icons` sizes off font-size.
		const glyph = document.createElement('span');
		glyph.className = 'cerb-icons cerb-icon-' + this._safeIcon(meta.icon);
		titles.appendChild(glyph);

		// Name and description share ONE line: seven surfaces stacked is a lot of vertical scroll, and the
		// description is orienting text rather than something you read every time. It truncates rather than
		// wrapping (which would save nothing), with the full text on hover.
		const line = document.createElement('div');
		line.className = 'cerb-agent-config--surface-head';

		const title = document.createElement('div');
		title.className = 'cerb-ui-header--title-sm';
		title.textContent = meta.label || surface;
		line.appendChild(title);

		if(meta.description) {
			const sub = document.createElement('div');
			sub.className = 'cerb-ui-header--subtitle cerb-agent-config--surface-desc';
			sub.textContent = meta.description;
			sub.title = meta.description;
			line.appendChild(sub);
		}

		titles.appendChild(line);

		// What this surface changes, without having to expand it. A row with no pills runs the defaults.
		const pills = document.createElement('div');
		pills.className = 'cerb-agent-config--surface-pills';
		titles.appendChild(pills);

		head.appendChild(titles);

		const right = document.createElement('div');
		right.className = 'cerb-ui-header--right cerb-u-flex cerb-u-items-center cerb-u-gap-2';

		const customize = document.createElement('button');
		customize.type = 'button';
		customize.className = 'cerb-ui-button cerb-ui-button--subtle';
		customize.innerHTML = '<span class="cerb-icons cerb-icon-chevron-down"></span>';
		customize.title = 'Customize what this agent brings here';
		right.appendChild(customize);

		const toggleLabel = document.createElement('label');
		toggleLabel.className = 'cerb-ui-toggle';
		toggleLabel.innerHTML = '<input type="checkbox"><span class="cerb-ui-toggle--slider"></span>';
		right.appendChild(toggleLabel);

		head.appendChild(right);
		row.appendChild(head);

		const body = document.createElement('div');
		body.className = 'cerb-agent-config--surface-body';
		body.hidden = true;
		body.appendChild(this._buildScopeFields(surface));
		row.appendChild(body);

		const input = toggleLabel.querySelector('input');
		input.checked = this._isEnabled(surface);

		// setValue() doesn't fire onChange, so the initial state above is set directly rather than through it.
		if(window.CerbUI && CerbUI.Toggle) {
			new CerbUI.Toggle(input, {
				onChange: (checked) => {
					this._setEnabled(surface, checked);
					row.classList.toggle('cerb-agent-config--surface-off', !checked);
				}
			});
		}

		row.classList.toggle('cerb-agent-config--surface-off', !input.checked);

		// Re-rendered on every change rather than patched, since the summary is derived from the whole block.
		this._pillHosts = this._pillHosts || {};
		this._pillHosts[surface] = pills;
		this._renderPills(surface);

		const toggleBody = () => {
			body.hidden = !body.hidden;
			customize.innerHTML = '<span class="cerb-icons cerb-icon-chevron-' + (body.hidden ? 'down' : 'up') + '"></span>';
		};

		customize.addEventListener('click', toggleBody);

		// The whole name/description/pills column is the disclosure target -- a chevron alone is a small thing
		// to hit, and clicking a row's name to open it is what the shape already suggests.
		titles.addEventListener('click', toggleBody);

		return row;
	}

	/*
	 * The field set, identical for the defaults scope and every surface override. `surface` is '' for defaults.
	 *
	 * Placeholders differ by scope on purpose: an override's blank state means "inherit", and saying so in the
	 * field is the cheapest way to answer "what happens if I leave this empty?".
	 */
	_buildScopeFields(surface) {
		const form = document.createElement('div');
		form.className = 'cerb-ui-form';
		form.setAttribute('data-cerb-agent-scope', surface);

		const scope = surface ? ((this.model.components || {})[surface] || {}) : this.model;
		const inherits = !!surface;

		form.appendChild(this._fieldSystemPrompt(surface, scope, inherits));
		form.appendChild(this._fieldModelsQuery(surface, scope, inherits));
		form.appendChild(this._fieldMounts(surface, scope, inherits));
		form.appendChild(this._fieldTools(surface, scope, inherits));
		form.appendChild(this._fieldTerminal(surface, scope, inherits));
		form.appendChild(this._fieldAutomation(surface, scope, inherits));

		return form;
	}

	_fieldMounts(surface, scope, inherits) {
		const field = this._field('Filesystems', inherits
			? 'Mounted here in addition to the agent\'s own.'
			: 'Volumes the agent can browse and read, everywhere it runs.');

		const host = document.createElement('div');
		host.className = 'cerb-ui-record-chooser';

		const refs = (this.opts.refs || {}).filesystems || {};

		Object.keys(scope.mounts || {}).forEach((key) => {
			const name = String(key).split('@')[0];
			const ref = refs[name];

			// A name that resolves to nothing still shows, as itself -- the honest rendering of a volume
			// someone deleted, and better than silently dropping it on the next save.
			host.appendChild(this._chooserSeed(ref ? ref.id : 0, ref ? ref.label : name));
		});

		field.insertBefore(host, field.querySelector('.cerb-ui-form--hint'));

		this._chooser(host, { context: 'cerb.contexts.agent.filesystem', multiple: true, emptyIcon: 'folder' }, (items) => {
			const mounts = {};

			// Keyed by NAME, not id: a name is what survives an export, and it's what reads correctly in KATA.
			// Any per-mount options already authored (`at:`, `mode:`) are carried over rather than reset.
			// Live scope, not the render-time snapshot: for a surface with no block yet, `scope` is a throwaway
			// object, so reading per-mount options off it would lose them.
			const was = this._scope(surface).mounts || {};

			items.forEach((item) => {
				const name = String(item.label || '');
				if(name) mounts[name] = was[name] || {};
			});

			this._set(surface, 'mounts', mounts);
		});

		return field;
	}

	_fieldTools(surface, scope, inherits) {
		// "Custom" because every surface already contributes its own built-in tools -- reading the editor,
		// writing a field, running a search. These are the ones you add.
		const field = this._field('Custom Tools', inherits
			? 'Available here in addition to the agent\'s own.'
			: 'Tool automations the agent can call, on top of what each surface already gives it. Each one describes itself to the model.');

		const host = document.createElement('div');
		host.className = 'cerb-ui-record-chooser';

		const refs = (this.opts.refs || {}).automations || {};

		Object.keys(scope.tools || {}).forEach((key) => {
			const uri = String(((scope.tools || {})[key] || {}).uri || '');
			const ref = refs[uri];

			host.appendChild(this._chooserSeed(ref ? ref.id : 0, ref ? ref.label : (uri || key)));
		});

		field.insertBefore(host, field.querySelector('.cerb-ui-form--hint'));

		this._chooser(host, {
			context: 'cerb.contexts.automation',
			multiple: true,
			emptyIcon: 'zap',
			query: 'trigger:cerb.trigger.llm.tool'
		}, (items) => {
			const tools = {};
			const was = this._scope(surface).tools || {};

			items.forEach((item) => {
				const name = String(item.label || '');
				if(!name) return;

				const uri = 'cerb:automation:' + name;

				// The entry KEY is the tool name the model calls, so it can't be the dotted automation name --
				// pick the last segment. An existing entry keeps whatever key and overrides it already had, so
				// a renamed tool or a hand-written description isn't reset by touching this chooser.
				let key = Object.keys(was).find((k) => String((was[k] || {}).uri || '') === uri);

				if(!key) key = 'automation/' + (name.split('.').pop() || name);

				tools[key] = Object.assign({}, was[key] || {}, { uri: uri });
			});

			this._set(surface, 'tools', tools);
		});

		return field;
	}

	_fieldAutomation(surface, scope, inherits) {
		const field = this._field('Interaction', inherits
			? 'Runs here instead of the agent\'s own.'
			: 'The chat this agent runs. Leave empty for the one Cerb ships.');

		const host = document.createElement('div');
		host.className = 'cerb-ui-record-chooser';

		const uri = String(scope.automation || '');

		if(uri) {
			const ref = ((this.opts.refs || {}).automations || {})[uri];
			host.appendChild(this._chooserSeed(ref ? ref.id : 0, ref ? ref.label : uri));
		}

		field.insertBefore(host, field.querySelector('.cerb-ui-form--hint'));

		this._chooser(host, {
			context: 'cerb.contexts.automation',
			emptyIcon: 'bot',
			query: 'trigger:cerb.trigger.interaction.worker.agent'
		}, (item) => {
			this._set(surface, 'automation', item ? ('cerb:automation:' + item.label) : '');
		});

		return field;
	}

	/*
	 * A pill per key this surface overrides, so "runs the defaults" and "changes three things" are one glance
	 * apart. Named after the fields, not the KATA keys -- this is the row a person reads.
	 */
	_renderPills(surface) {
		const host = (this._pillHosts || {})[surface];
		if(!host) return;

		const block = ((this.model.components || {})[surface]) || {};

		const labels = {
			system_prompt: 'Instructions',
			models_query: 'Models',
			mounts: 'Filesystems',
			tools: 'Custom Tools',
			terminal: 'Terminal',
			commands: 'Commands',
			automation: 'Interaction'
		};

		host.replaceChildren();

		Object.keys(labels).forEach((key) => {
			const v = block[key];

			// An empty block is not an override -- it's what a cleared field leaves behind for one repaint.
			if(v === undefined || v === null || v === '' || (typeof v === 'object' && !Object.keys(v).length))
				return;

			const pill = document.createElement('span');
			pill.className = 'cerb-ui-pill';
			pill.textContent = labels[key];
			host.appendChild(pill);
		});
	}

	_chooserSeed(id, label) {
		const li = document.createElement('li');
		li.setAttribute('data-context-id', String(id || 0));
		li.setAttribute('data-label', String(label || ''));
		return li;
	}

	/*
	 * A RecordChooser wired to re-serialize on every change.
	 *
	 * `onSelect` fires on ADD ONLY -- removing a chip calls the instance's private `_removeValue()` and
	 * notifies nobody, and the clear button stops propagation so delegation won't see it either. So the
	 * instance method is wrapped: the original runs, then we read the new value. Without this, clearing a
	 * filesystem looks like it worked and saves the old set.
	 */
	_chooser(host, opts, onChange) {
		if(!(window.CerbUI && CerbUI.RecordChooser)) return null;

		const chooser = new CerbUI.RecordChooser(host, Object.assign({ searchPlaceholder: 'Search…' }, opts, {
			onSelect: () => onChange(chooser.getValue())
		}));

		const removeValue = chooser._removeValue;

		if(typeof removeValue === 'function') {
			chooser._removeValue = function() {
				removeValue.apply(this, arguments);
				onChange(chooser.getValue());
			};
		}

		return chooser;
	}

	_fieldSystemPrompt(surface, scope, inherits) {
		// "Custom Instructions", not "System prompt": the system prompt is the whole concatenation -- the
		// surface's own role, the tool inventory, pointers at the mounted volumes -- and this is only the part
		// the author adds. Naming it after the whole thing invites rewriting what Cerb already said.
		const field = this._field('Custom Instructions', inherits
			? 'Optional. Added after the agent\'s own instructions when it runs here.'
			: 'Optional. Cerb already tells the agent what each surface is and what its tools do; filesystems and tools add their own. This is only what you want to add on top.');

		const ta = document.createElement('textarea');
		ta.rows = inherits ? 4 : 6;
		ta.spellcheck = false;
		ta.value = scope.system_prompt || '';
		ta.placeholder = inherits ? 'Nothing extra here.' : 'Nothing extra.';

		ta.addEventListener('input', () => this._set(surface, 'system_prompt', ta.value));

		field.insertBefore(ta, field.querySelector('.cerb-ui-form--hint'));

		return field;
	}

	_fieldModelsQuery(surface, scope, inherits) {
		const field = this._field('Models', inherits
			? 'Replaces the agent\'s query here. Leave empty to use it.'
			: 'Which agent models this agent may use. Leave empty for any available model.');

		const ta = document.createElement('textarea');
		ta.rows = 1;
		ta.spellcheck = false;
		ta.value = scope.models_query || '';
		ta.placeholder = inherits ? '(inherit)' : 'rating.privacy:>=3';

		field.insertBefore(ta, field.querySelector('.cerb-ui-form--hint'));

		const commit = () => this._set(surface, 'models_query', ta.value.trim());

		if(window.CerbUI && CerbUI.SearchQuery) {
			// The same query vocabulary `llm.router: models_query:` autocompletes, so a query written in one
			// place reads the same in the other.
			const ctx = 'cerb.contexts.agent.model';

			new CerbUI.SearchQuery(ta, {
				context: ctx,
				onAutocomplete: CerbUI.SearchQuery.queryFieldSource(ctx),
				onSearch: commit
			});
		}

		ta.addEventListener('input', commit);
		ta.addEventListener('blur', commit);

		return field;
	}

	_fieldTerminal(surface, scope, inherits) {
		const namespaces = this.opts.namespaces || {};
		const names = Object.keys(namespaces);

		const field = this._field('Terminal', inherits
			? 'What the `cerb` command line can do here. Starts from the agent\'s own; unchecking one switches it off for this surface only.'
			: 'What the `cerb` command line can report about this install. Read-only reflection -- an agent looks a name up instead of recalling one that may not exist here.');

		if(!names.length) {
			field.querySelector('.cerb-ui-form--hint').textContent = 'No terminal commands are registered.';
			return field;
		}

		const list = document.createElement('div');
		list.className = 'cerb-u-flex cerb-u-items-center cerb-u-gap-3 cerb-u-flex-wrap';

		names.forEach((name) => {
			const wrap = document.createElement('label');
			wrap.className = 'cerb-u-flex cerb-u-items-center cerb-u-gap-1';
			wrap.title = namespaces[name] || '';

			const cb = document.createElement('input');
			cb.type = 'checkbox';
			cb.checked = this._terminalState(surface, name);

			cb.addEventListener('change', () => this._setTerminal(surface, name, cb.checked));

			wrap.appendChild(cb);
			wrap.appendChild(document.createTextNode(name));
			list.appendChild(wrap);
		});

		field.insertBefore(list, field.querySelector('.cerb-ui-form--hint'));

		return field;
	}

	/* Is `name` on for this scope once the agent's defaults are taken into account? */
	_terminalState(surface, name) {
		const own = ((this._scope(surface).terminal || {}).cerb || {});

		if(Object.prototype.hasOwnProperty.call(own, name))
			return own[name] !== false;

		if(!surface) return false;

		return Object.prototype.hasOwnProperty.call(((this.model.terminal || {}).cerb || {}), name);
	}

	/*
	 * Unlike tools and filesystems, a surface merging its terminal block can only ADD -- the CLI gates on a
	 * namespace KEY being present, not on its value. So switching one off HERE writes an explicit `false`
	 * rather than deleting the key, which is what `Config::_pruneTerminal()` reads. Deleting would mean
	 * "inherit", i.e. leave it on.
	 *
	 * In the defaults scope there's nothing above to inherit from, so off is simply absent.
	 */
	_setTerminal(surface, name, on) {
		const scope = this._scope(surface);

		const terminal = (scope.terminal && typeof scope.terminal === 'object') ? scope.terminal : {};
		const cerb = (terminal.cerb && typeof terminal.cerb === 'object') ? terminal.cerb : {};

		const inherited = surface && Object.prototype.hasOwnProperty.call(((this.model.terminal || {}).cerb || {}), name);

		if(on) {
			cerb[name] = {};

			// Back to plain inheritance rather than a redundant re-statement of the default.
			if(inherited) delete cerb[name];
		} else if(inherited) {
			cerb[name] = false;
		} else {
			delete cerb[name];
		}

		if(Object.keys(cerb).length) {
			terminal.cerb = cerb;
			scope.terminal = terminal;
		} else {
			// An empty `cerb:` block is not the same as none -- it would still advertise the CLI with nothing
			// under it -- so the whole block goes when nothing is left to say.
			delete scope.terminal;
		}

		this._sync();
	}

	// ---- KATA pane ---------------------------------------------------------------------------------------

	_bindKataPane() {
		const toggle = this.el.querySelector('[data-cerb-agent-kata-toggle]');
		const pane = this.el.querySelector('[data-cerb-agent-kata-preview]');
		const ta = this.el.querySelector('[data-cerb-agent-kata-editor]');

		if(!toggle || !pane) return;

		toggle.addEventListener('click', () => {
			pane.hidden = !pane.hidden;
			toggle.innerHTML = '<span class="cerb-icons cerb-icon-console"></span> ' + (pane.hidden ? 'Show' : 'Hide');

			// Built lazily: a KataEditor measures on construction, and it can't do that inside a hidden pane.
			if(!pane.hidden && ta && !this._kataEditor && window.CerbUI && CerbUI.KataEditor)
				this._kataEditor = new CerbUI.KataEditor(ta, { readOnly: true, minLines: 6, maxLines: 24 });

			// Every open repaints: edits made while it was closed are skipped by _syncPreview().
			if(!pane.hidden) {
				clearTimeout(this._previewTimer);
				this._fetchPreview();
			}
		});
	}

	// ---- helpers -----------------------------------------------------------------------------------------

	/* A `cerb-ui-form--field` with a label and a hint; callers insert their control before the hint. */
	_field(label, hint) {
		const field = document.createElement('div');
		field.className = 'cerb-ui-form--field';

		const lbl = document.createElement('label');
		lbl.className = 'cerb-ui-form--label';
		lbl.textContent = label;
		field.appendChild(lbl);

		const h = document.createElement('div');
		h.className = 'cerb-ui-form--hint';
		h.textContent = hint || '';
		field.appendChild(h);

		return field;
	}

	/* An icon name reaches the DOM as a class, so anything but the set's own character range is refused. */
	_safeIcon(name) {
		return /^[a-z0-9-]+$/.test(String(name || '')) ? name : 'bot';
	}

	destroy() {
		clearTimeout(this._previewTimer);
		if(this._kataEditor && typeof this._kataEditor.destroy === 'function') this._kataEditor.destroy();
		CerbUI.AgentConfig._instances.delete(this.el);
	}
};
