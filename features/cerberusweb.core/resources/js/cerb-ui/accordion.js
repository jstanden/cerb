/*
 * CerbUI.Accordion — expanding/collapsing sections (zero-dependency progressive enhancement).
 *
 * Usage:
 *   new CerbUI.Accordion(el, { active: 0, collapsible: false, onExpand: (i, info) => { ... } });
 *
 * Enhances a container whose direct children alternate <h3> (header) and <div> (panel). Each <h3> becomes a
 * clickable trigger with a chevron (cerb-icon-chevron-down) that rotates when open; its following <div>
 * expands/collapses via a CSS grid-row animation. One section open at a time by default.
 *
 * Options:
 *   active      0-based index of the initially-open section (default 0; pass -1 for all collapsed)
 *   collapsible when true, clicking the open section collapses it (all sections can be closed; default false)
 *   scrollable  when true, panels are height-capped and scroll (cap = --cerb-ui-accordion-max-height, 300px)
 *   onExpand / onCollapse  callbacks receiving (index, { index, header, panel })
 *
 * Also dispatches a `cerb-ui-accordion:toggle` CustomEvent on the container (detail = { index, expanded }).
 */
CerbUI.Accordion = class {
	static _uid = 0;
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Accordion._instances.get(el); }

	constructor(container, opts = {}) {
		this.container = (typeof container === 'string') ? document.querySelector(container) : container;
		this.opts = Object.assign({
			active:      0,
			collapsible: false,
			scrollable:  false,
			onExpand:    null,
			onCollapse:  null,
		}, opts);
		this.uid = ++CerbUI.Accordion._uid;
		this.sections = [];
		this.expandedIndex = -1;

		this.onClick = this.onClick.bind(this);
		this.onKeydown = this.onKeydown.bind(this);

		if(!this.container) return;
		this.init();
		this.container.addEventListener('click', this.onClick);
		this.container.addEventListener('keydown', this.onKeydown);
		CerbUI.Accordion._instances.set(this.container, this);
	}

	// ── Public API ──────────────────────────────────────────────────────────

	// 0-based index of the currently expanded section, or -1 if none.
	get expanded() {
		return this.expandedIndex;
	}

	// Expand a section by 0-based index.
	expand(index) {
		this.expandSection(index);
	}

	// Collapse a section by 0-based index.
	collapse(index) {
		this.collapseSection(index);
	}

	destroy() {
		CerbUI.Accordion._instances.delete(this.container);
		this.container.removeEventListener('click', this.onClick);
		this.container.removeEventListener('keydown', this.onKeydown);
		this.container.classList.remove('cerb-ui-accordion', 'cerb-ui-accordion--scrollable');

		for(const s of this.sections) {
			// Restore panel: move it out of wrapper, remove wrapper.
			// (ARIA attrs role/aria-labelledby live on the wrapper and disappear with it.)
			s.wrapper.parentNode?.insertBefore(s.panel, s.wrapper);
			s.wrapper.remove();
			s.panel.classList.remove('cerb-ui-accordion--panel-inner');
			while(s.body.firstChild) s.panel.insertBefore(s.body.firstChild, s.body);
			s.body.remove();

			// Restore header: remove button, put original nodes back.
			s.trigger.remove();
			for(const node of s.originalNodes) s.header.appendChild(node);
			s.header.classList.remove('cerb-ui-accordion--header');
		}

		this.sections = [];
		this.expandedIndex = -1;
	}

	// ── Init ─────────────────────────────────────────────────────────────────

	init() {
		this.container.classList.add('cerb-ui-accordion');
		if(this.opts.scrollable) this.container.classList.add('cerb-ui-accordion--scrollable');

		const children = Array.from(this.container.children);
		for(let i = 0; i < children.length - 1; i++) {
			const hdr = children[i];
			const pnl = children[i + 1];
			if(hdr.tagName !== 'H3' || pnl.tagName !== 'DIV') continue;

			const index = this.sections.length;
			const header = hdr;
			const panel = pnl;

			// Save original header children for destroy().
			const originalNodes = Array.from(header.childNodes);

			// Build trigger button: move h3 children into it, append chevron icon.
			const trigger = document.createElement('button');
			trigger.type = 'button';
			trigger.className = 'cerb-ui-accordion--trigger';
			while(header.firstChild) trigger.appendChild(header.firstChild);

			// Leading disclosure chevron via the cerb-icons set: points right when collapsed, rotates
			// down when expanded (CSS). Prepended so it sits before the title — no innerHTML.
			const icon = document.createElement('span');
			icon.className = 'cerb-icons cerb-icon-chevron-right cerb-ui-accordion--icon';
			icon.setAttribute('aria-hidden', 'true');
			trigger.insertBefore(icon, trigger.firstChild);

			const triggerId = `cerb-ui-accordion-${this.uid}-${index}-btn`;
			const wrapperId = `cerb-ui-accordion-${this.uid}-${index}-panel`;

			trigger.id = triggerId;
			trigger.setAttribute('aria-expanded', 'false');
			trigger.setAttribute('aria-controls', wrapperId);

			header.appendChild(trigger);
			header.classList.add('cerb-ui-accordion--header');

			// Wrap panel in grid-animation wrapper; panel keeps its own id.
			const wrapper = document.createElement('div');
			wrapper.className = 'cerb-ui-accordion--panel-wrap';
			wrapper.id = wrapperId;
			wrapper.setAttribute('role', 'region');
			wrapper.setAttribute('aria-labelledby', triggerId);
			panel.parentNode?.insertBefore(wrapper, panel);
			wrapper.appendChild(panel);
			panel.classList.add('cerb-ui-accordion--panel-inner');

			// Move panel children into a body div so padding doesn't leak through the
			// overflow:hidden clip when the grid row is 0fr.
			const body = document.createElement('div');
			body.className = 'cerb-ui-accordion--panel-body';
			while(panel.firstChild) body.appendChild(panel.firstChild);
			panel.appendChild(body);

			this.sections.push({ header, panel, body, wrapper, trigger, originalNodes, expanded: false });
			i++; // skip the panel we just consumed
		}

		const initial = this.opts.active ?? 0;
		if(initial >= 0 && initial < this.sections.length) {
			this.expandSection(initial, false);
		}
	}

	// ── Expand / collapse ─────────────────────────────────────────────────────

	expandSection(index, fireCallbacks = true) {
		if(index < 0 || index >= this.sections.length) return;

		// Collapse the previously open section (if different).
		if(this.expandedIndex >= 0 && this.expandedIndex !== index) {
			this.collapseSection(this.expandedIndex, fireCallbacks);
		}

		const s = this.sections[index];
		if(s.expanded) return;

		s.expanded = true;
		s.wrapper.classList.add('cerb-ui-accordion--expanded');
		s.trigger.setAttribute('aria-expanded', 'true');
		this.expandedIndex = index;

		if(fireCallbacks && this.opts.onExpand) this.opts.onExpand(index, this.makeInfo(index));
		this.container.dispatchEvent(new CustomEvent('cerb-ui-accordion:toggle', {
			detail: { index, expanded: true },
			bubbles: true,
		}));
	}

	collapseSection(index, fireCallbacks = true) {
		if(index < 0 || index >= this.sections.length) return;
		const s = this.sections[index];
		if(!s.expanded) return;

		s.expanded = false;
		s.wrapper.classList.remove('cerb-ui-accordion--expanded');
		s.trigger.setAttribute('aria-expanded', 'false');
		if(this.expandedIndex === index) this.expandedIndex = -1;

		if(fireCallbacks && this.opts.onCollapse) this.opts.onCollapse(index, this.makeInfo(index));
		this.container.dispatchEvent(new CustomEvent('cerb-ui-accordion:toggle', {
			detail: { index, expanded: false },
			bubbles: true,
		}));
	}

	// ── Events ────────────────────────────────────────────────────────────────

	onClick(e) {
		const trigger = e.target.closest('button.cerb-ui-accordion--trigger');
		if(!trigger) return;
		const index = this.sections.findIndex(s => s.trigger === trigger);
		if(index < 0) return;

		if(this.sections[index].expanded) {
			if(this.opts.collapsible) this.collapseSection(index);
		} else {
			this.expandSection(index);
		}
	}

	onKeydown(e) {
		const trigger = document.activeElement?.closest('button.cerb-ui-accordion--trigger');
		if(!trigger) return;
		const index = this.sections.findIndex(s => s.trigger === trigger);
		if(index < 0) return;

		const last = this.sections.length - 1;

		switch(e.key) {
			case 'ArrowDown': {
				e.preventDefault();
				const next = (index + 1) % this.sections.length;
				this.sections[next].trigger.focus();
				break;
			}
			case 'ArrowUp': {
				e.preventDefault();
				const prev = (index - 1 + this.sections.length) % this.sections.length;
				this.sections[prev].trigger.focus();
				break;
			}
			case 'Home':
				e.preventDefault();
				this.sections[0].trigger.focus();
				break;
			case 'End':
				e.preventDefault();
				this.sections[last].trigger.focus();
				break;
		}
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	makeInfo(index) {
		const s = this.sections[index];
		return { index, header: s.header, panel: s.panel };
	}
};
