/*
 * CerbUI.NodeCanvas — the pannable / zoomable surface that hosts nodes and the SVG edge-layer.
 *
 * It wraps a container element with: a large absolutely-positioned `viewport` layer (translated + scaled as one for
 * pan/zoom) and, inside it, an <svg> edge-layer beneath the node <div>s. Nodes store *logical* coordinates; the
 * viewport transform maps logical → screen, so a single CSS transform moves/zooms everything coherently. The canvas
 * also acts as the drop zone (via CerbUI.Droppable, owned by the editor) for palette tiles dragged in from the sidebar.
 *
 * Coordinate model (mirrors js-flow's Canvas, src/core/canvas.js): the viewport is OFFSET×2 square offset by -OFFSET
 * so logical (0,0) sits at its center. A point inside the viewport at "viewport space" vs maps to the container as
 *   container = -OFFSET + transform + vs * scale          (so vs = logical + OFFSET)
 * which inverts to  vs = (container + OFFSET - transform) / scale. Node DOM left/top and the SVG path coordinates are
 * both in viewport space (= logical + OFFSET); only serialization & drop placement use bare logical coords.
 * Theme-aware: the surface uses --cerb-color-* tokens rather than js-flow's hard-coded near-black.
 *
 *   const canvas = new CerbUI.NodeCanvas(el, { onTransform:(t)=>{}, minScale:0.1, maxScale:3 });
 *   canvas.appendNode(nodeEl);  canvas.edgeLayer;  canvas.screenToCanvas({x,y});  // client → logical
 *   canvas.handleViewportCenter(handleEl);  // a handle's center in viewport space (for edge routing)
 *   canvas.pan(dx,dy);  canvas.zoomAt(clientX,clientY,scale);  canvas.zoomIn();  canvas.fitToNodes(nodes);
 */
CerbUI.NodeCanvas = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.NodeCanvas._instances.get(el); }

	static OFFSET = 5000;   // viewport half-extent: logical (0,0) sits at the viewport's center
	static _seq = 0;        // for unique per-canvas arrowhead marker ids

	static _DEFAULTS = {
		minScale: 0.2,
		maxScale: 2.5,
		onTransform: null,    // (transform) => {}  after any pan/zoom
		onBackgroundDown: null, // (event) => {}  mousedown on empty canvas (editor clears selection / could pan)
		minimap: false,       // show a bottom-right minimap (rects + viewport box, click/drag to pan)
	};

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.opts = Object.assign({}, CerbUI.NodeCanvas._DEFAULTS, opts);
		this.transform = { x: 0, y: 0, scale: 1 };
		this.el.classList.add('cerb-ui-node-canvas');
		CerbUI.NodeCanvas._instances.set(this.el, this);
		CerbUI.NodeCanvas._installWheelTracker();

		// The pan/zoom layer; logical (0,0) is its center thanks to the -OFFSET CSS offset (see _node-canvas.scss).
		this.viewport = document.createElement('div');
		this.viewport.className = 'cerb-ui-node-canvas--viewport';

		// Edges render beneath nodes, in the same viewport space, so one transform moves both.
		this.edgeLayer = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		this.edgeLayer.classList.add('cerb-ui-node-canvas--edges');

		// A shared arrowhead marker (unique id per canvas) for edge target ends.
		this.markerId = 'cerb-ui-node-arrow-' + (++CerbUI.NodeCanvas._seq);
		const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
		const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
		marker.setAttribute('id', this.markerId);
		marker.setAttribute('markerWidth', '8');
		marker.setAttribute('markerHeight', '8');
		marker.setAttribute('refX', '6');
		marker.setAttribute('refY', '3');
		marker.setAttribute('orient', 'auto');
		marker.setAttribute('markerUnits', 'strokeWidth');
		const tip = document.createElementNS('http://www.w3.org/2000/svg', 'path');
		tip.setAttribute('d', 'M0,0 L6,3 L0,6 z');
		marker.appendChild(tip);
		defs.appendChild(marker);
		this.edgeLayer.appendChild(defs);

		this.viewport.appendChild(this.edgeLayer);
		this.el.appendChild(this.viewport);

		this._bindPanZoom();
		if(this.opts.minimap) this._buildMinimap();
		this.updateTransform();
	}

	// ── Coordinate helpers ──────────────────────────────────────────────

	// A client (mouse) point → viewport space (= logical + OFFSET): what node left/top and SVG paths use.
	clientToViewport(clientX, clientY) {
		const r = this.el.getBoundingClientRect();
		const s = this.transform.scale;
		return {
			x: (clientX - r.left + CerbUI.NodeCanvas.OFFSET - this.transform.x) / s,
			y: (clientY - r.top  + CerbUI.NodeCanvas.OFFSET - this.transform.y) / s,
		};
	}

	// A client point → bare logical coordinates (for node placement on drop + serialization).
	screenToCanvas(point) {
		const vp = this.clientToViewport(point.x, point.y);
		return { x: vp.x - CerbUI.NodeCanvas.OFFSET, y: vp.y - CerbUI.NodeCanvas.OFFSET };
	}

	// The center of a handle element in viewport space (robust to layout/scale — measured live).
	handleViewportCenter(handleEl) {
		const r = handleEl.getBoundingClientRect();
		return this.clientToViewport(r.left + r.width / 2, r.top + r.height / 2);
	}

	// ── Pan / zoom ──────────────────────────────────────────────────────

	_bindPanZoom() {
		this._onDown = (e) => {
			if(e.button !== 0) return;
			const t = e.target;
			// Nodes / handles / edges stopPropagation on their own mousedown, so reaching here = empty canvas.
			if(t.closest && t.closest('.cerb-ui-node, .cerb-ui-node--handle')) return;
			if(typeof this.opts.onBackgroundDown === 'function') this.opts.onBackgroundDown(e);

			this._panning = true;
			this._panLast = { x: e.clientX, y: e.clientY };
			this.el.classList.add('cerb-ui-node-canvas--panning');
			e.preventDefault();
			document.addEventListener('mousemove', this._onMove);
			document.addEventListener('mouseup', this._onUp);
		};
		this._onMove = (e) => {
			if(!this._panning) return;
			this.pan(e.clientX - this._panLast.x, e.clientY - this._panLast.y);
			this._panLast = { x: e.clientX, y: e.clientY };
		};
		this._onUp = () => {
			this._panning = false;
			this.el.classList.remove('cerb-ui-node-canvas--panning');
			document.removeEventListener('mousemove', this._onMove);
			document.removeEventListener('mouseup', this._onUp);
		};
		this._onWheel = (e) => {
			// Only zoom on a wheel gesture that STARTED over this canvas (see _installWheelTracker). A page-scroll
			// gesture passing over the canvas keeps scrolling the page instead of being hijacked into a zoom.
			if(CerbUI.NodeCanvas._wheelOwner !== this.el) return;
			e.preventDefault();
			const factor = e.deltaY > 0 ? 0.9 : 1.1;
			this.zoomAt(e.clientX, e.clientY, this.transform.scale * factor);
		};
		this.el.addEventListener('mousedown', this._onDown);
		this.el.addEventListener('wheel', this._onWheel, { passive: false });
	}

	// A wheel "gesture" is a burst of wheel events; a quiet gap starts a new one. One document-level capture
	// listener (installed once) decides, at each gesture's start, which canvas (if any) is under the cursor and
	// stores it as the owner for the whole burst — so a canvas scrolling under a stationary cursor mid-momentum
	// can't steal a page scroll. Capture phase runs before per-canvas handlers + any container stopPropagation.
	static _installWheelTracker() {
		if(CerbUI.NodeCanvas._wheelTracker) return;
		CerbUI.NodeCanvas._wheelTracker = true;
		CerbUI.NodeCanvas._wheelTs = 0;
		CerbUI.NodeCanvas._wheelOwner = null;
		document.addEventListener('wheel', (e) => {
			const now = e.timeStamp || 0;
			if(now - CerbUI.NodeCanvas._wheelTs > 200) {   // ms gap → a new gesture
				const t = e.target;
				CerbUI.NodeCanvas._wheelOwner = (t && t.closest) ? t.closest('.cerb-ui-node-canvas') : null;
			}
			CerbUI.NodeCanvas._wheelTs = now;
		}, { capture: true, passive: true });
	}

	pan(dx, dy) {
		this.transform.x += dx;
		this.transform.y += dy;
		this.updateTransform();
		return this;
	}

	// Zoom toward a client point, keeping the viewport-space point under the cursor stationary.
	zoomAt(clientX, clientY, newScale) {
		newScale = Math.max(this.opts.minScale, Math.min(this.opts.maxScale, newScale));
		const r = this.el.getBoundingClientRect();
		const cx = clientX - r.left, cy = clientY - r.top;
		const O = CerbUI.NodeCanvas.OFFSET;
		// viewport-space point currently under the cursor
		const vsX = (cx + O - this.transform.x) / this.transform.scale;
		const vsY = (cy + O - this.transform.y) / this.transform.scale;
		this.transform.x = cx + O - vsX * newScale;
		this.transform.y = cy + O - vsY * newScale;
		this.transform.scale = newScale;
		this.updateTransform();
		return this;
	}

	zoomIn()  { const r = this.el.getBoundingClientRect(); return this.zoomAt(r.left + r.width / 2, r.top + r.height / 2, this.transform.scale * 1.2); }
	zoomOut() { const r = this.el.getBoundingClientRect(); return this.zoomAt(r.left + r.width / 2, r.top + r.height / 2, this.transform.scale * 0.8); }

	// Frame all nodes (each {position:{x,y}, el}) centered in the container with padding.
	fitToNodes(nodes) {
		if(!nodes || !nodes.length) return this;
		let x0 = Infinity, y0 = Infinity, x1 = -Infinity, y1 = -Infinity;
		nodes.forEach(n => {
			if(!n.el) return;
			const w = n.el.offsetWidth || 180, h = n.el.offsetHeight || 80;
			x0 = Math.min(x0, n.position.x);       y0 = Math.min(y0, n.position.y);
			x1 = Math.max(x1, n.position.x + w);    y1 = Math.max(y1, n.position.y + h);
		});
		if(!isFinite(x0)) return this;

		const r = this.el.getBoundingClientRect();
		const pad = 60, O = CerbUI.NodeCanvas.OFFSET;
		const bw = (x1 - x0) + pad * 2, bh = (y1 - y0) + pad * 2;
		const scale = Math.max(this.opts.minScale, Math.min(this.opts.maxScale, Math.min(r.width / bw, r.height / bh, 1)));
		const vsCenterX = (x0 + x1) / 2 + O, vsCenterY = (y0 + y1) / 2 + O;
		this.transform.scale = scale;
		this.transform.x = r.width / 2 + O - vsCenterX * scale;
		this.transform.y = r.height / 2 + O - vsCenterY * scale;
		this.updateTransform();
		return this;
	}

	// Focus a set of nodes at a FIXED scale (default 1 = 100%) — unlike fitToNodes it never shrinks to fit. The
	// bbox is centered horizontally and top-aligned (its top ~40px below the viewport top) so a tall flow shows
	// its start and reads downward.
	focusNodes(nodes, scale) {
		if(!nodes || !nodes.length) return this;
		scale = Math.max(this.opts.minScale, Math.min(this.opts.maxScale, scale || 1));
		let x0 = Infinity, y0 = Infinity, x1 = -Infinity;
		nodes.forEach(n => {
			if(!n.el) return;
			const w = n.el.offsetWidth || 180;
			x0 = Math.min(x0, n.position.x);    y0 = Math.min(y0, n.position.y);
			x1 = Math.max(x1, n.position.x + w);
		});
		if(!isFinite(x0)) return this;

		const r = this.el.getBoundingClientRect();
		const O = CerbUI.NodeCanvas.OFFSET, padTop = 40;
		const vsCenterX = (x0 + x1) / 2 + O, vsTop = y0 + O;
		this.transform.scale = scale;
		this.transform.x = r.width / 2 + O - vsCenterX * scale;
		this.transform.y = padTop + O - vsTop * scale;
		this.updateTransform();
		return this;
	}

	updateTransform() {
		if(this.viewport)
			this.viewport.style.transform = 'translate(' + this.transform.x + 'px,' + this.transform.y + 'px) scale(' + this.transform.scale + ')';
		if(typeof this.opts.onTransform === 'function') this.opts.onTransform(this.transform);
		if(this._minimap) this._renderMinimap();
		return this;
	}

	appendNode(nodeEl) { if(this.viewport) this.viewport.appendChild(nodeEl); return this; }

	// ── Minimap ─────────────────────────────────────────────────────────
	// A proportional overview: one filled rect per top-level node + a stroked rectangle for the visible area.
	// Click/drag inside it pans the main view. Optional, lower-right, with a minimize toggle.

	_buildMinimap() {
		const box = document.createElement('div');
		// Start collapsed — it does no rendering work until expanded (see _renderMinimap's early return).
		box.className = 'cerb-ui-node-canvas--minimap cerb-ui-node-canvas--minimap-minimized';
		const cv = document.createElement('canvas');
		cv.className = 'cerb-ui-node-canvas--minimap-canvas';
		const toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.className = 'cerb-ui-node-canvas--minimap-toggle';
		toggle.title = 'Toggle minimap';
		const toggleIcon = document.createElement('span');
		toggleIcon.className = 'cerb-icons cerb-icon-map';   // map glyph when minimized, minus when shown
		toggle.appendChild(toggleIcon);
		box.appendChild(cv);
		box.appendChild(toggle);
		this.el.appendChild(box);
		this._minimap = { box: box, canvas: cv, toggle: toggle, bbox: null, scale: 1 };

		// The minimap owns its own pointer events — don't let them pan/zoom the main canvas.
		box.addEventListener('wheel', (e) => e.stopPropagation());
		toggle.addEventListener('mousedown', (e) => e.stopPropagation());
		toggle.addEventListener('click', (e) => {
			e.stopPropagation();
			const min = box.classList.toggle('cerb-ui-node-canvas--minimap-minimized');
			toggleIcon.className = 'cerb-icons cerb-icon-' + (min ? 'map' : 'minus');
			if(!min) this._renderMinimap();
		});

		// Click / drag anywhere on the map → recenter the main view on that content point.
		const panTo = (e) => {
			const m = this._minimap; if(!m || !m.bbox) return;
			const r = m.canvas.getBoundingClientRect();
			const lx = e.clientX - r.left, ly = e.clientY - r.top;
			const ptX = m.bbox.x0 + (lx - m.padX) / m.scale, ptY = m.bbox.y0 + (ly - m.padY) / m.scale;
			const cr = this.el.getBoundingClientRect(), O = CerbUI.NodeCanvas.OFFSET, s = this.transform.scale;
			this.transform.x = cr.width / 2 + O - ptX * s;
			this.transform.y = cr.height / 2 + O - ptY * s;
			this.updateTransform();
		};
		cv.addEventListener('mousedown', (e) => {
			if(e.button !== 0) return;
			e.stopPropagation(); e.preventDefault();
			panTo(e);
			const mv = (ev) => panTo(ev);
			const up = () => { document.removeEventListener('mousemove', mv); document.removeEventListener('mouseup', up); };
			document.addEventListener('mousemove', mv);
			document.addEventListener('mouseup', up);
		});
	}

	refreshMinimap() { if(this._minimap) this._renderMinimap(); return this; }

	_renderMinimap() {
		const m = this._minimap;
		if(!m || m.box.classList.contains('cerb-ui-node-canvas--minimap-minimized')) return;
		if(this._miniRaf) return;
		this._miniRaf = requestAnimationFrame(() => { this._miniRaf = null; this._drawMinimap(); });
	}

	_drawMinimap() {
		const m = this._minimap;
		if(!m) return;
		const cv = m.canvas, ctx = cv.getContext('2d');
		const cssW = cv.clientWidth || 190, cssH = cv.clientHeight || 130;
		const dpr = window.devicePixelRatio || 1;
		if(cv.width !== Math.round(cssW * dpr) || cv.height !== Math.round(cssH * dpr)) {
			cv.width = Math.round(cssW * dpr); cv.height = Math.round(cssH * dpr);
		}
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		ctx.clearRect(0, 0, cssW, cssH);

		// Top-level node rects (viewport space). Contained children live inside their parent's rect.
		const rects = [];
		let x0 = Infinity, y0 = Infinity, x1 = -Infinity, y1 = -Infinity;
		const kids = this.viewport ? this.viewport.querySelectorAll(':scope > .cerb-ui-node') : [];
		kids.forEach((el) => {
			const x = el.offsetLeft, y = el.offsetTop, w = el.offsetWidth, h = el.offsetHeight;
			rects.push({ x: x, y: y, w: w, h: h });
			x0 = Math.min(x0, x); y0 = Math.min(y0, y); x1 = Math.max(x1, x + w); y1 = Math.max(y1, y + h);
		});
		if(!isFinite(x0)) { m.bbox = null; return; }

		const pad = 8;
		const bw = (x1 - x0) || 1, bh = (y1 - y0) || 1;
		const scale = Math.min((cssW - pad * 2) / bw, (cssH - pad * 2) / bh);
		const padX = pad + ((cssW - pad * 2) - bw * scale) / 2;
		const padY = pad + ((cssH - pad * 2) - bh * scale) / 2;
		m.bbox = { x0: x0, y0: y0, x1: x1, y1: y1 };
		m.scale = scale; m.padX = padX; m.padY = padY;
		const mapX = (x) => padX + (x - x0) * scale;
		const mapY = (y) => padY + (y - y0) * scale;

		ctx.fillStyle = this._cssVar('--cerb-color-background-contrast-180', '#888');
		rects.forEach((r) => ctx.fillRect(mapX(r.x), mapY(r.y), Math.max(1, r.w * scale), Math.max(1, r.h * scale)));

		// Visible viewport rectangle (the canvas corners mapped to viewport space).
		const cr = this.el.getBoundingClientRect();
		const tl = this.clientToViewport(cr.left, cr.top), br = this.clientToViewport(cr.right, cr.bottom);
		ctx.strokeStyle = this._cssVar('--cerb-color-link', '#3b82f6');
		ctx.lineWidth = 1.5;
		ctx.strokeRect(mapX(tl.x), mapY(tl.y), (br.x - tl.x) * scale, (br.y - tl.y) * scale);
	}

	_cssVar(name, fallback) {
		const v = getComputedStyle(this.el).getPropertyValue(name);
		return (v && v.trim()) || fallback;
	}

	destroy() {
		if(this._miniRaf) { cancelAnimationFrame(this._miniRaf); this._miniRaf = null; }
		if(this._minimap && this._minimap.box && this._minimap.box.parentNode) this._minimap.box.parentNode.removeChild(this._minimap.box);
		if(this.el) {
			this.el.removeEventListener('mousedown', this._onDown);
			this.el.removeEventListener('wheel', this._onWheel);
			CerbUI.NodeCanvas._instances.delete(this.el);
		}
		document.removeEventListener('mousemove', this._onMove);
		document.removeEventListener('mouseup', this._onUp);
	}
};
