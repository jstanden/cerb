/*
 * CerbUI.Spinner — the design-system port of Cerb's legacy svg.cerb-spinner: a pure-CSS animated SVG ring.
 *
 * Like Tooltip, it builds its own element (there's no source node, so no from() registry).
 *
 * Usage:
 *   const s = new CerbUI.Spinner();   el.appendChild(s.el);
 *   el.appendChild(CerbUI.Spinner.create());   // one-shot element, no instance
 *
 * Or author the markup directly (CSS does the work):
 *   <svg class="cerb-ui-spinner" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45"/></svg>
 *
 * CSS lives in cerb.css (.cerb-ui-spinner) — this component never injects styles.
 */
CerbUI.Spinner = class {
	constructor() {
		this.el = CerbUI.Spinner.create();
	}

	// Build a fresh spinner SVG (the markup the CSS animates)
	static create() {
		const NS = 'http://www.w3.org/2000/svg';
		const svg = document.createElementNS(NS, 'svg');
		svg.setAttribute('class', 'cerb-ui-spinner');
		svg.setAttribute('viewBox', '0 0 100 100');
		const circle = document.createElementNS(NS, 'circle');
		circle.setAttribute('cx', '50');
		circle.setAttribute('cy', '50');
		circle.setAttribute('r', '45');
		svg.appendChild(circle);
		return svg;
	}
};
