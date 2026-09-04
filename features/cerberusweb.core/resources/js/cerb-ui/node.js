/*
 * CerbUI.Node — one node instance on a CerbUI.NodeCanvas, built from a node-type schema.
 *
 * A node is a positioned <div> in the canvas viewport (viewport-space coords = logical + OFFSET) with: a colored
 * header (accent + icon + label), a main inlet handle in the top-left corner and a main outlet handle in the
 * bottom-right corner, and a content area that later holds inline form fields, parameter inlets, property-outlet
 * sockets, and dynamic branch outlets. The main outlet connects only to another node's main inlet; the corner-to-
 * corner chain of those is the sequential execution order. Dragging the header repositions the node (firing onMove
 * so connected CerbUI.NodeEdge instances recompute). Pressing an outlet starts a free-edge connection drag.
 *
 * Schema-driven: the shape comes from a plain, serializable node-type definition (mirrors js-flow's registerBlockType
 * config), e.g. { id, label, icon, headerColor, category, start, terminal, … }. `start` nodes omit the inlet;
 * `terminal` nodes omit the outlet.
 *
 *   const node = new CerbUI.Node(canvas, { schema, id, position:{x,y}, data:{inputs:{}},
 *       onMove, onSelect, onConnectStart });
 *   node.setPosition(x,y);  node.getHandleEl('_in');  node.bringToFront();  node.toJSON();  node.destroy();
 *
 * [TODO Phase 2] data-typed param inlets / property-outlet sockets / branch outlets + the reusable "+ Add" control.
 * [TODO Phase 3] inline form fields (cerb-ui-form / CerbUI.Toggle / CerbUI.SelectMenu) bound to data.inputs.
 */
CerbUI.Node = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Node._instances.get(el); }
	static _seq = 0;
	static _zTop = 10;      // running z-index so the last-touched node floats above the rest

	static _DEFAULTS = {
		schema: null,
		id: null,
		position: { x: 0, y: 0 },
		data: null,
		readonly: false,       // read-only viewer: no ⋮ menu, no connect-start on outlets, no dblclick-to-open.
		                       //   Drag (with onMove so edges follow) still works so a viewer can declutter.
		nested: false,         // rendered INSIDE a parent node's container socket (not on the canvas): no flow
		                       //   handles (_in/_next), no canvas drag — its order in the container is the sequence.
		inletCorner: false,    // pin this node's main inlet to the top-left corner (even with centered ports) — for
		                       //   a node fed from a branch outlet that sits off to the side.
		onMove: null,          // (node) => {}  while/after dragging — edges re-route
		onSelect: null,        // (node, event) => {}
		onConnectStart: null,  // (node, handleId, event) => {}  outlet pressed — begin a connection drag
		onRemove: null,        // (node) => {}  the header × was clicked
		onRemoveHandle: null,  // (node, handleId) => {}  a dynamic row's handle was removed (editor drops its edges)
		onSchemaChange: null,  // (node) => {}  a dynamic row was added/removed
		onOpen: null,          // (node) => {}  double-clicked (e.g. open an expression node's builder)
		onPartOpen: null,      // (part, node, event) => {}  clicked a branch/preview row
	};

	// Build a palette tile element from a {icon, color, kind, name} dataset (mirrors CerbUI.Sidebar's tiles).
	static buildTile(d) {
		const tile = document.createElement('div');
		tile.className = 'cerb-ui-tile';
		const ico = document.createElement('span');
		ico.className = 'cerb-ui-tile--icon';
		if(d.color) ico.style.background = d.color;
		if(d.icon) {
			const g = document.createElement('span');
			g.className = 'cerb-icons cerb-icon-' + d.icon;
			ico.appendChild(g);
		}
		tile.appendChild(ico);
		const text = document.createElement('div');
		text.className = 'cerb-ui-tile--text';
		if(d.kind) {
			const k = document.createElement('div');
			k.className = 'cerb-ui-tile--kind';
			k.textContent = d.kind;
			text.appendChild(k);
		}
		const n = document.createElement('div');
		n.className = 'cerb-ui-tile--name';
		n.textContent = d.name || '';
		text.appendChild(n);
		tile.appendChild(text);
		return tile;
	}

	constructor(canvas, opts = {}) {
		this.canvas = canvas;
		this.opts = Object.assign({}, CerbUI.Node._DEFAULTS, opts);
		this.schema = this.opts.schema || {};
		this.id = this.opts.id || ('node-' + (++CerbUI.Node._seq));
		this.position = Object.assign({ x: 0, y: 0 }, this.opts.position);
		this.data = this.opts.data || { inputs: {} };
		if(!this.data.inputs) this.data.inputs = {};   // field controls bind their values in here
		this.handles = new Map();   // handleId -> { el, kind:'inlet'|'outlet', dataType }
		this._dynSeq = 0;

		this.el = document.createElement('div');
		this.el.classList.add('cerb-ui-node');
		this.el.dataset.nodeId = this.id;
		if(this.schema.id) this.el.dataset.nodeType = this.schema.id;
		if(this.schema.start) this.el.classList.add('cerb-ui-node--start');
		if(this.schema.terminal) this.el.classList.add('cerb-ui-node--terminal');
		if(this.opts.readonly) this.el.classList.add('cerb-ui-node--readonly');
		if(this.opts.nested) this.el.classList.add('cerb-ui-node--nested');
		if(this.opts.inletCorner) this.el.classList.add('cerb-ui-node--inlet-corner');
		// Opt-in: single flow ports centered (top-middle inlet, bottom-middle outlet) for clean linear stacks.
		if(this.schema.ports === 'center') this.el.classList.add('cerb-ui-node--ports-center');
		CerbUI.Node._instances.set(this.el, this);

		// Comment nodes float (no header/handles). Expression nodes are a collapsed (f)+name card with one value
		// outlet — double-click opens the nested expression builder. Global-variable nodes are a compact chip+name
		// reference with one value outlet typed to the variable. Everything else is the standard node.
		if(this.schema.comment) { this._renderComment(); this._bindCommentDrag(); }
		else if(this.schema.expression) { this._renderExpression(); this._bindExpressionEvents(); }
		else if(this.schema.globalVar) { this._renderGlobalVar(); this._bindCardEvents(); }
		else { this._render(); if(!this.opts.nested) this._bindDrag(); }
		this.setPosition(this.position.x, this.position.y);
		this.bringToFront();
	}

	_render() {
		const O = CerbUI.NodeCanvas.OFFSET;

		// Header — doubles as the drag handle. The sidebar cerb-ui-tile look: a colored icon square + kind/name on
		// a neutral header (tells you category + name at a glance, not just an accent color).
		this.header = document.createElement('div');
		this.header.className = 'cerb-ui-node--header';
		const labelText = this.data.label || this.schema.label || this.schema.id || 'Node';
		this.header.appendChild(CerbUI.Node.buildTile({
			icon: this.schema.icon, color: this.schema.headerColor,
			kind: this.schema.category, name: labelText,
		}));

		// ⋮ options menu (Delete, + Edit for editable nodes) — shown on hover/selection. Omitted in read-only mode.
		if(!this.opts.readonly) this.header.appendChild(this._nodeMenuBtn());

		this.el.appendChild(this.header);

		// A node with no body content (a "leaf" — e.g. a contained automation, or a bare spine step) renders as a
		// single panel: skip the empty body div and give the header full radius (no dangling bottom border).
		const s = this.schema;
		const hasBody = !!(s.container || (s.fields || []).length || (s.valueIn || []).length
			|| (s.inlets || []).length || (s.outputs || []).length || s.canAddOutputs
			|| (s.branches || []).length || (s.previewRows || []).length || s.canAddOutcomes || this.data.description);
		if(!hasBody) {
			this.el.classList.add('cerb-ui-node--leaf');
			this._buildHandles();
			void O;
			return;
		}

		// Body — built from the schema to match the Kataflow node anatomy.
		this.body = document.createElement('div');
		this.body.className = 'cerb-ui-node--body';
		this.el.appendChild(this.body);

		// A per-node description line (e.g. an automation event's record description), muted, under the header.
		if(this.data.description) {
			const desc = document.createElement('div');
			desc.className = 'cerb-ui-node--desc';
			desc.textContent = this.data.description;
			this.body.appendChild(desc);
		}

		// Data-flow value inputs (expression builder): a labeled left-edge inlet you wire a value into.
		(this.schema.valueIn || []).forEach((v) => this.body.appendChild(this._renderValueIn(v)));

		// Inline form fields: a label + control (text/number/select/toggle), each with a left-edge param inlet
		// you can wire a value into (overriding the literal).
		(this.schema.fields || []).forEach((f) => this.body.appendChild(this._renderField(f)));

		// Property INLET sockets — a dashed "variable" pill, an arrow, then the UPPERCASE name ([pill] → NAME).
		// (Variable assignment is rigged in Phase 5; the pill is a visual socket for now.)
		if((this.schema.inlets || []).length) {
			const sec = this._section();
			this.schema.inlets.forEach((p) => sec.appendChild(this._renderProperty(p, 'inlet')));
		}
		// Property OUTLET sockets — UPPERCASE name, an arrow, then the pill (NAME → [pill]).
		if((this.schema.outputs || []).length || this.schema.canAddOutputs) {
			const sec = this._section();
			this._outputsSec = sec;
			if(this.schema.canAddOutputs) sec.appendChild(this._sectionLabel('Outputs'));
			(this.schema.outputs || []).forEach((o) => sec.appendChild(this._renderProperty(o, 'outlet')));
			if(this.schema.canAddOutputs) {
				this._addOutputBtn = this._addButton(this.schema.addOutputLabel || '+ Add output', () => this.addOutputRow());
				sec.appendChild(this._addOutputBtn);
				(this.data.outputs || []).forEach((o) => this.addOutputRow(o));   // restore dynamic rows on load
			}
		}

		// Flow BRANCH outlets — a right-aligned name + an outlet handle on the right edge (e.g. success / error).
		if((this.schema.branches || []).length || this.schema.canAddOutcomes) {
			const sec = this._section('cerb-ui-node--branches');
			this._branchesSec = sec;
			(this.schema.branches || []).forEach((b) => sec.appendChild(this._renderBranch(b)));
			if(this.schema.canAddOutcomes) {
				this._addOutcomeBtn = this._addButton(this.schema.addOutcomeLabel || '+ Add outcome', () => this.addOutcomeRow());
				sec.appendChild(this._addOutcomeBtn);
				(this.data.outcomes || []).forEach((o) => this.addOutcomeRow(o));   // restore dynamic rows on load
			}
		}

		if((this.schema.previewRows || []).length) {
			const sec = this._section('cerb-ui-node--preview');
			this.schema.previewRows.forEach((row) => sec.appendChild(this._renderPreviewRow(row)));
		}

		// Container socket: a parent that holds an ordered list of nested child nodes (light/serial children) in
		// place of branch-outlet fan-out. Children are appended via appendChildNode().
		if(this.schema.container) {
			this.container = document.createElement('div');
			this.container.className = 'cerb-ui-node--container';
			this.container.addEventListener('wheel', (e) => {
				// Let a scrollable container scroll natively instead of the canvas zooming (NodeCanvas listens on
				// an ancestor and preventDefaults to zoom — stopping propagation here keeps that from firing).
				if(this.container.scrollHeight > this.container.clientHeight) e.stopPropagation();
			}, { passive: true });
			this.body.appendChild(this.container);
		}

		this._buildHandles();
		void O;
	}

	// Add flow handles: main inlet (top) unless a start node, main outlet (bottom) unless terminal. Data nodes
	// carry a single value outlet instead; nested nodes carry none (their order in a container is the sequence).
	_buildHandles() {
		if(this.schema.dataNode) {
			if(this.schema.valueOut) this._addHandle('out:value', 'outlet', this.schema.outType || 'any', { posClass: 'cerb-ui-node--value-out' });
		} else if(!this.opts.nested) {
			if(!this.schema.start) this._addHandle('_in', 'inlet', '_flow', { posClass: 'cerb-ui-node--inlet' });
			if(!this.schema.terminal) this._addHandle('_next', 'outlet', '_flow', { posClass: 'cerb-ui-node--outlet' });
		}
	}

	// A data-flow value input row: [inlet] label (no control). Used by expression-builder scripting nodes.
	_renderValueIn(v) {
		const row = document.createElement('div');
		row.className = 'cerb-ui-node--valuein';
		this._addHandle('in:' + v.name, 'inlet', v.dataType || 'any', { parent: row, posClass: 'cerb-ui-node--field-handle' });
		const lbl = document.createElement('span');
		lbl.className = 'cerb-ui-node--io-label';
		lbl.textContent = v.label || v.name;
		row.appendChild(lbl);
		return row;
	}

	// A divider'd body section (appended to the body); optional extra class.
	_section(extraClass) {
		const s = document.createElement('div');
		s.className = 'cerb-ui-node--section' + (extraClass ? ' ' + extraClass : '');
		this.body.appendChild(s);
		return s;
	}
	_sectionLabel(text) {
		const l = document.createElement('div');
		l.className = 'cerb-ui-node--section-label';
		l.textContent = text;
		return l;
	}

	// One inline form field: [param inlet] | label / control.
	_renderField(f) {
		const row = document.createElement('div');
		row.className = 'cerb-ui-node--field';
		row.dataset.field = f.name;
		this._addHandle('param:' + f.name, 'inlet', f.dataType || 'any', { parent: row, posClass: 'cerb-ui-node--field-handle' });
		const main = document.createElement('div');
		main.className = 'cerb-ui-node--field-main';
		const lbl = document.createElement('div');
		lbl.className = 'cerb-ui-node--field-label';
		lbl.textContent = f.label || f.name;
		main.appendChild(lbl);
		main.appendChild(this._control(f));
		row.appendChild(main);
		return row;
	}

	// Render a field's control (Cerb UI building blocks where they exist), bound to data.inputs[name]: the
	// initial value comes from data.inputs (else the schema default), and edits write straight back.
	_control(f) {
		const kind = f.control || 'text';
		const saved = this.data.inputs[f.name];
		const cur = (saved !== undefined) ? saved : f.value;
		if(kind === 'toggle') {
			const wrap = document.createElement('label');
			wrap.className = 'cerb-ui-toggle';
			const input = document.createElement('input');
			input.type = 'checkbox';
			if(cur) input.checked = true;
			const slider = document.createElement('span');
			slider.className = 'cerb-ui-toggle--slider';
			wrap.appendChild(input);
			wrap.appendChild(slider);
			if(window.CerbUI && CerbUI.Toggle) new CerbUI.Toggle(wrap, { onChange: (checked) => { this.data.inputs[f.name] = checked; } });
			else input.addEventListener('change', () => { this.data.inputs[f.name] = input.checked; });
			return wrap;
		}
		if(kind === 'select') {
			const sel = document.createElement('select');
			sel.className = 'cerb-ui-node--field-input';
			const addOpt = (o, parent) => {
				const opt = document.createElement('option');
				opt.value = o;
				opt.textContent = o;
				if(o === cur) opt.selected = true;
				parent.appendChild(opt);
			};
			if(f.groups) {   // optgroups, e.g. Math's Binary / Unary operations
				f.groups.forEach((g) => {
					const og = document.createElement('optgroup');
					og.label = g.label;
					(g.options || []).forEach((o) => addOpt(o, og));
					sel.appendChild(og);
				});
			} else {
				(f.options || []).forEach((o) => addOpt(o, sel));
			}
			sel.addEventListener('change', () => { this.data.inputs[f.name] = sel.value; });
			return sel;
		}
		const input = document.createElement('input');
		input.className = 'cerb-ui-node--field-input';
		input.type = (kind === 'number') ? 'number' : 'text';
		if(cur != null) input.value = cur;
		if(f.placeholder) input.placeholder = f.placeholder;
		input.addEventListener('input', () => { this.data.inputs[f.name] = input.value; });
		return input;
	}

	// A property socket row: inlet = [pill] → NAME ; outlet = NAME → [pill].
	_renderProperty(p, kind) {
		const row = document.createElement('div');
		row.className = 'cerb-ui-node--prop cerb-ui-node--prop-' + kind;
		const pill = document.createElement('div');
		pill.className = 'cerb-ui-node--socket';
		const ph = document.createElement('span');
		ph.className = 'cerb-ui-node--socket-placeholder';
		ph.textContent = 'variable';
		pill.appendChild(ph);
		pill.addEventListener('mousedown', (e) => e.stopPropagation());   // don't drag the node from the socket
		const arrow = document.createElement('span');
		arrow.className = 'cerb-ui-node--prop-arrow';
		arrow.textContent = '→';
		const lbl = document.createElement('span');
		lbl.className = 'cerb-ui-node--prop-label';
		lbl.textContent = p.label || p.name;
		if(kind === 'inlet') { row.appendChild(pill); row.appendChild(arrow); row.appendChild(lbl); }
		else { row.appendChild(lbl); row.appendChild(arrow); row.appendChild(pill); }
		return row;
	}

	// A flow branch outlet row: an (optional) name + an outlet handle on the right edge. A branch with an empty
	// label renders as a bare connector handle (used by read-only graphs where the target node carries the name).
	_renderBranch(b) {
		const row = document.createElement('div');
		row.className = 'cerb-ui-node--branch';
		const text = (b.label != null) ? b.label : b.name;
		if(text !== '') {
			const lbl = document.createElement('span');
			lbl.className = 'cerb-ui-node--branch-label';
			lbl.textContent = text;
			row.appendChild(lbl);
		}
		this._bindPartOpen(row, b);
		this._addHandle('branch:' + b.name, 'outlet', '_flow', { parent: row, posClass: 'cerb-ui-node--branch-handle' });
		return row;
	}

	_renderPreviewRow(row) {
		const el = document.createElement('div');
		el.className = 'cerb-ui-node--preview-row';
		if(row.variant) el.classList.add('cerb-ui-node--preview-row--' + row.variant);
		const icon = document.createElement('span');
		icon.className = 'cerb-icons cerb-icon-' + (row.icon || 'form') + ' cerb-ui-node--preview-icon';
		if(row.tooltip) icon.title = row.tooltip;
		const label = document.createElement('span');
		label.className = 'cerb-ui-node--preview-label';
		label.textContent = row.label || '';
		el.appendChild(icon);
		el.appendChild(label);
		this._bindPartOpen(el, row);
		return el;
	}

	_bindPartOpen(el, part) {
		if(part.line == null || typeof this.opts.onPartOpen !== 'function') return;
		el.classList.add('cerb-ui-node--part-clickable');
		el.addEventListener('mousedown', (e) => e.stopPropagation());
		el.addEventListener('click', (e) => {
			e.stopPropagation();
			this.opts.onPartOpen(part, this, e);
		});
		el.addEventListener('dblclick', (e) => e.stopPropagation());
	}

	// A dashed "+ Add …" button tinted by the node's accent.
	_addButton(text, onClick) {
		const b = document.createElement('button');
		b.type = 'button';
		b.className = 'cerb-ui-node--add';
		b.textContent = text;
		if(this.schema.headerColor) b.style.color = this.schema.headerColor;
		b.addEventListener('mousedown', (e) => e.stopPropagation());
		b.addEventListener('click', (e) => { e.stopPropagation(); if(typeof onClick === 'function') onClick(); });
		return b;
	}

	// "+ Add output" → a key/value row whose value inlet wires an expression; the key + value become a
	// `key: <expr>` entry in the terminal command's dictionary (e.g. error: / return:). Bound to data.outputs.
	addOutputRow(entry) {
		if(!this.data.outputs) this.data.outputs = [];
		entry = this._dynEntry(entry, this.data.outputs, 'out:', 'key');
		return this._dynRow({
			entry: entry, arr: this.data.outputs, valueKey: 'key',
			handleKind: 'inlet', posClass: 'cerb-ui-node--field-handle',
			placeholder: 'key', before: this._addOutputBtn,
		});
	}

	// "+ Add outcome" → a named flow branch outlet (a new outgoing path). Bound to data.outcomes.
	addOutcomeRow(entry) {
		if(!this.data.outcomes) this.data.outcomes = [];
		entry = this._dynEntry(entry, this.data.outcomes, 'branch:o', 'name');
		return this._dynRow({
			entry: entry, arr: this.data.outcomes, valueKey: 'name',
			handleKind: 'outlet', posClass: 'cerb-ui-node--branch-handle',
			placeholder: 'outcome', before: this._addOutcomeBtn,
		});
	}

	// Resolve the persistent data entry for a dynamic row: reuse a restored one (it has an .id — also keep the id
	// counter ahead of it so future ids don't collide), else create + push a fresh one.
	_dynEntry(entry, arr, prefix, valueKey) {
		if(entry && entry.id) {
			const n = parseInt(String(entry.id).replace(/\D/g, ''), 10);
			if(n > this._dynSeq) this._dynSeq = n;
			return entry;
		}
		const fresh = { id: prefix + (++this._dynSeq) };
		fresh[valueKey] = (entry && entry[valueKey]) || '';
		arr.push(fresh);
		return fresh;
	}

	// Shared editable dynamic row: [handle?] [× remove] [text input bound to entry[valueKey]] [handle?].
	_dynRow(o) {
		const row = document.createElement('div');
		row.className = 'cerb-ui-node--dynrow';
		const mkHandle = () => this._addHandle(o.entry.id, o.handleKind, o.handleKind === 'inlet' ? 'any' : '_flow', { parent: row, posClass: o.posClass });
		if(o.handleKind === 'inlet') mkHandle();   // value inlet on the left
		const rm = document.createElement('button');
		rm.type = 'button';
		rm.className = 'cerb-ui-node--rowremove';
		rm.title = 'Remove';
		const ri = document.createElement('span');
		ri.className = 'cerb-icons cerb-icon-trash';
		rm.appendChild(ri);
		rm.addEventListener('mousedown', (e) => e.stopPropagation());
		rm.addEventListener('click', (e) => { e.stopPropagation(); this._removeRow(row, o.entry, o.arr); });
		row.appendChild(rm);
		const input = document.createElement('input');
		input.type = 'text';
		input.className = 'cerb-ui-node--field-input cerb-ui-node--dynkey';
		input.placeholder = o.placeholder;
		if(o.entry[o.valueKey] != null) input.value = o.entry[o.valueKey];
		input.addEventListener('input', () => { o.entry[o.valueKey] = input.value; });
		row.appendChild(input);
		if(o.handleKind === 'outlet') mkHandle();   // branch outlet on the right
		if(o.before && o.before.parentNode) o.before.parentNode.insertBefore(row, o.before);
		else this.body.appendChild(row);
		if(typeof this.opts.onSchemaChange === 'function') this.opts.onSchemaChange(this);
		return row;
	}

	_removeRow(row, entry, arr) {
		this.removeHandle(entry.id);
		if(typeof this.opts.onRemoveHandle === 'function') this.opts.onRemoveHandle(this, entry.id);
		const i = arr.indexOf(entry);
		if(i >= 0) arr.splice(i, 1);
		if(row.parentNode) row.parentNode.removeChild(row);
		if(typeof this.opts.onSchemaChange === 'function') this.opts.onSchemaChange(this);
	}

	removeHandle(id) {
		const h = this.handles.get(id);
		if(h && h.el && h.el.parentNode) h.el.parentNode.removeChild(h.el);
		this.handles.delete(id);
	}

	// Has the user put anything in this node (field values / dynamic rows / comment text)? Drives the
	// confirm-before-delete gate in the editor.
	isConfigured() {
		// An expression node hides real work in its nested builder — treat it as configured once it's named or its
		// graph holds anything beyond the lone Output/terminal node, so deleting it always asks first.
		if(this.schema.expression) {
			const ex = this.data.expression || {};
			if(ex.name) return true;
			const nodes = (ex.graph && ex.graph.nodes) || [];
			if(nodes.some((n) => n.type !== 'terminal' && n.type !== 'output')) return true;
		}
		const fields = this.el.querySelectorAll('input, select, textarea');
		for(let i = 0; i < fields.length; i++) {
			const el = fields[i];
			if(el.type === 'checkbox') { if(el.checked) return true; }
			else if(el.value != null && String(el.value).trim() !== '') return true;
		}
		const editable = this.el.querySelectorAll('[contenteditable="true"]');
		for(let i = 0; i < editable.length; i++)
			if((editable[i].textContent || '').trim() !== '') return true;
		return false;
	}

	// Comment node: a free-floating dashed card with a (mock) format toolbar + editable body. No handles.
	_renderComment() {
		this.el.classList.add('cerb-ui-node--comment');
		this.toolbar = document.createElement('div');
		this.toolbar.className = 'cerb-ui-node--comment-toolbar';
		['H1', 'H2', 'B', 'I'].forEach((t) => {
			const b = document.createElement('button');
			b.type = 'button';
			b.className = 'cerb-ui-node--comment-btn';
			b.textContent = t;
			b.addEventListener('mousedown', (e) => e.stopPropagation());
			this.toolbar.appendChild(b);
		});
		this.el.appendChild(this.toolbar);

		this.content = document.createElement('div');
		this.content.className = 'cerb-ui-node--comment-content';
		this.content.setAttribute('contenteditable', 'true');
		this.content.dataset.placeholder = 'Comment…';
		if(this.data.text) this.content.textContent = this.data.text;
		this.content.addEventListener('input', () => { this.data.text = this.content.textContent; });
		this.el.appendChild(this.content);

		// Restore a saved size; track resize-grip changes back into data.size.
		if(this.data.size) {
			if(this.data.size.w) this.el.style.width = this.data.size.w + 'px';
			if(this.data.size.h) this.el.style.height = this.data.size.h + 'px';
		}
		if(window.ResizeObserver) {
			this._ro = new ResizeObserver(() => { this.data.size = { w: this.el.offsetWidth, h: this.el.offsetHeight }; });
			this._ro.observe(this.el);
		}
	}

	_bindCommentDrag() {
		const onDown = (e) => {
			if(e.button !== 0) return;
			if(e.target.closest('button, [contenteditable="true"]')) return;   // edit / format, don't drag
			e.stopPropagation();
			this.bringToFront();
			if(typeof this.opts.onSelect === 'function') this.opts.onSelect(this, e);
			const start = { x: e.clientX, y: e.clientY };
			const origin = { x: this.position.x, y: this.position.y };
			const move = (ev) => {
				const s = this.canvas.transform.scale || 1;
				this.setPosition(origin.x + (ev.clientX - start.x) / s, origin.y + (ev.clientY - start.y) / s);
				if(typeof this.opts.onMove === 'function') this.opts.onMove(this);
			};
			const up = () => {
				document.removeEventListener('mousemove', move);
				document.removeEventListener('mouseup', up);
			};
			document.addEventListener('mousemove', move);
			document.addEventListener('mouseup', up);
		};
		this.el.addEventListener('mousedown', onDown);
	}

	// Create a handle (inlet/outlet) of a given data type. opts: { parent, posClass, allowMultiple }.
	// Main flow handles use the sentinel type '_flow' (they only mate with each other); data handles carry a
	// real CerbUI.nodeTypes name and are tinted by its color.
	_addHandle(id, kind, dataType, opts) {
		opts = opts || {};
		const parent = opts.parent || this.el;
		const h = document.createElement('span');
		h.className = 'cerb-ui-node--handle ' + (opts.posClass || '');
		h.dataset.handleId = id;
		h.dataset.handleKind = kind;
		h.dataset.nodeId = this.id;
		h.dataset.dataType = dataType;
		// Handles are uniformly neutral gray (Kataflow look); the data type shows in the drag green/red and the
		// property-row label, not the handle. Keep the type as a tooltip only.
		if(dataType && dataType !== '_flow') h.title = dataType;
		parent.appendChild(h);
		this.handles.set(id, { el: h, kind: kind, dataType: dataType, allowMultiple: !!opts.allowMultiple });

		// Outlets begin a connection drag on press; inlets are passive targets. (Read-only: no connection drag.)
		if(kind === 'outlet' && !this.opts.readonly) {
			h.addEventListener('mousedown', (e) => {
				if(e.button !== 0) return;
				e.preventDefault();
				e.stopPropagation();
				if(typeof this.opts.onConnectStart === 'function') this.opts.onConnectStart(this, id, e);
			});
		}
		return h;
	}

	_bindDrag() {
		const onDown = (e) => {
			if(e.button !== 0) return;
			if(e.target.closest('.cerb-ui-node--handle')) return;   // handles own their own gesture
			// Let form controls / sockets / the remove + add buttons receive the press instead of dragging.
			if(e.target.closest('input, select, textarea, button, label, .cerb-ui-node--socket')) return;
			e.stopPropagation();   // keep the canvas from panning
			this.bringToFront();
			if(typeof this.opts.onSelect === 'function') this.opts.onSelect(this, e);

			const start = { x: e.clientX, y: e.clientY };
			const origin = { x: this.position.x, y: this.position.y };
			const move = (ev) => {
				const s = this.canvas.transform.scale || 1;
				this.setPosition(origin.x + (ev.clientX - start.x) / s, origin.y + (ev.clientY - start.y) / s);
				if(typeof this.opts.onMove === 'function') this.opts.onMove(this);
			};
			const up = () => {
				document.removeEventListener('mousemove', move);
				document.removeEventListener('mouseup', up);
			};
			document.addEventListener('mousemove', move);
			document.addEventListener('mouseup', up);
		};
		this.header.addEventListener('mousedown', onDown);
		if(this.body) this.body.addEventListener('mousedown', onDown);   // leaf nodes have no body
	}

	// Shared free-drag from a press on `el` (used by comment + expression cards). Skips handles/controls.
	_startDrag(e) {
		const start = { x: e.clientX, y: e.clientY };
		const origin = { x: this.position.x, y: this.position.y };
		const move = (ev) => {
			const s = this.canvas.transform.scale || 1;
			this.setPosition(origin.x + (ev.clientX - start.x) / s, origin.y + (ev.clientY - start.y) / s);
			if(typeof this.opts.onMove === 'function') this.opts.onMove(this);
		};
		const up = () => {
			document.removeEventListener('mousemove', move);
			document.removeEventListener('mouseup', up);
		};
		document.addEventListener('mousemove', move);
		document.addEventListener('mouseup', up);
	}

	// A borderless mini cerb-ui-tile (icon square + kind eyebrow + name) for the compact card nodes — keeps them
	// consistent with the palette tiles / node headers.
	_miniTile(o) {
		const tile = document.createElement('div');
		tile.className = 'cerb-ui-tile';
		const ico = document.createElement('span');
		ico.className = 'cerb-ui-tile--icon';
		if(o.color) ico.style.background = o.color;
		if(o.iconText != null) ico.textContent = o.iconText;
		else if(o.icon) { const g = document.createElement('span'); g.className = 'cerb-icons cerb-icon-' + o.icon; ico.appendChild(g); }
		tile.appendChild(ico);
		const text = document.createElement('div');
		text.className = 'cerb-ui-tile--text';
		const kind = document.createElement('div');
		kind.className = 'cerb-ui-tile--kind';
		if(o.kind != null) kind.textContent = o.kind;
		text.appendChild(kind);
		const name = document.createElement('div');
		name.className = 'cerb-ui-tile--name';
		if(o.name != null) name.textContent = o.name;
		text.appendChild(name);
		tile.appendChild(text);
		return { el: tile, iconEl: ico, kindEl: kind, nameEl: name };
	}

	_typeColor(t) { return (window.CerbUI && CerbUI.nodeTypes) ? CerbUI.nodeTypes.getColor(t) : '#718096'; }
	_typeLabel(t) { return (window.CerbUI && CerbUI.nodeTypes && CerbUI.nodeTypes.get(t)) ? CerbUI.nodeTypes.get(t).label : t; }

	// A ⋮ "more" button (every node) that opens a small CerbUI.Menu — Delete for now, room for more later. Going
	// through a menu (+ the editor's confirm) avoids the accidental one-click delete, esp. next to the value outlet.
	_nodeMenuBtn() {
		const b = document.createElement('button');
		b.type = 'button';
		b.className = 'cerb-ui-node--menu';
		b.title = 'Node options';
		const i = document.createElement('span');
		i.className = 'cerb-icons cerb-icon-more-vertical';
		b.appendChild(i);
		b.addEventListener('mousedown', (e) => e.stopPropagation());
		b.addEventListener('click', (e) => { e.stopPropagation(); this._openNodeMenu(b); });
		return b;
	}

	_openNodeMenu(anchor) {
		if(!(window.CerbUI && CerbUI.Menu)) { if(typeof this.opts.onRemove === 'function') this.opts.onRemove(this); return; }
		const items = [];
		// Edit is shown when the node has a popup editor (expression nodes → the builder).
		if(this.schema.expression) items.push({ action: 'edit', icon: 'edit', label: 'Edit' });
		items.push({ action: 'delete', icon: 'trash', label: 'Delete' });
		const ul = document.createElement('ul');
		items.forEach((it) => {
			const li = document.createElement('li');
			li.dataset.action = it.action;
			li.dataset.icon = it.icon;
			li.textContent = it.label;
			ul.appendChild(li);
		});
		const menu = new CerbUI.Menu(ul, {
			fixed: true,
			onRenderItem: (renderedLi, srcLi) => {
				if(!srcLi.dataset.icon) return;
				const ic = document.createElement('span');
				ic.className = 'cerb-icons cerb-icon-' + srcLi.dataset.icon;
				renderedLi.insertBefore(ic, renderedLi.firstChild);
			},
			onSelect: (renderedLi, srcLi) => {
				const a = srcLi.dataset.action;
				if(a === 'edit' && typeof this.opts.onOpen === 'function') this.opts.onOpen(this);
				else if(a === 'delete' && typeof this.opts.onRemove === 'function') this.opts.onRemove(this);
			},
		});
		menu.open(anchor);
	}

	// Expression node — a compact tile with an "Expression" eyebrow + the expression name; one value outlet.
	_renderExpression() {
		this.el.classList.add('cerb-ui-node--expr');
		this.el.title = 'Double-click to edit';
		const tile = this._miniTile({ iconText: '(f)', color: this.schema.headerColor, kind: 'Expression' });
		this.exprName = tile.nameEl;
		this.el.appendChild(tile.el);
		if(!this.opts.readonly) this.el.appendChild(this._nodeMenuBtn());
		this._syncExprName();
		const ann = (this.data.expression && this.data.expression.annotation) || 'any';
		this._addHandle('out:value', 'outlet', ann, { posClass: 'cerb-ui-node--value-out' });
	}

	_syncExprName() {
		const name = this.data.expression && this.data.expression.name;
		if(this.exprName) {
			this.exprName.textContent = name || 'expression';
			this.exprName.classList.toggle('cerb-ui-node--expr-name-empty', !name);
		}
	}

	// Store the result of the builder (name, @annotation, nested graph) and refresh the collapsed card + outlet type.
	setExpression(expr) {
		this.data.expression = expr || {};
		this._syncExprName();
		const h = this.handles.get('out:value');
		if(h) {
			h.dataType = (expr && expr.annotation) || 'any';
			if(h.el) { h.el.dataset.dataType = h.dataType; h.el.title = h.dataType; }
		}
	}

	// Compact "card" nodes (expression / global variable) drag from a press anywhere but the value handle.
	_bindCardEvents() {
		this.el.addEventListener('mousedown', (e) => {
			if(e.button !== 0) return;
			if(e.target.closest('.cerb-ui-node--handle')) return;
			e.stopPropagation();
			this.bringToFront();
			if(typeof this.opts.onSelect === 'function') this.opts.onSelect(this, e);
			this._startDrag(e);
		});
	}

	// Global Variable node — a compact tile whose eyebrow is the variable's DATA TYPE and name is the variable
	// name; one value outlet typed to (and polymorphing with) that type. It's an expression with a single
	// placeholder output.
	_renderGlobalVar() {
		this.el.classList.add('cerb-ui-node--var');
		const type = this.schema.outType || 'any';
		const tile = this._miniTile({
			color: this._typeColor(type),
			kind: this._typeLabel(type),
			name: this.schema.label || (this.data && this.data.varName) || 'variable',
		});
		this._varTileIcon = tile.iconEl;
		this._varTileKind = tile.kindEl;
		this._varTileName = tile.nameEl;
		this.el.appendChild(tile.el);
		if(!this.opts.readonly) this.el.appendChild(this._nodeMenuBtn());
		this._addHandle('out:value', 'outlet', type, { posClass: 'cerb-ui-node--value-out' });
	}

	// Refresh a placed Global Variable node after its blackboard variable was edited.
	setGlobalVar(name, type) {
		this.schema.label = name;
		this.schema.outType = type;
		this.data.varName = name;
		this.data.varType = type;
		if(this._varTileName) this._varTileName.textContent = name;
		if(this._varTileKind) this._varTileKind.textContent = this._typeLabel(type);
		if(this._varTileIcon) this._varTileIcon.style.background = this._typeColor(type);
		const h = this.handles.get('out:value');
		if(h) { h.dataType = type; if(h.el) { h.el.dataset.dataType = type; h.el.title = type; } }
	}

	_bindExpressionEvents() {
		this._bindCardEvents();
		this.el.addEventListener('dblclick', (e) => {
			e.stopPropagation();
			if(typeof this.opts.onOpen === 'function') this.opts.onOpen(this);
		});
	}

	// Append a nested child node into this node's container socket (in order). The child should be built with
	// { nested: true } so it renders in-flow without flow handles or canvas drag.
	appendChildNode(child) {
		if(this.container && child && child.el) this.container.appendChild(child.el);
		return this;
	}

	setPosition(x, y) {
		const O = CerbUI.NodeCanvas.OFFSET;
		this.position = { x: x, y: y };
		this.el.style.left = (x + O) + 'px';
		this.el.style.top = (y + O) + 'px';
		return this;
	}

	bringToFront() { this.el.style.zIndex = String(++CerbUI.Node._zTop); return this; }
	setSelected(on) { this.el.classList.toggle('cerb-ui-node--selected', !!on); return this; }

	// Reflect connection state on a handle (CSS does the rest): a connected outlet fills, a connected inlet
	// hides so the incoming edge's arrowhead points the flow (like Kataflow).
	setHandleConnected(id, connected) {
		const h = this.handles.get(id);
		if(!h) return this;
		h.connected = !!connected;
		h.el.classList.toggle('cerb-ui-node--handle-connected', !!connected);
		return this;
	}

	getHandleEl(id) { const h = this.handles.get(id); return h ? h.el : null; }
	getHandle(id) { return this.handles.get(id) || null; }

	toJSON() {
		return {
			id: this.id,
			type: this.schema.id || null,
			position: { x: this.position.x, y: this.position.y },
			data: this.data,
		};
	}

	destroy() {
		if(this._ro) { this._ro.disconnect(); this._ro = null; }
		if(this.el && this.el.parentNode) this.el.parentNode.removeChild(this.el);
		if(this.el) CerbUI.Node._instances.delete(this.el);
	}
};
