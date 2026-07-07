/*
 * CerbUI.Map — a self-contained SVG region/point map (choropleth + POIs), the successor to the d3.js +
 * topojson.v3 renderer (internal/widgets/map/geopoints/render_regions.tpl). No charting/geo library: it
 * decodes TopoJSON, projects with a hand-rolled Web Mercator or AlbersUsa composite, generates SVG paths,
 * tints regions (choropleth / color-key / color-map), places POI circles, and does pan/zoom + selection
 * itself. The projection math is validated against d3.geoMercator/geoAlbersUsa (see the node harness).
 *
 * The configuration mirrors the parsed map KATA (`$map` from DevblocksUiMap::parse), so production can pass
 * it through verbatim as `options.map`. Geometry sources are given as a URL to fetch OR a pre-loaded object.
 *
 * Usage:
 *   new CerbUI.Map(el, {
 *     map: {                                       // == the parsed $map config
 *       projection: { type:'mercator', scale:90, center:{longitude:0, latitude:25}, zoom:{…} },
 *       regions: { fill:{choropleth:{property:'pop_est', classes:5, colors:['#f4e153','#362142']}},
 *                  properties:{join:{property:'iso_a2', case:'upper'}}, filter:{…}, label:{…} },
 *       points:  { size:{default:2}, fill:{default:'…'}, filter:{…}, label:{title:'name', properties:{…}} },
 *     },
 *     regions:           urlOrObject,              // TopoJSON or GeoJSON of the region geometry
 *     regionProperties:  urlOrObject,              // optional { joinValue: {prop:…} } resource
 *     regionPropertiesInline: {…},                 // optional inline properties (merged over the resource)
 *     points:            urlOrObject,              // optional GeoJSON point FeatureCollection
 *     pointsInline:      { type:'FeatureCollection', features:[…] },   // optional inline points (concatenated)
 *     click:             { enabled:true, c:'profiles', a:'invokeWidget', widget_id:123 },  // mapClicked round-trip
 *     width: 600, height: 325,
 *   });
 *
 * It PUBLISHES bubbling events on the element:
 *   'cerb-ui-map:click'  detail = { feature_type:'region'|'point', properties, point:{x,y} }
 *   'cerb-ui-map:ready'  detail = { regions:Number, points:Number }
 */
CerbUI.Map = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Map._instances.get(el); }

	static NS = 'http://www.w3.org/2000/svg';
	static RAD = Math.PI / 180;
	static MERCATOR_MAX_LAT = 85.0511287798; // atan(sinh(π)) — Mercator's usable latitude cap
	static OVERSCROLL_RELEASE = 200; // px of zoom-out scroll absorbed at min zoom before the wheel releases to the page

	constructor(el, options = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		CerbUI.Map._instances.set(this.el, this);

		this.options = options;
		this.map = options.map || {};
		this.width = options.width || 600;
		this.height = options.height || 325;
		this.click = options.click || null;
		this.NS = CerbUI.Map.NS;

		this._transform = { k: 1, x: 0, y: 0 };
		this._lastK = null; // last zoom factor points were scaled for (so pan skips the point-rescale loop)
		this._selected = null; // the currently-selected feature (region or point datum)
		this._dragMoved = false; // a pan in progress passed the click-suppression threshold
		this._overscroll = 0; // accumulated zoom-out scroll while pinned at min zoom (dead-zone before page-scroll)
		this._overscrollAt = 0; // timestamp of the last overscroll tick (resets the accumulator after an idle gap)

		this.el.classList.add('cerb-ui-map');
		this._buildChrome();
		this._load();
	}

	// ─────────────────────────────────────────────────────────────────────────────
	// DOM chrome (toolbar / legend / label / coordinate readout / svg)
	// ─────────────────────────────────────────────────────────────────────────────

	_buildChrome() {
		this.el.replaceChildren();

		// Floating label panel (feature detail popup)
		this._label = this._div('cerb-ui-map--label');
		this._label.style.display = 'none';
		const close = this._div('cerb-ui-map--label-close');
		close.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove-2"></span>';
		close.addEventListener('click', () => this._clearSelection());
		this._labelBody = this._div('cerb-ui-map--label-body');
		this._label.appendChild(close);
		this._label.appendChild(this._labelBody);
		this.el.appendChild(this._label);

		// Zoom toolbar
		this._toolbar = this._div('cerb-ui-map--toolbar');
		this._toolbar.appendChild(this._toolButton('reset', 'restart'));
		this._toolbar.appendChild(this._toolButton('zoom-in', 'zoom-in'));
		this._toolbar.appendChild(this._toolButton('zoom-out', 'zoom-out'));
		this.el.appendChild(this._toolbar);

		// Choropleth legend + coordinate readout
		this._legend = this._div('cerb-ui-map--legend');
		this._legend.style.display = 'none';
		this.el.appendChild(this._legend);
		this._coords = this._div('cerb-ui-map--coords');
		this.el.appendChild(this._coords);

		// Loading spinner (until data arrives)
		this._spinner = this._div('cerb-ui-map--spinner');
		if(typeof Devblocks !== 'undefined' && Devblocks.getSpinner)
			this._spinner.innerHTML = Devblocks.getSpinner();
		else
			this._spinner.textContent = 'Loading…';
		this.el.appendChild(this._spinner);
	}

	_div(cls) { const d = document.createElement('div'); d.className = cls; return d; }

	_toolButton(action, icon) {
		const b = document.createElement('button');
		b.type = 'button';
		b.className = 'cerb-ui-map--tool';
		b.dataset.action = action;
		b.innerHTML = '<span class="cerb-icons cerb-icon-' + icon + '"></span>';
		b.addEventListener('click', () => this._onToolClick(action));
		return b;
	}

	// ─────────────────────────────────────────────────────────────────────────────
	// Data loading (fetch URL or accept a pre-loaded object)
	// ─────────────────────────────────────────────────────────────────────────────

	_resolve(src) {
		if(src == null) return Promise.resolve(null);
		if(typeof src === 'string')
			return fetch(src, { credentials: 'same-origin' }).then(r => r.ok ? r.json() : null).catch(() => null);
		return Promise.resolve(src);
	}

	_load() {
		Promise.all([
			this._resolve(this.options.regions),
			this._resolve(this.options.regionProperties),
			this._resolve(this.options.points),
		]).then(([regions, regionProps, points]) => {
			if(this._spinner) this._spinner.remove();
			this._regionsData = regions;
			this._regionPropsData = regionProps;
			this._pointsData = points;
			try {
				this._build();
			} catch(e) {
				if(window.console && console.error) console.error(e);
			}
		});
	}

	// ─────────────────────────────────────────────────────────────────────────────
	// Geometry ingestion: TopoJSON decode → GeoJSON features
	// ─────────────────────────────────────────────────────────────────────────────

	// Decode a TopoJSON Topology's first (or named) object into GeoJSON-like features. Replaces
	// topojson.feature(): delta-decode arcs, stitch arc indices into rings (negative index = reversed
	// arc via ~i), and carry each geometry's properties. Handles Polygon/MultiPolygon/Point/MultiPoint/
	// LineString/MultiLineString.
	static decodeTopology(topo, objectName) {
		const key = objectName || Object.keys(topo.objects || {})[0];
		const obj = topo.objects && topo.objects[key];
		if(!obj) return [];

		const tf = topo.transform;
		const sx = tf ? tf.scale[0] : 1, sy = tf ? tf.scale[1] : 1;
		const tx = tf ? tf.translate[0] : 0, ty = tf ? tf.translate[1] : 0;

		// Pre-decode every arc to absolute [lon,lat] coordinates (delta-decoded if quantized).
		const arcs = (topo.arcs || []).map(arc => {
			let x = 0, y = 0;
			return arc.map(p => {
				if(tf) { x += p[0]; y += p[1]; return [x * sx + tx, y * sy + ty]; }
				return [p[0], p[1]];
			});
		});

		// Stitch a ring from a list of arc indices; a negative index references arc ~i reversed. The
		// shared vertex between consecutive arcs is de-duplicated (drop the first point of each appended arc).
		const ring = (indexes) => {
			const out = [];
			indexes.forEach(idx => {
				let a = idx < 0 ? arcs[~idx].slice().reverse() : arcs[idx];
				if(out.length) a = a.slice(1);
				for(let i = 0; i < a.length; i++) out.push(a[i]);
			});
			return out;
		};

		// topojson-client pads degenerate polygon rings to a minimum of 4 points (closing back to the
		// first) — replicate so tiny slivers (e.g. Vatican) match topojson.feature exactly. Lines are not padded.
		const polygonRing = (indexes) => { const p = ring(indexes); while(p.length < 4) p.push(p[0]); return p; };
		// Point/MultiPoint coordinates are quantized too (scale/translate, but NOT delta-accumulated like arcs).
		const point = (p) => tf ? [p[0] * sx + tx, p[1] * sy + ty] : [p[0], p[1]];

		const geometry = (g) => {
			switch(g.type) {
				case 'Polygon':
					return { type: 'Polygon', coordinates: g.arcs.map(polygonRing) };
				case 'MultiPolygon':
					return { type: 'MultiPolygon', coordinates: g.arcs.map(poly => poly.map(polygonRing)) };
				case 'LineString':
					return { type: 'LineString', coordinates: ring(g.arcs) };
				case 'MultiLineString':
					return { type: 'MultiLineString', coordinates: g.arcs.map(ring) };
				case 'Point':
					return { type: 'Point', coordinates: point(g.coordinates) };
				case 'MultiPoint':
					return { type: 'MultiPoint', coordinates: g.coordinates.map(point) };
				default:
					return null;
			}
		};

		const geoms = obj.type === 'GeometryCollection' ? obj.geometries : [obj];
		return geoms.map(g => ({
			type: 'Feature',
			properties: g.properties || {},
			geometry: geometry(g),
		})).filter(f => f.geometry);
	}

	// Normalize any region/point resource JSON into a flat array of GeoJSON features.
	static toFeatures(json) {
		if(!json || typeof json !== 'object') return [];
		if(json.type === 'Topology') return CerbUI.Map.decodeTopology(json);
		if(json.type === 'FeatureCollection') return json.features || [];
		if(json.type === 'Feature') return [json];
		return [];
	}

	// ─────────────────────────────────────────────────────────────────────────────
	// Projections (validated against d3.geoMercator / d3.geoAlbersUsa)
	// ─────────────────────────────────────────────────────────────────────────────

	// A projection = { project([lon,lat]) → [x,y]|null, invert([x,y]) → [lon,lat] }.
	// `spec` mirrors $map.projection: { type, scale, center:{longitude,latitude}, translate:[x,y] }.
	static projection(spec) {
		spec = spec || {};
		const type = spec.type || 'mercator';
		const scale = (spec.scale != null) ? +spec.scale : 90;
		const translate = spec.translate || [300, 162.5];
		if(type === 'albersUsa')
			return CerbUI.Map._albersUsa(scale, translate);
		// mercator (and naturalEarth / unknown → mercator fallback)
		const center = spec.center || { longitude: 0, latitude: 25 };
		return CerbUI.Map._cylindricalOrConic(
			CerbUI.Map._mercatorRaw, 0, scale, translate,
			[center.longitude || 0, center.latitude || 0]
		);
	}

	// ── raw projections (radians in, unit-plane out) — ported from d3-geo ──

	static _mercatorRaw() {
		const max = CerbUI.Map.MERCATOR_MAX_LAT * CerbUI.Map.RAD;
		return {
			forward: (lambda, phi) => {
				if(phi > max) phi = max; else if(phi < -max) phi = -max;
				return [lambda, Math.log(Math.tan((Math.PI / 2 + phi) / 2))];
			},
			invert: (x, y) => [x, 2 * Math.atan(Math.exp(y)) - Math.PI / 2],
		};
	}

	static _conicEqualAreaRaw(y0, y1) {
		const sy0 = Math.sin(y0), n = (sy0 + Math.sin(y1)) / 2;
		// Degenerate (n ≈ 0) → cylindrical equal-area; not used by our two projections, so keep it simple.
		const c = 1 + sy0 * (2 * n - sy0), r0 = Math.sqrt(c) / n;
		return {
			forward: (x, y) => {
				const r = Math.sqrt(c - 2 * n * Math.sin(y)) / n, xn = x * n;
				return [r * Math.sin(xn), r0 - r * Math.cos(xn)];
			},
			invert: (x, y) => {
				const r0y = r0 - y;
				let l = Math.atan2(x, Math.abs(r0y)) * Math.sign(r0y);
				if(r0y * n < 0) l -= Math.PI * Math.sign(x) * Math.sign(r0y);
				return [l / n, Math.asin((c - (x * x + r0y * r0y) * n * n) / (2 * n))];
			},
		};
	}

	// Build a full projection (raw + longitude rotation + center recenter + scale/translate), matching
	// d3's recenter math so `.center()` and `.rotate([λ,0])` compose exactly.
	static _cylindricalOrConic(rawFactory, rotateDegLon, scale, translate, centerDeg, rawArgs) {
		const raw = rawArgs ? rawFactory(rawArgs[0], rawArgs[1]) : rawFactory();
		const RAD = CerbUI.Map.RAD;
		const dl = (rotateDegLon || 0) * RAD;
		const normLon = (a) => (a > Math.PI ? a - 2 * Math.PI : (a < -Math.PI ? a + 2 * Math.PI : a));
		const rotate = (lonRad) => normLon(lonRad + dl);
		const unrotate = (lonRad) => normLon(lonRad - dl);

		const k = scale, tx = translate[0], ty = translate[1];
		// center's projected raw point (subtracted so the center lands on the translate). The center is
		// specified in the ROTATED frame (matches d3: e.g. Albers rotate([96,0]) + center([-0.6,38.7])),
		// so it is NOT passed through the longitude rotation here.
		const prc = raw.forward((centerDeg[0] || 0) * RAD, (centerDeg[1] || 0) * RAD);

		return {
			scale: k,
			translate: [tx, ty],
			project: (lonlat) => {
				const pr = raw.forward(rotate(lonlat[0] * RAD), lonlat[1] * RAD);
				return [tx + k * (pr[0] - prc[0]), ty - k * (pr[1] - prc[1])];
			},
			invert: (xy) => {
				const prx = (xy[0] - tx) / k + prc[0];
				const pry = prc[1] - (xy[1] - ty) / k;
				const ll = raw.invert(prx, pry);
				return [unrotate(ll[0]) / RAD, ll[1] / RAD];
			},
		};
	}

	// AlbersUsa: lower-48 Albers + Alaska + Hawaii insets, each with a pixel-space clip window. Ported
	// from d3.geoAlbersUsa (constants verbatim). project() routes a point to whichever inset owns it
	// (null if outside all); invert() picks the inset by testing the three windows.
	static _albersUsa(scale, translate) {
		const s = scale, x = translate[0], y = translate[1], e = 1e-6;

		const lower48 = CerbUI.Map._cylindricalOrConic(
			CerbUI.Map._conicEqualAreaRaw, 96, s, [x, y], [-0.6, 38.7],
			[29.5 * CerbUI.Map.RAD, 45.5 * CerbUI.Map.RAD]
		);
		const alaska = CerbUI.Map._cylindricalOrConic(
			CerbUI.Map._conicEqualAreaRaw, 154, s * 0.35, [x - 0.307 * s, y + 0.201 * s], [-2, 58.5],
			[55 * CerbUI.Map.RAD, 65 * CerbUI.Map.RAD]
		);
		const hawaii = CerbUI.Map._cylindricalOrConic(
			CerbUI.Map._conicEqualAreaRaw, 157, s, [x - 0.205 * s, y + 0.212 * s], [-3, 19.9],
			[8 * CerbUI.Map.RAD, 18 * CerbUI.Map.RAD]
		);

		const insets = [
			{ p: lower48, x0: x - 0.455 * s, y0: y - 0.238 * s, x1: x + 0.455 * s, y1: y + 0.238 * s },
			{ p: alaska, x0: x - 0.425 * s + e, y0: y + 0.120 * s + e, x1: x - 0.214 * s - e, y1: y + 0.234 * s - e },
			{ p: hawaii, x0: x - 0.214 * s + e, y0: y + 0.166 * s + e, x1: x - 0.115 * s - e, y1: y + 0.234 * s - e },
		];

		return {
			scale: s,
			translate: [x, y],
			project: (lonlat) => {
				for(let i = 0; i < insets.length; i++) {
					const it = insets[i], p = it.p.project(lonlat);
					if(p && p[0] >= it.x0 && p[0] < it.x1 && p[1] >= it.y0 && p[1] < it.y1) return p;
				}
				return null;
			},
			invert: (xy) => {
				const xx = (xy[0] - x) / s, yy = (xy[1] - y) / s;
				const sub = (yy >= 0.120 && yy < 0.234 && xx >= -0.425 && xx < -0.214) ? alaska
					: (yy >= 0.166 && yy < 0.234 && xx >= -0.214 && xx < -0.115) ? hawaii
						: lower48;
				return sub.invert(xy);
			},
		};
	}

	// ─────────────────────────────────────────────────────────────────────────────
	// Path generation (GeoJSON geometry → SVG "d")
	// ─────────────────────────────────────────────────────────────────────────────

	// Build the SVG path for a Polygon/MultiPolygon (fill-rule:evenodd handles holes; no winding logic).
	// `splitAntimeridian` inserts a pen-up when a segment jumps >180° in longitude, so a country crossing
	// ±180° (Russia/Fiji on a world Mercator) doesn't smear across the map. Coords truncated to 2 decimals.
	static geometryToPath(geometry, projection, splitAntimeridian) {
		if(!geometry) return '';
		const polys =
			geometry.type === 'Polygon' ? geometry.coordinates :
				geometry.type === 'MultiPolygon' ? [].concat.apply([], geometry.coordinates) : null;
		if(!polys) return '';

		const round = (n) => Math.round(n * 100) / 100;
		let d = '';
		for(let r = 0; r < polys.length; r++) {
			const lonlats = polys[r];
			let started = false, prevLon = null;
			for(let i = 0; i < lonlats.length; i++) {
				const ll = lonlats[i];
				const p = projection.project(ll);
				if(!p) { started = false; prevLon = null; continue; } // off-map (AlbersUsa) → pen up
				const wrap = splitAntimeridian && prevLon != null && Math.abs(ll[0] - prevLon) > 180;
				d += (!started || wrap ? 'M' : 'L') + round(p[0]) + ',' + round(p[1]);
				started = true;
				prevLon = ll[0];
			}
			if(started) d += 'Z';
		}
		return d;
	}

	// ─────────────────────────────────────────────────────────────────────────────
	// Color / choropleth (ports d3.interpolateHcl + quantize + scaleQuantize)
	// ─────────────────────────────────────────────────────────────────────────────

	static _parseColor(str) {
		str = (str || '').trim();
		if(str.charAt(0) === '#') {
			let hex = str.slice(1);
			if(hex.length === 3) hex = hex.replace(/./g, c => c + c);
			const n = parseInt(hex, 16);
			if(hex.length === 6 && !isNaN(n)) return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
		}
		const m = str.match(/rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)/i);
		if(m) return [+m[1], +m[2], +m[3]];
		return [0, 0, 0];
	}

	// sRGB ↔ CIE-Lab ↔ HCL — for perceptual choropleth ramps. Constants + the Bradford-adapted D50 matrix
	// are verbatim from d3-color, so the ramp matches d3.interpolateHcl exactly.
	static _rgbToHcl(rgb) {
		const Xn = 0.96422, Yn = 1, Zn = 0.82521, t0 = 4 / 29, t1 = 6 / 29, t2 = 3 * t1 * t1, t3 = t1 * t1 * t1;
		const lin = (v) => { v /= 255; return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
		const f = (t) => t > t3 ? Math.cbrt(t) : t / t2 + t0;
		const r = lin(rgb[0]), g = lin(rgb[1]), b = lin(rgb[2]);
		const y = f((0.2225045 * r + 0.7168786 * g + 0.0606169 * b) / Yn);
		let x, z;
		if(r === g && g === b) { x = z = y; }
		else {
			x = f((0.4360747 * r + 0.3850649 * g + 0.1430804 * b) / Xn);
			z = f((0.0139322 * r + 0.0971045 * g + 0.7141733 * b) / Zn);
		}
		const L = 116 * y - 16, A = 500 * (x - y), B = 200 * (y - z);
		let h = Math.atan2(B, A) * 180 / Math.PI;
		if(h < 0) h += 360;
		return [h, Math.sqrt(A * A + B * B), L]; // [hue°, chroma, luminance]
	}

	static _hclToRgb(hcl) {
		const Xn = 0.96422, Yn = 1, Zn = 0.82521, t0 = 4 / 29, t1 = 6 / 29, t2 = 3 * t1 * t1;
		const h = hcl[0] * Math.PI / 180, C = hcl[1], L = hcl[2];
		const A = Math.cos(h) * C, B = Math.sin(h) * C;
		let y = (L + 16) / 116, x = y + A / 500, z = y - B / 200;
		const g = (t) => t > t1 ? t * t * t : t2 * (t - t0);
		x = Xn * g(x); y = Yn * g(y); z = Zn * g(z);
		const un = (v) => 255 * (v <= 0.0031308 ? 12.92 * v : 1.055 * Math.pow(v, 1 / 2.4) - 0.055);
		const clamp = (v) => v < 0 ? 0 : v > 255 ? 255 : Math.round(v);
		return [
			clamp(un(3.1338561 * x - 1.6168667 * y - 0.4906146 * z)),
			clamp(un(-0.9787684 * x + 1.9161415 * y + 0.0334540 * z)),
			clamp(un(0.0719453 * x - 0.2289914 * y + 1.4052427 * z)),
		];
	}

	// n perceptual steps from `from` to `to` (== d3.quantize(d3.interpolateHcl(from,to), n)).
	static hclRamp(from, to, n) {
		const a = CerbUI.Map._rgbToHcl(CerbUI.Map._parseColor(from));
		const b = CerbUI.Map._rgbToHcl(CerbUI.Map._parseColor(to));
		// shortest-path hue interpolation (d3 color hue())
		let dh = b[0] - a[0];
		if(dh > 180 || dh < -180) dh -= 360 * Math.round(dh / 360);
		const out = [];
		for(let i = 0; i < n; i++) {
			const t = n === 1 ? 0 : i / (n - 1);
			const rgb = CerbUI.Map._hclToRgb([a[0] + dh * t, a[1] + (b[1] - a[1]) * t, a[2] + (b[2] - a[2]) * t]);
			out.push('rgb(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ')');
		}
		return out;
	}

	// scaleQuantize: map a value in [min,max] to one of `colors` (uniform buckets). Matches d3.scaleQuantize.
	static quantizeScale(min, max, colors) {
		const n = colors.length;
		return (v) => {
			if(!(max > min)) return colors[0];
			let i = Math.floor((v - min) / (max - min) * n);
			if(i < 0) i = 0; else if(i >= n) i = n - 1;
			return colors[i];
		};
	}

	// Compact large numbers for the legend (K/M/B/T), matching the old renderer's fill_format.
	static _compact(n) {
		const a = Math.abs(n);
		if(a >= 1e12) return (n / 1e12).toFixed(1) + 'T';
		if(a >= 1e9) return (n / 1e9).toFixed(1) + 'B';
		if(a >= 1e6) return (n / 1e6).toFixed(1) + 'M';
		if(a >= 1e3) return (n / 1e3).toFixed(1) + 'K';
		return '' + n;
	}

	// ─────────────────────────────────────────────────────────────────────────────
	// Build: project + draw regions/points, legend, and wire interaction
	// ─────────────────────────────────────────────────────────────────────────────

	_build() {
		this._projection = CerbUI.Map.projection(Object.assign(
			{ translate: [this.width / 2, this.height / 2] },
			this.map.projection || {}
		));
		const isMercator = (this.map.projection || {}).type !== 'albersUsa';

		this._svg = document.createElementNS(this.NS, 'svg');
		this._svg.setAttribute('class', 'cerb-ui-map--plot');
		this._svg.setAttribute('viewBox', '0 0 ' + this.width + ' ' + this.height);
		this._svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
		this.el.appendChild(this._svg);

		this._g = document.createElementNS(this.NS, 'g');
		this._svg.appendChild(this._g);

		// ── regions ──
		let regions = CerbUI.Map.toFeatures(this._regionsData);
		regions = this._joinRegionProperties(regions);
		regions = regions.filter(f => this._passesFilter(f, (this.map.regions || {}).filter));

		const fillFn = this._buildRegionFill(regions);
		this._regionEls = [];
		const regionsG = document.createElementNS(this.NS, 'g');
		regions.forEach((f) => {
			const d = CerbUI.Map.geometryToPath(f.geometry, this._projection, isMercator);
			if(!d) return;
			const path = document.createElementNS(this.NS, 'path');
			path.setAttribute('class', 'cerb-ui-map--region');
			path.setAttribute('d', d);
			path.setAttribute('fill-rule', 'evenodd');
			path.setAttribute('fill', fillFn(f));
			path.setAttribute('stroke-width', 0.2);
			// Browser holds the stroke constant under the <g> zoom transform, so panning/zooming never
			// needs to re-set per-region stroke-width (and borders stay crisp instead of thinning to 0.2/k).
			path.setAttribute('vector-effect', 'non-scaling-stroke');
			path._feature = f;
			path.addEventListener('click', (e) => { e.stopPropagation(); this._onFeatureClick(f, 'region', e); });
			regionsG.appendChild(path);
			this._regionEls.push(path);
		});
		this._g.appendChild(regionsG);

		// ── points ──
		let points = CerbUI.Map.toFeatures(this._pointsData);
		const inline = this.options.pointsInline;
		if(inline && inline.features && inline.features.length)
			points = points.concat(inline.features);
		points = points.filter(f => this._passesFilter(f, (this.map.points || {}).filter));

		this._pointEls = [];
		const pointsG = document.createElementNS(this.NS, 'g');
		points.forEach((f) => {
			if(!f.geometry || f.geometry.type !== 'Point') return;
			const xy = this._projection.project(f.geometry.coordinates);
			if(!xy) return;
			const r = this._pointRadius(f);
			const c = document.createElementNS(this.NS, 'circle');
			c.setAttribute('class', 'cerb-ui-map--point');
			c.setAttribute('r', r);
			c.setAttribute('stroke-width', r * 0.2);
			c.setAttribute('fill', this._pointFill(f));
			c.setAttribute('transform', 'translate(' + xy[0] + ',' + xy[1] + ')');
			c.dataset.r = r;
			c._feature = f;
			c.addEventListener('click', (e) => { e.stopPropagation(); this._onFeatureClick(f, 'point', e); });
			pointsG.appendChild(c);
			this._pointEls.push(c);
		});
		this._g.appendChild(pointsG);

		this._bindZoom();
		this._applyInitialZoom();
		this._updateCoords();

		this.el.dispatchEvent(new CustomEvent('cerb-ui-map:ready', {
			detail: { regions: this._regionEls.length, points: this._pointEls.length }, bubbles: true,
		}));
	}

	// ── region property join + filters ──

	_joinRegionProperties(regions) {
		const join = ((this.map.regions || {}).properties || {}).join;
		if(!join || !join.property) return regions;
		const resource = this._regionPropsData || {};
		const inline = this.options.regionPropertiesInline || {};
		regions.forEach((f) => {
			let v = f.properties ? f.properties[join.property] : null;
			if(typeof v !== 'string') return;
			if(join.case === 'upper') v = v.toUpperCase();
			else if(join.case === 'lower') v = v.toLowerCase();
			if(resource && Object.prototype.hasOwnProperty.call(resource, v))
				f.properties = Object.assign({}, f.properties, resource[v]);
			if(inline && Object.prototype.hasOwnProperty.call(inline, v))
				f.properties = Object.assign({}, f.properties, inline[v]);
		});
		return regions;
	}

	// Mirror the renderer's filter: {property, is|not} where the test value is a string, number, or array.
	_passesFilter(f, filter) {
		if(!filter || !filter.property) return true;
		const k = filter.property;
		const has = f.properties && Object.prototype.hasOwnProperty.call(f.properties, k);
		const not = (filter.not !== undefined && filter.not !== null);
		let v = not ? filter.not : filter.is;
		if(v === undefined || v === null) return false;
		if(!has) return not === true;
		if(typeof v === 'number') v = '' + v;
		const pv = f.properties[k];
		let equal;
		if(Array.isArray(v)) equal = v.some(vv => vv == pv);
		else equal = (pv == v);
		// `is`: keep when equal; `not`: keep when NOT equal
		return not ? !equal : equal;
	}

	// ── region fill (choropleth | color_key | color_map) ──

	_buildRegionFill(regions) {
		const fill = (this.map.regions || {}).fill || {};
		const fallback = 'var(--cerb-color-background-contrast-170)';

		if(fill.choropleth && fill.choropleth.property) {
			const prop = fill.choropleth.property;
			const classes = parseInt(fill.choropleth.classes, 10) || 5;
			const colorsSpec = fill.choropleth.colors || [];
			const from = colorsSpec[0] || '#f4e153', to = colorsSpec[1] || '#362142';
			let min = Infinity, max = -Infinity;
			regions.forEach((f) => {
				if(f.properties && f.properties[prop] != null) {
					const v = parseFloat(f.properties[prop]);
					if(!isNaN(v)) { if(v < min) min = v; if(v > max) max = v; }
				}
			});
			if(!isFinite(min)) { min = 0; max = 0; }
			const ramp = CerbUI.Map.hclRamp(from, to, classes);
			const scale = CerbUI.Map.quantizeScale(min, max, ramp);
			this._renderLegend(min, max, ramp, classes);
			return (f) => {
				if(f.properties && f.properties[prop] != null) {
					const v = parseFloat(f.properties[prop]);
					if(!isNaN(v)) return scale(v);
				}
				return fallback;
			};
		}

		if(fill.color_key && fill.color_key.property) {
			const prop = fill.color_key.property;
			return (f) => (f.properties && f.properties[prop] != null) ? f.properties[prop] : fallback;
		}

		if(fill.color_map && fill.color_map.property) {
			const prop = fill.color_map.property;
			const cmap = fill.color_map.colors || {};
			return (f) => {
				if(f.properties && f.properties[prop] != null && cmap[f.properties[prop]] != null)
					return cmap[f.properties[prop]];
				return fallback;
			};
		}

		return () => fallback;
	}

	_renderLegend(min, max, ramp, classes) {
		const legend = this._legend;
		legend.replaceChildren();
		legend.style.display = 'flex';

		const lo = this._div('cerb-ui-map--legend-end');
		lo.textContent = CerbUI.Map._compact(min);
		legend.appendChild(lo);

		const spread = Math.abs(min) + Math.abs(max), step = spread / classes;
		ramp.forEach((color, i) => {
			const sw = this._div('cerb-ui-map--legend-swatch');
			sw.style.backgroundColor = color;
			const a = Math.ceil(min + i * step), b = Math.ceil(min + (i + 1) * step) - 1;
			sw.title = CerbUI.Map._compact(a) + ' – ' + CerbUI.Map._compact(b);
			legend.appendChild(sw);
		});

		const hi = this._div('cerb-ui-map--legend-end');
		hi.textContent = CerbUI.Map._compact(max);
		legend.appendChild(hi);
	}

	// ── point size + fill ──

	_pointRadius(f) {
		const size = (this.map.points || {}).size || {};
		const def = (size.default != null) ? parseFloat(size.default) : 2.0;
		if(size.value_map && size.value_map.property) {
			const v = f.properties ? f.properties[size.value_map.property] : null;
			const values = size.value_map.values || {};
			if(v != null && values[v] != null && !isNaN(parseFloat(values[v]))) return parseFloat(values[v]);
		}
		return def;
	}

	_pointFill(f) {
		const fill = (this.map.points || {}).fill || {};
		const def = fill.default || 'var(--cerb-color-background-contrast-100)';
		if(fill.color_map && fill.color_map.property) {
			const v = f.properties ? f.properties[fill.color_map.property] : null;
			const colors = fill.color_map.colors || {};
			if(v != null && colors[v] != null) return colors[v];
		}
		return def;
	}

	// ─────────────────────────────────────────────────────────────────────────────
	// Pan / zoom
	// ─────────────────────────────────────────────────────────────────────────────

	// A shared, page-wide wheel-gesture tracker: a single passive capture-phase listener records where the
	// current scroll gesture BEGAN (the nearest .cerb-ui-map, or null for the page). A new gesture starts
	// after an idle gap. Each map only zooms when it owns the active gesture — so a page scroll that merely
	// passes over a map falls through and keeps scrolling the dashboard.
	static _ensureWheelGesture() {
		if(CerbUI.Map._wheelGesture) return;
		CerbUI.Map._wheelGesture = { owner: null, at: 0 };
		if(typeof document === 'undefined' || !document.addEventListener) return;
		document.addEventListener('wheel', (e) => {
			const now = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
			const g = CerbUI.Map._wheelGesture;
			if(now - g.at > 250) // idle gap → a fresh gesture; bind it to wherever it began
				g.owner = (e.target && e.target.closest) ? e.target.closest('.cerb-ui-map') : null;
			g.at = now;
		}, { capture: true, passive: true });
	}

	_bindZoom() {
		const svg = this._svg;
		CerbUI.Map._ensureWheelGesture();

		this._onWheel = (e) => {
			// Only zoom if the active scroll gesture began over THIS map; otherwise let the page scroll.
			const g = CerbUI.Map._wheelGesture;
			if(!g || g.owner !== this.el) return;

			// Already zoomed all the way out: don't escape to the page instantly. Absorb a little zoom-out
			// scroll first (a dead zone that signals "you're fully zoomed out"), then release the wheel to
			// the page once the user pushes past the threshold. The accumulator resets after an idle gap (a
			// fresh gesture) or as soon as any real zoom happens (the branch below).
			if(this._transform.k <= 1 && e.deltaY > 0) {
				const now = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
				if(now - this._overscrollAt > 250) this._overscroll = 0; // fresh gesture → a new dead zone
				this._overscrollAt = now;
				let dy = e.deltaY;
				if(e.deltaMode === 1) dy *= 16; else if(e.deltaMode === 2) dy *= this.height; // lines/pages → ~px
				this._overscroll += dy;
				if(this._overscroll < CerbUI.Map.OVERSCROLL_RELEASE) { e.preventDefault(); return; } // absorb: nothing moves
				return; // pushed past the dead zone → let the page scroll
			}

			this._overscroll = 0; // any real zoom (in, or out while still zoomable) clears the resistance
			e.preventDefault();
			const p = this._clientToSvg(e);
			this._zoomAt(p[0], p[1], e.deltaY < 0 ? 1.2 : 1 / 1.2);
		};
		svg.addEventListener('wheel', this._onWheel, { passive: false });

		// Drag-to-pan. A pan must NOT select the feature under the cursor on release, so once the pointer
		// travels past a small threshold we flag `_dragMoved` and let the capture-phase click handler below
		// eat the trailing click (equivalent to d3.zoom's clickDistance()). Threshold is in viewBox units
		// (~1 unit ≈ 1px at the default 600-wide viewBox); 4 units squared = 16.
		let dragging = false, last = null, down = null;
		this._onDown = (e) => {
			dragging = true; this._dragMoved = false;
			last = down = this._clientToSvg(e);
			svg.classList.add('is-panning');
		};
		this._onMove = (e) => {
			if(!dragging) return;
			const p = this._clientToSvg(e);
			this._transform.x += p[0] - last[0]; this._transform.y += p[1] - last[1];
			last = p;
			if(!this._dragMoved) {
				const ddx = p[0] - down[0], ddy = p[1] - down[1];
				if(ddx * ddx + ddy * ddy > 16) this._dragMoved = true;
			}
			this._applyTransform();
		};
		this._onUp = () => {
			if(!dragging) return;
			dragging = false;
			svg.classList.remove('is-panning');
			if(this._dragMoved) this._updateCoords();
		};
		// Capture phase runs before the region/point target-phase click listeners, so stopPropagation here
		// prevents the pan-release click from selecting a feature. Reset so the next genuine click works.
		this._onCaptureClick = (e) => { if(this._dragMoved) { e.stopPropagation(); this._dragMoved = false; } };

		svg.addEventListener('pointerdown', this._onDown);
		svg.addEventListener('click', this._onCaptureClick, true);
		window.addEventListener('pointermove', this._onMove);
		window.addEventListener('pointerup', this._onUp);
	}

	// Convert a pointer event's client coords into the svg's viewBox coordinate space.
	_clientToSvg(e) {
		const rect = this._svg.getBoundingClientRect();
		if(!rect.width || !rect.height) return [this.width / 2, this.height / 2];
		return [
			(e.clientX - rect.left) / rect.width * this.width,
			(e.clientY - rect.top) / rect.height * this.height,
		];
	}

	_zoomAt(px, py, factor) {
		const t = this._transform;
		const newK = Math.max(1, Math.min(40, t.k * factor));
		const f = newK / t.k;
		t.x = px - (px - t.x) * f;
		t.y = py - (py - t.y) * f;
		t.k = newK;
		this._applyTransform();
		this._updateCoords();
	}

	_applyTransform() {
		const t = this._transform;
		this._g.setAttribute('transform', 'translate(' + t.x + ',' + t.y + ') scale(' + t.k + ')');
		// Region strokes stay constant via vector-effect:non-scaling-stroke (set once at creation) — no
		// per-region work here. Points are <circle>s whose r is in user units, so they still need a
		// counter-scale — but ONLY when the zoom factor actually changes. A pan leaves k untouched, so it
		// costs just the <g> transform above (O(1)/frame) instead of looping every point every mousemove.
		if(t.k !== this._lastK) {
			this._lastK = t.k;
			this._pointEls.forEach(c => {
				const r = parseFloat(c.dataset.r) / t.k;
				c.setAttribute('r', r);
				c.setAttribute('stroke-width', r * 0.2);
			});
		}
	}

	_applyInitialZoom() {
		const zoom = (this.map.projection || {}).zoom;
		this._applyTransform();
		if(!zoom) return;
		const hasLL = (zoom.longitude != null && zoom.latitude != null);
		const scale = zoom.scale != null ? Math.max(1, Math.min(40, parseFloat(zoom.scale))) : this._transform.k;
		if(hasLL) this._zoomToLonLat([parseFloat(zoom.longitude), parseFloat(zoom.latitude)], scale);
	}

	// Center the given lon/lat at scale k (used by initial zoom + the toolbar buttons via the current center).
	_zoomToLonLat(lonlat, k) {
		const p = this._projection.project(lonlat);
		if(!p) return;
		k = Math.max(1, Math.min(40, k));
		this._transform.k = k;
		this._transform.x = this.width / 2 - k * p[0];
		this._transform.y = this.height / 2 - k * p[1];
		this._applyTransform();
		this._updateCoords();
	}

	_currentCenterLonLat() {
		const t = this._transform;
		return this._projection.invert([(this.width / 2 - t.x) / t.k, (this.height / 2 - t.y) / t.k]);
	}

	_onToolClick(action) {
		if(action === 'reset') {
			this._transform = { k: 1, x: 0, y: 0 };
			this._applyTransform();
			this._clearSelection();
			this._updateCoords();
		} else if(action === 'zoom-in') {
			this._zoomToLonLat(this._currentCenterLonLat(), this._transform.k * 1.5);
		} else if(action === 'zoom-out') {
			this._zoomToLonLat(this._currentCenterLonLat(), this._transform.k / 1.5);
		}
	}

	_updateCoords() {
		if(!this._projection) return;
		const ll = this._currentCenterLonLat();
		if(!ll) { this._coords.textContent = ''; return; }
		this._coords.textContent = 'Lat: ' + ll[1].toFixed(4) + ' Long: ' + ll[0].toFixed(4)
			+ ' Scale: ' + (this._projection.scale * this._transform.k).toFixed(0);
	}

	// ─────────────────────────────────────────────────────────────────────────────
	// Selection + label + automation round-trip
	// ─────────────────────────────────────────────────────────────────────────────

	_onFeatureClick(f, type, e) {
		if(this._selected === f) { this._clearSelection(); return; }
		this._selected = f;
		this._focus();

		this.el.dispatchEvent(new CustomEvent('cerb-ui-map:click', {
			detail: { feature_type: type, properties: f.properties, point: { x: e.clientX, y: e.clientY } }, bubbles: true,
		}));

		if(this.click && this.click.enabled && this.click.widget_id && typeof genericAjaxPost === 'function'
			&& typeof Devblocks !== 'undefined' && Devblocks.objectToFormData) {
			const fd = new FormData();
			fd.set('c', this.click.c || 'profiles');
			fd.set('a', this.click.a || 'invokeWidget');
			fd.set('widget_id', this.click.widget_id);
			fd.set('action', 'mapClicked');
			Devblocks.objectToFormData({ feature_type: type, feature_properties: f.properties }, fd);
			genericAjaxPost(fd, null, null, (json) => {
				if(typeof json !== 'object' || !json) return;
				if(json.error) { Devblocks.clearAlerts(); Devblocks.createAlertError(json.error); }
				else if(json.sheet) { this._label.style.display = 'inline-block'; this._labelBody.innerHTML = json.sheet; }
				else this._showLabel(f, type);
			});
		} else {
			this._showLabel(f, type);
		}
	}

	_showLabel(f, type) {
		const label = ((this.map[type + 's'] || {}).label) || {};
		this._setLabelToProperties(f, label.properties, label.title);
	}

	// Property-table popup (mirrors the renderer's setLabelToProperties): optional title, then a rows table.
	_setLabelToProperties(f, propertyMeta, title) {
		const props = f.properties || {};
		if(!propertyMeta) { propertyMeta = {}; Object.keys(props).forEach(k => { propertyMeta[k] = {}; }); }
		this._labelBody.replaceChildren();

		const titleKey = title || 'name';
		if(props[titleKey] != null) {
			const h = document.createElement('h1');
			h.className = 'cerb-ui-map--label-title';
			h.textContent = props[titleKey];
			this._labelBody.appendChild(h);
		}

		const table = document.createElement('table');
		table.className = 'cerb-ui-map--label-table';
		Object.keys(propertyMeta).forEach((k) => {
			const meta = propertyMeta[k] || {};
			const tr = document.createElement('tr');
			const tdk = document.createElement('td');
			tdk.className = 'cerb-ui-map--label-key';
			tdk.textContent = (meta.label != null ? meta.label : k) + ':';
			const tdv = document.createElement('td');
			let val = props[k] != null ? props[k] : '';
			if(meta.format === 'number' && val !== '' && CerbUI.num) val = CerbUI.num.format(',')(val);
			tdv.textContent = val;
			tr.appendChild(tdk); tr.appendChild(tdv);
			table.appendChild(tr);
		});
		this._labelBody.appendChild(table);
		this._label.style.display = 'inline-block';
	}

	// Dim every feature except the selected one (regions and points both).
	_focus() {
		const sel = this._selected;
		this._regionEls.forEach(p => p.classList.toggle('is-dimmed', sel != null && p._feature !== sel));
		this._pointEls.forEach(c => c.classList.toggle('is-dimmed', sel != null && c._feature !== sel));
	}

	_clearSelection() {
		this._selected = null;
		this._label.style.display = 'none';
		this._labelBody.replaceChildren();
		if(this._regionEls) this._regionEls.forEach(p => p.classList.remove('is-dimmed'));
		if(this._pointEls) this._pointEls.forEach(c => c.classList.remove('is-dimmed'));
	}

	// ─────────────────────────────────────────────────────────────────────────────

	destroy() {
		if(this._onWheel && this._svg) this._svg.removeEventListener('wheel', this._onWheel);
		if(this._onCaptureClick && this._svg) this._svg.removeEventListener('click', this._onCaptureClick, true);
		if(this._onMove) window.removeEventListener('pointermove', this._onMove);
		if(this._onUp) window.removeEventListener('pointerup', this._onUp);
		this.el.replaceChildren();
		this.el.classList.remove('cerb-ui-map');
		CerbUI.Map._instances.delete(this.el);
	}
};

// Node harness (pure-logic tests) can require the projection/topology/color statics without a DOM.
if(typeof module !== 'undefined' && module.exports)
	module.exports = CerbUI.Map;
