/*
 * cerb-ui shared color palettes. Components assign colors by element index, so a distbar and a legend
 * built from the same ordered list match. (A fuller D3-like color system can replace this later.)
 */
CerbUI.palettes = {
	// Cerb's default 10-color scheme (D3 category10)
	category10: ['#0088e6','#ff7f0e','#2ca02c','#d62728','#9467bd','#8c564b','#e377c2','#7f7f7f','#bcbd22','#17becf'],
	// 12-color rainbow
	rainbow: ['#6e40aa','#b83cb0','#f6478d','#ff6956','#f59f30','#c4d93e','#83f557','#38f17a','#19d3b5','#29a0dd','#5069d9','#c93c45']
};

// Accepts an explicit array or a palette name; defaults to category10
CerbUI.resolvePalette = function(p) {
	return Array.isArray(p) ? p : (CerbUI.palettes[p] || CerbUI.palettes.category10);
};

/*
 * CerbUI.ColorScale — an ordinal color scale (like D3 scaleOrdinal). color(key) assigns the next palette
 * color the first time it sees a key, then returns that same color for that key forever. Share ONE scale
 * instance across components (Distbar/Legend) so the same key is the same color everywhere — the way to
 * keep colors consistent across related charts. (Index-based coloring stays the default for standalone charts.)
 */
CerbUI.ColorScale = class {
	constructor(palette) {
		this.palette = CerbUI.resolvePalette(palette);
		this._paletteExplicit = (palette != null); // created with its own palette? then nobody overrides it
		this._map = new Map();
		this._next = 0;
	}

	// Let a component's palette seed THIS scale — but only a default scale (no explicit palette of its
	// own) that hasn't handed out colors yet, so an explicit colorScale(palette) and any already-shared
	// assignments both stay stable.
	usePalette(palette) {
		if(!this._paletteExplicit && this._next === 0 && palette != null)
			this.palette = CerbUI.resolvePalette(palette);
		return this;
	}

	color(key) {
		key = (key == null) ? '' : key;
		if(!this._map.has(key))
			this._map.set(key, this.palette[this._next++ % this.palette.length]);
		return this._map.get(key);
	}
};

CerbUI.colorScale = function(palette) { return new CerbUI.ColorScale(palette); };
