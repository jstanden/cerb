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
 * As a placeholder it pairs with a real photo: pass imageUrl (or data-avatar-image) and the monogram
 * shows instantly as a "graybox", then the picture swaps in once it loads — how a long list of profile
 * images paints without flashing empty.
 *
 * Enhancer data-* attributes (all optional except the label):
 *   data-avatar="Jane Doe"  data-avatar-seed="worker:5"  data-avatar-image="/avatar/worker/5"  data-avatar-size="32"
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

	// Apply avatar styling + content to `el` in place. spec: { label, seed, imageUrl, size, enqueue }.
	static _apply(el, spec) {
		el.classList.add('cerb-ui-avatar');
		el.setAttribute('aria-hidden', 'true');
		if(spec.size) {
			el.style.width = el.style.height = spec.size + 'px';
			el.style.fontSize = Math.round(spec.size * 0.42) + 'px';
		}
		const label = spec.label || '';
		const seed = (spec.seed != null && spec.seed !== '') ? spec.seed : label;
		el.style.backgroundColor = CerbUI.Avatar.color(seed);
		el.style.backgroundImage = '';
		el.textContent = CerbUI.Avatar.initials(label);
		el.classList.remove('cerb-ui-avatar--image');
		if(spec.imageUrl) CerbUI.Avatar._loadImage(el, spec.imageUrl, spec.enqueue);
		return el;
	}

	// Swap the monogram for a real image once it loads. `enqueue` (optional) routes the load through a
	// caller-supplied bounded queue (e.g. chooserCore's) so a long list never bursts the server.
	static _loadImage(el, url, enqueue) {
		const onload = function(src) {
			el.style.backgroundImage = 'url("' + src + '")';
			el.style.backgroundColor = 'transparent';
			el.textContent = '';
			el.classList.add('cerb-ui-avatar--image');
		};
		if(typeof enqueue === 'function') return enqueue(url, onload);
		const probe = new Image();
		probe.addEventListener('load', function() { onload(probe.src); });
		probe.src = url;
		return null;
	}

	// ── Build a fresh element ───────────────────────────────────────────
	// opts: { label, seed, imageUrl, size(px), className, tag, enqueue }.
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
			size:     sizeAttr || 0,
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
