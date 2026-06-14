/*
 * CerbUI.Spinner — the design-system port of Cerb's legacy svg.cerb-spinner: a pure-CSS animated SVG ring.
 *
 * Like Tooltip, it builds its own element (there's no source node, so no from() registry).
 *
 * Usage:
 *   const s = new CerbUI.Spinner();   el.appendChild(s.el);
 *   el.appendChild(CerbUI.Spinner.create());         // one-shot element, no instance
 *   el.appendChild(CerbUI.Spinner.create('arc'));    // 90° arc spinning around its center
 *   el.appendChild(CerbUI.Spinner.create('dots'));   // three dots pulsing left→right
 *   el.appendChild(CerbUI.Spinner.create('spark'));  // radial hashes with a rotating tail
 *
 * Or author the markup directly (CSS does the work):
 *   <svg class="cerb-ui-spinner" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45"/></svg>
 *   <svg class="cerb-ui-spinner cerb-ui-spinner--dots" viewBox="0 0 120 40"><circle cx="20" cy="20" r="12"/><circle cx="60" cy="20" r="12"/><circle cx="100" cy="20" r="12"/></svg>
 *
 * CSS lives in cerb.css (.cerb-ui-spinner) — this component never injects styles.
 */
CerbUI.Spinner = class {
	constructor(variant) {
		this.el = CerbUI.Spinner.create(variant);
	}

	// Build a fresh spinner SVG (the markup the CSS animates). variant: undefined|'arc'|'dots'
	static create(variant) {
		const NS = 'http://www.w3.org/2000/svg';
		const svg = document.createElementNS(NS, 'svg');
		svg.setAttribute('class', 'cerb-ui-spinner' + (variant ? ' cerb-ui-spinner--' + variant : ''));

		if(variant === 'dots') {
			// Three dots in a wide-short viewBox (the dots pulse; the svg doesn't rotate)
			svg.setAttribute('viewBox', '0 0 120 40');
			[20, 60, 100].forEach(function(cx) {
				const circle = document.createElementNS(NS, 'circle');
				circle.setAttribute('cx', cx);
				circle.setAttribute('cy', '20');
				circle.setAttribute('r', '12');
				svg.appendChild(circle);
			});
		} else if(variant === 'spark') {
			// The cerb-icon-spinner's 8 radial hashes (the CSS fades each on a staggered cycle)
			svg.setAttribute('viewBox', '0 0 24 24');
			const lines = [
				[12, 2, 12, 6], [12, 18, 12, 22],
				[4.93, 4.93, 7.76, 7.76], [16.24, 16.24, 19.07, 19.07],
				[2, 12, 6, 12], [18, 12, 22, 12],
				[4.93, 19.07, 7.76, 16.24], [16.24, 7.76, 19.07, 4.93]
			];
			lines.forEach(function(pts) {
				const line = document.createElementNS(NS, 'line');
				line.setAttribute('x1', pts[0]);
				line.setAttribute('y1', pts[1]);
				line.setAttribute('x2', pts[2]);
				line.setAttribute('y2', pts[3]);
				svg.appendChild(line);
			});
		} else {
			// Default ring and 'arc' share the single-circle markup; the class picks the look
			svg.setAttribute('viewBox', '0 0 100 100');
			const circle = document.createElementNS(NS, 'circle');
			circle.setAttribute('cx', '50');
			circle.setAttribute('cy', '50');
			circle.setAttribute('r', '45');
			svg.appendChild(circle);
		}
		return svg;
	}
};
