/*
 * CerbUI.SelectMenu — enhances a native <select> with a styled trigger + searchable dropdown.
 *
 * A thin adapter over CerbUI.Menu: it builds a trigger and a source <ul> from the <select>'s <option>s,
 * hands the list to CerbUI.Menu, and syncs selection back to the <select> (which stays in the DOM, hidden,
 * for normal form submission). Menu supplies the floating panel, viewport positioning, windowed
 * virtualization for long lists, type-to-filter (start typing to search — great for timezones), and full
 * keyboard nav. Requires CerbUI.Menu to be loaded first.
 *
 * Usage:
 *   new CerbUI.SelectMenu(selectEl, { placeholder: 'Choose…', onSelect: (value, text, option) => { ... } });
 *
 * Icons / custom rendering (two paths) — applied to BOTH the dropdown items AND the selected trigger:
 *   - simple:   add data-cerb-ui-icon="check" to an <option> -> a leading `cerb-icons cerb-icon-check` span
 *   - advanced: pass onRender(el, option) to prepend arbitrary markup (e.g. a CerbUI.Pip). `el` is the item
 *               element (a menu <li>, or the trigger's value container) with the label already present as
 *               its first child — insert your adornment before el.firstChild.
 *
 * Options: placeholder (string), filter (bool, default true — forwarded to Menu),
 *   onSelect(value, text, option), onRender(el, option). The inner Menu is exposed as `.menu`.
 *
 * v1 limitations: <optgroup> labels are flattened away (uses the flat select.options); disabled options
 * render muted and are non-selectable; the dropdown uses Menu's width (180–320px), not the trigger's.
 */
CerbUI.SelectMenu = class {
	static _uid = 0;
	static _instances = new WeakMap();
	static from(el) { return CerbUI.SelectMenu._instances.get(el); }

	constructor(select, opts = {}) {
		this.select = (typeof select === 'string') ? document.querySelector(select) : select;
		this.opts = Object.assign({
			placeholder: '',
			filter:      true,
			onSelect:    null,
			onRender:    null,
		}, opts);
		if(!this.select) return;
		this.uid = ++CerbUI.SelectMenu._uid;

		this.select.classList.add('cerb-ui-selectmenu--source'); // CSS hides it; a hidden select still submits

		// Build the trigger (combobox button)
		this.trigger = document.createElement('div');
		this.trigger.className = 'cerb-ui-selectmenu';
		this.trigger.setAttribute('role', 'combobox');
		this.trigger.setAttribute('aria-haspopup', 'menu');
		this.trigger.setAttribute('aria-expanded', 'false');
		this.trigger.setAttribute('aria-controls', `cerb-ui-sm-${this.uid}`);
		this.trigger.setAttribute('tabindex', '0');
		if(this.select.disabled) this.trigger.setAttribute('aria-disabled', 'true');

		// The value container holds the label (+ any icon/pip adornment) so the selected option can mirror
		// whatever the dropdown items render. Rebuilt on each sync.
		this.triggerValue = document.createElement('span');
		this.triggerValue.className = 'cerb-ui-selectmenu--value';
		this.triggerText = document.createElement('span');
		this.triggerText.className = 'cerb-ui-selectmenu--text';
		this.triggerValue.appendChild(this.triggerText);
		this.trigger.appendChild(this.triggerValue);

		const chevron = document.createElement('span');
		chevron.className = 'cerb-icons cerb-icon-chevron-down cerb-ui-selectmenu--chevron';
		chevron.setAttribute('aria-hidden', 'true');
		this.trigger.appendChild(chevron);

		this.select.insertAdjacentElement('afterend', this.trigger);

		// Build the source <ul> from the options (kept detached — Menu only parses it)
		this.ul = this._buildSourceUl();

		// Hand the list to CerbUI.Menu
		this.menu = new CerbUI.Menu(this.ul, {
			filter:       this.opts.filter,
			onSelect:     (renderedLi, sourceLi) => this._onSelect(sourceLi),
			onRenderItem: (renderedLi, sourceLi) => this._onRenderItem(renderedLi, sourceLi),
			onClose:      () => {
				this.trigger.setAttribute('aria-expanded', 'false');
				this.trigger.classList.remove('cerb-ui-selectmenu--open');
			},
		});

		// Trigger interactions
		this._onTriggerClick = () => {
			if(this.select.disabled) return;
			if(this.menu.isOpen()) this.menu.close();
			else this._open();
		};
		this.trigger.addEventListener('click', this._onTriggerClick);

		// Open on keyboard when the trigger is focused and the menu is closed (Menu drives it once open).
		this._onTriggerKey = (e) => {
			if(this.menu.isOpen()) return;
			if(e.key === ' ' || e.key === 'Enter' || e.key === 'ArrowDown' || e.key === 'ArrowUp') {
				e.preventDefault();
				if(!this.select.disabled) this._open();
			}
		};
		this.trigger.addEventListener('keydown', this._onTriggerKey);

		this._syncTriggerText();
		CerbUI.SelectMenu._instances.set(this.select, this);
	}

	// ── Internal ─────────────────────────────────────────────────────────

	_open() {
		this.trigger.setAttribute('aria-expanded', 'true');
		this.trigger.classList.add('cerb-ui-selectmenu--open');
		this.menu.open(this.trigger);
	}

	_buildSourceUl() {
		const ul = document.createElement('ul');
		ul.id = `cerb-ui-sm-${this.uid}`;
		Array.from(this.select.options).forEach(option => {
			const li = document.createElement('li');
			li.textContent = option.text; // textContent — never innerHTML for user data
			li._option = option;          // back-reference so callbacks can reach the real <option>
			ul.appendChild(li);
		});
		return ul;
	}

	_syncTriggerText() {
		// Rebuild the value container, dropping any prior icon/pip adornment (the label span persists).
		this.triggerValue.replaceChildren(this.triggerText);

		const idx = this.select.selectedIndex;
		const opt = idx >= 0 ? this.select.options[idx] : null;
		const isPlaceholder = !opt || (idx === 0 && opt.value === '' && this.opts.placeholder);

		if(isPlaceholder && this.opts.placeholder) {
			this.triggerText.textContent = this.opts.placeholder;
			this.triggerText.classList.add('cerb-ui-selectmenu--text-placeholder');
			return; // no adornment for the placeholder
		}

		this.triggerText.textContent = opt ? opt.text : '';
		this.triggerText.classList.remove('cerb-ui-selectmenu--text-placeholder');
		if(opt) this._decorate(this.triggerValue, opt);
	}

	// Prepend an icon (data-cerb-ui-icon) or custom markup (onRender) before the label in `el` — used for
	// both dropdown items and the trigger's value container, so the selection mirrors the menu.
	_decorate(el, option) {
		if(typeof this.opts.onRender === 'function') {
			this.opts.onRender(el, option);
			return;
		}
		const iconName = option.dataset.cerbUiIcon;
		if(iconName) {
			const icon = document.createElement('span');
			icon.className = 'cerb-icons cerb-icon-' + iconName + ' cerb-ui-selectmenu--icon';
			icon.setAttribute('aria-hidden', 'true');
			el.insertBefore(icon, el.firstChild);
		}
	}

	_onRenderItem(renderedLi, sourceLi) {
		const option = sourceLi._option;
		if(!option) return;

		if(option.selected) renderedLi.classList.add('cerb-ui-selectmenu--current');
		if(option.disabled) renderedLi.classList.add('cerb-ui-selectmenu--item-disabled');

		this._decorate(renderedLi, option);
	}

	_onSelect(sourceLi) {
		const option = sourceLi._option;
		if(!option || option.disabled) return;

		this.select.value = option.value;
		this.select.dispatchEvent(new Event('change', { bubbles: true }));
		this._syncTriggerText();
		if(typeof this.opts.onSelect === 'function') this.opts.onSelect(option.value, option.text, option);
		this.trigger.focus(); // Menu closes itself afterward
	}

	// ── Public API ───────────────────────────────────────────────────────

	getValue() {
		return this.select.value;
	}

	setValue(value) {
		this.select.value = value;
		this._syncTriggerText();
	}

	open() {
		if(!this.select.disabled && !this.menu.isOpen()) this._open();
	}

	close() {
		this.menu.close();
	}

	isOpen() {
		return this.menu.isOpen();
	}

	destroy() {
		CerbUI.SelectMenu._instances.delete(this.select);
		this.menu.destroy();
		this.trigger.removeEventListener('click', this._onTriggerClick);
		this.trigger.removeEventListener('keydown', this._onTriggerKey);
		this.trigger.remove();
		this.select.classList.remove('cerb-ui-selectmenu--source');
	}
};
