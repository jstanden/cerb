/*
 * CerbUI.NodeEdge — one directed connection between an outlet handle and an inlet handle, drawn as an SVG path.
 *
 * An edge renders into the shared <svg> edge-layer that CerbUI.NodeCanvas owns (above the nodes, in viewport space so
 * it pans & zooms with them). It's a left-to-right bezier from the source outlet to the target inlet, with an
 * arrowhead at the target end; it recomputes whenever either endpoint moves. A fat invisible "hit" path makes the
 * thin line easy to click (double-click removes it, wired by the editor).
 *
 * The *free-edge connect-drag* affordance — pressing an outlet and dragging a provisional edge that follows the
 * cursor and turns green/red over a valid/invalid inlet — lives in CerbUI.NodeEditor (it needs the schema registry +
 * connect()). This is deliberately NOT CerbUI.Draggable (that's bbox element-drag); an edge is a free path between two
 * points. (Palette tile → canvas drops DO use Draggable/Droppable; edges don't.)
 *
 *   const edge = new CerbUI.NodeEdge(canvas, { id, source, sourceHandle, target, targetHandle, onRemove });
 *   edge.update();    // recompute the path from current handle positions
 *   edge.setState('valid'|'invalid'|null);  edge.destroy();
 *
 * [TODO Phase 2] type-aware default coloring once handles carry real data types (CerbUI.nodeTypes).
 */
CerbUI.NodeEdge = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.NodeEdge._instances.get(el); }
	static _seq = 0;

	static _DEFAULTS = {
		source: null,        // CerbUI.Node the edge leaves
		sourceHandle: '_next',
		target: null,        // CerbUI.Node the edge enters
		targetHandle: '_in',
		bidirectional: false,// draw an arrowhead at both ends (used by branch edges to imply return to the parent)
		straight: false,     // draw a straight line instead of the bezier (clean for centered ports)
		endpointHit: false,  // add a transparent hit-circle at the target end (the arrowhead marker isn't clickable)
		onRemove: null,      // (edge) => {}  double-click to delete
	};

	constructor(canvas, opts = {}) {
		this.canvas = canvas;
		this.svg = canvas ? canvas.edgeLayer : null;
		if(!this.svg) return;
		this.opts = Object.assign({}, CerbUI.NodeEdge._DEFAULTS, opts);
		this.id = opts.id || ('edge-' + (++CerbUI.NodeEdge._seq));
		this.source = this.opts.source;
		this.sourceHandle = this.opts.sourceHandle;
		this.target = this.opts.target;
		this.targetHandle = this.opts.targetHandle;

		this.el = document.createElementNS('http://www.w3.org/2000/svg', 'g');
		this.el.classList.add('cerb-ui-node-edge');
		this.el.dataset.edgeId = this.id;

		this.hit = document.createElementNS('http://www.w3.org/2000/svg', 'path');
		this.hit.classList.add('cerb-ui-node-edge--hit');
		this.line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
		this.line.classList.add('cerb-ui-node-edge--line');
		this.line.setAttribute('marker-end', 'url(#' + canvas.markerId + ')');
		if(this.opts.bidirectional) this.line.setAttribute('marker-start', 'url(#' + canvas.markerId + ')');
		this.el.appendChild(this.hit);
		this.el.appendChild(this.line);
		// The arrowhead is a shared SVG marker (not a hit-testable element). A small transparent circle at the
		// target end gives it a hover/click target — hovering it highlights the edge (it's inside this group), and
		// the host can wire a click (e.g. jump to the tail node). Opt-in so the editor's live inlets stay clear.
		if(this.opts.endpointHit) {
			this.arrowHit = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
			this.arrowHit.classList.add('cerb-ui-node-edge--arrow-hit');
			this.arrowHit.setAttribute('r', '9');
			this.el.appendChild(this.arrowHit);
		}
		this.svg.appendChild(this.el);
		CerbUI.NodeEdge._instances.set(this.el, this);

		// Click selects (handled by the editor via this el); double-click removes.
		this.el.addEventListener('mousedown', (e) => { e.stopPropagation(); });
		this.el.addEventListener('dblclick', (e) => {
			e.stopPropagation();
			if(typeof this.opts.onRemove === 'function') this.opts.onRemove(this);
		});

		this.update();
	}

	// Build a horizontal-tangent bezier between two viewport-space points (the js-flow edge look).
	static pathD(p1, p2) {
		const c = Math.max(40, Math.abs(p2.x - p1.x) * 0.5);
		return 'M ' + p1.x + ' ' + p1.y + ' C ' + (p1.x + c) + ' ' + p1.y + ', ' + (p2.x - c) + ' ' + p2.y + ', ' + p2.x + ' ' + p2.y;
	}

	// A straight segment between two viewport-space points.
	static lineD(p1, p2) {
		return 'M ' + p1.x + ' ' + p1.y + ' L ' + p2.x + ' ' + p2.y;
	}

	update() {
		if(!this.source || !this.target) return this;
		const a = this.source.getHandleEl(this.sourceHandle);
		const b = this.target.getHandleEl(this.targetHandle);
		if(!a || !b) return this;
		let p1 = this.canvas.handleViewportCenter(a);
		const p2 = this.canvas.handleViewportCenter(b);
		// Branch handles sit on the right edge. Start just beyond the outlet circle so the line/marker stays clear.
		if(this.sourceHandle && this.sourceHandle.startsWith('branch:'))
			p1 = { x: p1.x + 8, y: p1.y };

		// Build the path with a control axis following the dominant delta (horizontal vs vertical flow).
		// The SVG marker automatically orients itself to the straight chord or bezier end tangent.
		let d;
		const dx = p2.x - p1.x, dy = p2.y - p1.y;
		if(this.opts.straight) {
			d = CerbUI.NodeEdge.lineD(p1, p2);
		} else if(Math.abs(dx) >= Math.abs(dy)) {
			const c = Math.max(40, Math.abs(dx) * 0.5) * (dx < 0 ? -1 : 1);
			d = 'M ' + p1.x + ' ' + p1.y + ' C ' + (p1.x + c) + ' ' + p1.y + ', ' + (p2.x - c) + ' ' + p2.y + ', ' + p2.x + ' ' + p2.y;
		} else {
			const c = Math.max(40, Math.abs(dy) * 0.5) * (dy < 0 ? -1 : 1);
			d = 'M ' + p1.x + ' ' + p1.y + ' C ' + p1.x + ' ' + (p1.y + c) + ', ' + p2.x + ' ' + (p2.y - c) + ', ' + p2.x + ' ' + p2.y;
		}
		this.line.setAttribute('d', d);
		this.hit.setAttribute('d', d);
		if(this.arrowHit) { this.arrowHit.setAttribute('cx', p2.x); this.arrowHit.setAttribute('cy', p2.y); }

		return this;
	}

	setSelected(on) { this.el.classList.toggle('cerb-ui-node-edge--selected', !!on); return this; }
	setState(state) {
		this.el.classList.remove('cerb-ui-node-edge--valid', 'cerb-ui-node-edge--invalid');
		if(state) this.el.classList.add('cerb-ui-node-edge--' + state);
		return this;
	}

	destroy() {
		if(this.el && this.el.parentNode) this.el.parentNode.removeChild(this.el);
		if(this.el) CerbUI.NodeEdge._instances.delete(this.el);
	}
};
