/*
 * CerbUI.Toggle — an on/off switch (a styled checkbox). The CSS works bare; this thin wrapper just gives you
 * a change callback + value helpers so callers don't re-implement the wiring.
 *
 * Markup:
 *   <label class="cerb-ui-toggle">
 *     <input type="checkbox">
 *     <span class="cerb-ui-toggle--slider"></span>
 *   </label>
 *
 * Usage (pass the <input>, or the <label> and it finds the input):
 *   new CerbUI.Toggle(el, {
 *     onChange: function(checked, input) { … }, // fires on change
 *     // checked: true,                          // set initial state
 *   });
 *   tog.getValue();  tog.setValue(true);  tog.setDisabled(true);
 */
CerbUI.Toggle = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Toggle._instances.get(el); }

	constructor(el, options = {}) {
		el = (typeof el === 'string') ? document.querySelector(el) : el;
		this.input = (el && el.matches && el.matches('input[type=checkbox]')) ? el
			: (el ? el.querySelector('input[type=checkbox]') : null);
		if(!this.input) return;
		// Look up by either the <input> or the <label> you passed
		CerbUI.Toggle._instances.set(this.input, this);
		if(el && el !== this.input) CerbUI.Toggle._instances.set(el, this);
		this.options = options;

		if(options.checked != null)
			this.input.checked = !!options.checked;

		this._onChange = (e) => {
			if(typeof options.onChange === 'function')
				options.onChange(this.input.checked, this.input, e);
		};
		this.input.addEventListener('change', this._onChange);
	}

	getValue() {
		return this.input ? this.input.checked : false;
	}

	setValue(on) {
		if(this.input) this.input.checked = !!on;
		return this;
	}

	setDisabled(disabled) {
		if(this.input) this.input.disabled = !!disabled;
		return this;
	}

	destroy() {
		if(this.input) this.input.removeEventListener('change', this._onChange);
	}
};
