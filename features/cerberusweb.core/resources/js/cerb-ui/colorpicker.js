/*
 * CerbUI.ColorPicker — a zero-dependency Photoshop-style color picker bound to a text <input>.
 *
 * Usage:
 *   new CerbUI.ColorPicker(inputEl, { palette: 'rainbow', alpha: false, onChange: (hex, rgba) => { ... } });
 *
 * Enhances a text <input>: wraps it in a color "well" (a swatch over a transparency checkerboard, next to
 * the input that shows/accepts the hex code) and builds a floating panel appended to <body> — an SV
 * (saturation/value) square, a vertical hue strip, an optional alpha strip, and a palette swatch row. The
 * <input> keeps holding the value, so forms submit it and direct typing works; every change fires input +
 * change on the input plus a `cerb-ui-colorpicker:change` CustomEvent (detail = { hex, rgba }). Chrome
 * colors are tokens, so dark mode is automatic. Pass showInput:false to render only the swatch chip — the
 * <input> stays in the DOM (hidden) so it still holds/posts the value.
 */

// ── Color math (module-level, pure) ───────────────────────────────────────────

function _cpClamp(n, min, max) { return n < min ? min : (n > max ? max : n); }
function _cpHex2(n) { return _cpClamp(Math.round(n), 0, 255).toString(16).padStart(2, '0'); }

// h ∈ [0,360), s/v ∈ [0,1] → {r,g,b} ∈ [0,255]
function _cpHsvToRgb(h, s, v) {
	h = ((h % 360) + 360) % 360;
	const c = v * s;
	const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
	const m = v - c;
	let r = 0, g = 0, b = 0;
	if(h < 60)       { r = c; g = x; }
	else if(h < 120) { r = x; g = c; }
	else if(h < 180) { g = c; b = x; }
	else if(h < 240) { g = x; b = c; }
	else if(h < 300) { r = x; b = c; }
	else             { r = c; b = x; }
	return { r: Math.round((r + m) * 255), g: Math.round((g + m) * 255), b: Math.round((b + m) * 255) };
}

// {r,g,b} ∈ [0,255] → {h ∈ [0,360), s/v ∈ [0,1]}
function _cpRgbToHsv(r, g, b) {
	r /= 255; g /= 255; b /= 255;
	const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
	let h = 0;
	if(d !== 0) {
		if(max === r)      h = 60 * ((((g - b) / d) % 6 + 6) % 6);
		else if(max === g) h = 60 * ((b - r) / d + 2);
		else               h = 60 * ((r - g) / d + 4);
	}
	return { h, s: max === 0 ? 0 : d / max, v: max };
}

// Accepts '#rgb' / '#rrggbb' / '#rrggbbaa' with or without the leading '#'; returns {r,g,b,a} or null.
function _cpParseHex(str) {
	if(typeof str !== 'string') return null;
	let s = str.trim().replace(/^#/, '');
	if(/^[0-9a-fA-F]{3}$/.test(s)) s = s.split('').map(c => c + c).join('');
	if(/^[0-9a-fA-F]{6}$/.test(s))
		return { r: parseInt(s.slice(0, 2), 16), g: parseInt(s.slice(2, 4), 16), b: parseInt(s.slice(4, 6), 16), a: 1 };
	if(/^[0-9a-fA-F]{8}$/.test(s))
		return { r: parseInt(s.slice(0, 2), 16), g: parseInt(s.slice(2, 4), 16), b: parseInt(s.slice(4, 6), 16), a: parseInt(s.slice(6, 8), 16) / 255 };
	return null;
}

CerbUI.ColorPicker = class {
	static _uid = 0;
	static _instances = new WeakMap();
	static from(el) { return CerbUI.ColorPicker._instances.get(el); }

	constructor(inputEl, opts = {}) {
		this.inputEl = (typeof inputEl === 'string') ? document.querySelector(inputEl) : inputEl;
		if(!this.inputEl) return;
		this.uid = ++CerbUI.ColorPicker._uid;

		this.opts = Object.assign({
			palette: 'rainbow',  // array or palette name (CerbUI.resolvePalette); default 'rainbow'
			alpha:   false,      // show an opacity strip and emit #rrggbbaa / rgba()
			showInput: true,     // false = render only the swatch chip; the hidden <input> still holds/posts the value
			onChange: null,      // (hex, rgba, input) => {}
			onOpen:   null,
			onClose:  null,
		}, opts);

		this.swatchColors = CerbUI.resolvePalette(this.opts.palette);

		// HSVA state (the canonical value; hex/rgb are derived)
		this.h = 0; this.s = 0; this.v = 0; this.a = 1;
		const parsed = _cpParseHex(this.inputEl.value);
		if(parsed) {
			const hsv = _cpRgbToHsv(parsed.r, parsed.g, parsed.b);
			this.h = hsv.h; this.s = hsv.s; this.v = hsv.v;
			this.a = this.opts.alpha ? parsed.a : 1;
		}

		// Stable handler references (add/remove)
		this.docClick = null;
		this.docKeydown = null;
		this.onInputInput = this.onInputInput.bind(this);
		this.onInputFocus = this.onInputFocus.bind(this);
		this.onSwatchClick = this.onSwatchClick.bind(this);

		this._buildWell();
		this._buildPanel();
		this._render();

		this.inputEl.setAttribute('autocomplete', 'off');
		this.inputEl.setAttribute('spellcheck', 'false');
		this.inputEl.addEventListener('input', this.onInputInput);
		this.inputEl.addEventListener('focus', this.onInputFocus);
		this.inputEl.addEventListener('click', this.onInputFocus);

		CerbUI.ColorPicker._instances.set(this.inputEl, this);
		CerbUI.ColorPicker._instances.set(this.well, this);
	}

	// ── DOM builders ──────────────────────────────────────────────────────────

	_buildWell() {
		this.well = document.createElement('span');
		this.well.className = 'cerb-ui-colorpicker';
		// Swatch-only: strip the field chrome and hide the input via CSS (the input stays in the DOM below).
		if(!this.opts.showInput) this.well.classList.add('cerb-ui-colorpicker--swatch-only');

		this.swatch = document.createElement('button');
		this.swatch.type = 'button';
		this.swatch.className = 'cerb-ui-colorpicker--swatch';
		this.swatch.setAttribute('aria-haspopup', 'dialog');
		this.swatch.setAttribute('aria-expanded', 'false');
		this.swatch.setAttribute('aria-controls', `cerb-ui-colorpicker-${this.uid}`);
		this.swatch.setAttribute('aria-label', 'Choose a color');

		this.swatchFill = document.createElement('span');
		this.swatchFill.className = 'cerb-ui-colorpicker--swatch-fill';
		this.swatch.appendChild(this.swatchFill);
		this.swatch.addEventListener('click', this.onSwatchClick);

		// Insert the well where the input is, then move the input inside it.
		this.inputEl.parentNode.insertBefore(this.well, this.inputEl);
		this.inputEl.classList.add('cerb-ui-colorpicker--input');
		this.well.appendChild(this.swatch);
		this.well.appendChild(this.inputEl);
	}

	_buildPanel() {
		this.el = document.createElement('div');
		this.el.className = 'cerb-ui-colorpicker--panel';
		this.el.setAttribute('role', 'dialog');
		this.el.setAttribute('aria-modal', 'false');
		this.el.setAttribute('aria-label', 'Color picker');
		this.el.setAttribute('id', `cerb-ui-colorpicker-${this.uid}`);
		this.el.setAttribute('hidden', '');

		const body = document.createElement('div');
		body.className = 'cerb-ui-colorpicker--body';

		// Saturation/value square
		this.sv = document.createElement('div');
		this.sv.className = 'cerb-ui-colorpicker--sv';
		this.svThumb = document.createElement('div');
		this.svThumb.className = 'cerb-ui-colorpicker--sv-thumb';
		this.sv.appendChild(this.svThumb);
		this.sv.addEventListener('pointerdown', (e) => {
			e.preventDefault();
			this._startDrag(e, this.sv, (x, y, w, hh) => {
				this.s = _cpClamp(x / w, 0, 1);
				this.v = _cpClamp(1 - y / hh, 0, 1);
				this._emit(true);
			});
		});

		// Hue strip
		this.hue = document.createElement('div');
		this.hue.className = 'cerb-ui-colorpicker--hue';
		this.hueThumb = document.createElement('div');
		this.hueThumb.className = 'cerb-ui-colorpicker--strip-thumb';
		this.hue.appendChild(this.hueThumb);
		this.hue.addEventListener('pointerdown', (e) => {
			e.preventDefault();
			this._startDrag(e, this.hue, (x, y, w, hh) => {
				this.h = _cpClamp(y / hh, 0, 1) * 360;
				this._emit(true);
			});
		});

		body.appendChild(this.sv);
		body.appendChild(this.hue);

		// Alpha strip (optional)
		if(this.opts.alpha) {
			this.alpha = document.createElement('div');
			this.alpha.className = 'cerb-ui-colorpicker--alpha';
			this.alphaFill = document.createElement('div');
			this.alphaFill.className = 'cerb-ui-colorpicker--alpha-fill';
			this.alphaThumb = document.createElement('div');
			this.alphaThumb.className = 'cerb-ui-colorpicker--strip-thumb';
			this.alpha.appendChild(this.alphaFill);
			this.alpha.appendChild(this.alphaThumb);
			this.alpha.addEventListener('pointerdown', (e) => {
				e.preventDefault();
				this._startDrag(e, this.alpha, (x, y, w, hh) => {
					this.a = _cpClamp(1 - y / hh, 0, 1);
					this._emit(true);
				});
			});
			body.appendChild(this.alpha);
		}

		this.el.appendChild(body);

		// Palette swatch row
		this.swatchRow = document.createElement('div');
		this.swatchRow.className = 'cerb-ui-colorpicker--swatches';
		this.swatchColors.forEach((color) => {
			const cell = document.createElement('button');
			cell.type = 'button';
			cell.className = 'cerb-ui-colorpicker--swatch-cell';
			cell.style.backgroundColor = color;
			cell.setAttribute('aria-label', color);
			cell.title = color;
			cell.addEventListener('click', () => {
				const rgb = _cpParseHex(color);
				if(!rgb) return;
				const hsv = _cpRgbToHsv(rgb.r, rgb.g, rgb.b);
				if(hsv.s !== 0) this.h = hsv.h; // keep current hue for grays (avoids thumb jump)
				this.s = hsv.s; this.v = hsv.v;
				this._emit(true);
			});
			this.swatchRow.appendChild(cell);
		});
		this.el.appendChild(this.swatchRow);

		// Swatch-only mode hides the inline field, so the panel carries its own hex input for copy/paste and
		// manual entry. (With showInput:true the inline field already serves that, so we skip it here.)
		if(!this.opts.showInput) {
			this.panelInput = document.createElement('input');
			this.panelInput.type = 'text';
			this.panelInput.className = 'cerb-ui-colorpicker--panel-input';
			this.panelInput.setAttribute('autocomplete', 'off');
			this.panelInput.setAttribute('spellcheck', 'false');
			this.panelInput.setAttribute('aria-label', 'Hex color value');
			this.panelInput.addEventListener('input', () => this._onPanelInput());
			this.el.appendChild(this.panelInput);
		}

		document.body.appendChild(this.el);
	}

	// Typing into the panel's own hex field (swatch-only mode). Mirrors onInputInput but pushes the value to
	// the hidden form <input> too. _render() leaves this field's text alone while it's focused (no cursor jump).
	_onPanelInput() {
		const parsed = _cpParseHex(this.panelInput.value);
		if(!parsed) return;
		const hsv = _cpRgbToHsv(parsed.r, parsed.g, parsed.b);
		if(hsv.s !== 0) this.h = hsv.h; // keep hue for grays so the SV/hue thumbs don't jump
		this.s = hsv.s; this.v = hsv.v;
		if(this.opts.alpha) this.a = parsed.a;
		this._emit(true);
	}

	// ── Render (visual only; no events, no input write) ───────────────────────

	_render() {
		const { r, g, b } = _cpHsvToRgb(this.h, this.s, this.v);
		const a = this.opts.alpha ? this.a : 1;
		const hue = _cpHsvToRgb(this.h, 1, 1);

		this.swatchFill.style.backgroundColor = `rgba(${r},${g},${b},${a})`;
		this.sv.style.backgroundColor = `rgb(${hue.r},${hue.g},${hue.b})`;

		this.svThumb.style.left = (this.s * 100) + '%';
		this.svThumb.style.top = ((1 - this.v) * 100) + '%';
		this.svThumb.style.backgroundColor = `rgb(${r},${g},${b})`;

		this.hueThumb.style.top = ((this.h / 360) * 100) + '%';

		if(this.opts.alpha) {
			this.alphaThumb.style.top = ((1 - this.a) * 100) + '%';
			this.alphaFill.style.background = `linear-gradient(to bottom, rgba(${r},${g},${b},1), rgba(${r},${g},${b},0))`;
		}

		// Keep the panel's hex field in sync — but not while the user is typing in it (avoids cursor jump).
		if(this.panelInput && document.activeElement !== this.panelInput)
			this.panelInput.value = this.getValue();
	}

	// ── Value derivation ──────────────────────────────────────────────────────

	getValue() {
		const { r, g, b } = _cpHsvToRgb(this.h, this.s, this.v);
		let hex = '#' + _cpHex2(r) + _cpHex2(g) + _cpHex2(b);
		if(this.opts.alpha && this.a < 1) hex += _cpHex2(this.a * 255);
		return hex;
	}

	getRgba() {
		const { r, g, b } = _cpHsvToRgb(this.h, this.s, this.v);
		const a = this.opts.alpha ? +this.a.toFixed(3) : 1;
		return `rgba(${r}, ${g}, ${b}, ${a})`;
	}

	setValue(hex) {
		const parsed = _cpParseHex(hex);
		if(!parsed) return this;
		const hsv = _cpRgbToHsv(parsed.r, parsed.g, parsed.b);
		if(hsv.s !== 0) this.h = hsv.h;
		this.s = hsv.s; this.v = hsv.v;
		if(this.opts.alpha) this.a = parsed.a;
		this._render();
		this.inputEl.value = this.getValue();
		return this;
	}

	// ── Change emission ───────────────────────────────────────────────────────

	// Picker gestures call this. writeInput=true updates the <input> + fires its native input/change;
	// callers driven by the input itself (typing) pass false to avoid re-dispatching the native events.
	_emit(writeInput) {
		this._render();
		const hex = this.getValue();
		const rgba = this.getRgba();
		if(writeInput) {
			this.inputEl.value = hex;
			this.inputEl.dispatchEvent(new Event('input', { bubbles: true }));
			this.inputEl.dispatchEvent(new Event('change', { bubbles: true }));
		}
		if(typeof this.opts.onChange === 'function') this.opts.onChange(hex, rgba, this.inputEl);
		this.inputEl.dispatchEvent(new CustomEvent('cerb-ui-colorpicker:change', { detail: { hex, rgba }, bubbles: true }));
	}

	// ── Pointer drag (tracks outside the target while held) ───────────────────

	_startDrag(e, el, onMove) {
		const run = (ev) => {
			const rect = el.getBoundingClientRect();
			onMove(ev.clientX - rect.left, ev.clientY - rect.top, rect.width, rect.height);
		};
		run(e);
		const up = () => {
			document.removeEventListener('pointermove', run);
			document.removeEventListener('pointerup', up);
		};
		document.addEventListener('pointermove', run);
		document.addEventListener('pointerup', up);
	}

	// ── Input / swatch listeners ──────────────────────────────────────────────

	onInputInput() {
		const parsed = _cpParseHex(this.inputEl.value);
		if(!parsed) return;
		const hsv = _cpRgbToHsv(parsed.r, parsed.g, parsed.b);
		if(hsv.s !== 0) this.h = hsv.h; // keep hue for grays so the SV/hue thumbs don't jump
		this.s = hsv.s; this.v = hsv.v;
		if(this.opts.alpha) this.a = parsed.a;
		this._render();
		// The browser already fired native input/change for the typed value; only add our extras.
		const hex = this.getValue(), rgba = this.getRgba();
		if(typeof this.opts.onChange === 'function') this.opts.onChange(hex, rgba, this.inputEl);
		this.inputEl.dispatchEvent(new CustomEvent('cerb-ui-colorpicker:change', { detail: { hex, rgba }, bubbles: true }));
	}

	onInputFocus() {
		if(!this.isOpen()) this.open();
	}

	onSwatchClick() {
		this.isOpen() ? this.close() : this.open();
	}

	// ── Positioning ───────────────────────────────────────────────────────────

	position() {
		const rect = this.well.getBoundingClientRect();
		const ph = this.el.offsetHeight || 240;
		const pw = this.el.offsetWidth || 232;

		// Flip above the well if there isn't enough room below in the viewport.
		const topViewport = (window.innerHeight - rect.bottom >= ph + 8 || rect.top < ph + 8)
			? rect.bottom + 4
			: rect.top - ph - 4;

		// Clamp so the right edge stays in the viewport.
		const leftViewport = Math.min(rect.left, document.documentElement.clientWidth - pw - 8);

		this.el.style.top = `${topViewport + window.scrollY}px`;
		this.el.style.left = `${Math.max(8, leftViewport) + window.scrollX}px`;
	}

	// ── Open / Close ──────────────────────────────────────────────────────────

	open() {
		if(!this.el.hasAttribute('hidden')) return;
		this._render();
		this.el.removeAttribute('hidden');
		this.position();
		this.swatch.setAttribute('aria-expanded', 'true');
		this.attachDocListeners();
		if(typeof this.opts.onOpen === 'function') this.opts.onOpen();
	}

	close() {
		if(this.el.hasAttribute('hidden')) return;
		this.el.setAttribute('hidden', '');
		this.swatch.setAttribute('aria-expanded', 'false');
		if(this.docClick) { document.removeEventListener('click', this.docClick); this.docClick = null; }
		if(this.docKeydown) { document.removeEventListener('keydown', this.docKeydown); this.docKeydown = null; }
		if(typeof this.opts.onClose === 'function') this.opts.onClose();
	}

	isOpen() {
		return !this.el.hasAttribute('hidden');
	}

	attachDocListeners() {
		this.docClick = (e) => {
			const t = e.target;
			if(!this.el.contains(t) && !this.well.contains(t)) this.close();
		};
		this.docKeydown = (e) => {
			if(e.key === 'Escape') { e.preventDefault(); this.close(); }
		};
		// Defer so the click that opened us doesn't immediately close it.
		requestAnimationFrame(() => {
			document.addEventListener('click', this.docClick);
			document.addEventListener('keydown', this.docKeydown);
		});
	}

	// ── Teardown ──────────────────────────────────────────────────────────────

	destroy() {
		CerbUI.ColorPicker._instances.delete(this.inputEl);
		CerbUI.ColorPicker._instances.delete(this.well);
		this.close();
		this.inputEl.removeEventListener('input', this.onInputInput);
		this.inputEl.removeEventListener('focus', this.onInputFocus);
		this.inputEl.removeEventListener('click', this.onInputFocus);
		this.inputEl.classList.remove('cerb-ui-colorpicker--input');
		// Restore the input to where the well sat, then drop the well + panel.
		if(this.well.parentNode) this.well.parentNode.insertBefore(this.inputEl, this.well);
		this.well.remove();
		this.el.remove();
	}
};
