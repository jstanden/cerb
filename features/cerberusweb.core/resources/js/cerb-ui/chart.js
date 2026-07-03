/*
 * CerbUI.Chart — the reusable base class for the cerb-ui chart family (the successor to c3.js). It owns the
 * cross-chart concerns so each concrete chart (PieChart now; a cartesian Chart, Gauge, … later) only has to
 * implement render():
 *   - lifecycle: one instance per element (WeakMap + from()), an <svg> plot, a ResizeObserver → render(),
 *     and destroy().
 *   - color: resolvePalette + an optional shared CerbUI.ColorScale, and the _color(i, item) idiom used across
 *     Sparkchart/Distbar/Legend (explicit item.color, else scale by key/label, else palette by index).
 *   - a lazily-created shared point-mode CerbUI.Tooltip (one across all charts).
 *   - bubbling 'cerb-ui-chart:{hover,leave,click}' events.
 *   - _search(context, query): the Cerb-search drill-through, shared instead of re-inlined per widget template.
 *
 * Subclasses call render() at the END of their own constructor (the base intentionally does NOT, so a subclass
 * can parse its options first). See piechart.js for the reference subclass.
 */
CerbUI.Chart = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Chart._instances.get(el); }

	constructor(el, options = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		CerbUI.Chart._instances.set(this.el, this);

		this.options = options;
		this.palette = CerbUI.resolvePalette(options.palette);
		this.scale = options.scale || null; // optional shared CerbUI.ColorScale (color by label across charts)
		if(this.scale && options.palette != null) this.scale.usePalette(options.palette);
		this.height = options.height || 320;
		this.tooltipEnabled = (options.tooltip !== false);
		this.NS = 'http://www.w3.org/2000/svg';

		this.el.classList.add('cerb-ui-chart');

		this._svg = document.createElementNS(this.NS, 'svg');
		this._svg.setAttribute('class', 'cerb-ui-chart--plot');
		this._svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
		this.el.appendChild(this._svg);

		if(window.ResizeObserver) {
			this._ro = new ResizeObserver(() => this.render());
			this._ro.observe(this.el);
		}
		// NOTE: no render() here — subclasses render() after parsing their own options.
	}

	// Shared color idiom: explicit item.color wins, else a shared scale by key/label, else palette by index.
	_color(i, item) {
		if(item && item.color) return item.color;
		if(this.scale) return this.scale.color(item && (item.key != null ? item.key : item.label));
		return this.palette[i % this.palette.length];
	}

	// One shared point-mode tooltip across every chart (only one is ever visible) — lazily created.
	_tooltip() {
		if(!this.tooltipEnabled || !CerbUI.Tooltip) return null;
		if(!CerbUI.Chart._sharedTooltip) CerbUI.Chart._sharedTooltip = new CerbUI.Tooltip();
		return CerbUI.Chart._sharedTooltip;
	}

	_emit(name, detail) {
		this.el.dispatchEvent(new CustomEvent('cerb-ui-chart:' + name, { detail: detail, bubbles: true }));
	}

	// Drill-through: open Cerb search for "<context> <query>" (the idiom the old chart render.tpls inlined).
	_search(context, query) {
		if(!query) return;
		if(typeof jQuery === 'undefined' || typeof jQuery.fn.cerbSearchTrigger !== 'function') return;
		jQuery('<div/>')
			.attr('data-context', context || '')
			.attr('data-query', query)
			.cerbSearchTrigger()
			.on('cerb-search-opened', function() { jQuery(this).remove(); })
			.click();
	}

	// Abstract — subclasses draw into this._svg.
	render() {}

	// Tear down the ResizeObserver, hide the shared tooltip, and clear the element.
	destroy() {
		if(this._ro) { this._ro.disconnect(); this._ro = null; }
		const tip = this._tooltip();
		if(tip) tip.hide();
		this.el.replaceChildren();
		this.el.classList.remove('cerb-ui-chart');
		CerbUI.Chart._instances.delete(this.el);
	}
};
