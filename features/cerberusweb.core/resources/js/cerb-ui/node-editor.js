/*
 * CerbUI.NodeEditor — the orchestrator that turns a container into a visual node-graph editor.
 *
 * It owns the node-type schema registry, builds the chrome (toolbar + palette + canvas), creates the
 * CerbUI.NodeCanvas, and coordinates connections between nodes (CerbUI.Node) via edges (CerbUI.NodeEdge). The palette
 * is a CerbUI.Sidebar in tile/palette mode with a CerbUI.Switcher for Blocks/Variables; tiles drag onto the canvas
 * (CerbUI.Draggable → a CerbUI.Droppable drop zone) to instantiate nodes. Dragging from an outlet draws a free edge
 * that snaps to a valid inlet, or — dropped on empty canvas — opens a CerbUI.Menu tile picker that creates + connects
 * a new node. The whole graph serializes to/from JSON.
 *
 * The same editor core is meant to drive two surfaces: the main automation graph (an explicit `start` node) and —
 * later, opened in a CerbUI.Dialog by double-clicking an Expression node — a nested editor with a different palette
 * and an explicit `Output` node. Node types are plain serializable schema objects (mirrors js-flow's registerBlockType).
 *
 *   const editor = new CerbUI.NodeEditor(el, { nodeTypes:[…schemas…], mode:'flow', palette:true });
 *   editor.registerNodeType(schema);  editor.addNode(typeId, {x,y});  editor.connect(a,'_next', b,'_in');
 *   editor.toJSON();  editor.loadJSON(graph);  editor.clear();  editor.fit();  editor.destroy();
 *
 * [TODO Phase 2] data-typed handles + branch outlets + "+ Add".  [TODO Phase 4] expression-builder Dialog mode.
 * [TODO Phase 5] blackboard variables behind the palette Switcher's Variables section.
 */
CerbUI.NodeEditor = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.NodeEditor._instances.get(el); }
	static _seq = 0;
	static SVGNS = 'http://www.w3.org/2000/svg';

	static _DEFAULTS = {
		nodeTypes: null,     // array of node-type schema objects
		mode: 'flow',        // 'flow' | 'expression'
		palette: true,       // show the left palette sidebar
		toolbar: true,       // show the top toolbar
		expressionNodeTypes: null, // scripting-block schemas for the nested expression builder (incl. an Output singleton)
		variables: null,     // blackboard seeded by the host — [{name, type}] — these are IMMUTABLE in the editor
		onChange: null,      // (editor) => {}  graph mutated
	};

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.opts = Object.assign({}, CerbUI.NodeEditor._DEFAULTS, opts);
		this.uid = ++CerbUI.NodeEditor._seq;
		this.el.classList.add('cerb-ui-node-editor');
		CerbUI.NodeEditor._instances.set(this.el, this);

		this.schemas = new Map();
		this.nodes = new Map();
		this.edges = new Map();
		this.selected = null;       // { kind:'node'|'edge', ref }
		this.canvas = null;

		// Blackboard. Host-seeded variables are immutable; user-added ones are removable.
		this._varSeq = 0;
		this.variables = (this.opts.variables || []).map((v) => ({ id: v.id || ('var-' + (++this._varSeq)), name: v.name, type: v.type || 'any', immutable: true }));

		(this.opts.nodeTypes || []).forEach(s => this.registerNodeType(s));

		this._buildLayout();
		if(this.opts.palette) this._buildPalette();
		this._buildDropZone();
		this._bindKeys();
	}

	// ── Schema registry ─────────────────────────────────────────────────
	registerNodeType(schema) { if(schema && schema.id) this.schemas.set(schema.id, schema); return this; }
	getNodeType(id) { return this.schemas.get(id) || null; }
	getNodeTypes() { return Array.from(this.schemas.values()); }

	// ── Blackboard (typed variables) ────────────────────────────────────
	getVariables() { return this.variables; }
	addVariable(v) {
		const variable = { id: 'var-' + (++this._varSeq), name: (v && v.name) || 'variable', type: (v && v.type) || 'any', immutable: false };
		this.variables.push(variable);
		this._renderVariables();
		this._changed();
		return variable;
	}
	// Remove a variable AND every Global Variable node that references it (those nodes are meaningless without it).
	removeVariable(id) {
		const v = this.variables.find((x) => x.id === id);
		if(!v || v.immutable) return this;   // host-seeded variables are locked
		Array.from(this.nodes.values()).forEach((node) => {
			if(node.schema && node.schema.globalVar && node.data && node.data.variableId === id) this.removeNode(node);
		});
		this.variables = this.variables.filter((x) => x.id !== id);
		this._renderVariables();
		this._changed();
		return this;
	}

	_varNodeCount(id) {
		let n = 0;
		this.nodes.forEach((node) => { if(node.schema && node.schema.globalVar && node.data && node.data.variableId === id) n++; });
		return n;
	}

	// Confirm before deleting a variable — it also removes its placed nodes, so say how many.
	_requestRemoveVariable(id) {
		const v = this.variables.find((x) => x.id === id);
		if(!v || v.immutable) return;
		const count = this._varNodeCount(id);
		const body = count > 0
			? ('Remove “' + v.name + '” and its ' + count + ' node' + (count === 1 ? '' : 's') + ' from the graph?')
			: ('Remove “' + v.name + '”?');
		if(window.CerbUI && CerbUI.Confirm)
			CerbUI.Confirm.open({ title: 'Remove variable', body: body, confirmText: 'Remove', onConfirm: () => this.removeVariable(id) });
		else if(typeof window === 'undefined' || window.confirm(body))
			this.removeVariable(id);
	}

	// Fill the palette's Variables tab: an "+ Add variable" button + a row per variable (type chip + name;
	// host-seeded ones show a lock, user ones an × remove).
	_renderVariables() {
		if(!this._varsBody) return;
		this._varsBody.textContent = '';
		const add = document.createElement('button');
		add.type = 'button';
		add.className = 'cerb-ui-node-editor--addvar';
		add.textContent = '+ Add variable';
		add.addEventListener('click', () => this._openVariableDialog(null));
		this._varsBody.appendChild(add);

		const list = document.createElement('div');
		list.className = 'cerb-ui-node-editor--varlist';
		this.variables.forEach((v) => list.appendChild(this._varRow(v)));
		this._varsBody.appendChild(list);
	}

	_varRow(v) {
		const row = document.createElement('div');
		row.className = 'cerb-ui-node-editor--varrow';
		row.dataset.varId = v.id;   // drag payload → addVariableNode
		row.title = v.immutable ? 'Defined at init — drag onto the canvas to use' : 'Drag onto the canvas to use · click to edit';

		// Render as a cerb-ui-tile (type-colored square + type eyebrow + name) so it's obviously a draggable tile.
		const tile = document.createElement('div');
		tile.className = 'cerb-ui-tile';
		const ico = document.createElement('span');
		ico.className = 'cerb-ui-tile--icon';
		if(window.CerbUI && CerbUI.nodeTypes) ico.style.background = CerbUI.nodeTypes.getColor(v.type);
		tile.appendChild(ico);
		const text = document.createElement('div');
		text.className = 'cerb-ui-tile--text';
		const kind = document.createElement('div');
		kind.className = 'cerb-ui-tile--kind';
		kind.textContent = (window.CerbUI && CerbUI.nodeTypes && CerbUI.nodeTypes.get(v.type)) ? CerbUI.nodeTypes.get(v.type).label : v.type;
		text.appendChild(kind);
		const name = document.createElement('div');
		name.className = 'cerb-ui-tile--name';
		name.textContent = v.name;
		text.appendChild(name);
		tile.appendChild(text);
		row.appendChild(tile);

		if(v.immutable) {
			const lock = document.createElement('span');
			lock.className = 'cerb-icons cerb-icon-lock cerb-ui-node-editor--varlock';
			lock.title = 'Defined at init — immutable';
			row.appendChild(lock);
		} else {
			const rm = document.createElement('button');
			rm.type = 'button';
			rm.className = 'cerb-ui-node-editor--varremove';
			rm.title = 'Remove variable';
			const ico2 = document.createElement('span');
			ico2.className = 'cerb-icons cerb-icon-trash';
			rm.appendChild(ico2);
			rm.addEventListener('mousedown', (e) => e.stopPropagation());   // don't start a drag from the ×
			rm.addEventListener('click', (e) => { e.stopPropagation(); this._requestRemoveVariable(v.id); });
			row.appendChild(rm);
			// A click (not a drag) edits the variable.
			row.addEventListener('click', () => { if(this._varDragging) return; this._openVariableDialog(v); });
		}
		return row;
	}

	// Edit a mutable variable's name/type and refresh any placed Global Variable nodes referencing it.
	editVariable(id, fields) {
		const v = this.variables.find((x) => x.id === id);
		if(!v || v.immutable) return this;
		if(fields.name != null && fields.name !== '') v.name = fields.name;
		if(fields.type) v.type = fields.type;
		this._renderVariables();
		this.nodes.forEach((n) => {
			if(n.schema && n.schema.globalVar && n.data && n.data.variableId === id && typeof n.setGlobalVar === 'function')
				n.setGlobalVar(v.name, v.type);
		});
		this._changed();
		return this;
	}

	// Add / edit variable modal: a name + a cascading type picker built from the CerbUI.nodeTypes inheritance tree
	// (Dictionary ▸ Credentials ▸ API Key …). Selecting any level (incl. a branch) picks that type.
	_openVariableDialog(existing) {
		if(!(window.CerbUI && CerbUI.Dialog)) return;
		const content = document.createElement('div');
		content.className = 'cerb-ui-node-editor--addvardialog';

		const nameField = this._exprField('Name', 'input');
		nameField.input.placeholder = 'name (letters, digits, _ or -)';
		if(existing) nameField.input.value = existing.name;
		const hint = document.createElement('div');
		hint.className = 'cerb-ui-node-editor--varhint';
		hint.textContent = 'Use letters, digits, _ or - — and don’t start with a number.';
		hint.style.display = 'none';
		nameField.wrap.appendChild(hint);
		// Live-strip anything outside [a-zA-Z0-9_-] (kills spaces); the no-leading-digit rule is checked on save.
		nameField.input.addEventListener('input', () => {
			const cleaned = nameField.input.value.replace(/[^a-zA-Z0-9_\-]/g, '');
			if(cleaned !== nameField.input.value) nameField.input.value = cleaned;
			hint.style.display = 'none';
		});

		const typeField = this._exprField('Type', 'div');
		let picked = existing ? existing.type : 'text';
		// A SelectMenu-style trigger (value + chevron) that opens the cascading type tree (ONE reused CerbUI.Menu —
		// re-instantiating per click was spawning a menu each time).
		const typeBtn = document.createElement('div');
		typeBtn.className = 'cerb-ui-selectmenu cerb-ui-node-editor--vartypebtn';
		typeBtn.setAttribute('tabindex', '0');
		const valWrap = document.createElement('span'); valWrap.className = 'cerb-ui-selectmenu--value';
		const valText = document.createElement('span'); valText.className = 'cerb-ui-selectmenu--text';
		valWrap.appendChild(valText); typeBtn.appendChild(valWrap);
		const chev = document.createElement('span'); chev.className = 'cerb-icons cerb-icon-chevron-down cerb-ui-selectmenu--chevron'; chev.setAttribute('aria-hidden', 'true');
		typeBtn.appendChild(chev);
		const typeLabel = () => (window.CerbUI && CerbUI.nodeTypes && CerbUI.nodeTypes.get(picked)) ? CerbUI.nodeTypes.get(picked).label : picked;
		valText.textContent = typeLabel();
		let typeMenu = null;
		typeBtn.addEventListener('click', () => {
			if(!(window.CerbUI && CerbUI.Menu)) return;
			if(!typeMenu) typeMenu = new CerbUI.Menu(this._buildTypeMenu(), {
				selectableParents: true,   // branches are clickable (Dictionary picks Dictionary; hover to drill in)
				filter: true,
				filterAlways: true,        // search box visible + focused from open → keyboard nav engages
				filterPlaceholder: 'Filter types…',
				filterIcon: 'search',
				onSelect: (rendered, src) => { picked = src.dataset.type; valText.textContent = typeLabel(); },
			});
			typeMenu.open(typeBtn);
		});
		typeField.input.appendChild(typeBtn);

		const foot = document.createElement('div');
		foot.className = 'cerb-ui-node-editor--exprfoot';
		const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'cerb-ui-button cerb-ui-button--subtle'; cancel.textContent = 'Cancel';
		const ok = document.createElement('button'); ok.type = 'button'; ok.className = 'cerb-ui-button'; ok.textContent = existing ? 'Save' : 'Add';
		foot.appendChild(cancel); foot.appendChild(ok);

		content.appendChild(nameField.wrap);
		content.appendChild(typeField.wrap);
		content.appendChild(foot);

		const dlg = new CerbUI.Dialog(content, { title: existing ? 'Edit variable' : 'Add variable', modal: true, width: 420 });
		cancel.addEventListener('click', () => dlg.close());
		ok.addEventListener('click', () => {
			const nm = (nameField.input.value || '').trim();
			if(!CerbUI.NodeEditor.isValidVariableName(nm)) { hint.style.display = ''; nameField.input.focus(); return; }
			if(existing) this.editVariable(existing.id, { name: nm, type: picked });
			else this.addVariable({ name: nm, type: picked });
			dlg.close();
		});
		dlg.open();
	}

	// A <ul> tree of every CerbUI.nodeTypes type, nested by inheritance. Branch types are directly clickable (the
	// menu runs with selectableParents) — so you can require `credentials` OR drill into a specific `api_key`.
	_buildTypeMenu() {
		const all = (window.CerbUI && CerbUI.nodeTypes) ? CerbUI.nodeTypes.all() : [];
		const kids = {};
		all.forEach((t) => { if(t.base) { (kids[t.base] = kids[t.base] || []).push(t); } });
		const ul = document.createElement('ul');
		const build = (t, parentUl) => {
			const li = document.createElement('li');
			li.dataset.type = t.name;
			li.textContent = t.label || t.name;
			parentUl.appendChild(li);
			const children = kids[t.name] || [];
			if(children.length) {
				const sub = document.createElement('ul');
				children.forEach((c) => build(c, sub));
				li.appendChild(sub);
			}
		};
		(kids['any'] || []).forEach((t) => build(t, ul));
		return ul;
	}

	// ── Chrome ──────────────────────────────────────────────────────────

	_buildLayout() {
		this.el.textContent = '';
		if(this.opts.toolbar) {
			this.toolbar = document.createElement('div');
			this.toolbar.className = 'cerb-ui-node-editor--toolbar';
			this._toolbarButton('move', 'Fit', () => this.fit());
			this._toolbarButton('zoom-in', 'Zoom in', () => this.canvas && this.canvas.zoomIn());
			this._toolbarButton('zoom-out', 'Zoom out', () => this.canvas && this.canvas.zoomOut());
			this._toolbarSpacer();
			this._toolbarButton('download', 'Export', () => this._exportDialog());
			this._toolbarButton('upload', 'Import', () => this._importDialog());
			this._toolbarButton('trash', 'Clear', () => this._confirmClear());
			this.el.appendChild(this.toolbar);
		}

		this.main = document.createElement('div');
		this.main.className = 'cerb-ui-node-editor--main';
		this.el.appendChild(this.main);

		this.canvasHost = document.createElement('div');
		this.canvasHost.className = 'cerb-ui-node-editor--canvas';
		this.main.appendChild(this.canvasHost);

		this.canvas = new CerbUI.NodeCanvas(this.canvasHost, {
			onTransform: () => this._updateAllEdges(),
			onBackgroundDown: () => this._select(null),
		});
	}

	_toolbarButton(icon, label, onClick) {
		const b = document.createElement('button');
		b.type = 'button';
		b.className = 'cerb-ui-button cerb-ui-node-editor--tool';
		b.title = label;
		b.innerHTML = '<span class="cerb-icons cerb-icon-' + icon + '"></span><span>' + label + '</span>';
		b.addEventListener('click', onClick);
		this.toolbar.appendChild(b);
		return b;
	}
	_toolbarSpacer() { const s = document.createElement('span'); s.className = 'cerb-ui-node-editor--tool-spacer'; this.toolbar.appendChild(s); }

	_buildPalette() {
		const aside = document.createElement('aside');
		aside.className = 'cerb-ui-sidebar cerb-ui-node-editor--palette';

		// Head: a Blocks / Variables switcher (Variables is a Phase 5 placeholder for now).
		const head = document.createElement('div');
		head.className = 'cerb-ui-sidebar--head';
		const sw = document.createElement('div');
		sw.className = 'cerb-ui-switcher';
		sw.innerHTML = '<button type="button" data-value="blocks" class="cerb-ui-switcher--active">Blocks</button>'
			+ '<button type="button" data-value="variables">Variables</button>';
		head.appendChild(sw);
		aside.appendChild(head);

		// Blocks body: tile palette grouped by category.
		const blocks = document.createElement('div');
		blocks.className = 'cerb-ui-sidebar--body';
		const byCat = new Map();
		this.getNodeTypes().forEach(s => {
			const cat = s.category || 'blocks';
			if(!byCat.has(cat)) byCat.set(cat, []);
			byCat.get(cat).push(s);
		});
		byCat.forEach((list, cat) => {
			const sec = document.createElement('div');
			sec.className = 'cerb-ui-sidebar--section';
			const lbl = document.createElement('div');
			lbl.className = 'cerb-ui-sidebar--label';
			lbl.textContent = cat;
			sec.appendChild(lbl);
			const ul = document.createElement('ul');
			list.forEach(s => {
				const li = document.createElement('li');
				li.dataset.nodeType = s.id;
				if(s.icon) li.dataset.icon = s.icon;
				if(s.headerColor) li.dataset.color = s.headerColor;
				li.dataset.name = s.label || s.id;
				li.dataset.kind = cat;
				ul.appendChild(li);
			});
			sec.appendChild(ul);
			blocks.appendChild(sec);
		});
		aside.appendChild(blocks);

		// Variables body — the blackboard (filled by _renderVariables).
		const vars = document.createElement('div');
		vars.className = 'cerb-ui-sidebar--body cerb-ui-node-editor--palette-vars';
		vars.style.display = 'none';
		this._varsBody = vars;
		aside.appendChild(vars);

		this.main.insertBefore(aside, this.canvasHost);

		this.sidebar = new CerbUI.Sidebar(aside, { palette: true, filter: true, filterPlaceholder: 'Search blocks…' });
		this.switcher = new CerbUI.Switcher(sw, {
			onSelect: (v) => {
				blocks.style.display = (v === 'blocks') ? '' : 'none';
				vars.style.display = (v === 'variables') ? '' : 'none';
			},
		});
		this._renderVariables();

		// Variable rows drag onto the canvas (clone helper) → a Global Variable node. The canvas Droppable
		// (built next) reads the row's data-var-id from the payload.
		if(window.CerbUI && CerbUI.Draggable) {
			this._varDraggable = new CerbUI.Draggable(this._varsBody, {
				items: '.cerb-ui-node-editor--varrow',
				helper: 'clone',
				tilt: true,
				data: (item) => ({ varId: item.dataset.varId }),
				onStart: () => { this._varDragging = true; },
				onStop: () => { setTimeout(() => { this._varDragging = false; }, 0); },   // swallow the trailing click
			});
		}
	}

	// The canvas accepts palette tiles; dropping creates a node at the drop position.
	_buildDropZone() {
		if(!(window.CerbUI && CerbUI.Droppable)) return;
		this.dropzone = new CerbUI.Droppable(this.canvas.el, {
			accept: (item) => !!(item && item.matches && (item.matches('.cerb-ui-sidebar--item') || item.matches('.cerb-ui-node-editor--varrow'))),
			onDrop: (info) => {
				const p = info.payload || {};
				const pos = this.canvas.screenToCanvas({ x: info.clientX, y: info.clientY });
				const node = p.varId ? this.addVariableNode(p.varId, pos)
					: ((p.nodeType && this.getNodeType(p.nodeType)) ? this.addNode(p.nodeType, pos) : null);
				if(!node) return false;   // refused (e.g. singleton already present) → revert the helper
				this._centerNodeAt(node, pos);
			},
		});
	}

	// ── Graph mutation ──────────────────────────────────────────────────

	addNode(typeId, position, opts) {
		const schema = this.getNodeType(typeId);
		if(!schema) return null;
		// Singleton types (Start, Output) may exist only once — refuse a second (covers drops, the connect-menu,
		// and import). The palette tile is also disabled while one exists (see _updatePalette).
		if(schema.singleton && this._countOfType(typeId) > 0) return null;
		return this._instantiate(schema, position, opts);
	}

	// Create a node from a (possibly synthetic) schema + wire all the editor callbacks. Shared by addNode and
	// addVariableNode.
	_instantiate(schema, position, opts) {
		opts = opts || {};
		const node = new CerbUI.Node(this.canvas, {
			schema: schema,
			id: opts.id,
			position: position || { x: 0, y: 0 },
			data: opts.data || { inputs: {} },
			onMove: (n) => this._updateEdgesForNode(n),
			onSelect: (n, e) => { this._select({ kind: 'node', ref: n }); if(e) e.stopPropagation(); },
			onConnectStart: (n, h, e) => this._beginConnect(n, h, e),
			onRemove: (n) => this._requestRemoveNode(n),
			onRemoveHandle: (n, h) => this._removeEdgesForHandle(n, h),
			onOpen: (n) => this._openExpressionBuilder(n),
		});
		this.canvas.appendNode(node.el);
		this.nodes.set(node.id, node);
		this._updatePalette();
		this._changed();
		return node;
	}

	// Re-center a just-created node on a logical point (the drop cursor). Drag helpers track from where you grabbed
	// — usually near the tile's middle — so centering the node avoids the jarring shift of placing its top-left there.
	_centerNodeAt(node, pos) {
		if(!node || !node.el) return;
		const w = node.el.offsetWidth || 0, h = node.el.offsetHeight || 0;
		node.setPosition(pos.x - w / 2, pos.y - h / 2);
		this._updateEdgesForNode(node);
	}

	// Create a Global Variable node referencing a blackboard variable (a synthetic schema — value outlet typed to
	// the variable). The variable's id/name/type are kept on the node data so it survives even if the blackboard
	// changes (round-trip).
	addVariableNode(variableId, position, opts) {
		opts = opts || {};
		const v = this.variables.find((x) => x.id === variableId);
		const data = opts.data || {};
		const name = v ? v.name : data.varName;
		const type = v ? v.type : (data.varType || 'any');
		if(!name) return null;
		data.variableId = variableId; data.varName = name; data.varType = type;
		if(!data.inputs) data.inputs = {};
		const schema = { id: 'globalvar', label: name, category: 'variable', headerColor: '#3182ce', dataNode: true, valueOut: true, outType: type, globalVar: true };
		return this._instantiate(schema, position, { id: opts.id, data: data });
	}

	_countOfType(typeId) {
		let n = 0;
		this.nodes.forEach(node => { if(node.schema && node.schema.id === typeId) n++; });
		return n;
	}

	// Disable a singleton type's palette tile while one exists; re-enable when removed.
	_updatePalette() {
		if(!this.sidebar || !this.sidebar.el) return;
		this.getNodeTypes().forEach(s => {
			if(!s.singleton) return;
			const li = this.sidebar.el.querySelector('li[data-node-type="' + s.id + '"]');
			if(li) li.classList.toggle('cerb-ui-node-editor--tile-used', this._countOfType(s.id) > 0);
		});
	}

	// Drop any edges attached to a (now-removed) dynamic handle.
	_removeEdgesForHandle(node, handleId) {
		Array.from(this.edges.values()).forEach(e => {
			if((e.source === node && e.sourceHandle === handleId) || (e.target === node && e.targetHandle === handleId))
				this.removeEdge(e);
		});
	}

	// Delete with a confirm if the node carries any configuration; otherwise remove straight away.
	_requestRemoveNode(node) {
		const configured = node.isConfigured && node.isConfigured()
			|| Array.from(this.edges.values()).some(e => e.source === node || e.target === node);
		if(configured && window.CerbUI && CerbUI.Confirm) {
			CerbUI.Confirm.open({
				title: 'Remove node',
				body: 'This ' + ((node.schema && node.schema.label) || 'node') + ' has configuration. Remove it and its connections?',
				confirmText: 'Remove',
				onConfirm: () => this.removeNode(node),
			});
		} else {
			this.removeNode(node);
		}
	}

	removeNode(node) {
		if(!node) return this;
		Array.from(this.edges.values())
			.filter(e => e.source === node || e.target === node)
			.forEach(e => this.removeEdge(e));
		this.nodes.delete(node.id);
		node.destroy();
		if(this.selected && this.selected.ref === node) this.selected = null;
		this._updatePalette();
		this._changed();
		return this;
	}

	connect(srcNode, srcHandle, dstNode, dstHandle) {
		if(!this._canConnect(srcNode, srcHandle, dstNode, dstHandle)) return null;
		// Single-connection handles replace their existing edge; multi handles (data outputs fan out) keep them.
		const sh = srcNode.getHandle(srcHandle), dh = dstNode.getHandle(dstHandle);
		Array.from(this.edges.values()).forEach(e => {
			if(sh && !sh.allowMultiple && e.source === srcNode && e.sourceHandle === srcHandle) this.removeEdge(e);
			else if(dh && !dh.allowMultiple && e.target === dstNode && e.targetHandle === dstHandle) this.removeEdge(e);
		});
		const edge = new CerbUI.NodeEdge(this.canvas, {
			source: srcNode, sourceHandle: srcHandle, target: dstNode, targetHandle: dstHandle,
			onRemove: (ed) => this.removeEdge(ed),
		});
		edge.el.addEventListener('mousedown', () => this._select({ kind: 'edge', ref: edge }));
		this.edges.set(edge.id, edge);
		this._updateHandleState(srcNode, srcHandle);
		this._updateHandleState(dstNode, dstHandle);
		this._changed();
		return edge;
	}

	removeEdge(edge) {
		if(!edge) return this;
		const s = edge.source, sh = edge.sourceHandle, t = edge.target, th = edge.targetHandle;
		this.edges.delete(edge.id);
		edge.destroy();
		if(this.selected && this.selected.ref === edge) this.selected = null;
		this._updateHandleState(s, sh);
		this._updateHandleState(t, th);
		this._changed();
		return this;
	}

	// Re-derive a handle's connected flag from the current edges and push it to the node (fill/hide via CSS).
	_updateHandleState(node, handleId) {
		if(!node || !node.getHandle) return;
		const h = node.getHandle(handleId);
		if(!h) return;
		let connected = false;
		for(const e of this.edges.values()) {
			if(h.kind === 'outlet' && e.source === node && e.sourceHandle === handleId) { connected = true; break; }
			if(h.kind === 'inlet'  && e.target === node && e.targetHandle === handleId) { connected = true; break; }
		}
		node.setHandleConnected(handleId, connected);
	}

	_canConnect(srcNode, srcHandle, dstNode, dstHandle) {
		if(!srcNode || !dstNode || srcNode === dstNode) return false;
		const sh = srcNode.getHandle(srcHandle), dh = dstNode.getHandle(dstHandle);
		if(!sh || !dh || sh.kind !== 'outlet' || dh.kind !== 'inlet') return false;
		// Flow handles only mate with flow handles; typed data handles mate by CerbUI.nodeTypes compatibility
		// (the source's type must be assignable into the target inlet's type).
		if(sh.dataType === '_flow' || dh.dataType === '_flow') {
			if(sh.dataType !== dh.dataType) return false;
		} else if(window.CerbUI && CerbUI.nodeTypes && !CerbUI.nodeTypes.isCompatible(dh.dataType, sh.dataType)) {
			return false;
		}
		for(const e of this.edges.values())
			if(e.source === srcNode && e.sourceHandle === srcHandle && e.target === dstNode && e.targetHandle === dstHandle)
				return false;
		// Keep the graph acyclic: reject if dst can already reach src (this edge would close a loop). The KATA
		// backend validates this too, but enforcing it here gives immediate red feedback while dragging.
		if(this._reaches(dstNode, srcNode)) return false;
		return true;
	}

	// Can `from` reach `target` by following outlet→inlet edges forward? (cycle test for a proposed edge)
	_reaches(from, target) {
		const seen = new Set();
		const stack = [from];
		while(stack.length) {
			const node = stack.pop();
			if(node === target) return true;
			if(seen.has(node)) continue;
			seen.add(node);
			for(const e of this.edges.values())
				if(e.source === node) stack.push(e.target);
		}
		return false;
	}

	// ── Free-edge connection drag ───────────────────────────────────────

	_beginConnect(srcNode, srcHandle, e) {
		const temp = document.createElementNS(CerbUI.NodeEditor.SVGNS, 'path');
		temp.setAttribute('class', 'cerb-ui-node-edge--line cerb-ui-node-edge--temp');
		this.canvas.edgeLayer.appendChild(temp);
		const p1 = this.canvas.handleViewportCenter(srcNode.getHandleEl(srcHandle));
		let target = null, targetHandle = null, marked = null;

		const setState = (s) => {
			temp.classList.remove('cerb-ui-node-edge--valid', 'cerb-ui-node-edge--invalid');
			if(s) temp.classList.add('cerb-ui-node-edge--' + s);
		};
		// Light up the inlet under the cursor green/red (filled + glowing) to match the edge line.
		const clearMark = () => {
			if(marked) marked.classList.remove('cerb-ui-node--handle-target-valid', 'cerb-ui-node--handle-target-invalid');
			marked = null;
		};
		const mark = (el, s) => {
			if(marked !== el) clearMark();
			marked = el;
			el.classList.remove('cerb-ui-node--handle-target-valid', 'cerb-ui-node--handle-target-invalid');
			el.classList.add('cerb-ui-node--handle-target-' + s);
		};
		const move = (ev) => {
			let p2 = this.canvas.clientToViewport(ev.clientX, ev.clientY);
			target = null; targetHandle = null;
			const under = document.elementFromPoint(ev.clientX, ev.clientY);
			const handleEl = under && under.closest ? under.closest('.cerb-ui-node--handle') : null;
			if(handleEl && handleEl.dataset.handleKind === 'inlet') {
				const tNode = CerbUI.Node.from(handleEl.closest('.cerb-ui-node'));
				const tHandle = handleEl.dataset.handleId;
				if(tNode && this._canConnect(srcNode, srcHandle, tNode, tHandle)) {
					target = tNode; targetHandle = tHandle;
					p2 = this.canvas.handleViewportCenter(handleEl);   // snap to the inlet
					setState('valid'); mark(handleEl, 'valid');
				} else { setState('invalid'); mark(handleEl, 'invalid'); }
			} else { setState(null); clearMark(); }
			temp.setAttribute('d', CerbUI.NodeEdge.pathD(p1, p2));
		};
		const up = (ev) => {
			document.removeEventListener('mousemove', move);
			document.removeEventListener('mouseup', up);
			clearMark();
			temp.remove();
			if(target) { this.connect(srcNode, srcHandle, target, targetHandle); return; }
			// Dropped on empty canvas → offer a tile picker that creates + connects a new node. Only the main
			// flow outlet wires into a new node's _in; a typed data outlet has nowhere generic to land.
			const sh = srcNode.getHandle(srcHandle);
			const under = document.elementFromPoint(ev.clientX, ev.clientY);
			const onCanvas = under && this.canvas.el.contains(under) && !(under.closest && under.closest('.cerb-ui-node'));
			if(onCanvas && sh && sh.dataType === '_flow') this._openConnectMenu(srcNode, srcHandle, ev.clientX, ev.clientY);
		};
		document.addEventListener('mousemove', move);
		document.addEventListener('mouseup', up);
	}

	// The "Add and connect block…" picker shown when an edge is dropped on empty canvas. Items render as
	// the same tiles as the palette (icon square + kind eyebrow + name) so the two surfaces match.
	_openConnectMenu(srcNode, srcHandle, clientX, clientY) {
		if(!(window.CerbUI && CerbUI.Menu)) return;
		const ul = document.createElement('ul');
		this.getNodeTypes().filter(s => !s.start && !(s.singleton && this._countOfType(s.id) > 0)).forEach(s => {
			const li = document.createElement('li');
			li.dataset.nodeType = s.id;
			if(s.icon) li.dataset.icon = s.icon;
			if(s.headerColor) li.dataset.color = s.headerColor;
			if(s.category) li.dataset.kind = s.category;
			li.dataset.name = s.label || s.id;
			li.textContent = s.label || s.id;   // label text (drives the menu's type-to-filter)
			ul.appendChild(li);
		});
		const anchor = document.createElement('div');
		anchor.style.cssText = 'position:fixed;width:0;height:0;left:' + clientX + 'px;top:' + clientY + 'px;';
		document.body.appendChild(anchor);

		const menu = new CerbUI.Menu(ul, {
			fixed: true,
			panelClass: 'cerb-ui-node-editor--addmenu',
			filter: true,
			filterAlways: true,                       // command-bar style: search box visible + focused from open
			filterPlaceholder: 'Add and connect block…',
			filterIcon: 'search',
			onRenderItem: (renderedLi, srcLi) => {
				renderedLi.classList.add('cerb-ui-menu--item-tile');
				const lbl = renderedLi.querySelector('.cerb-ui-menu--label');
				if(lbl) lbl.remove();   // replace the plain label with a palette tile
				renderedLi.appendChild(CerbUI.Node.buildTile(srcLi.dataset));
			},
			onSelect: (renderedLi, srcLi) => {
				const typeId = srcLi.dataset.nodeType;
				const pos = this.canvas.screenToCanvas({ x: clientX, y: clientY });
				const node = this.addNode(typeId, pos);
				if(node) { this._centerNodeAt(node, pos); this.connect(srcNode, srcHandle, node, '_in'); }
			},
			onClose: () => { if(anchor.parentNode) anchor.parentNode.removeChild(anchor); },
		});
		menu.open(anchor);
	}

	// ── Expression builder (nested editor in a dialog) ──────────────────
	// Double-clicking an expression node opens THE SAME CerbUI.NodeEditor in a modal, but with the scripting
	// palette + an `Output` singleton instead of `Start`. The expression's name becomes the KATA key; its
	// @annotation becomes the value outlet's type. Saving stores { name, annotation, optional, graph } on the node.
	_openExpressionBuilder(node) {
		if(!(window.CerbUI && CerbUI.Dialog)) return;
		const expr = (node.data && node.data.expression) || {};
		const types = this.opts.expressionNodeTypes || CerbUI.NodeEditor.DEFAULT_EXPRESSION_TYPES;

		const content = document.createElement('div');
		content.className = 'cerb-ui-node-editor--exprbuilder';

		// Header: Label (the key/name), @annotation (output type), @optional.
		const head = document.createElement('div');
		head.className = 'cerb-ui-node-editor--exprhead';
		const nameField = this._exprField('Label', 'input');
		nameField.input.placeholder = 'Enter expression name…';
		nameField.input.value = expr.name || '';
		const annField = this._exprField('Annotation', 'select');
		// Real Cerb KATA annotations (libs/devblocks/api/services/kata.php :: $_valid_annotations).
		[['', '(none)'], ['bool', '@bool'], ['bit', '@bit'], ['int', '@int'], ['text', '@text'], ['list', '@list'],
		 ['json', '@json'], ['csv', '@csv'], ['date', '@date'], ['base64', '@base64'], ['key', '@key'],
		 ['kata', '@kata'], ['raw', '@raw'], ['nowrap', '@nowrap'], ['trim', '@trim']]
			.forEach(([val, lbl]) => { const o = document.createElement('option'); o.value = val; o.textContent = lbl; if((expr.annotation || '') === val) o.selected = true; annField.input.appendChild(o); });
		const optWrap = document.createElement('label');
		optWrap.className = 'cerb-ui-node-editor--exproptional cerb-ui-toggle';
		const optInput = document.createElement('input'); optInput.type = 'checkbox'; if(expr.optional) optInput.checked = true;
		const optSlider = document.createElement('span'); optSlider.className = 'cerb-ui-toggle--slider';
		optWrap.appendChild(optInput); optWrap.appendChild(optSlider);
		if(window.CerbUI && CerbUI.Toggle) new CerbUI.Toggle(optWrap, {});
		const optText = document.createElement('span'); optText.className = 'cerb-ui-node-editor--exproptionallabel'; optText.textContent = '@optional';
		head.appendChild(nameField.wrap); head.appendChild(annField.wrap);
		const optGroup = document.createElement('div'); optGroup.className = 'cerb-ui-node-editor--exprfield';
		optGroup.appendChild(optWrap); optGroup.appendChild(optText); head.appendChild(optGroup);

		const host = document.createElement('div');
		host.className = 'cerb-ui-node-editor--exprcanvas';

		const foot = document.createElement('div');
		foot.className = 'cerb-ui-node-editor--exprfoot';
		const cancelBtn = document.createElement('button'); cancelBtn.type = 'button'; cancelBtn.className = 'cerb-ui-button cerb-ui-button--subtle'; cancelBtn.textContent = 'Cancel';
		const saveBtn = document.createElement('button'); saveBtn.type = 'button'; saveBtn.className = 'cerb-ui-button'; saveBtn.textContent = 'Save Expression';
		foot.appendChild(cancelBtn); foot.appendChild(saveBtn);

		content.appendChild(head); content.appendChild(host); content.appendChild(foot);

		// Cleanup is hooked to the Dialog's onClose so it runs no matter HOW it closes (×, Escape, backdrop, or a
		// button) — otherwise a stale nested editor lingers on _modalStack and silently blocks the parent's Delete.
		let nested = null;
		// ~90% of the window for now (not mobile-friendly, and that's fine) — avoids a slight horizontal scroll.
		const dlg = new CerbUI.Dialog(content, {
			title: 'Expression Builder', modal: true, width: Math.round((window.innerWidth || 1200) * 0.9),
			onClose: () => {
				const i = CerbUI.NodeEditor._modalStack.indexOf(nested);
				if(i >= 0) CerbUI.NodeEditor._modalStack.splice(i, 1);
				if(nested) nested.destroy();
			},
		});
		dlg.open();

		// The nested editor — same machinery, expression palette + a `Terminal` singleton. Built after open() so
		// the host is in the DOM and sized. [TODO] live preview: a callback POSTs nested.toJSON() to the backend
		// and writes the returned value into the Terminal's preview (state stays local; backend computes).
		// Share the blackboard: variables defined in the main graph show up here (immutable in the builder).
		nested = new CerbUI.NodeEditor(host, {
			mode: 'expression', nodeTypes: types,
			variables: this.getVariables().map((v) => ({ name: v.name, type: v.type })),
		});
		nested.loadJSON(expr.graph || { nodes: [{ id: 'terminal', type: 'terminal', position: { x: 320, y: 160 } }], edges: [] });
		CerbUI.NodeEditor._modalStack.push(nested);   // this nested editor now owns the Delete key

		cancelBtn.addEventListener('click', () => dlg.close());
		saveBtn.addEventListener('click', () => {
			node.setExpression({
				name: (nameField.input.value || '').trim(),
				annotation: annField.input.value || null,
				optional: !!optInput.checked,
				graph: nested.toJSON(),   // read BEFORE close (onClose destroys nested)
			});
			dlg.close();
			this._changed();
		});
	}

	_exprField(labelText, kind) {
		const wrap = document.createElement('div');
		wrap.className = 'cerb-ui-node-editor--exprfield';
		const lbl = document.createElement('label');
		lbl.className = 'cerb-ui-node-editor--exprlabel';
		lbl.textContent = labelText;
		const input = document.createElement(kind === 'select' ? 'select' : (kind === 'div' ? 'div' : 'input'));
		input.className = 'cerb-ui-node-editor--exprinput';
		wrap.appendChild(lbl); wrap.appendChild(input);
		return { wrap, input };
	}

	// ── Selection / delete ──────────────────────────────────────────────

	_select(sel) {
		if(this.selected) {
			if(this.selected.kind === 'node') this.selected.ref.setSelected(false);
			else this.selected.ref.setSelected(false);
		}
		this.selected = sel;
		if(sel) sel.ref.setSelected(true);
	}

	_bindKeys() {
		this._onKey = (e) => {
			if(e.key !== 'Delete' && e.key !== 'Backspace') return;
			// Only the topmost editor reacts: while an expression-builder modal is open, its nested editor is on
			// the stack and the parent editor (not on the stack) stays quiet.
			const stack = CerbUI.NodeEditor._modalStack;
			if(stack.length ? (stack[stack.length - 1] !== this) : false) return;
			const a = document.activeElement;
			if(a && (a.tagName === 'INPUT' || a.tagName === 'TEXTAREA' || a.isContentEditable)) return;
			if(!this.selected) return;
			e.preventDefault();
			if(this.selected.kind === 'node') this._requestRemoveNode(this.selected.ref);
			else this.removeEdge(this.selected.ref);
		};
		document.addEventListener('keydown', this._onKey);
	}

	// ── Edges bookkeeping ───────────────────────────────────────────────
	_updateAllEdges() { this.edges.forEach(e => e.update()); }
	_updateEdgesForNode(node) { this.edges.forEach(e => { if(e.source === node || e.target === node) e.update(); }); }

	// ── Toolbar actions ─────────────────────────────────────────────────
	fit() { if(this.canvas) this.canvas.fitToNodes(Array.from(this.nodes.values())); return this; }
	clear() {
		Array.from(this.edges.values()).forEach(e => e.destroy());
		Array.from(this.nodes.values()).forEach(n => n.destroy());
		this.edges.clear(); this.nodes.clear(); this.selected = null;
		this._updatePalette();
		this._changed();
		return this;
	}
	_confirmClear() {
		if(window.CerbUI && CerbUI.Confirm)
			CerbUI.Confirm.open({ title: 'Clear graph', body: 'Remove all nodes and edges?', confirmText: 'Clear', onConfirm: () => this.clear() });
		else if(window.confirm('Remove all nodes and edges?')) this.clear();
	}

	// ── Serialization (js-flow JSON shape; KATA later) ──────────────────
	toJSON() {
		return {
			nodes: Array.from(this.nodes.values()).map(n => n.toJSON()),
			edges: Array.from(this.edges.values()).map(e => ({
				id: e.id,
				source: e.source.id, sourceHandle: e.sourceHandle,
				target: e.target.id, targetHandle: e.targetHandle,
			})),
		};
	}

	loadJSON(graph) {
		this.clear();
		if(!graph) return this;
		(graph.nodes || []).forEach(n => {
			if(n.type === 'globalvar')
				this.addVariableNode(n.data && n.data.variableId, n.position || { x: 0, y: 0 }, { id: n.id, data: n.data || {} });
			else
				this.addNode(n.type, n.position || { x: 0, y: 0 }, { id: n.id, data: n.data || { inputs: {} } });
		});
		(graph.edges || []).forEach(ed => {
			const s = this.nodes.get(ed.source), t = this.nodes.get(ed.target);
			if(s && t) this.connect(s, ed.sourceHandle || '_next', t, ed.targetHandle || '_in');
		});
		this.fit();
		return this;
	}

	// ── Import / Export dialogs ─────────────────────────────────────────
	_exportDialog() {
		const json = JSON.stringify(this.toJSON(), null, '\t');
		const wrap = document.createElement('div');
		wrap.className = 'cerb-ui-node-editor--io';
		const ta = document.createElement('textarea');
		ta.className = 'cerb-ui-node-editor--io-text';
		ta.readOnly = true;
		ta.value = json;
		wrap.appendChild(ta);
		if(window.CerbUI && CerbUI.Dialog) {
			new CerbUI.Dialog(wrap, { title: 'Export graph (JSON)', modal: true, width: 560 }).open();
			ta.select();
		}
	}
	_importDialog() {
		const wrap = document.createElement('div');
		wrap.className = 'cerb-ui-node-editor--io';
		const ta = document.createElement('textarea');
		ta.className = 'cerb-ui-node-editor--io-text';
		ta.placeholder = 'Paste graph JSON…';
		wrap.appendChild(ta);
		const bar = document.createElement('div');
		bar.className = 'cerb-ui-node-editor--io-actions';
		const load = document.createElement('button');
		load.type = 'button';
		load.className = 'cerb-ui-button';
		load.textContent = 'Load';
		bar.appendChild(load);
		wrap.appendChild(bar);
		if(!(window.CerbUI && CerbUI.Dialog)) return;
		const dlg = new CerbUI.Dialog(wrap, { title: 'Import graph (JSON)', modal: true, width: 560 });
		load.addEventListener('click', () => {
			try { this.loadJSON(JSON.parse(ta.value)); dlg.close(); }
			catch(err) { ta.classList.add('cerb-ui-node-editor--io-error'); }
		});
		dlg.open();
	}

	_changed() { if(typeof this.opts.onChange === 'function') this.opts.onChange(this); }

	destroy() {
		document.removeEventListener('keydown', this._onKey);
		this.edges.forEach(e => e.destroy());
		this.nodes.forEach(n => n.destroy());
		if(this.dropzone) this.dropzone.destroy();
		if(this._varDraggable) this._varDraggable.destroy();
		if(this.sidebar) this.sidebar.destroy();
		if(this.switcher) this.switcher.destroy();
		if(this.canvas) this.canvas.destroy();
		if(this.el) CerbUI.NodeEditor._instances.delete(this.el);
	}
};

// Open expression-builder editors, innermost last. The Delete key only acts for the top one (see _bindKeys).
CerbUI.NodeEditor._modalStack = [];

// Variable names are identifiers: [a-zA-Z0-9_-], not starting with a digit (no spaces).
CerbUI.NodeEditor.isValidVariableName = function(name) {
	return /^[a-zA-Z_\-][a-zA-Z0-9_\-]*$/.test(String(name == null ? '' : name));
};

// Fallback scripting palette for the expression builder (data-flow nodes: value inputs/outputs, no execution flow).
// `Output` is the singleton terminal whose inlet defines the expression's value. Callers can pass their own via
// the `expressionNodeTypes` option.
CerbUI.NodeEditor.DEFAULT_EXPRESSION_TYPES = [
	{ id: 'terminal',     label: 'Output',           icon: 'circle',       headerColor: '#48bb78', category: 'terminal', singleton: true, dataNode: true, valueIn: [{ name: 'value' }] },
	{ id: 'var_global',   label: 'Global Variable',  icon: 'placeholders', headerColor: '#3182ce', category: 'input',    dataNode: true, valueOut: true, outType: 'any' },
	{ id: 'literal_text', label: 'Literal (Text)',   icon: 'text',         headerColor: '#3182ce', category: 'input',    dataNode: true, valueOut: true, outType: 'text',  fields: [{ name: 'v', label: 'value', control: 'text', value: '' }] },
	{ id: 'literal_num',  label: 'Literal (Number)', icon: 'collection',   headerColor: '#3182ce', category: 'input',    dataNode: true, valueOut: true, outType: 'int',   fields: [{ name: 'v', label: 'value', control: 'number', value: '0' }] },
	{ id: 'math',         label: 'Math',             icon: 'adjust',       headerColor: '#805ad5', category: 'operator', dataNode: true, valueIn: [{ name: 'a' }, { name: 'b' }], valueOut: true, outType: 'float',
		fields: [{ name: 'op', label: 'op', control: 'select', value: 'Add (a + b)', groups: [
			{ label: 'Binary (a, b)', options: ['Add (a + b)', 'Subtract (a − b)', 'Multiply (a × b)', 'Divide (a / b)', 'Floor Divide (a // b)', 'Modulo (a % b)', 'Power (a ** b)', 'Min (a, b)', 'Max (a, b)', 'Compare (a <=> b)'] },
			{ label: 'Unary (a)', options: ['Absolute |a|', 'Negate (−a)'] },
		] }] },
	{ id: 'compare',      label: 'Comparison',       icon: 'adjust',       headerColor: '#805ad5', category: 'operator', dataNode: true, valueIn: [{ name: 'a' }, { name: 'b' }], valueOut: true, outType: 'bool',  fields: [{ name: 'op', label: 'op', control: 'select', value: '==', options: ['==', '!=', '<', '>', '<=', '>='] }] },
];
