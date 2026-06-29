/*
 * CerbUI.DiffViewer — a read-only, side-by-side KATA diff (the plain-JS replacement for the ace-diff viewer in
 * the record-changeset "change history" popup). Two read-only CerbUI.KataEditor panes (left = a historical
 * version, right = the current value) with per-line add/remove tints and IDEA-style bezier connectors drawn in a
 * center gutter. It's a VIEWER, not a merge tool: no editing, no merge arrows/checkboxes. A "Restore this
 * version" host button copies the shown left (historical) document back into the source editor via onRestore.
 *
 * The diff is computed CLIENT-SIDE — a line-level LCS over the two documents (no server round-trip, no external
 * library). KATA docs are small, so the O(n·m) LCS is cheap; a guard falls back to a whole-document replace for
 * pathologically large inputs.
 *
 * Markup: the component builds its own DOM into the host element (it only needs an empty container):
 *   <div class="cerb-ui-diffviewer">
 *     <div class="cerb-ui-diffviewer--pane …--left">   … a .cerb-ui-kataeditor … </div>
 *     <svg class="cerb-ui-diffviewer--connector"></svg>
 *     <div class="cerb-ui-diffviewer--pane …--right">  … a .cerb-ui-kataeditor … </div>
 *   </div>
 *
 * Usage:
 *   const viewer = new CerbUI.DiffViewer(el, {
 *     left:  historicalKata,         // left pane content (changeset)
 *     right: currentKata,            // right pane content (the live field value)
 *     lines: 24,                     // fixed visible height in rows (both panes scroll internally)
 *     onRestore: (content) => {…},   // wired to the host's "Restore this version" button
 *   });
 *   viewer.setLeft(changesetKata);   // swap the historical pane when a changeset row is clicked
 *   viewer.scrollToDiff(i);          // step toolbar: scroll both panes to change-block i
 *
 * Loads AFTER kataeditor.js (it composes CerbUI.KataEditor). CSS lives in cerb.css (.cerb-ui-diffviewer--*).
 */
CerbUI.DiffViewer = class {
	static _SVG_NS = 'http://www.w3.org/2000/svg';
	static _instances = new WeakMap();
	static from(el) { return CerbUI.DiffViewer._instances.get(el); }

	static _DEFAULTS = {
		left: '',          // historical (left) document text
		right: '',         // current (right) document text
		mode: 'kata',      // only 'kata' today (the panes are KataEditors); reserved for future syntaxes
		lines: 24,         // fixed visible height (rows) for both panes; content beyond this scrolls
		onRestore: null,   // (leftContent) => void — the host's "Restore this version" action
	};

	constructor(el, opts = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!el) return;

		this.el = el;
		this.opts = Object.assign({}, CerbUI.DiffViewer._DEFAULTS, opts);
		this.diffs = [];                                          // change blocks {leftStartLine,leftEndLine,rightStart…}
		this._onRestore = (typeof this.opts.onRestore === 'function') ? this.opts.onRestore : null;

		el.classList.add('cerb-ui-diffviewer');
		el.innerHTML = '';

		this._leftEl = this._buildPane('left');
		this._connector = document.createElementNS(CerbUI.DiffViewer._SVG_NS, 'svg');
		this._connector.setAttribute('class', 'cerb-ui-diffviewer--connector');
		this._connector.setAttribute('aria-hidden', 'true');
		this._rightEl = this._buildPane('right');
		el.appendChild(this._leftEl.pane);
		el.appendChild(this._connector);
		el.appendChild(this._rightEl.pane);

		const edOpts = {
			readOnly: true,
			folding: false,                  // 1 model row = 1 view row, so add/remove tints align with the diff
			indentGuides: false,
			minLines: this.opts.lines,       // min === max pins a fixed height; both panes scroll internally
			maxLines: this.opts.lines,
		};
		this.left = new CerbUI.KataEditor(this._leftEl.editor, edOpts);
		this.right = new CerbUI.KataEditor(this._rightEl.editor, edOpts);

		this.left.setValue(CerbUI.DiffViewer._normalize(this.opts.left));
		this.right.setValue(CerbUI.DiffViewer._normalize(this.opts.right));

		// IDEA-style synchronized scroll: scrolling one pane drives the other through a piecewise-linear line map
		// (slope 1 in equal regions, interpolated across a change block), and the connectors redraw. A re-entrancy
		// lock (released next frame) stops the driven pane's own scroll event from mapping back.
		this._syncing = false;
		this._onLeftScroll = () => this._sync(true);
		this._onRightScroll = () => this._sync(false);
		this.left.textarea.addEventListener('scroll', this._onLeftScroll, { passive: true });
		this.right.textarea.addEventListener('scroll', this._onRightScroll, { passive: true });

		CerbUI.DiffViewer._instances.set(el, this);

		this._recompute();
		// If built inside a still-animating dialog the panes have no layout yet (lineHeight/clientHeight unknown);
		// recompute once they're visible so bands + connectors land on real pixels.
		if(typeof CerbUI.editorCore.onFirstReveal === 'function')
			this._revealDisposer = CerbUI.editorCore.onFirstReveal(el, () => { this._revealDisposer = null; this._recompute(); });
	}

	// ── Public API ──────────────────────────────────────────────────────

	// Swap the LEFT (historical) document — e.g. when a changeset row is clicked.
	setLeft(text) { this.left.setValue(CerbUI.DiffViewer._normalize(text)); this._recompute(); return this; }
	// Set the RIGHT (current) document — the live field value the popup compares against.
	setCurrent(text) { this.right.setValue(CerbUI.DiffViewer._normalize(text)); this._recompute(); return this; }

	getLeft() { return this.left.getValue(); }
	getCurrent() { return this.right.getValue(); }

	getDiffs() { return this.diffs; }

	// Step toolbar: scroll BOTH panes so change-block `index` is near the top (a little headroom, like the old viewer).
	scrollToDiff(index) {
		const d = this.diffs[index];
		if(!d) return this;
		let lr = d.leftStartLine, rr = d.rightStartLine;
		if(lr > 5) lr -= 5;
		if(rr > 5) rr -= 5;
		// Drive both panes directly to the block; hold the sync lock through this frame so the resulting scroll
		// events don't re-map one pane off the other.
		this._syncing = true;
		this.left.scrollToLine(lr);
		this.right.scrollToLine(rr);
		this._renderConnectors();
		if(typeof window.requestAnimationFrame === 'function') window.requestAnimationFrame(() => { this._syncing = false; });
		else this._syncing = false;
		return this;
	}

	// Register / replace the "Restore this version" handler (gets the current LEFT document).
	onRestore(cb) { this._onRestore = (typeof cb === 'function') ? cb : null; return this; }
	// Whether a restore handler is registered — lets a host hide its "Restore this version" button for a
	// read-only diff (no onRestore registered → nothing to restore into).
	hasRestore() { return !!this._onRestore; }
	restore() { if(this._onRestore) this._onRestore(this.left.getValue()); return this; }

	destroy() {
		if(this._revealDisposer) { this._revealDisposer(); this._revealDisposer = null; }
		if(this.left) { this.left.textarea.removeEventListener('scroll', this._onLeftScroll); this.left.destroy(); }
		if(this.right) { this.right.textarea.removeEventListener('scroll', this._onRightScroll); this.right.destroy(); }
		CerbUI.DiffViewer._instances.delete(this.el);
	}

	// ── Internals ───────────────────────────────────────────────────────

	_buildPane(side) {
		const pane = document.createElement('div');
		pane.className = 'cerb-ui-diffviewer--pane cerb-ui-diffviewer--' + side;
		const editor = document.createElement('div');
		editor.className = 'cerb-ui-kataeditor';
		editor.innerHTML =
			'<div class="cerb-ui-kataeditor--gutter" aria-hidden="true"></div>' +
			'<div class="cerb-ui-kataeditor--field">' +
				'<div class="cerb-ui-kataeditor--highlight" aria-hidden="true"></div>' +
				'<textarea class="cerb-ui-kataeditor--input" spellcheck="false"></textarea>' +
				'<span class="cerb-ui-kataeditor--caret-anchor"></span>' +
			'</div>';
		pane.appendChild(editor);
		return { pane: pane, editor: editor };
	}

	// Recompute the line diff, repaint both panes' add/remove tints, rebuild the change blocks + connectors.
	_recompute() {
		const leftLines = this.left.getValue().split('\n').length;
		const rightLines = this.right.getValue().split('\n').length;
		const aligned = CerbUI.DiffViewer._diffLines(this.left.getValue(), this.right.getValue());

		const leftDecos = new Map(), rightDecos = new Map();
		for(const op of aligned) {
			if(op.type === 'del') leftDecos.set(op.left, 'cerb-ui-diffviewer--line-removed');
			else if(op.type === 'add') rightDecos.set(op.right, 'cerb-ui-diffviewer--line-added');
		}

		this.diffs = CerbUI.DiffViewer._blocks(aligned);

		// For a pure insertion/deletion the OTHER editor has no lines to tint — draw a thin horizontal rule there,
		// inside that editor, at the boundary row where the change would sit (a normal top rule, or a bottom rule
		// when the change is past the last line).
		for(const d of this.diffs) {
			if(d.leftEndLine === d.leftStartLine) {              // pure insertion → mark the LEFT editor
				if(d.leftStartLine < leftLines) leftDecos.set(d.leftStartLine, 'cerb-ui-diffviewer--gap-added');
				else if(leftLines > 0) leftDecos.set(leftLines - 1, 'cerb-ui-diffviewer--gap-added-end');
			} else if(d.rightEndLine === d.rightStartLine) {     // pure deletion → mark the RIGHT editor
				if(d.rightStartLine < rightLines) rightDecos.set(d.rightStartLine, 'cerb-ui-diffviewer--gap-removed');
				else if(rightLines > 0) rightDecos.set(rightLines - 1, 'cerb-ui-diffviewer--gap-removed-end');
			}
		}

		this.left.setLineDecorations(leftDecos);
		this.right.setLineDecorations(rightDecos);

		this._anchors = this._buildScrollAnchors();
		this._renderConnectors();
		return this;
	}

	_lineHeight(ta) {
		const cs = window.getComputedStyle(ta);
		return parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.5);
	}

	// Draw one filled bezier band per change block, mapping the left line-range to the right line-range. An
	// add-only block has a zero-height left edge (a point) and vice-versa, so the band reads as a wedge — the
	// IDEA-style block map, minus the merge arrows. Colored by block kind (added / removed / changed).
	_renderConnectors() {
		const svg = this._connector;
		while(svg.firstChild) svg.removeChild(svg.firstChild);

		const lta = this.left.textarea, rta = this.right.textarea;
		const h = lta.clientHeight;
		if(!h) return;                                            // not laid out yet (hidden) — onFirstReveal repaints
		const W = svg.clientWidth || 0;
		if(!W) return;

		const lh = this._lineHeight(lta);
		const padTop = parseFloat(window.getComputedStyle(lta).paddingTop) || 0;
		const lScroll = lta.scrollTop, rScroll = rta.scrollTop;

		svg.setAttribute('viewBox', '0 0 ' + W + ' ' + h);
		svg.setAttribute('width', W);
		svg.setAttribute('height', h);
		const xm = W / 2;

		for(const d of this.diffs) {
			const lt = padTop + d.leftStartLine * lh - lScroll;
			const lb = padTop + d.leftEndLine * lh - lScroll;
			const rt = padTop + d.rightStartLine * lh - rScroll;
			const rb = padTop + d.rightEndLine * lh - rScroll;
			if(Math.max(lb, rb) < 0 || Math.min(lt, rt) > h) continue;   // wholly off-screen — skip

			const kind = (d.leftEndLine === d.leftStartLine) ? 'added'
				: (d.rightEndLine === d.rightStartLine) ? 'removed' : 'changed';
			const path = document.createElementNS(CerbUI.DiffViewer._SVG_NS, 'path');
			path.setAttribute('d',
				'M 0,' + lt +
				' C ' + xm + ',' + lt + ' ' + xm + ',' + rt + ' ' + W + ',' + rt +
				' L ' + W + ',' + rb +
				' C ' + xm + ',' + rb + ' ' + xm + ',' + lb + ' 0,' + lb + ' Z');
			path.setAttribute('class', 'cerb-ui-diffviewer--connector-band cerb-ui-diffviewer--connector-' + kind);
			svg.appendChild(path);
		}
	}

	// ── Synchronized scroll ─────────────────────────────────────────────

	// A piecewise-linear map between left and right LINE positions: the anchors are (0,0), then each change block's
	// (start, start) and (end, end), then (leftLines, rightLines). Equal regions get slope 1 (the panes scroll in
	// lockstep); a change block interpolates its line-count difference, so the offset is absorbed across it.
	_buildScrollAnchors() {
		const leftLines = this.left.getValue().split('\n').length;
		const rightLines = this.right.getValue().split('\n').length;
		const anchors = [{ L: 0, R: 0 }];
		for(const d of this.diffs) {
			anchors.push({ L: d.leftStartLine, R: d.rightStartLine });
			anchors.push({ L: d.leftEndLine, R: d.rightEndLine });
		}
		anchors.push({ L: leftLines, R: rightLines });
		return anchors;
	}

	// Map a source scrollTop to the other pane's scrollTop through the anchor map.
	_mapScroll(scrollTop, fromLeft) {
		const srcTa = fromLeft ? this.left.textarea : this.right.textarea;
		const dstTa = fromLeft ? this.right.textarea : this.left.textarea;
		const pos = scrollTop / this._lineHeight(srcTa);          // source line position
		const anchors = this._anchors || [{ L: 0, R: 0 }];
		const key = fromLeft ? 'L' : 'R', oth = fromLeft ? 'R' : 'L';
		let mapped = 0;
		for(let i = 0; i < anchors.length - 1; i++) {
			const a = anchors[i], b = anchors[i + 1];
			if(pos <= b[key] || i === anchors.length - 2) {
				const seg = b[key] - a[key];
				const t = seg > 0 ? (pos - a[key]) / seg : 0;
				mapped = a[oth] + t * (b[oth] - a[oth]);
				break;
			}
		}
		return Math.max(0, mapped * this._lineHeight(dstTa));
	}

	// Drive the other pane from a scroll event, guarded against the resulting echo event mapping back.
	_sync(fromLeft) {
		if(this._syncing) return;
		this._syncing = true;
		const srcTa = fromLeft ? this.left.textarea : this.right.textarea;
		const dstTa = fromLeft ? this.right.textarea : this.left.textarea;
		const target = this._mapScroll(srcTa.scrollTop, fromLeft);
		if(Math.abs(dstTa.scrollTop - target) > 0.5) dstTa.scrollTop = target;
		this._renderConnectors();
		if(typeof window.requestAnimationFrame === 'function') window.requestAnimationFrame(() => { this._syncing = false; });
		else this._syncing = false;
	}

	// ── Static line diff (Myers O(ND)) ──────────────────────────────────

	// Normalize a document for the editors + the diff: coerce to string and fold CRLF/CR to LF (the old ace-diff
	// forced unix newlines too). Without this, a `\r\n`-stored changeset vs a `\n` live value mismatches EVERY line.
	static _normalize(text) { return (text == null ? '' : String(text)).replace(/\r\n?/g, '\n'); }

	// Align two documents by lines. Returns a flat op list in document order:
	//   { type:'eq'|'del'|'add', left, right }  where left/right are the 0-based row each op sits at.
	// A 'del' consumes a LEFT row (right holds at the insertion point); an 'add' consumes a RIGHT row. So within a
	// contiguous run of non-'eq' ops the consumed left rows are contiguous from the first op's `left`, and the
	// consumed right rows contiguous from its `right` — which _blocks() relies on.
	static _diffLines(aText, bText) {
		const a = String(aText).split('\n'), b = String(bText).split('\n');

		// Intern lines to integer ids so the inner Myers loop compares ints, not strings.
		const ids = new Map();
		const idOf = (s) => { let id = ids.get(s); if(id === undefined) { id = ids.size; ids.set(s, id); } return id; };
		const A = a.map(idOf), B = b.map(idOf);

		const types = CerbUI.DiffViewer._diffTypes(A, B);   // ordered 0=eq / 1=del / 2=add

		// Walk the type sequence, assigning the running left/right row each op sits at.
		const out = [];
		let i = 0, j = 0;
		for(const t of types) {
			if(t === 0) { out.push({ type: 'eq', left: i, right: j }); i++; j++; }
			else if(t === 1) { out.push({ type: 'del', left: i, right: j }); i++; }
			else { out.push({ type: 'add', left: i, right: j }); j++; }
		}
		return out;
	}

	// Ordered edit types for two integer sequences. Trims the common prefix + suffix (so near-identical documents
	// reduce to a tiny middle), then runs Myers on the middle. This is the key fix vs a full O(n·m) LCS table:
	// versions of the same KATA doc differ in a handful of lines, so the work + memory stay small even at ~10K lines.
	static _diffTypes(A, B) {
		const N = A.length, M = B.length;
		const head = [];
		let lo = 0;
		while(lo < N && lo < M && A[lo] === B[lo]) { head.push(0); lo++; }
		const tail = [];
		let hiA = N, hiB = M;
		while(hiA > lo && hiB > lo && A[hiA - 1] === B[hiB - 1]) { tail.push(0); hiA--; hiB--; }

		const mid = CerbUI.DiffViewer._myers(A.subarray ? A.subarray(lo, hiA) : A.slice(lo, hiA),
		                                     B.subarray ? B.subarray(lo, hiB) : B.slice(lo, hiB));
		return head.concat(mid, tail);   // tail is all-eq, so order within it is irrelevant
	}

	// Classic Myers shortest-edit-script over two integer arrays -> ordered types (0=eq,1=del,2=add). O(ND) time;
	// the V snapshots are O(D·(N+M)) memory, which is tiny when D (the edit distance) is small — the common case
	// for consecutive document versions. A memory-budgeted cap on D falls back to "replace the middle" for the
	// rare pair that's wildly different (where a precise diff isn't useful anyway).
	static _myers(A, B) {
		const N = A.length, M = B.length;
		if(N === 0) { const o = new Array(M); for(let j = 0; j < M; j++) o[j] = 2; return o; }
		if(M === 0) { const o = new Array(N); for(let i = 0; i < N; i++) o[i] = 1; return o; }

		const MAX = N + M;
		const offset = MAX;
		const size = 2 * MAX + 1;
		// Cap D so the trace can't blow past ~200MB (size ints per snapshot, D+1 snapshots).
		const dCap = Math.max(1, Math.min(MAX, Math.floor(50000000 / size)));

		const v = new Int32Array(size);
		const trace = [];
		let foundD = -1;

		for(let d = 0; d <= dCap; d++) {
			trace.push(Int32Array.from(v));
			for(let k = -d; k <= d; k += 2) {
				let x;
				if(k === -d || (k !== d && v[offset + k - 1] < v[offset + k + 1])) x = v[offset + k + 1];   // down (insert)
				else x = v[offset + k - 1] + 1;                                                             // right (delete)
				let y = x - k;
				while(x < N && y < M && A[x] === B[y]) { x++; y++; }
				v[offset + k] = x;
				if(x >= N && y >= M) { foundD = d; break; }
			}
			if(foundD >= 0) break;
		}

		if(foundD < 0) {   // exceeded the cap — degrade to replace-the-middle
			const o = []; for(let i = 0; i < N; i++) o.push(1); for(let j = 0; j < M; j++) o.push(2); return o;
		}

		// Backtrack through the snapshots to recover the ordered edit (built in reverse).
		const rev = [];
		let x = N, y = M;
		for(let d = foundD; d > 0; d--) {
			const vd = trace[d];
			const k = x - y;
			let prevK;
			if(k === -d || (k !== d && vd[offset + k - 1] < vd[offset + k + 1])) prevK = k + 1;
			else prevK = k - 1;
			const prevX = vd[offset + prevK];
			const prevY = prevX - prevK;
			while(x > prevX && y > prevY) { rev.push(0); x--; y--; }   // diagonal (equal lines)
			if(x === prevX) { rev.push(2); y--; }                      // down move -> an added (right) line
			else { rev.push(1); x--; }                                // right move -> a deleted (left) line
		}
		while(x > 0 && y > 0) { rev.push(0); x--; y--; }              // d=0 leading diagonal
		while(x > 0) { rev.push(1); x--; }
		while(y > 0) { rev.push(2); y--; }
		rev.reverse();
		return rev;
	}

	// Collapse the op list into change blocks: each maximal run of non-'eq' ops -> a left line-span [start,end) and
	// a right line-span [start,end). dels in the run count toward the left span, adds toward the right.
	static _blocks(aligned) {
		const blocks = [];
		let cur = null;
		for(const op of aligned) {
			if(op.type === 'eq') { if(cur) { blocks.push(cur); cur = null; } continue; }
			if(!cur) cur = { leftStartLine: op.left, rightStartLine: op.right, dels: 0, adds: 0 };
			if(op.type === 'del') cur.dels++; else cur.adds++;
		}
		if(cur) blocks.push(cur);
		return blocks.map(b => ({
			leftStartLine: b.leftStartLine,
			leftEndLine: b.leftStartLine + b.dels,
			rightStartLine: b.rightStartLine,
			rightEndLine: b.rightStartLine + b.adds,
		}));
	}
};
