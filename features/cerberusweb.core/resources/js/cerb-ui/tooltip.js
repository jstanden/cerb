/*
 * CerbUI.Tooltip — a floating panel positioned in the viewport (position:fixed). Two modes:
 *
 *  - point mode  (show/move): sit above a viewport point, flip below / clamp horizontally to stay
 *    on-screen. No arrow, pointer-events disabled. Used for chart hover details (CerbUI.Sparkchart).
 *
 *  - anchored mode (anchor): pin to a DOM element, auto-picking the side with the most room (flip-fit,
 *    like CerbUI.Menu) and drawing an SVG arrow that points back at the element. Interactive by default
 *    (dismiss on click or outside-click). Powers automation callouts (Devblocks.tooltip).
 *
 * Usage:
 *   const tip = new CerbUI.Tooltip();
 *   tip.show(nodeOrHtml, clientX, clientY, owner);   // point mode: set content + position + reveal
 *   tip.move(clientX, clientY);                       // point mode: reposition (same content)
 *   tip.anchor(nodeOrHtml, targetEl, {my, at, interactive}); // anchored mode: pin to an element w/ an arrow
 *   tip.hide();
 *
 * Pass the triggering element as `owner` (or, in anchored mode, the target) and the tooltip auto-hides if
 * that node leaves the DOM (e.g. a worklist refresh swaps the chart out from under the cursor — no
 * mouseleave fires, so it would otherwise orphan on-screen with no way to dismiss it).
 *
 * In point mode pointer-events are disabled so the tooltip never steals hover from whatever triggered it.
 * Reuse one instance per trigger/widget rather than creating one per show.
 */
CerbUI.Tooltip = class {
	static _SVG_NS = 'http://www.w3.org/2000/svg';
	static _ARROW_LONG = 16;  // px along the panel edge
	static _ARROW_SHORT = 8;  // px the arrow protrudes toward the target
	static _ARROW_OVERLAP = 1; // px the arrow base tucks into the panel (hides the base seam)
	static _CORNER = 8;       // px border-radius to keep the arrow clear of (matches the CSS)

	constructor(options = {}) {
		this.gap = (options.gap != null) ? options.gap : 10; // px between the target/point and the panel
		this.el = document.createElement('div');
		this.el.className = 'cerb-ui-tooltip';
		this.el.setAttribute('role', 'tooltip');
		this.el.hidden = true;

		// One reusable SVG arrow, hidden until anchored mode positions it. An *open* path: the fill renders
		// the triangle while the stroke draws only the two legs, so the base merges into the panel edge.
		const NS = CerbUI.Tooltip._SVG_NS;
		this.arrow = document.createElementNS(NS, 'svg');
		this.arrow.setAttribute('class', 'cerb-ui-tooltip--arrow');
		this.arrow.setAttribute('viewBox', '0 0 16 8');
		this.arrow.setAttribute('aria-hidden', 'true');
		const path = document.createElementNS(NS, 'path');
		path.setAttribute('d', 'M0.5,8 L8,0.5 L15.5,8');
		this.arrow.appendChild(path);
		this.arrow.toggleAttribute('hidden', true); // SVG: no .hidden IDL prop — toggle the attribute
		this.el.appendChild(this.arrow);

		this._onDocDown = null;
		this._onClick = null;
		this._resizeObserver = null;
		this._anchorTarget = null;
		this._anchorOptions = null;

		document.body.appendChild(this.el);
	}

	// ── Point mode (hover) ──────────────────────────────────────────────

	show(content, x, y, owner) {
		this._teardownInteractive();
		if(this._resizeObserver) this._resizeObserver.disconnect(); // drop any anchored-mode resize watch
		this._anchorTarget = null;
		this._anchorOptions = null;
		this.arrow.toggleAttribute('hidden', true); // SVG: no .hidden IDL prop — toggle the attribute
		this.el.classList.remove('cerb-ui-tooltip--interactive');
		this._setContent(content);
		this.el.hidden = false;
		this.move(x, y);
		this._watchOwner(owner || null);
		return this;
	}

	// Reposition relative to a viewport point: above-centered by default, flip below / clamp to stay on-screen
	move(x, y) {
		const r = this.el.getBoundingClientRect();
		let left = x - r.width / 2;
		let top = y - r.height - this.gap;
		if(top < 4) top = y + this.gap;
		left = Math.max(4, Math.min(left, window.innerWidth - r.width - 4));
		this.el.style.left = left + 'px';
		this.el.style.top = top + 'px';
		return this;
	}

	// ── Anchored mode (callout) ─────────────────────────────────────────

	// Pin to a DOM element, drawing an arrow pointing at it, and (by default) dismiss on click /
	// outside-click. Reuse the owner-watch so it self-closes if the target is removed from the DOM.
	// Position is jQuery-UI-style: options.my = the point ON THE TOOLTIP, options.at = the point ON THE
	// TARGET; they align, then we add a gap + arrow and flip to the opposite side if there's no room.
	// Default (my "center bottom" / at "center top") sits the tooltip above the target with its
	// bottom-middle arrow pointing at the target's top-middle.
	anchor(content, target, options = {}) {
		if(!target) return this;
		const interactive = (options.interactive !== false);

		this._teardownInteractive();
		this._setContent(content);
		this.el.classList.toggle('cerb-ui-tooltip--interactive', interactive);
		this.el.hidden = false;
		this.arrow.toggleAttribute('hidden', false);
		this._anchorTarget = target;
		this._anchorOptions = options;
		this._positionAnchored(target, options);
		this._watchOwner(target);
		this._watchResize();

		if(interactive) {
			this._onClick = () => this.hide();
			this.el.addEventListener('click', this._onClick);
			// Dismiss on a genuine outside click (capture, like CerbUI.Menu's docDown).
			this._onDocDown = (e) => { if(!this.el.contains(e.target)) this.hide(); };
			document.addEventListener('pointerdown', this._onDocDown, { capture: true });
		}
		return this;
	}

	// Parse a jQuery-UI-style anchor ("center bottom", "left top", "middle bottom" — tokens in either
	// order; "middle" == "center") into {h, v} fractions: h left=0 center=0.5 right=1; v top=0 …bottom=1.
	static _parseAnchor(str, def) {
		if(str == null || str === '') return { h: def.h, v: def.v };
		let h = null, v = null;
		String(str).toLowerCase().trim().split(/\s+/).forEach(tok => {
			if(tok === 'left') h = 0;
			else if(tok === 'right') h = 1;
			else if(tok === 'top') v = 0;
			else if(tok === 'bottom') v = 1;
			// 'center'/'middle' resolve to 0.5 on whichever axis is left unset (the fallback below)
		});
		return { h: (h == null ? 0.5 : h), v: (v == null ? 0.5 : v) };
	}

	// Align the tooltip's `my` point to the target's `at` point, push it off by `gap` (+ arrow), and flip
	// to the opposite side if the preferred one has no viewport room. Slide along the cross-axis to stay
	// on-screen; the arrow tracks the target's anchor point (so it slides toward a corner when clamped).
	_positionAnchored(target, options = {}) {
		const T = CerbUI.Tooltip;
		const vw = document.documentElement.clientWidth;
		const vh = document.documentElement.clientHeight;
		const r = target.getBoundingClientRect();

		const my = T._parseAnchor(options.my, { h: 0.5, v: 1 }); // tooltip bottom-center
		const at = T._parseAnchor(options.at, { h: 0.5, v: 0 }); // target top-center
		// The arrow rides the tooltip edge where `my` sits at an extreme; prefer the vertical axis.
		const vertical = (my.v === 0 || my.v === 1);

		// Two passes: place using an offscreen measurement, then re-measure ON-SCREEN and place again if the
		// size changed. A callout's height can depend on its final width (text wrap) or on late-applied web
		// fonts; without this the panel sits mis-distanced from the target and the arrow visibly detaches.
		let pw = 0, ph = 0;
		for(let pass = 0; pass < 2; pass++) {
			// Pass 0 measures parked offscreen-left (no viewport-edge scrollbar); pass 1 measures in place.
			if(pass === 0) { this.el.style.left = '-9999px'; this.el.style.top = '0px'; }
			const mw = this.el.offsetWidth, mh = this.el.offsetHeight;
			if(pass > 0 && mw === pw && mh === ph) break; // settled — keep the first placement
			pw = mw; ph = mh;

			let side, left, top, axis;
			if(vertical) {
				// Align the tooltip's my.v point to the target's at.v point, then push off by the gap (my bottom
				// edge ⇒ panel above ⇒ move up; my top edge ⇒ panel below ⇒ move down).
				const placeV = (mv, av) => (r.top + av * r.height) - mv * ph + (mv === 1 ? -this.gap : this.gap);
				const roomV = (mv, av) => (mv === 1) ? (r.top + av * r.height - 4) : (vh - 4 - (r.top + av * r.height));
				const fitsV = (p) => p >= 4 && p + ph <= vh - 4;

				let mv = my.v, av = at.v, pos = placeV(mv, av);
				if(!fitsV(pos)) { // no room → flip both my and at to the opposite edge
					const posF = placeV(1 - mv, 1 - av);
					if(fitsV(posF) || roomV(1 - mv, 1 - av) > roomV(mv, av)) { mv = 1 - mv; av = 1 - av; pos = posF; }
				}
				side = (mv === 1) ? 'top' : 'bottom';
				top = Math.max(4, Math.min(pos, vh - ph - 4)); // viewport backstop

				const atX = r.left + at.h * r.width;
				left = Math.max(4, Math.min(atX - my.h * pw, vw - pw - 4)); // slide along the cross-axis
				axis = atX - left; // arrow X within the panel, points at the target's anchor point
			} else {
				const placeH = (mh2, ah) => (r.left + ah * r.width) - mh2 * pw + (mh2 === 1 ? -this.gap : this.gap);
				const roomH = (mh2, ah) => (mh2 === 1) ? (r.left + ah * r.width - 4) : (vw - 4 - (r.left + ah * r.width));
				const fitsH = (p) => p >= 4 && p + pw <= vw - 4;

				let mh2 = my.h, ah = at.h, pos = placeH(mh2, ah);
				if(!fitsH(pos)) {
					const posF = placeH(1 - mh2, 1 - ah);
					if(fitsH(posF) || roomH(1 - mh2, 1 - ah) > roomH(mh2, ah)) { mh2 = 1 - mh2; ah = 1 - ah; pos = posF; }
				}
				side = (mh2 === 1) ? 'left' : 'right';
				left = Math.max(4, Math.min(pos, vw - pw - 4));

				const atY = r.top + at.v * r.height;
				top = Math.max(4, Math.min(atY - my.v * ph, vh - ph - 4));
				axis = atY - top; // arrow Y within the panel
			}

			this.el.style.left = left + 'px';
			this.el.style.top = top + 'px';
			this._positionArrow(side, pw, ph, axis);
		}
	}

	// Place + rotate the arrow on the panel edge facing the target. `axis` is the offset along that edge.
	_positionArrow(side, pw, ph, axis) {
		const T = CerbUI.Tooltip;
		const long = T._ARROW_LONG, short = T._ARROW_SHORT, ov = T._ARROW_OVERLAP;
		const a = this.arrow.style;
		a.right = '';

		if(side === 'bottom' || side === 'top') {
			const min = T._CORNER + long / 2, max = pw - T._CORNER - long / 2;
			const x = (min <= max) ? Math.max(min, Math.min(axis, max)) : pw / 2;
			a.left = (x - long / 2) + 'px';
			a.top = (side === 'bottom') ? (ov - short) + 'px' : (ph - ov) + 'px';
			a.transform = (side === 'bottom') ? 'rotate(0deg)' : 'rotate(180deg)';
		} else {
			const min = T._CORNER + long / 2, max = ph - T._CORNER - long / 2;
			const y = (min <= max) ? Math.max(min, Math.min(axis, max)) : ph / 2;
			a.top = (y - long / 2) + 'px';
			// rotate the 16x8 box about its center: for 'right' (points left) the base lands 1px inside the
			// panel's left edge; for 'left' (points right) it lands 1px inside the right edge.
			a.left = (side === 'right') ? (ov - long + short / 2) + 'px' : (pw - ov - short / 2) + 'px';
			a.transform = (side === 'right') ? 'rotate(270deg)' : 'rotate(90deg)';
		}
	}

	// ── Shared ──────────────────────────────────────────────────────────

	_setContent(content) {
		if(content instanceof Node) this.el.replaceChildren(this.arrow, content);
		else { this.el.replaceChildren(this.arrow); this.el.insertAdjacentHTML('beforeend', content); }
	}

	// Reposition if the panel's own size settles later — a web font finishing loading changes the text
	// metrics, or the content reflows. Repositioning only moves the panel (never resizes it), so this
	// can't loop on itself. Observe only while anchored.
	_watchResize() {
		if(!window.ResizeObserver) return;
		if(!this._resizeObserver) {
			this._resizeObserver = new ResizeObserver(() => {
				if(!this.el.hidden && this._anchorTarget && this._anchorTarget.isConnected)
					this._positionAnchored(this._anchorTarget, this._anchorOptions);
			});
		}
		this._resizeObserver.observe(this.el);
	}

	// Auto-hide if the triggering node is removed from the DOM. Observe only while visible (transient).
	_watchOwner(owner) {
		this._owner = owner;
		if(!owner || !window.MutationObserver) return;
		if(!this._observer) {
			this._observer = new MutationObserver(() => {
				if(this._owner && !this._owner.isConnected) this.hide();
			});
		}
		this._observer.observe(document.body, { childList: true, subtree: true });
	}

	_teardownInteractive() {
		if(this._onClick) { this.el.removeEventListener('click', this._onClick); this._onClick = null; }
		if(this._onDocDown) {
			document.removeEventListener('pointerdown', this._onDocDown, { capture: true });
			this._onDocDown = null;
		}
	}

	hide() {
		this.el.hidden = true;
		this.arrow.toggleAttribute('hidden', true); // SVG: no .hidden IDL prop — toggle the attribute
		this.el.classList.remove('cerb-ui-tooltip--interactive');
		this._owner = null;
		this._anchorTarget = null;
		this._anchorOptions = null;
		this._teardownInteractive();
		if(this._observer) this._observer.disconnect();
		if(this._resizeObserver) this._resizeObserver.disconnect();
		return this;
	}

	destroy() {
		this._teardownInteractive();
		if(this._observer) { this._observer.disconnect(); this._observer = null; }
		if(this._resizeObserver) { this._resizeObserver.disconnect(); this._resizeObserver = null; }
		this.el.remove();
	}
};
