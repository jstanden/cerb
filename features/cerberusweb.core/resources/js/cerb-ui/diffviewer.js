/*
 * CerbUI.DiffViewer — a read-only, side-by-side KATA diff (the plain-JS replacement for the ace-diff viewer in
 * the record-changeset "change history" popup). Two read-only CerbUI.KataEditor panes (left = a historical
 * version, right = the current value) with per-line add/remove tints and IDEA-style bezier connectors drawn in a
 * center gutter. Read-only by default (a viewer, no merge arrows/checkboxes); opt into `editableCurrent` to make
 * the RIGHT pane editable so the diff re-computes live as you type (a light manual-merge affordance — read the
 * result back with getCurrent()). A "Restore this version" host button copies the shown left (historical) document
 * back into the source editor via onRestore.
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
 *     editableCurrent: true,         // make the right pane editable; diff re-computes live (read via getCurrent())
 *     onChange: (content) => {…},    // fired after an edit re-computes the diff
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
		editableCurrent: false, // make the RIGHT ("current") pane editable → the diff re-computes live as you type
		                        // (a light manual-merge affordance); read it back via getCurrent()
		onChange: null,    // (rightContent) => void — fired after an edit re-computes the diff (editableCurrent only)
		onRestore: null,   // (leftContent) => void — the host's "Restore this version" action
		collapseUnchanged: false, // false | true | {context:3} — elide long runs of identical lines behind a
		                          // clickable "tear" divider (click reveals that run). Gutter numbers keep
		                          // printing MODEL rows, so they jump across a tear (1,2,3…47,48).
		dragKeys: false,   // hover a key in the RIGHT (current) pane to float a drag handle — see KataEditor's
		                   // dragKeys. Right only: the left is a BEFORE document, so a key that the diff shows as
		                   // removed no longer exists to reference. Set the pane's opts.onKeyClick to handle a click.
	};

	// A run must hide at least this many lines to be worth a tear.
	static _MIN_ELIDE = 2;
	static _DEFAULT_CONTEXT = 3;

	constructor(el, opts = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!el) return;

		this.el = el;
		this.opts = Object.assign({}, CerbUI.DiffViewer._DEFAULTS, opts);
		this.diffs = [];                                          // change blocks {leftStartLine,leftEndLine,rightStart…}
		this._onRestore = (typeof this.opts.onRestore === 'function') ? this.opts.onRestore : null;
		this._runs = [];                                          // elided unchanged runs currently applied
		this._expanded = new Set();                               // run keys the user clicked open (survive recompute)
		this._tears = [];                                         // the clickable tear elements, rebuilt per recompute

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
		// The right pane can opt into being editable (manual merge); the left ("historical/before") stays read-only.
		this.right = new CerbUI.KataEditor(this._rightEl.editor, Object.assign({}, edOpts, {
			readOnly: !this.opts.editableCurrent,
			dragKeys: !!this.opts.dragKeys,
		}));

		this.left.setValue(CerbUI.DiffViewer._normalize(this.opts.left));
		this.right.setValue(CerbUI.DiffViewer._normalize(this.opts.right));

		// Editable right pane → recompute the diff on every edit (rAF-coalesced) so tints/connectors track the text,
		// and relay the new value to the host. The pane gets a marker class for optional styling.
		if(this.opts.editableCurrent) {
			this._onChange = (typeof this.opts.onChange === 'function') ? this.opts.onChange : null;
			this._rightEl.pane.classList.add('cerb-ui-diffviewer--editable');
			this.right.onChange(() => {
				if(this._recomputeRaf) return;
				this._recomputeRaf = requestAnimationFrame(() => {
					this._recomputeRaf = 0;
					this._recompute();
					if(this._onChange) this._onChange(this.right.getValue());
				});
			});
		}

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
		// Back off a little for headroom — but not when collapsed: the context lines already provide it, and
		// scrollToLine reveals whatever it lands on, so backing into an elided run would silently expand it.
		if(!this.opts.collapseUnchanged) {
			if(lr > 5) lr -= 5;
			if(rr > 5) rr -= 5;
		}
		// Drive both panes directly to the block; hold the sync lock through this frame so the resulting scroll
		// events don't re-map one pane off the other.
		this._syncing = true;
		this.left.scrollToLine(lr);
		this.right.scrollToLine(rr);
		this._renderConnectors();
		this._positionTears();
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

		// Elide unchanged runs (opt-in). Decorations above are MODEL-keyed, so hiding rows after them is safe —
		// _renderLineDecorations just skips a row with no view row.
		if(this.opts.collapseUnchanged) {
			// Keep EVERY candidate run — an expanded one still needs its seam rendered, or re-collapsing it
			// becomes impossible (the tear is the only handle). Only the hidden set is filtered.
			this._runs = this._collapseRuns(aligned);
			const collapsed = this._runs.filter(r => !this._expanded.has(r.key));
			this.left.setHiddenRanges(collapsed.map(r => r.left));
			this.right.setHiddenRanges(collapsed.map(r => r.right));
		}

		this._anchors = this._buildScrollAnchors();
		this._renderConnectors();
		this._renderTears();
		return this;
	}

	// The unchanged runs worth eliding, as {key, left:{startRow,endRow}, right:{startRow,endRow}}.
	//
	// Every 'eq' op advances BOTH sides by one (see _diffLines), so a run's left and right spans are always the
	// same length — eliding them hides an equal number of rows from each pane, which is what keeps the two panes
	// aligned. That's a property of the diff, not something this code arranges; _assertSymmetric holds it honest.
	_collapseRuns(aligned) {
		const cfg = this.opts.collapseUnchanged;
		if(!cfg) return [];
		const ctx = Math.max(0, (cfg === true || cfg.context == null)
			? CerbUI.DiffViewer._DEFAULT_CONTEXT : (cfg.context | 0));

		const runs = [];
		let i = 0;
		while(i < aligned.length) {
			if(aligned[i].type !== 'eq') { i++; continue; }
			let j = i;
			while(j < aligned.length && aligned[j].type === 'eq') j++;   // aligned[i..j-1] is a maximal eq run

			// Keep `ctx` lines of context beside each neighbouring change. A run against the document's start or
			// end has no change on that side, so it needs no context there.
			let head = (i === 0) ? 0 : ctx;
			const tail = (j === aligned.length) ? 0 : ctx;
			// setHiddenRanges can't hide row 0 (a fold's header row anchors it, and row 0 has nothing above it).
			// Push the window down on BOTH sides together rather than letting each pane clamp independently —
			// that's what would break symmetry.
			while(i + head < j && (aligned[i + head].left < 1 || aligned[i + head].right < 1)) head++;

			if(j - tail - (i + head) >= CerbUI.DiffViewer._MIN_ELIDE) {
				const first = aligned[i + head], last = aligned[j - tail - 1];
				runs.push({
					key: first.left + ':' + first.right,
					left:  { startRow: first.left,  endRow: last.left  },
					right: { startRow: first.right, endRow: last.right },
				});
			}
			i = j;
		}
		return runs;
	}

	// A MODEL line POSITION → its view position, discounting elided rows above it. Not _modelRowToViewRow: this
	// must answer for an exclusive end (a boundary, which may sit on a hidden row) and for hidden rows, where
	// that returns -1.
	_viewPos(ed, modelLine) {
		let hidden = 0;
		for(const r of ed.getHiddenRanges()) {
			if(r.endRow < modelLine) hidden += (r.endRow - r.startRow + 1);
			else if(r.startRow < modelLine) hidden += (modelLine - r.startRow);
		}
		return modelLine - hidden;
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
			// Block lines are MODEL rows; with collapse on, elided rows above shift where they actually paint.
			const lt = padTop + this._viewPos(this.left, d.leftStartLine) * lh - lScroll;
			const lb = padTop + this._viewPos(this.left, d.leftEndLine) * lh - lScroll;
			const rt = padTop + this._viewPos(this.right, d.rightStartLine) * lh - rScroll;
			const rb = padTop + this._viewPos(this.right, d.rightEndLine) * lh - rScroll;
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

	// One clickable "tear" per elided run, per pane — a perforated divider standing in for the hidden lines.
	//
	// It has to be a real element ABOVE the textarea (--input is z-index:1): the obvious home would be a
	// --line-deco band, but those paint BEHIND the mirror text at z-index:-1 with pointer-events:none, so they
	// can't be clicked. Same stacking trick the KataEditor dragKeys handle uses.
	_renderTears() {
		this._tears.forEach(t => t.remove());
		this._tears = [];
		if(!this.opts.collapseUnchanged || !this._runs.length) return;

		for(const [ed, key] of [[this.left, 'left'], [this.right, 'right']]) {
			const field = ed.el.querySelector('.cerb-ui-kataeditor--field');
			if(!field) continue;
			for(const run of this._runs) {
				const hidden = run[key].endRow - run[key].startRow + 1;
				const expanded = this._expanded.has(run.key);
				const tear = document.createElement('div');
				// Expanded → the seam stays put as a solid rule (see the CSS): it's the only way back, and a
				// perforation would claim something is still missing there.
				tear.className = 'cerb-ui-diffviewer--tear' + (expanded ? ' cerb-ui-diffviewer--tear-expanded' : '');
				tear.title = (expanded ? 'Hide ' : 'Show ') + hidden + ' unchanged line' + (hidden === 1 ? '' : 's');
				tear.addEventListener('click', () => {
					if(this._expanded.has(run.key)) this._expanded.delete(run.key); else this._expanded.add(run.key);
					this._recompute();
				});
				field.appendChild(tear);
				this._tears.push(tear);
				tear._run = run; tear._ed = ed; tear._side = key;
			}
		}
		this._positionTears();
	}

	// Park each tear on the boundary BELOW its last visible context line (the fold's anchor row), and track the
	// pane's scroll — same geometry as the connectors, so they move together.
	_positionTears() {
		for(const tear of this._tears) {
			const ed = tear._ed, run = tear._run[tear._side];
			const ta = ed.textarea;
			const lh = this._lineHeight(ta);
			const padTop = parseFloat(window.getComputedStyle(ta).paddingTop) || 0;
			const vr = this._viewPos(ed, run.startRow);              // the elided run collapses to this boundary
			const y = padTop + vr * lh - ta.scrollTop;
			tear.style.top = y + 'px';                               // CSS centers it on the boundary (translateY)
			tear.hidden = (y < -8 || y > ta.clientHeight + 8);       // scrolled out of the pane
		}
	}

	// ── Synchronized scroll ─────────────────────────────────────────────

	// A piecewise-linear map between left and right LINE positions: the anchors are (0,0), then each change block's
	// (start, start) and (end, end), then (leftLines, rightLines). Equal regions get slope 1 (the panes scroll in
	// lockstep); a change block interpolates its line-count difference, so the offset is absorbed across it.
	// Anchors are VIEW line positions, because _mapScroll compares them against scrollTop/lineHeight. Without
	// collapse the two spaces are identical (folding is off); with it, elided rows must be discounted.
	_buildScrollAnchors() {
		const leftLines = this.left.textarea.value.split('\n').length;    // the projection, not the model
		const rightLines = this.right.textarea.value.split('\n').length;
		const anchors = [{ L: 0, R: 0 }];
		for(const d of this.diffs) {
			anchors.push({ L: this._viewPos(this.left, d.leftStartLine), R: this._viewPos(this.right, d.rightStartLine) });
			anchors.push({ L: this._viewPos(this.left, d.leftEndLine), R: this._viewPos(this.right, d.rightEndLine) });
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
		this._positionTears();
		if(typeof window.requestAnimationFrame === 'function') window.requestAnimationFrame(() => { this._syncing = false; });
		else this._syncing = false;
	}

	// ── Static line diff (Myers O(ND)) ──────────────────────────────────
	// The engine moved to CerbUI.editorCore.lineDiff so the editors' gutter-diff feature can reach it at
	// construction time (editor-core.js loads before this file). These stay as thin delegations — the names the
	// rest of this class calls (_normalize/_diffLines/_blocks) are unchanged.

	static _normalize(text) { return CerbUI.editorCore.lineDiff.normalize(text); }
	static _diffLines(aText, bText) { return CerbUI.editorCore.lineDiff.diffLines(aText, bText); }
	static _blocks(aligned) { return CerbUI.editorCore.lineDiff.blocks(aligned); }
};
