/*
 * CerbUI.Tooltip — a floating panel positioned in the viewport (position:fixed). Reusable: chart hover
 * details now, and a base for Menu later. Give it content + a viewport point; it sits above the point and
 * flips below / clamps horizontally to stay on-screen.
 *
 * Usage:
 *   const tip = new CerbUI.Tooltip();
 *   tip.show(nodeOrHtml, clientX, clientY, owner);  // set content + position + reveal
 *   tip.move(clientX, clientY);                      // reposition (same content)
 *   tip.hide();
 *
 * Pass the triggering element as `owner` and the tooltip auto-hides if that node leaves the DOM (e.g. a
 * worklist refresh swaps the chart out from under the cursor — no mouseleave fires, so it would otherwise
 * orphan on-screen with no way to dismiss it).
 *
 * pointer-events are disabled so the tooltip never steals hover from whatever triggered it.
 * Reuse one instance per trigger/widget rather than creating one per show.
 */
CerbUI.Tooltip = class {
	constructor(options = {}) {
		this.gap = (options.gap != null) ? options.gap : 10; // px between the point and the panel
		this.el = document.createElement('div');
		this.el.className = 'cerb-ui-tooltip';
		this.el.setAttribute('role', 'tooltip');
		this.el.hidden = true;
		document.body.appendChild(this.el);
	}

	show(content, x, y, owner) {
		if(content instanceof Node) this.el.replaceChildren(content);
		else this.el.innerHTML = content;
		this.el.hidden = false;
		this.move(x, y);
		this._watchOwner(owner || null);
		return this;
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

	hide() {
		this.el.hidden = true;
		this._owner = null;
		if(this._observer) this._observer.disconnect();
		return this;
	}

	destroy() {
		if(this._observer) { this._observer.disconnect(); this._observer = null; }
		this.el.remove();
	}
};
