/*
 * CerbUI.Switcher — a segmented toggle (one active button at a time).
 *
 * Usage:
 *   new CerbUI.Switcher(el, {
 *     value:      'objects',                       // optional initial value (else derived)
 *     storageKey: 'cerb.storage.metric',           // optional localStorage persistence
 *     onSelect:   function(value, button, toggle) { ... }
 *   });
 *
 * Markup: a .cerb-ui-switcher container of <button data-value="..."> elements.
 * The active button carries .cerb-ui-switcher--active. Bare HTML works without this class;
 * wiring it up is opt-in.
 */
CerbUI.Switcher = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Switcher._instances.get(el); }

	constructor(el, options = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(this.el) CerbUI.Switcher._instances.set(this.el, this);
		this.options = options;
		this.activeClass = 'cerb-ui-switcher--active';
		this._buttons = this.el ? Array.from(this.el.querySelectorAll('button')) : [];
		this._value = null;

		if(!this.el || !this._buttons.length)
			return;

		// Resolve the initial value: explicit option -> stored -> existing active button -> first button
		let initial = options.value;

		if(initial == null && options.storageKey && window.localStorage)
			initial = localStorage.getItem(options.storageKey);

		if(initial == null) {
			const active = this._buttons.find(b => b.classList.contains(this.activeClass));
			initial = active ? active.dataset.value : this._buttons[0].dataset.value;
		}

		// Sync the active class to the initial value without firing onSelect
		this._apply(initial, false);

		this._onClick = (e) => {
			const button = e.target.closest('button');
			if(button && this.el.contains(button))
				this.setValue(button.dataset.value, {fireCallback: true});
		};

		this.el.addEventListener('click', this._onClick);
	}

	getValue() {
		return this._value;
	}

	// Programmatic select; fires onSelect only when fireCallback is true
	setValue(value, {fireCallback = false} = {}) {
		if(value === this._value)
			return;

		this._apply(value, fireCallback);
	}

	_apply(value, fireCallback) {
		const button = this._buttons.find(b => b.dataset.value === value);

		if(!button)
			return;

		this._buttons.forEach(b => b.classList.toggle(this.activeClass, b === button));
		this._value = value;

		if(this.options.storageKey && window.localStorage)
			localStorage.setItem(this.options.storageKey, value);

		if(fireCallback && typeof this.options.onSelect === 'function')
			this.options.onSelect(value, button, this);
	}

	destroy() {
		if(this._onClick)
			this.el.removeEventListener('click', this._onClick);
	}
};
