/*
 * CerbUI.Avatar — a small, stable client-side monogram avatar (initials + a hash-locked color).
 *
 * The color is derived deterministically from a seed (mirrors the server's crc32 renderMonogram
 * intent in avatar.php): the same identity always paints the same color, so a list stays visually
 * stable across reloads. Use it three ways:
 *   - Build a fresh element:           CerbUI.Avatar.create({ label, seed, imageUrl, size })
 *   - Enhance one you already rendered: new CerbUI.Avatar(el)  // reads data-avatar* off the element
 *   - Enhance a whole list at once:     CerbUI.Avatar.enhance(scope)  // every [data-avatar] within
 *
 * As a placeholder it pairs with a real photo: pass imageUrl (or data-avatar-image) and the avatar
 * shows a pulsing skeleton until the picture swaps in — how a long list of profile images paints
 * without flashing empty. Loads run in bounded batches with a per-request timeout and retries, so a
 * page full of avatars doesn't burst the server; if one exhausts its retries the skeleton clears and
 * the monogram/icon underneath is what remains.
 *
 * Pass an `icon` (option) or data-avatar-icon (a cerb-icons name) to paint a glyph inside the color-locked
 * circle instead of initials — e.g. a record-type / category avatar that's an icon, not a person's monogram.
 *
 * Pass a `color` (option) or data-avatar-color (any CSS color) to force the background instead of the
 * seed-derived one — e.g. a category avatar tinted to its configured color. `textColor` (or
 * data-avatar-text-color) does the same for the glyph/monogram; pass 'auto' to pick whichever of
 * near-black/white reads better on the background actually painted (a CSS-var background it can't
 * measure defers to the stylesheet).
 *
 * Non-square (16:9 "art") avatars: pass `ratio` ("16:9") with `size` (read as the height), or explicit
 * `width`+`height`. A non-square avatar becomes a rounded rectangle (--art) with the glyph/monogram
 * centered and extra horizontal space — used for package/workflow thumbnails.
 *
 * Enhancer data-* attributes (all optional except the label):
 *   data-avatar="Jane Doe"  data-avatar-seed="worker:5"  data-avatar-image="/avatar/worker/5"  data-avatar-size="32"  data-avatar-icon="bot"  data-avatar-color="#c0392b"  data-avatar-text-color="auto"  data-avatar-ratio="16:9"  data-avatar-width="240"  data-avatar-height="135"
 *
 * CerbUI.AvatarStack — a row of overlapping avatars with a trailing "+N" for the overflow. Enhance a
 * container of [data-avatar] children (new CerbUI.AvatarStack(el)) or pass { items:[…], max, size }.
 *
 * Static helpers: CerbUI.Avatar.initials('Jane Doe') -> 'JD'; CerbUI.Avatar.color('worker:5') -> css color.
 * Requires CerbUI.palettes (for the monogram color scale). CSS lives in cerb.css (.cerb-ui-avatar*).
 */
CerbUI.Avatar = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Avatar._instances.get(el); }

	// ── Static helpers (no element needed) ──────────────────────────────

	// 32-bit string hash (stable, fast) — the seed of both the color pick and any caller keying.
	static hash(str) {
		let h = 0;
		str = String(str);
		for(let i = 0; i < str.length; i++) {
			h = ((h << 5) - h + str.charCodeAt(i)) | 0; // 32-bit
		}
		return Math.abs(h);
	}

	// Up to two initials: two words → first letter of each; one word → its first two letters.
	static initials(label) {
		const parts = String(label || '').trim().split(/\s+/).filter(Boolean);
		if(!parts.length) return '?';
		if(parts.length === 1) return parts[0].substr(0, 2).toUpperCase();
		return (parts[0][0] + parts[1][0]).toUpperCase();
	}

	// Hash-locked color: index a categorical palette by the seed's hash so it's stable per identity.
	static color(seed) {
		const palette = CerbUI.resolvePalette('category10');
		return palette[CerbUI.Avatar.hash(seed) % palette.length];
	}

	// ── Paint (shared by create() + the enhancer) ───────────────────────

	// Resolve pixel dimensions from the spec: explicit width+height, else ratio ("16:9") + size-as-height,
	// else size (square). Returns { w, h } (0/0 when unsized).
	static _dims(spec) {
		if(spec.width && spec.height)
			return { w: spec.width, h: spec.height };
		if(spec.ratio && spec.size) {
			const m = String(spec.ratio).split(/[:x\/]/).map(Number);
			if(m.length === 2 && m[0] && m[1])
				return { w: Math.round(spec.size * m[0] / m[1]), h: spec.size };
		}
		if(spec.size) return { w: spec.size, h: spec.size };
		return { w: 0, h: 0 };
	}

	// Apply avatar styling + content to `el` in place. spec: { label, seed, imageUrl, size, ratio, width, height, icon, color, textColor, enqueue }.
	static _apply(el, spec) {
		el.classList.add('cerb-ui-avatar');
		el.setAttribute('aria-hidden', 'true');
		const { w, h } = CerbUI.Avatar._dims(spec);
		if(w && h) {
			el.style.width = w + 'px';
			el.style.height = h + 'px';
			el.style.fontSize = Math.round(h * 0.42) + 'px'; // glyph/monogram scales off the shorter (height) dim
			if(w !== h) el.classList.add('cerb-ui-avatar--art'); // non-square → rounded rect, not circle
		}
		const label = spec.label || '';
		const seed = (spec.seed != null && spec.seed !== '') ? spec.seed : label;
		el.style.backgroundColor = (spec.color != null && spec.color !== '') ? spec.color : CerbUI.Avatar.color(seed);
		el.style.backgroundImage = '';
		el.style.color = CerbUI.Avatar._textColor(el, spec.textColor);
		if(spec.icon) {
			// A cerb-icons glyph in place of initials (inherits the avatar's foreground color).
			const g = document.createElement('span');
			g.className = 'cerb-icons cerb-icon-' + spec.icon;
			el.replaceChildren(g);
		} else {
			el.textContent = CerbUI.Avatar.initials(label);
		}
		el.classList.remove('cerb-ui-avatar--image', 'cerb-ui-avatar--loading', 'cerb-u-anim-pulse');
		if(spec.imageUrl) CerbUI.Avatar._loadImage(el, spec.imageUrl, spec.enqueue);
		return el;
	}

	// Resolve the glyph/monogram color: a literal color as given, 'auto' as the better of near-black or
	// white on the background just painted, and '' (the CSS default) when there's nothing to go on.
	// getComputedStyle resolves a var() background, but only once the element is in the document — a
	// detached one (Avatar.create) falls back to the stylesheet rather than guessing.
	static _textColor(el, textColor) {
		if(textColor == null || textColor === '')
			return '';

		if(textColor !== 'auto')
			return textColor;

		let bg = el.style.backgroundColor;

		if(!CerbUI.color.parseHex(bg) && el.isConnected)
			bg = window.getComputedStyle(el).backgroundColor;

		return CerbUI.color.idealTextColor(bg) || '';
	}

	// ── Bounded image loader ────────────────────────────────────────────
	// Every avatar URL is its own backend request, and a list can hold dozens (the connected-service
	// package library is ~60). Requesting them all at once exhausts the browser's per-host connection
	// pool and the server's workers, and enough of them time out that some tiles never paint. So load
	// in small batches, cap how long any one request may hold a slot, and put a failure back in line
	// for another try instead of leaving the tile stuck on its placeholder.
	static MAX_INFLIGHT = 6;
	static TIMEOUT_MS = 15000;
	static MAX_ATTEMPTS = 3;
	static _queue = [];
	static _inflight = 0;

	static _pump() {
		while(CerbUI.Avatar._inflight < CerbUI.Avatar.MAX_INFLIGHT && CerbUI.Avatar._queue.length) {
			const job = CerbUI.Avatar._queue.shift();
			if(job.cancelled) continue;

			CerbUI.Avatar._inflight++;

			const img = new Image();
			let settled = false;

			// One exit for load / error / timeout: free the slot exactly once, then either paint, retry,
			// or give up. A hung request that never fires an event would otherwise hold a slot forever
			// and stall everything queued behind it.
			const finish = function(ok) {
				if(settled) return;
				settled = true;
				clearTimeout(timer);
				img.onload = img.onerror = null;
				CerbUI.Avatar._inflight--;

				if(!job.cancelled) {
					if(ok) {
						job.onload(img.src);
					} else if(++job.attempts < CerbUI.Avatar.MAX_ATTEMPTS) {
						CerbUI.Avatar._queue.push(job); // back of the line, after the current batch drains
					} else if(typeof job.onfail === 'function') {
						job.onfail();
					}
				}

				CerbUI.Avatar._pump();
			};

			const timer = setTimeout(function() {
				img.src = ''; // abort the in-flight request so the retry isn't racing it
				finish(false);
			}, CerbUI.Avatar.TIMEOUT_MS);

			img.onload = function() { finish(true); };
			img.onerror = function() { finish(false); };
			img.src = job.url;
		}
	}

	// Queue one image. Returns the job so a caller can set `cancelled` (e.g. a row scrolled away).
	static _enqueue(url, onload, onfail) {
		const job = { url: url, onload: onload, onfail: onfail, attempts: 0, cancelled: false };
		CerbUI.Avatar._queue.push(job);
		CerbUI.Avatar._pump();
		return job;
	}

	// Swap the skeleton for a real image once it loads. `enqueue` (optional) routes the load through a
	// caller-supplied queue (e.g. chooserCore's, which also cancels rows that scroll away) instead of
	// the shared one above.
	static _loadImage(el, url, enqueue) {
		const settle = function() {
			el.classList.remove('cerb-ui-avatar--loading', 'cerb-u-anim-pulse');
		};
		const onload = function(src) {
			settle();
			el.style.backgroundImage = 'url("' + src + '")';
			el.style.backgroundColor = 'transparent';
			el.textContent = '';
			el.classList.add('cerb-ui-avatar--image');
		};
		// Out of retries: drop the skeleton and leave the monogram/icon already painted underneath, so
		// a tile that can't load its picture still reads as that record rather than pulsing forever.
		const onfail = settle;

		// Skeleton until one of those fires -- a pulsing block reads as "still loading", where a glyph
		// that never resolves is indistinguishable from the record's real art.
		el.classList.add('cerb-ui-avatar--loading', 'cerb-u-anim-pulse');

		if(typeof enqueue === 'function') return enqueue(url, onload, onfail);
		return CerbUI.Avatar._enqueue(url, onload, onfail);
	}

	// ── Build a fresh element ───────────────────────────────────────────
	// opts: { label, seed, imageUrl, icon, color, textColor, size(px), className, tag, enqueue }.
	static create(opts) {
		opts = opts || {};
		const el = document.createElement(opts.tag || 'span');
		if(opts.className) el.className = opts.className;
		return CerbUI.Avatar._apply(el, opts);
	}

	// ── Batch-enhance every [data-avatar] within a scope (element | selector | document) ──
	static enhance(scope, selector) {
		const root = (typeof scope === 'string') ? document.querySelector(scope) : (scope || document);
		if(!root || !root.querySelectorAll) return [];
		const out = [];
		root.querySelectorAll(selector || '[data-avatar]').forEach(el => out.push(new CerbUI.Avatar(el)));
		return out;
	}

	// ── Enhancer: read data-avatar* off an existing element and paint it in place ──
	// opts override the data-* attributes.
	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		const d = this.el.dataset;
		const sizeAttr = opts.size != null ? opts.size : (d.avatarSize ? parseInt(d.avatarSize, 10) : 0);
		this.spec = {
			label:    opts.label    != null ? opts.label    : (d.avatar != null ? d.avatar : (this.el.textContent || '').trim()),
			seed:     opts.seed     != null ? opts.seed     : (d.avatarSeed || ''),
			imageUrl: opts.imageUrl != null ? opts.imageUrl : (d.avatarImage || ''),
			icon:     opts.icon     != null ? opts.icon     : (d.avatarIcon || ''),
			color:    opts.color    != null ? opts.color    : (d.avatarColor || ''),
			textColor: opts.textColor != null ? opts.textColor : (d.avatarTextColor || ''),
			size:     sizeAttr || 0,
			ratio:    opts.ratio    != null ? opts.ratio    : (d.avatarRatio || ''),
			width:    opts.width    != null ? opts.width    : (d.avatarWidth ? parseInt(d.avatarWidth, 10) : 0),
			height:   opts.height   != null ? opts.height   : (d.avatarHeight ? parseInt(d.avatarHeight, 10) : 0),
			enqueue:  opts.enqueue,
		};
		CerbUI.Avatar._apply(this.el, this.spec);
		CerbUI.Avatar._instances.set(this.el, this);
	}
};

/*
 * CerbUI.AvatarStack — overlapping avatars + a trailing "+N" overflow bubble.
 *
 *   <div class="cerb-ui-avatar-stack" data-max="5">
 *     <span data-avatar="Jane Doe" data-avatar-seed="worker:1"></span> … </div>
 *   new CerbUI.AvatarStack(el);                                   // enhance the [data-avatar] children
 *   new CerbUI.AvatarStack(el, { max: 4, size: 32, items: [{label, seed, imageUrl}, …] });  // data-driven
 *
 * `max` is the total bubble footprint (avatars + the +N), so the row never grows past it. CSS: .cerb-ui-avatar-stack.
 */
CerbUI.AvatarStack = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.AvatarStack._instances.get(el); }

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;
		this.el.classList.add('cerb-ui-avatar-stack');
		this.opts = Object.assign({ max: 0, size: 0, items: null }, opts);

		const max = this.opts.max || parseInt(this.el.getAttribute('data-max'), 10) || 4;
		const size = this.opts.size || parseInt(this.el.getAttribute('data-size'), 10) || 0;

		// Gather specs: explicit items win, else read the existing [data-avatar] children.
		const specs = Array.isArray(this.opts.items) ? this.opts.items
			: Array.from(this.el.querySelectorAll('[data-avatar]')).map(c => ({
				label:    c.getAttribute('data-avatar') || '',
				seed:     c.getAttribute('data-avatar-seed') || '',
				imageUrl: c.getAttribute('data-avatar-image') || '',
			}));

		this.el.replaceChildren();

		// Keep the footprint ≤ max: when there's overflow, the last slot becomes the +N bubble.
		const total = specs.length;
		const visible = total > max ? max - 1 : total;
		for(let i = 0; i < visible; i++)
			this.el.appendChild(CerbUI.Avatar.create(Object.assign({ size: size }, specs[i])));

		if(total > visible) {
			const more = document.createElement('span');
			more.className = 'cerb-ui-avatar cerb-ui-avatar--more';
			if(size) {
				more.style.width = more.style.height = size + 'px';
				more.style.fontSize = Math.round(size * 0.42) + 'px';
			}
			more.textContent = '+' + (total - visible);
			more.setAttribute('title', (total - visible) + ' more');
			this.el.appendChild(more);
		}
		CerbUI.AvatarStack._instances.set(this.el, this);
	}
};
