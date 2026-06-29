/*
 * CerbUI.ImageEditor — an editable image "well" (a CerbUI.Avatar) that opens a CerbUI.Dialog with a canvas
 * editor: pan/zoom an image under a fixed-aspect crop frame, fill a background color, erase, pick a Cerb
 * icon (CerbUI.IconPicker), and pull image bytes from the server-configured
 * `record.profile.image.editor` toolbar (emoji/monogram text, an uploaded or remote URL, or a Cerb icon via
 * `image:icon:`). Non-destructive — pan/zoom/crop only rasterize to a PNG data URL on Save.
 * Replaces the legacy jQuery `ajax.chooserAvatar`; the saved value (a `data:image/png;base64,…` URL, or
 * `data:null` to delete) is written to a hidden input so the existing profile save path
 * (DAO_ContextAvatar::upsertWithImage) is unchanged.
 *
 * Usage (enhance an avatar element):
 *   <span class="cerb-ui-avatar" data-cerb-image-editor
 *         data-context="cerberusweb.contexts.worker" data-context-id="5"
 *         data-image-width="256" data-image-height="256" data-name="avatar_image"
 *         data-avatar="Jane Doe" data-avatar-seed="worker:5"
 *         data-avatar-image="…c=avatars&context=worker&context_id=5"></span>
 *   <input type="hidden" name="avatar_image" value="">
 *   new CerbUI.ImageEditor(el);
 *
 * Options: context, contextId, width, height (output px; crop aspect = width:height), name (hidden field),
 *   value (seed image URL/data URL), palette (bg swatches), onSave(dataUrl|null), trigger (external opener
 *   selector/element). Context-less standalone use is allowed (no toolbar fetch — caller seeds via value).
 *
 * API: ed.open(); ed.close(); ed.getValue(); ed.destroy(); static CerbUI.ImageEditor.from(el).
 * Requires CerbUI.Dialog + CerbUI.ColorPicker (+ CerbUI.Toolbar for the server toolbar) + genericAjaxGet.
 */
CerbUI.ImageEditor = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.ImageEditor._instances.get(el); }

	static MIN_SCALE = 0.05;
	static MAX_SCALE = 20;
	static TOOLBAR_NAME = 'cerb.toolbar.record.profile.image.editor';

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		const d = this.el.dataset;

		this.opts = Object.assign({
			context:     d.context || '',
			contextId:   d.contextId || '',
			width:       parseInt(opts.width || d.imageWidth, 10) || 256,
			height:      parseInt(opts.height || d.imageHeight, 10) || 256,
			name:        opts.name || d.name || 'avatar_image',
			value:       opts.value || d.avatarImage || '',
			palette:     opts.palette || ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#CF25F5','#ADADAD','#34434E','#FFFFFF'],
			trigger:     opts.trigger || d.trigger || null,
			onSave:      opts.onSave || null,
		}, opts);

		this.outW = this.opts.width;
		this.outH = this.opts.height;
		// Crop overlay shape: 'rect' (the width:height aspect) or 'circle' (1:1, a circular guide — how most
		// profile pics are displayed). Square outputs default to a circle; override via data-crop-shape.
		this.cropShape = opts.cropShape || d.cropShape || (this.outW === this.outH ? 'circle' : 'rect');

		// Paint the avatar (current image else monogram/icon)
		if(CerbUI.Avatar && CerbUI.Avatar._apply)
			CerbUI.Avatar._apply(this.el, { label: d.avatar || '', seed: d.avatarSeed || '', imageUrl: d.avatarImage || '', icon: d.avatarIcon || '' });
		else if(CerbUI.Avatar)
			new CerbUI.Avatar(this.el);

		// The hidden input that posts the result (created if absent). Empty = no change. Resolve it now,
		// while the avatar still sits next to it (before we wrap the avatar below).
		this.input = null;
		if(this.opts.name) {
			const next = this.el.nextElementSibling;
			this.input = (next && next.matches && next.matches('input[type=hidden][name="' + this.opts.name + '"]'))
				? next
				: (this.el.parentNode ? this.el.parentNode.querySelector('input[type=hidden][name="' + this.opts.name + '"]') : null);
			if(!this.input) {
				this.input = document.createElement('input');
				this.input.type = 'hidden';
				this.input.name = this.opts.name;
				this.input.value = '';
				this.el.after(this.input);
			}
		}

		// Wrap the avatar in a click-target "well" with a hover pencil overlay. We WRAP (rather than append the
		// overlay as a child of the avatar) because CerbUI.Avatar wipes its own children when its image loads
		// (el.textContent=''), which would erase the overlay; the wrapper is never touched by Avatar.
		this.well = document.createElement('span');
		this.well.className = 'cerb-ui-image-editor';
		this.well.setAttribute('role', 'button');
		this.well.setAttribute('tabindex', '0');
		this.well.setAttribute('aria-label', 'Edit image');
		this.el.parentNode.insertBefore(this.well, this.el);
		this.well.appendChild(this.el); // move the avatar inside the well

		this.editBtn = document.createElement('span');
		this.editBtn.className = 'cerb-ui-image-editor--edit';
		this.editBtn.setAttribute('aria-hidden', 'true');
		this.editBtn.innerHTML = '<span class="cerb-icons cerb-icon-edit"></span>';
		this.well.appendChild(this.editBtn);

		// Clicking anywhere on the well opens the editor
		this._onElClick = (e) => { e.preventDefault(); this.open(); };
		this._onElKey = (e) => { if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.open(); } };
		this.well.addEventListener('click', this._onElClick);
		this.well.addEventListener('keydown', this._onElKey);

		// External trigger (a page button) — optional
		this._triggerEl = this.opts.trigger ? (typeof this.opts.trigger === 'string' ? document.querySelector(this.opts.trigger) : this.opts.trigger) : null;
		if(this._triggerEl) {
			this._onTriggerClick = (e) => { e.preventDefault(); this.open(); };
			this._triggerEl.addEventListener('click', this._onTriggerClick);
		}

		// Editor state
		this.image = null;      // HTMLImageElement currently loaded (the source); null = empty (delete)
		this.scale = 1;
		this.tx = 0; this.ty = 0;
		this.bgcolor = '#ffffff'; // canvas background fill
		this.fgcolor = '#ffffff'; // icon/text tint
		this._lastIconName = null; // last icon applied (so the fg color well can re-tint it live)
		this.dialog = null;

		CerbUI.ImageEditor._instances.set(this.el, this);
	}

	// ── Open / build the dialog ────────────────────────────────────────────────
	open() {
		if(this.dialog && this.dialog.isOpen && this.dialog.isOpen()) return;
		this._buildContent();
		this.dialog = new CerbUI.Dialog(this.content, {
			// NOT modal: the toolbar image sources open their own interaction popups, which a modal backdrop
			// would block.
			title: 'Image',
			modal: false,
			width: 560,
			closable: true,
			onClose: () => { this._teardownCanvas(); return true; },
		});
		this.dialog.open();
		this._initCanvas();

		// Seed from the current stored image + fetch the server toolbar
		this._fetchEditorData();
	}

	close() { if(this.dialog) this.dialog.close(); }

	getValue() { return this.input ? this.input.value : ''; }

	// ── Dialog content (built in JS) ───────────────────────────────────────────
	_buildContent() {
		const c = document.createElement('div');
		c.className = 'cerb-ui-image-editor--panel';

		// Stage: the working canvas
		const stage = document.createElement('div');
		stage.className = 'cerb-ui-image-editor--stage';
		this.canvas = document.createElement('canvas');
		this.canvas.className = 'cerb-ui-image-editor--canvas';
		stage.appendChild(this.canvas);

		// Tools
		const tools = document.createElement('div');
		tools.className = 'cerb-ui-image-editor--tools';

		const strip = document.createElement('div');
		strip.className = 'cerb-ui-image-editor--toolstrip';
		strip.appendChild(this._toolBtn('zoom-in', 'Zoom in', () => this._zoomAtCenter(1.1)));
		strip.appendChild(this._toolBtn('zoom-out', 'Zoom out', () => this._zoomAtCenter(1 / 1.1)));
		strip.appendChild(this._toolBtn('erase', 'Clear', () => this._erase()));
		// Icon picker well (CerbUI.IconPicker, enhanced in _initCanvas) — selecting an icon rasterizes it
		this.iconInput = document.createElement('input');
		this.iconInput.type = 'text';
		this.iconInput.className = 'cerb-ui-image-editor--iconinput';
		this.iconInput.setAttribute('aria-label', 'Choose an icon');
		strip.appendChild(this.iconInput);
		// Background + foreground color wells, right-aligned in the strip (swatch only; hex in the popup)
		const colors = document.createElement('div');
		colors.className = 'cerb-ui-image-editor--colors';
		this.bgInput = this._colorWell(colors, 'color-palette', 'Background color', this.bgcolor);
		this.fgInput = this._colorWell(colors, 'text-color', 'Foreground color', this.fgcolor);
		strip.appendChild(colors);
		tools.appendChild(strip);

		// Server toolbar mount (image sources)
		this.toolbarMount = document.createElement('div');
		this.toolbarMount.className = 'cerb-ui-image-editor--sources';
		tools.appendChild(this.toolbarMount);

		// Footer
		const footer = document.createElement('div');
		footer.className = 'cerb-ui-image-editor--footer';
		this.saveBtn = document.createElement('button');
		this.saveBtn.type = 'button';
		this.saveBtn.className = 'cerb-ui-button';
		this.saveBtn.innerHTML = '<span class="cerb-icons cerb-icon-circle-ok"></span> Save';
		this.cancelBtn = document.createElement('button');
		this.cancelBtn.type = 'button';
		this.cancelBtn.className = 'cerb-ui-button cerb-ui-button--subtle';
		this.cancelBtn.textContent = 'Cancel';
		footer.appendChild(this.saveBtn);
		footer.appendChild(this.cancelBtn);

		this.errorEl = document.createElement('div');
		this.errorEl.className = 'cerb-ui-image-editor--error';

		c.appendChild(stage);
		c.appendChild(tools);
		c.appendChild(this.errorEl);
		c.appendChild(footer);
		this.content = c;

		this._onSaveClick = () => this._save();
		this._onCancelClick = () => this.close();
		this.saveBtn.addEventListener('click', this._onSaveClick);
		this.cancelBtn.addEventListener('click', this._onCancelClick);
	}

	_toolBtn(icon, label, onClick) {
		const b = document.createElement('button');
		b.type = 'button';
		b.className = 'cerb-ui-image-editor--tool';
		b.title = label;
		b.setAttribute('aria-label', label);
		b.innerHTML = '<span class="cerb-icons cerb-icon-' + icon + '" aria-hidden="true"></span>';
		b.addEventListener('click', (e) => { e.preventDefault(); onClick(); });
		return b;
	}

	// A color well: an icon (with a tooltip) + a text input enhanced by CerbUI.ColorPicker in _initCanvas
	_colorWell(parent, icon, title, value) {
		const wrap = document.createElement('div');
		wrap.className = 'cerb-ui-image-editor--color';
		wrap.title = title;
		const glyph = document.createElement('span');
		glyph.className = 'cerb-icons cerb-icon-' + icon + ' cerb-ui-image-editor--color-icon';
		glyph.setAttribute('aria-hidden', 'true');
		const input = document.createElement('input');
		input.type = 'text';
		input.value = value;
		input.className = 'cerb-ui-image-editor--colorinput';
		input.setAttribute('spellcheck', 'false');
		input.setAttribute('aria-label', title);
		wrap.appendChild(glyph);
		wrap.appendChild(input);
		parent.appendChild(wrap);
		return input;
	}

	// ── Canvas setup ───────────────────────────────────────────────────────────
	_initCanvas() {
		// A square-ish working surface; the crop frame (output aspect) is centered inside with padding.
		const box = 360, pad = 24;
		this.canvas.width = box;
		this.canvas.height = box;
		this.ctx = this.canvas.getContext('2d');

		// Crop rect = output aspect fit inside (box - 2*pad)
		const avail = box - pad * 2;
		const ar = this.outW / this.outH;
		let cw = avail, ch = avail;
		if(ar >= 1) ch = avail / ar; else cw = avail * ar;
		this.crop = { x: (box - cw) / 2, y: (box - ch) / 2, w: cw, h: ch };

		// Color pickers: background fill + foreground tint
		if(CerbUI.ColorPicker) {
			this.colorPicker = new CerbUI.ColorPicker(this.bgInput, {
				palette: this.opts.palette,
				showInput: false, // swatch only; the hex shows in the popup panel
				onChange: (hex) => { this.bgcolor = hex; this._redraw(); },
			});
			this.fgColorPicker = new CerbUI.ColorPicker(this.fgInput, {
				palette: this.opts.palette,
				showInput: false,
				// Re-tint the current icon live when the foreground color changes
				onChange: (hex) => { this.fgcolor = hex; if(this._lastIconName) this.setFromIcon(this._lastIconName); },
			});
		}
		this.bgcolor = this.bgInput.value || '#ffffff';
		this.fgcolor = this.fgInput.value || '#ffffff';

		// Icon picker — selecting an icon rasterizes it; then reset the picker (it's a momentary action, not a
		// stored value), so the well returns to its placeholder ready for the next use.
		if(CerbUI.IconPicker) {
			this.iconPicker = new CerbUI.IconPicker(this.iconInput, {
				emptyIcon: 'sparkles',
				onChange: (name) => { if(name) { this.setFromIcon(name); this.iconPicker.setValue(''); } },
			});
		}

		// Pointer pan
		this._onDown = (e) => {
			this._panning = true;
			this._lastX = e.clientX; this._lastY = e.clientY;
			this.canvas.setPointerCapture && this.canvas.setPointerCapture(e.pointerId);
		};
		this._onMove = (e) => {
			if(!this._panning) return;
			this.tx += (e.clientX - this._lastX);
			this.ty += (e.clientY - this._lastY);
			this._lastX = e.clientX; this._lastY = e.clientY;
			this._redraw();
		};
		this._onUp = (e) => {
			this._panning = false;
			this.canvas.releasePointerCapture && this.canvas.hasPointerCapture && this.canvas.hasPointerCapture(e.pointerId) && this.canvas.releasePointerCapture(e.pointerId);
		};
		this._onWheel = (e) => {
			e.preventDefault();
			const r = this.canvas.getBoundingClientRect();
			const sx = this.canvas.width / r.width, sy = this.canvas.height / r.height;
			this._zoomAt((e.clientX - r.left) * sx, (e.clientY - r.top) * sy, e.deltaY < 0 ? 1.1 : 1 / 1.1);
		};
		this.canvas.addEventListener('pointerdown', this._onDown);
		this.canvas.addEventListener('pointermove', this._onMove);
		this.canvas.addEventListener('pointerup', this._onUp);
		this.canvas.addEventListener('pointercancel', this._onUp);
		this.canvas.addEventListener('wheel', this._onWheel, { passive: false });

		this._redraw();
	}

	_teardownCanvas() {
		if(this.colorPicker) { this.colorPicker.destroy(); this.colorPicker = null; }
		if(this.fgColorPicker) { this.fgColorPicker.destroy(); this.fgColorPicker = null; }
		if(this.iconPicker) { this.iconPicker.destroy(); this.iconPicker = null; }
		if(this.toolbar && this.toolbar.destroy) { this.toolbar.destroy(); this.toolbar = null; }
	}

	// ── Transform helpers ──────────────────────────────────────────────────────
	_clampScale(s) { return Math.max(CerbUI.ImageEditor.MIN_SCALE, Math.min(CerbUI.ImageEditor.MAX_SCALE, s)); }

	_zoomAt(cx, cy, factor) {
		if(!this.image) return;
		const ns = this._clampScale(this.scale * factor);
		this.tx = cx - (cx - this.tx) * (ns / this.scale);
		this.ty = cy - (cy - this.ty) * (ns / this.scale);
		this.scale = ns;
		this._redraw();
	}

	_zoomAtCenter(factor) { this._zoomAt(this.crop.x + this.crop.w / 2, this.crop.y + this.crop.h / 2, factor); }

	// Fit the current image to the crop: 'cover' fills it, 'contain' shows the whole thing with padding.
	_fit(mode) {
		if(!this.image) return;
		const iw = this.image.naturalWidth || 1, ih = this.image.naturalHeight || 1;
		const sCover = Math.max(this.crop.w / iw, this.crop.h / ih);
		const sContain = Math.min(this.crop.w / iw, this.crop.h / ih);
		this.scale = (mode === 'contain') ? sContain * 0.82 : sCover;
		this.tx = this.crop.x + (this.crop.w - iw * this.scale) / 2;
		this.ty = this.crop.y + (this.crop.h - ih * this.scale) / 2;
	}

	// ── Render ─────────────────────────────────────────────────────────────────
	_redraw() {
		if(!this.ctx) return;
		const ctx = this.ctx, W = this.canvas.width, H = this.canvas.height, cr = this.crop;
		ctx.clearRect(0, 0, W, H);

		// Background fill everywhere (transparent image areas show it; outside-crop gets dimmed below)
		ctx.fillStyle = this.bgcolor;
		ctx.fillRect(0, 0, W, H);

		if(this.image)
			ctx.drawImage(this.image, this.tx, this.ty, this.image.naturalWidth * this.scale, this.image.naturalHeight * this.scale);

		ctx.fillStyle = 'rgba(0,0,0,0.45)';
		ctx.strokeStyle = 'rgba(255,255,255,0.9)';
		ctx.lineWidth = 1;

		if(this.cropShape === 'circle') {
			// Dim everything outside a circle inscribed in the (square) crop, then frame it. The saved image is
			// still the square — the circle just previews how a round avatar will be cropped.
			const cx = cr.x + cr.w / 2, cy = cr.y + cr.h / 2, r = Math.min(cr.w, cr.h) / 2;
			ctx.beginPath();
			ctx.rect(0, 0, W, H);
			ctx.arc(cx, cy, r, 0, Math.PI * 2, true); // reverse winding punches a hole
			ctx.fill('evenodd');
			ctx.beginPath();
			ctx.arc(cx, cy, r, 0, Math.PI * 2);
			ctx.stroke();
		} else {
			// Dim everything outside the crop rect, then frame it
			ctx.fillRect(0, 0, W, cr.y);
			ctx.fillRect(0, cr.y + cr.h, W, H - (cr.y + cr.h));
			ctx.fillRect(0, cr.y, cr.x, cr.h);
			ctx.fillRect(cr.x + cr.w, cr.y, W - (cr.x + cr.w), cr.h);
			ctx.strokeRect(cr.x + 0.5, cr.y + 0.5, cr.w - 1, cr.h - 1);
		}
	}

	// ── Image sources ──────────────────────────────────────────────────────────
	_setImage(src, fit) {
		const img = new Image();
		img.addEventListener('load', () => {
			this.image = img;
			this._fit(fit || 'cover');
			this._redraw();
		});
		img.addEventListener('error', () => this._showError('The image could not be loaded.'));
		img.src = src;
	}

	setFromImageData(dataUrl) { this._lastIconName = null; if(dataUrl) this._setImage(dataUrl, 'cover'); }

	setFromText(text) {
		if(!text) return;
		this._lastIconName = null;
		// Text reads best on a colored background; switch off plain white like the legacy.
		if((this.bgcolor || '').toLowerCase() === '#ffffff') this._setBg('#1e5271');

		const c = document.createElement('canvas');
		c.width = this.outW; c.height = this.outH;
		const t = c.getContext('2d');
		let pt = Math.round(this.outH * 0.7);
		let bounds = { width: this.outW };
		do {
			pt -= Math.max(2, Math.round(this.outH * 0.04));
			t.font = 'Bold ' + pt + 'pt Arial';
			bounds = t.measureText(text);
		} while(bounds.width > this.outW * 0.92 && pt > 8);
		t.fillStyle = this.fgcolor || '#FFFFFF';
		t.textBaseline = 'middle';
		t.fillText(text, (this.outW - bounds.width) / 2, this.outH / 2);
		this._setImage(c.toDataURL('image/png'), 'cover');
	}

	setFromUrl(url) {
		if(!url) return;
		this._lastIconName = null;
		this._showError('');
		this._setBusy(true);
		genericAjaxGet('', 'c=avatars&a=_fetch&url=' + encodeURIComponent(url), (json) => {
			this._setBusy(false);
			if(!json || !json.status || !json.imageData) {
				this._showError((json && json.error) ? json.error : 'No image data was available at the given URL.');
				return;
			}
			this._setBg('#ffffff');
			this._setImage(json.imageData, 'cover');
		}, { error: () => { this._setBusy(false); this._showError('The image could not be fetched.'); } });
	}

	// Pull a usable image src out of a CSS url(...) value. The cerb-icons mask is an inline SVG data URL whose
	// inner attributes use single quotes (url("data:image/svg+xml;utf8,<svg xmlns='…'>")) — so strip the OUTER
	// quotes only, and percent-encode the (un-encoded) SVG payload so new Image() loads it across browsers.
	_cssUrlToSrc(value) {
		if(!value) return null;
		const start = value.indexOf('url(');
		if(start === -1) return null;
		let inner = value.slice(start + 4);
		const end = inner.lastIndexOf(')');
		if(end !== -1) inner = inner.slice(0, end);
		inner = inner.trim();
		if(inner.length >= 2 && ((inner[0] === '"' && inner.endsWith('"')) || (inner[0] === "'" && inner.endsWith("'"))))
			inner = inner.slice(1, -1);
		if(!inner) return null;
		const comma = inner.indexOf(',');
		if(comma !== -1 && /^data:image\/svg\+xml/i.test(inner) && !/;base64/i.test(inner.slice(0, comma))) {
			let payload = inner.slice(comma + 1);
			// These icon SVGs declare only a viewBox; give them an explicit size so canvas drawImage renders
			// reliably (a sizeless SVG can measure 0 and draw blank in some browsers).
			const tag = payload.slice(0, payload.indexOf('>') + 1);
			if(/^<svg\b/i.test(payload) && !/\bwidth=/i.test(tag))
				payload = payload.replace(/<svg\b/i, "<svg width='256' height='256'");
			return 'data:image/svg+xml,' + encodeURIComponent(payload);
		}
		return inner;
	}

	// Rasterize a Cerb icon (its CSS mask SVG) tinted over the background fill — one-time, no live link.
	setFromIcon(name, color) {
		if(!name) return;
		const probe = document.createElement('span');
		probe.className = 'cerb-icons cerb-icon-' + name;
		probe.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
		document.body.appendChild(probe);
		const cs = getComputedStyle(probe);
		const mask = (cs.maskImage && cs.maskImage !== 'none') ? cs.maskImage : cs.webkitMaskImage;
		document.body.removeChild(probe);
		const src = this._cssUrlToSrc(mask);
		if(!src) { this._showError('That icon could not be loaded.'); return; }

		this._lastIconName = name;
		if((this.bgcolor || '').toLowerCase() === '#ffffff') this._setBg('#1e5271');
		const tint = color || this.fgcolor || '#ffffff';

		const im = new Image();
		im.addEventListener('load', () => {
			const c = document.createElement('canvas');
			c.width = this.outW; c.height = this.outH;
			const t = c.getContext('2d');
			const pad = Math.round(Math.min(this.outW, this.outH) * 0.18);
			const box = Math.min(this.outW, this.outH) - pad * 2;
			const ar = (im.naturalWidth || 1) / (im.naturalHeight || 1);
			let dw = box, dh = box;
			if(ar > 1) dh = box / ar; else dw = box * ar;
			t.drawImage(im, (this.outW - dw) / 2, (this.outH - dh) / 2, dw, dh);
			t.globalCompositeOperation = 'source-in';
			t.fillStyle = tint;
			t.fillRect(0, 0, this.outW, this.outH);
			this._setImage(c.toDataURL('image/png'), 'contain');
		});
		im.addEventListener('error', () => this._showError('That icon could not be loaded.'));
		im.src = src;
	}

	_setBg(hex) {
		this.bgcolor = hex;
		if(this.colorPicker) this.colorPicker.setValue(hex); else if(this.bgInput) this.bgInput.value = hex;
		this._redraw();
	}

	_erase() {
		// Clear the image but keep the chosen bg/fg colors; the canvas fills with the current background.
		this.image = null;
		this._lastIconName = null;
		this.scale = 1; this.tx = 0; this.ty = 0;
		this._redraw();
	}

	// ── Server data (current image + toolbar) ──────────────────────────────────
	_fetchEditorData() {
		// An unsaved edit already lives in the hidden input (a data: URL, or 'data:null' = pending delete).
		// Prefer it over the stored image so re-opening the editor shows the pending change, not the old avatar.
		const pending = this.input ? this.input.value : '';
		const hasPending = pending && pending !== 'data:null';
		const pendingDelete = (pending === 'data:null');

		// Standalone (no context): seed from the pending value, else the value option. No toolbar.
		if(!this.opts.context) {
			if(hasPending) this._setImage(pending, 'cover');
			else if(!pendingDelete && this.opts.value) this._setImage(this.opts.value, 'cover');
			return;
		}
		const args = 'c=internal&a=invoke&module=records&action=imageEditor'
			+ '&context=' + encodeURIComponent(this.opts.context)
			+ '&context_id=' + encodeURIComponent(this.opts.contextId)
			+ '&image_width=' + encodeURIComponent(this.outW)
			+ '&image_height=' + encodeURIComponent(this.outH);
		genericAjaxGet('', args, (json) => {
			if(!json) return;
			if(hasPending) this._setImage(pending, 'cover');                  // unsaved edit wins
			else if(!pendingDelete && json.imagedata) this._setImage(json.imagedata, 'cover'); // else the stored avatar
			if(json.toolbar) { this.toolbarMount.innerHTML = json.toolbar; this._wireToolbar(); }
		});
	}

	_wireToolbar() {
		const ul = this.toolbarMount.querySelector('ul.cerb-ui-toolbar');
		if(!ul || !window.CerbUI || !CerbUI.Toolbar) return;
		this.toolbar = new CerbUI.Toolbar(ul, {
			caller: {
				name: CerbUI.ImageEditor.TOOLBAR_NAME,
				params: {
					'record__context': this.opts.context,
					'record_id': this.opts.contextId,
					'image_width': this.outW,
					'image_height': this.outH,
				},
			},
			done: (e) => {
				e.stopPropagation();
				if(!e.eventData || e.eventData.exit !== 'return') return;
				if(window.Devblocks && Devblocks.interactionWorkerPostActions) Devblocks.interactionWorkerPostActions(e.eventData);
				const image = e.eventData.return && e.eventData.return.image;
				if(!image || typeof image !== 'object') return;
				if(image.url) this.setFromUrl(image.url);
				else if(image.text) this.setFromText(image.text);
				else if(image.icon) this.setFromIcon(image.icon, image.color);
			},
		});
	}

	// ── Save ───────────────────────────────────────────────────────────────────
	_save() {
		const dataUrl = this._export(); // null = empty → delete
		const value = dataUrl ? dataUrl : 'data:null';
		if(this.input) this.input.value = value;
		this._updatePreview(dataUrl);
		if(typeof this.opts.onSave === 'function') this.opts.onSave(dataUrl);
		this.close();
	}

	_export() {
		if(!this.image) return null;
		const out = document.createElement('canvas');
		out.width = this.outW; out.height = this.outH;
		const o = out.getContext('2d');
		o.fillStyle = this.bgcolor;
		o.fillRect(0, 0, this.outW, this.outH);
		const s = this.outW / this.crop.w; // crop maps to output (aspect preserved)
		o.drawImage(
			this.image,
			(this.tx - this.crop.x) * s, (this.ty - this.crop.y) * s,
			this.image.naturalWidth * this.scale * s, this.image.naturalHeight * this.scale * s
		);
		return out.toDataURL('image/png');
	}

	// Repaint the well to reflect the just-saved image (or revert to the monogram on delete).
	_updatePreview(dataUrl) {
		const d = this.el.dataset;
		this.el.classList.remove('cerb-ui-avatar--image');
		this.el.style.backgroundImage = '';
		if(dataUrl) {
			this.el.classList.add('cerb-ui-avatar--image');
			this.el.style.backgroundImage = 'url("' + dataUrl + '")';
			this.el.textContent = '';
		} else if(CerbUI.Avatar && CerbUI.Avatar._apply) {
			CerbUI.Avatar._apply(this.el, { label: d.avatar || '', seed: d.avatarSeed || '', icon: d.avatarIcon || '' });
		}
		// The pencil overlay lives on the wrapping well (not a child of the avatar), so Avatar's repaint
		// doesn't disturb it — nothing to re-attach here.
	}

	_showError(msg) { if(this.errorEl) { this.errorEl.textContent = msg || ''; this.errorEl.style.display = msg ? '' : 'none'; } }
	_setBusy(on) { if(this.content) this.content.classList.toggle('cerb-ui-image-editor--busy', !!on); }

	// ── Teardown ───────────────────────────────────────────────────────────────
	destroy() {
		CerbUI.ImageEditor._instances.delete(this.el);
		if(this.dialog) this.dialog.close();
		this._teardownCanvas();
		this.well.removeEventListener('click', this._onElClick);
		this.well.removeEventListener('keydown', this._onElKey);
		if(this._triggerEl && this._onTriggerClick) this._triggerEl.removeEventListener('click', this._onTriggerClick);
		// Unwrap: move the avatar back out, then drop the well (+ its overlay)
		if(this.well.parentNode) this.well.parentNode.insertBefore(this.el, this.well);
		this.well.remove();
	}
};
