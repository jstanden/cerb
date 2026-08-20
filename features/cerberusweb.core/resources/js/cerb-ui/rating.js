/*
 * CerbUI.Rating — an ordinal rating drawn as a row of repeated glyphs, filled from the left UP TO the
 * selected value (the familiar star-rating read).
 *
 * Usage:
 *   new CerbUI.Rating(el, {
 *     input:    '#hiddenInput',                    // optional; else data-cerb-input (an element id)
 *     label:    '#tierLabel',                      // optional; else data-cerb-label (an element id)
 *     labels:   ['Basic','Efficient','Advanced'],  // optional; else data-cerb-labels (comma-separated)
 *     emptyLabel: 'Unrated',                       // shown at 0
 *     onSelect: function(value, rating) { ... }
 *   });
 *
 * Markup: a .cerb-ui-rating container of <button data-value="..."> elements, each holding a glyph.
 * Filled buttons carry .cerb-ui-rating--on; a hover preview uses .cerb-ui-rating--preview.
 *
 * VALUES ARE ARBITRARY, not 1..N — the host picks the scale and this only compares. Cerb stores ratings
 * as sparse decades (10/20/30/40) so a tier can be inserted later without rewriting rows, and 0 means
 * unrated. Nothing here assumes that; a plain 1..5 works identically.
 *
 * Clicking the currently-selected top button clears back to 0. Without it a mis-click is unfixable
 * unless the host adds a separate Clear affordance.
 */
CerbUI.Rating = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.Rating._instances.get(el); }

	constructor(el, options = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(this.el) CerbUI.Rating._instances.set(this.el, this);
		this.options = options;
		this.onClass = 'cerb-ui-rating--on';
		this.previewClass = 'cerb-ui-rating--preview';
		this._buttons = this.el ? Array.from(this.el.querySelectorAll('button')) : [];

		if(!this.el || !this._buttons.length)
			return;

		const byIdOrSelector = (given, attr) => {
			if(given)
				return (typeof given === 'string') ? document.querySelector(given) : given;
			const id = this.el.getAttribute(attr);
			return id ? document.getElementById(id) : null;
		};

		this._input = byIdOrSelector(options.input, 'data-cerb-input');
		this._label = byIdOrSelector(options.label, 'data-cerb-label');

		this._labels = options.labels
			|| String(this.el.getAttribute('data-cerb-labels') || '').split(',').filter(s => s.length);
		this._emptyLabel = ('emptyLabel' in options) ? options.emptyLabel : 'Unrated';

		// A bare <button> defaults to type=submit; inside a form a click would submit it.
		this._buttons.forEach((b) => { if(!b.getAttribute('type')) b.type = 'button'; });

		this._value = this._readValue();

		this._onClick = (e) => {
			const button = e.target.closest('button');
			if(!button || !this.el.contains(button))
				return;
			const tier = parseInt(button.getAttribute('data-value'), 10);
			this.setValue((tier === this._value) ? 0 : tier, {fireCallback: true});
		};

		this._onOver = (e) => {
			const button = e.target.closest('button');
			if(button && this.el.contains(button))
				this._paint(parseInt(button.getAttribute('data-value'), 10), true);
		};

		this._onLeave = () => this._paint(this._value, false);

		this.el.addEventListener('click', this._onClick);
		this.el.addEventListener('mouseover', this._onOver);
		this.el.addEventListener('mouseleave', this._onLeave);

		this._paint(this._value, false);
	}

	getValue() {
		return this._value;
	}

	setValue(value, {fireCallback = false} = {}) {
		this._value = parseInt(value, 10) || 0;

		if(this._input) {
			this._input.value = this._value;
			this._input.dispatchEvent(new Event('change', {bubbles: true}));
		}

		this._paint(this._value, false);

		if(fireCallback && typeof this.options.onSelect === 'function')
			this.options.onSelect(this._value, this);
	}

	_readValue() {
		if(this._input)
			return parseInt(this._input.value, 10) || 0;

		// No input: infer from whatever the server already marked as filled.
		const on = this._buttons.filter(b => b.classList.contains(this.onClass));
		return on.length ? parseInt(on[on.length - 1].getAttribute('data-value'), 10) || 0 : 0;
	}

	// Both classes are cleared each pass so a hover preview REPLACES the stored fill rather than
	// stacking a second color on top of it.
	_paint(value, isPreview) {
		this._buttons.forEach((button) => {
			button.classList.remove(this.onClass, this.previewClass);
			if(parseInt(button.getAttribute('data-value'), 10) <= value)
				button.classList.add(isPreview ? this.previewClass : this.onClass);
		});

		if(this._label) {
			const index = this._buttons.findIndex(b => parseInt(b.getAttribute('data-value'), 10) === value);
			this._label.textContent = (index >= 0 && this._labels[index] != null)
				? this._labels[index]
				: this._emptyLabel;
		}
	}

	destroy() {
		if(!this.el) return;
		this.el.removeEventListener('click', this._onClick);
		this.el.removeEventListener('mouseover', this._onOver);
		this.el.removeEventListener('mouseleave', this._onLeave);
	}
};
