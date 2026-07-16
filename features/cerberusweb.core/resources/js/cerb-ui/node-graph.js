/*
 * CerbUI.NodeGraph — a READ-ONLY viewer for a node graph (no palette, no connect-drag, no delete).
 *
 * It's the lightweight counterpart to CerbUI.NodeEditor: it owns a CerbUI.NodeCanvas and instantiates CerbUI.Node
 * (with `readonly:true`) + CerbUI.NodeEdge straight from a serialized graph, computes a deterministic tiered layout
 * (no positions required in the data), and frames it. Nodes can still be dragged to declutter (edges follow); they
 * just can't be edited, connected, or removed.
 *
 * It is abstract: it builds NO chrome — just the canvas + an API. The consumer builds whatever toolbar it wants
 * via fit() / fitLane(i) / getLaneCount() and the canvas (g.canvas.zoomIn()/zoomOut()). "Lanes" are the graph's
 * disconnected components (independent start trees), exposed so a host can add lane navigation.
 *
 * It's the foundation for rendering server-built graphs (e.g. the automation-event flow overview) and, later,
 * KATA-defined flowcharts converted to a {nodes, edges} document.
 *
 *   const g = new CerbUI.NodeGraph(el, { nodeTypes:[…schemas…] });
 *   g.loadJSON({ nodes:[{id,type,label,tier,data}], edges:[{source,target,sourceHandle,targetHandle}] });
 *   g.fit();  g.fitLane(0);  g.focusLane(0);  g.getLaneCount();  g.destroy();
 *
 * Progressive enhancement (alternative to loadJSON): if `el` is seeded with node/edge markup it's read on
 * construction (like CerbUI.RecordChooser/Menu). Only data-* is honored (the security boundary):
 *   <div id="g">
 *     <div data-node-id="f1" data-node-type="start" data-label="On A" data-tier="0"></div>
 *     <div data-node-id="g1" data-node-type="group" data-label="Group" data-tier="1">
 *       <div data-node-id="a1" data-node-type="item" data-label="item 1"></div>  <!-- nested = contained child -->
 *     </div>
 *     <div data-edge data-source="f1" data-target="g1" data-curve></div>
 *   </div>
 *   new CerbUI.NodeGraph('#g', { nodeTypes:[…] });   // no loadJSON needed
 *
 * Layout model: each node carries a `tier` (column). x = tier * colWidth. The graph is a "spine" of tier-0 nodes
 * (chained start → step → step …) where each step fans out to higher tiers (tier+1). y is assigned by walking the
 * spine top-to-bottom, giving each spine node's whole fan-out subtree its own vertical band, and centering a parent
 * against its children. Spine edges connect same-tier nodes; fan-out edges connect tier → tier+1.
 */
CerbUI.NodeGraph = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.NodeGraph._instances.get(el); }

	static _DEFAULTS = {
		nodeTypes: null,     // array of node-type schema objects (id, label, icon, headerColor, start, terminal, …)
		colWidth: 280,       // x distance between tiers
		rowHeight: 110,      // fallback row height when a node hasn't been measured
		rowGap: 40,          // vertical gap between stacked nodes (added to each node's measured height)
		minimap: false,      // show the canvas minimap (forwarded to CerbUI.NodeCanvas)
		onNodeOpen: null,    // (nodeData, node, event) => {}  double-click a node (not a handle) — e.g. open its peek
		onNodePartOpen: null,// (part, nodeData, node, event) => {}  click a branch/preview row
	};

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.opts = Object.assign({}, CerbUI.NodeGraph._DEFAULTS, opts);
		this.el.classList.add('cerb-ui-node-graph');
		CerbUI.NodeGraph._instances.set(this.el, this);

		this.schemas = new Map();
		(this.opts.nodeTypes || []).forEach(s => this.registerNodeType(s));
		this.nodes = new Map();    // canvas-positioned nodes (spine + listener chain), keyed by id
		this.edges = [];
		this._nested = [];         // nested child nodes living inside a parent's container socket
		this.lanes = [];           // connected components (independent start lanes); see _computeLanes

		// Read any seed markup BEFORE _buildLayout() clears the host, then enhance it into the graph.
		const seed = this._parseMarkup(this.el);
		this._buildLayout();
		if(seed) this.loadJSON(seed);
	}

	registerNodeType(schema) { if(schema && schema.id) this.schemas.set(schema.id, schema); return this; }
	getNodeType(id) { return this.schemas.get(id) || null; }

	// ── Progressive enhancement: build a {nodes, edges} graph from data-* markup (security boundary: only
	// data-* is read; strings via getAttribute, never innerHTML). Returns null when the host has no node markup.
	_parseMarkup(root) {
		if(!root) return null;
		const nodeEls = Array.prototype.filter.call(root.children, (c) => c.matches && c.matches('[data-node-id]'));
		if(!nodeEls.length) return null;
		const nodes = nodeEls.map((el) => this._parseNodeEl(el));
		const edges = Array.prototype.map.call(root.querySelectorAll('[data-edge]'), (el) => {
			const e = { source: el.getAttribute('data-source'), target: el.getAttribute('data-target') };
			const sh = el.getAttribute('data-source-handle'); if(sh) e.sourceHandle = sh;
			const th = el.getAttribute('data-target-handle'); if(th) e.targetHandle = th;
			if(el.hasAttribute('data-curve')) e.curve = true;
			return e;
		});
		return { nodes: nodes, edges: edges };
	}

	_parseNodeEl(el) {
		const spec = { id: el.getAttribute('data-node-id'), type: el.getAttribute('data-node-type') };
		const label = el.getAttribute('data-label'); if(label != null) spec.label = label;
		const tier = el.getAttribute('data-tier'); if(tier != null) spec.tier = parseInt(tier, 10) || 0;
		const icon = el.getAttribute('data-node-icon'); if(icon) spec.icon = icon;
		if(el.hasAttribute('data-inlet-corner')) spec.inletCorner = true;
		const branchEls = Array.prototype.filter.call(el.children, (c) => c.matches && c.matches('[data-branch]'));
		if(branchEls.length) {
			spec.branches = branchEls.map((b) => ({
				name: b.getAttribute('data-name'),
				label: b.getAttribute('data-label') || '',
				line: b.hasAttribute('data-line') ? parseInt(b.getAttribute('data-line'), 10) : null,
			})).filter((b) => b.name);
		} else {
			// Backward compatibility with the original comma-separated branch-name markup.
			const branches = el.getAttribute('data-branches');
			if(branches) spec.branches = branches.split(',').map((n) => ({ name: n.trim(), label: '' }));
		}
		const previewEls = Array.prototype.filter.call(el.children, (c) => c.matches && c.matches('[data-preview-row]'));
		if(previewEls.length) spec.previewRows = previewEls.map((row) => ({
			label: row.getAttribute('data-label') || '',
			icon: row.getAttribute('data-icon') || '',
			tooltip: row.getAttribute('data-tooltip') || '',
			variant: row.getAttribute('data-variant') || '',
			line: row.hasAttribute('data-line') ? parseInt(row.getAttribute('data-line'), 10) : null,
		}));

		const data = {};
		const ctx = el.getAttribute('data-context'), cid = el.getAttribute('data-context-id');
		if(ctx && cid) { data.context = ctx; data.contextId = cid; }
		const url = el.getAttribute('data-url'); if(url) data.url = url;
		const desc = el.getAttribute('data-description'); if(desc) data.description = desc;
		if(Object.keys(data).length) spec.data = data;

		// Nested node elements (direct children) are this node's contained children — recurse.
		const childEls = Array.prototype.filter.call(el.children, (c) => c.matches && c.matches('[data-node-id]'));
		if(childEls.length) spec.children = childEls.map((c) => this._parseNodeEl(c));
		return spec;
	}

	// Bare chrome: just the canvas host. The consumer builds any toolbar via the public API (fit / fitLane /
	// getLaneCount / g.canvas.zoomIn()/zoomOut()).
	_buildLayout() {
		this.el.textContent = '';
		this.canvasHost = document.createElement('div');
		this.canvasHost.className = 'cerb-ui-node-graph--canvas';
		this.el.appendChild(this.canvasHost);

		this.canvas = new CerbUI.NodeCanvas(this.canvasHost, {
			onTransform: () => this._updateAllEdges(),
			minimap: this.opts.minimap,
		});
	}

	// ── Build from a serialized graph ────────────────────────────────────
	// A node may carry `children:[…]` — those render INSIDE this node's container socket (in order), not on the
	// canvas, and have no edges. Build everything first (so the DOM has real sizes), then lay out the canvas
	// nodes using measured heights (container parents vary a lot), then draw edges and fit.
	loadJSON(graph) {
		this.clear();
		if(!graph) return this;

		(graph.nodes || []).forEach((n) => this._addNode(n, null));

		const heights = {}, widths = {};
		this.nodes.forEach((node, id) => {
			heights[id] = node.el.offsetHeight || this.opts.rowHeight;
			widths[id] = node.el.offsetWidth || 0;
		});
		const positions = this._layout(graph, heights, widths);
		this.nodes.forEach((node, id) => { const p = positions[id]; if(p) node.setPosition(p.x, p.y); });

		(graph.edges || []).forEach((e) => {
			const s = this.nodes.get(e.source), t = this.nodes.get(e.target);
			if(!s || !t) return;
			const sh = e.sourceHandle || '_next', th = e.targetHandle || '_in';
			const edge = new CerbUI.NodeEdge(this.canvas, { source: s, sourceHandle: sh, target: t, targetHandle: th, straight: !e.curve });
			this.edges.push(edge);
			// Reflect connection state so the inlet hides and the edge reads as an arrow (Kataflow look).
			s.setHandleConnected(sh, true);
			t.setHandleConnected(th, true);
		});

		// Compute lanes (exposed via getLaneCount/fitLane) and frame the whole graph by default. A host that wants
		// lane-by-lane framing calls fitLane(0) after loadJSON.
		this.lanes = this._computeLanes();
		this.fit();
		return this;
	}

	// Independent start lanes = connected components over the canvas nodes (lanes share no edges). Returned
	// left→right by each component's min x. Contained children live inside a parent's bbox, so only this.nodes matter.
	_computeLanes() {
		const adj = new Map();
		this.nodes.forEach((node, id) => adj.set(id, []));
		this.edges.forEach((e) => {
			const a = e.source && e.source.id, b = e.target && e.target.id;
			if(adj.has(a) && adj.has(b)) { adj.get(a).push(b); adj.get(b).push(a); }
		});
		const seen = new Set();
		const lanes = [];
		this.nodes.forEach((node, id) => {
			if(seen.has(id)) return;
			seen.add(id);
			const stack = [id], comp = [];
			while(stack.length) {
				const cur = stack.pop();
				const n = this.nodes.get(cur);
				if(n) comp.push(n);
				(adj.get(cur) || []).forEach((m) => { if(!seen.has(m)) { seen.add(m); stack.push(m); } });
			}
			lanes.push(comp);
		});
		const minX = (comp) => comp.reduce((m, n) => Math.min(m, n.position.x), Infinity);
		lanes.sort((c1, c2) => minX(c1) - minX(c2));
		return lanes;
	}

	// ── Public lane API (a host builds its own lane navigator on top) ────
	getLaneCount() { return (this.lanes || []).length; }

	// Frame one lane (a disconnected component / independent start tree), 0-based, shrinking to fit.
	fitLane(i) {
		if(!this.lanes || !this.lanes.length) return this;
		i = Math.max(0, Math.min(i, this.lanes.length - 1));
		if(this.canvas) this.canvas.fitToNodes(this.lanes[i]);
		return this;
	}

	// Focus one lane at a fixed scale (default 100%), top-aligned — useful to "go to" a flow without shrinking it.
	focusLane(i, scale) {
		if(!this.lanes || !this.lanes.length) return this;
		i = Math.max(0, Math.min(i, this.lanes.length - 1));
		if(this.canvas) this.canvas.focusNodes(this.lanes[i], scale || 1);
		return this;
	}

	// Build a node and (recursively) its contained children. Top-level nodes go on the canvas; children render in
	// their parent's container socket (nested, no edges) — supports arbitrary depth (event ▸ listener ▸ automation).
	_addNode(spec, parent) {
		const node = this._buildNode(spec, !!parent);
		if(parent) { parent.appendChildNode(node); this._nested.push(node); }
		else { this.canvas.appendNode(node.el); this.nodes.set(spec.id, node); }
		this._wireRecord(spec, node);
		(spec.children || []).forEach((c) => this._addNode(c, node));
		return node;
	}

	// Build one CerbUI.Node (read-only) from a node spec. `nested` children render in-flow inside a parent's
	// container (no flow handles / no canvas drag).
	_buildNode(n, nested) {
		const base = this.getNodeType(n.type) || { id: n.type, label: n.label || n.type };
		const overrides = {};
		// A node may declare its own branch outlets (one edge each).
		if(n.branches) overrides.branches = n.branches;
		if(n.previewRows) overrides.previewRows = n.previewRows;
		if(n.icon) overrides.icon = n.icon;
		// A container node with no children renders as a plain panel (no empty container socket).
		if(base.container && !(n.children && n.children.length)) overrides.container = false;
		const schema = Object.keys(overrides).length ? Object.assign({}, base, overrides) : base;
		const data = Object.assign({}, n.data || {});
		if(n.label != null) data.label = n.label;
		if(!data.inputs) data.inputs = {};

		return new CerbUI.Node(this.canvas, {
			schema: schema,
			id: n.id,
			position: { x: 0, y: 0 },
			data: data,
			readonly: true,
			nested: !!nested,
			inletCorner: !!n.inletCorner,
			onPartOpen: (part, nd, e) => {
				if(typeof this.opts.onNodePartOpen === 'function') this.opts.onNodePartOpen(part, n, nd, e);
			},
			onMove: nested ? null : (nd) => { this._updateEdgesForNode(nd); if(this.canvas.refreshMinimap) this.canvas.refreshMinimap(); },
		});
	}

	// Record nodes: mirror context onto the element + a clickable affordance, and open on DOUBLE-click (not single
	// click / drag-stop / focus). stopPropagation so a dblclick on a nested child opens its peek, not the parent's.
	_wireRecord(n, node) {
		const data = node.data || {};
		if(data.context && data.contextId) {
			node.el.dataset.context = data.context;
			node.el.dataset.contextId = data.contextId;
			node.el.classList.add('cerb-ui-node--clickable');
		}
		if(data.url) node.el.dataset.url = data.url;

		if(typeof this.opts.onNodeOpen === 'function') {
			node.el.addEventListener('dblclick', (e) => {
				if(e.target.closest('.cerb-ui-node--handle')) return;
				e.stopPropagation();
				this.opts.onNodeOpen(n, node, e);
			});
		}
	}

	// Deterministic layout. Returns { nodeId: {x,y} }. The graph is a chain + branch tree: an edge to the next
	// SAME-tier node is a "chain" (sequential siblings — the spine, and listeners), an edge to a tier+1 node is a
	// "branch" (a parent reaching a child column). x = tier·colWidth; y is assigned by walking each chain top to
	// bottom and giving every node's branch-children their own vertical band to the right.
	_layout(graph, heights, widths) {
		const nodes = graph.nodes || [];
		const edges = graph.edges || [];
		const colW = this.opts.colWidth, rowH = this.opts.rowHeight, gap = this.opts.rowGap;
		heights = heights || {};
		widths = widths || {};
		const h = (id) => heights[id] || rowH;

		const tier = {};
		nodes.forEach((n) => { tier[n.id] = n.tier || 0; });

		const chainNext = {}, branchKids = {}, incoming = {};
		nodes.forEach((n) => { branchKids[n.id] = []; incoming[n.id] = 0; });
		edges.forEach((e) => {
			if(!(e.source in tier) || !(e.target in tier)) return;
			incoming[e.target] = (incoming[e.target] || 0) + 1;
			if(tier[e.target] === tier[e.source] + 1) branchKids[e.source].push(e.target);
			else if(tier[e.target] === tier[e.source] && !(e.source in chainNext)) chainNext[e.source] = e.target;
		});

		const pos = {};
		const placed = {};

		// Mutually recursive (function declarations avoid const TDZ): place() lays out a node + its branch-child
		// columns and centers it against them; placeChain() lays out a node then its same-tier successors. Vertical
		// stepping uses each node's measured height (container parents vary a lot) plus a fixed gap.
		function place(id, topY) {
			if(placed[id]) return topY;
			placed[id] = true;
			// Center on the column axis (tier·colWidth) so centered ports align and edges stay vertical/orthogonal.
			const x = ((tier[id] || 0) * colW) - (widths[id] || 0) / 2;
			const kids = branchKids[id].filter((k) => !placed[k]);
			if(!kids.length) {
				pos[id] = { x: x, y: topY };
				return topY + h(id) + gap;
			}
			let cy = topY;
			kids.forEach((k) => { cy = placeChain(k, cy); });
			const bandBottom = cy - gap;   // strip the trailing gap to get the children band's true bottom
			pos[id] = { x: x, y: Math.max(topY, topY + ((bandBottom - topY) - h(id)) / 2) };
			return Math.max(cy, pos[id].y + h(id) + gap);
		}
		function placeChain(id, topY) {
			let cur = id, cursor = topY;
			while(cur != null && !placed[cur]) {
				cursor = place(cur, cursor);
				cur = chainNext[cur];
			}
			return cursor;
		}

		// Each root (a node with no incoming edge) is an independent LANE. Lay each out top-aligned (topY=0), then
		// pack the lanes side-by-side as columns: shift each lane so its left edge sits at a running laneX.
		const laneGap = colW;
		let laneX = 0;
		const layoutLane = (rootId) => {
			const before = Object.keys(pos).length;
			placeChain(rootId, 0);
			const laneIds = Object.keys(pos).slice(before);
			if(!laneIds.length) return;
			let minX = Infinity, maxX = -Infinity;
			laneIds.forEach((id) => { minX = Math.min(minX, pos[id].x); maxX = Math.max(maxX, pos[id].x + (widths[id] || 0)); });
			const shift = laneX - minX;
			laneIds.forEach((id) => { pos[id].x += shift; });
			laneX += (maxX - minX) + laneGap;
		};
		nodes.filter((n) => !incoming[n.id]).forEach((root) => layoutLane(root.id));
		// Any leftover (cycles / unreferenced) nodes get their own trailing lane.
		nodes.forEach((n) => { if(!placed[n.id]) layoutLane(n.id); });

		return pos;
	}

	// ── Edges bookkeeping ───────────────────────────────────────────────
	_updateAllEdges() { this.edges.forEach((e) => e.update()); }
	_updateEdgesForNode(node) { this.edges.forEach((e) => { if(e.source === node || e.target === node) e.update(); }); }

	fit() { if(this.canvas) this.canvas.fitToNodes(Array.from(this.nodes.values())); return this; }

	clear() {
		this.edges.forEach((e) => e.destroy());
		this._nested.forEach((n) => n.destroy());
		this.nodes.forEach((n) => n.destroy());
		this.edges = [];
		this._nested = [];
		this.nodes.clear();
		return this;
	}

	destroy() {
		this.clear();
		if(this.canvas) this.canvas.destroy();
		if(this.el) CerbUI.NodeGraph._instances.delete(this.el);
	}
};
