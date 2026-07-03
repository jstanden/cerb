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

/*
 * CerbUI.color — small color-math helpers (WCAG luminance + auto text contrast). Use idealTextColor(bg)
 * to pick legible label text on an arbitrary fill (tag/event/chart colors, user-chosen swatches, etc.).
 */
CerbUI.color = {
	// Parse '#rgb' / '#rrggbb' (with or without the leading '#') into {r,g,b}; null if not a hex color.
	parseHex: function(c) {
		if(typeof c !== 'string') return null;
		let s = c.trim();
		if(s.charAt(0) === '#') s = s.slice(1);
		if(s.length === 3) s = s.charAt(0) + s.charAt(0) + s.charAt(1) + s.charAt(1) + s.charAt(2) + s.charAt(2);
		if(s.length !== 6 || /[^0-9a-fA-F]/.test(s)) return null;
		const n = parseInt(s, 16);
		return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
	},

	// WCAG relative luminance (0..1) of a hex color or an {r,g,b}; null if unparseable.
	luminance: function(c) {
		const rgb = (c && typeof c === 'object') ? c : CerbUI.color.parseHex(c);
		if(!rgb) return null;
		const lin = (v) => { v /= 255; return (v <= 0.03928) ? (v / 12.92) : Math.pow((v + 0.055) / 1.055, 2.4); };
		return 0.2126 * lin(rgb.r) + 0.7152 * lin(rgb.g) + 0.0722 * lin(rgb.b);
	},

	// WCAG contrast ratio (1..21) between two colors; null if either is unparseable.
	contrastRatio: function(a, b) {
		const la = CerbUI.color.luminance(a), lb = CerbUI.color.luminance(b);
		if(la == null || lb == null) return null;
		const hi = Math.max(la, lb), lo = Math.min(la, lb);
		return (hi + 0.05) / (lo + 0.05);
	},

	// Near-black ('#141414') or white ('#ffffff') — whichever has the higher contrast on `bg`.
	// Returns null for a non-hex bg (a CSS var / named color) so the caller can defer to the stylesheet.
	idealTextColor: function(bg) {
		const L = CerbUI.color.luminance(bg);
		if(L == null) return null;
		const contrastWhite = 1.05 / (L + 0.05);   // (1.0 + 0.05) / (L + 0.05)
		const contrastBlack = (L + 0.05) / 0.05;
		return (contrastBlack >= contrastWhite) ? '#141414' : '#ffffff';
	}
};
