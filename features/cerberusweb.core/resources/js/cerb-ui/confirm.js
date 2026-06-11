/*
 * CerbUI.Confirm — a forced-modal confirmation (the design-system replacement for confirmPopup()).
 *
 * Built on CerbUI.Dialog with the chrome stripped down to a question + two buttons: always modal (a backdrop
 * dims the page and the z-counter floats it above any open dialog), with no ×/minimize/resize/drag and Escape
 * ignored — the user must pick Cancel or OK. Title and body default when omitted; either button's label can be
 * customized. Cancel is muted (cerb-ui-button--subtle), OK is the prominent default button.
 *
 * Usage:
 *   CerbUI.Confirm.open({
 *     title:       'Discard changes',   // default 'Confirm'
 *     body:        'Are you sure?',     // default 'Are you sure?'  (string, or a DOM node)
 *     confirmText: 'OK',                // default 'OK'
 *     cancelText:  'Cancel',            // default 'Cancel'
 *     onConfirm:   function() {},       // OK pressed
 *     onCancel:    function() {},       // Cancel pressed
 *   });
 *
 * CSS lives in cerb.css (reuses .cerb-ui-dialog--* / .cerb-ui-button[--subtle] / .cerb-ui-header--right) —
 * this component injects no styles of its own.
 */
CerbUI.Confirm = class {
	constructor(opts = {}) {
		this.opts = Object.assign({
			title:       'Confirm',
			body:        'Are you sure?',
			confirmText: 'OK',
			cancelText:  'Cancel',
			onConfirm:   null,
			onCancel:    null,
		}, opts);

		this._done = false; // a button click resolves exactly once

		// Content: the message, then a right-aligned Cancel / OK row.
		const content = document.createElement('div');

		const msg = document.createElement('div');
		msg.style.marginBottom = '1.2em';
		if(this.opts.body instanceof Node) msg.appendChild(this.opts.body);
		else msg.textContent = this.opts.body; // textContent = the security boundary
		content.appendChild(msg);

		const footer = document.createElement('div');
		footer.className = 'cerb-ui-header cerb-ui-header--tight';
		footer.style.marginBottom = '0';
		footer.appendChild(document.createElement('div')); // spacer (the header is space-between)

		const right = document.createElement('div');
		right.className = 'cerb-ui-header--right';
		this.cancelBtn  = this._makeBtn('cerb-ui-button cerb-ui-button--subtle', this.opts.cancelText);
		this.confirmBtn = this._makeBtn('cerb-ui-button', this.opts.confirmText);
		right.appendChild(this.cancelBtn);
		right.appendChild(this.confirmBtn);
		footer.appendChild(right);
		content.appendChild(footer);

		this.content = content;

		// A locked-down modal Dialog; fixed so it stays viewport-centered above the backdrop.
		this.dialog = new CerbUI.Dialog(content, {
			title:         this.opts.title,
			header:        'bar',
			modal:         true,
			closable:      false,
			resizable:     false,
			draggable:     false,
			closeOnEscape: false,
			fixed:         true,
			width:         420,
		});

		this.cancelBtn.addEventListener('click',  () => this._resolve(false));
		this.confirmBtn.addEventListener('click', () => this._resolve(true));
	}

	_makeBtn(className, label) {
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = className;
		btn.textContent = label; // textContent — never innerHTML for caller-supplied labels
		return btn;
	}

	open() {
		this.dialog.open();
		this._center(); // true viewport center (its midpoint at vw/2, vh/2)
		// Land on the safe action (Cancel) first. focusVisible forces the keyboard ring even though this
		// is programmatic focus — without it the browser's :focus-visible heuristic hides the ring after a
		// mouse-driven open, so it looks unfocused until the user tabs.
		try { this.cancelBtn.focus({ focusVisible: true }); }
		catch(e) { this.cancelBtn.focus(); } // older browsers reject the options arg
		return this;
	}

	// Center the modal in the viewport. Dialog opens top-center; a confirm reads better dead-center, so we
	// re-place it once its size is known (fixed → left/top are viewport-relative).
	_center() {
		const el = this.dialog.el;
		const x = Math.max(0, Math.round((window.innerWidth  - el.offsetWidth)  / 2));
		const y = Math.max(0, Math.round((window.innerHeight - el.offsetHeight) / 2));
		this.dialog.x = x;
		this.dialog.y = y;
		el.style.left = x + 'px';
		el.style.top  = y + 'px';
	}

	// Fire the chosen callback once, then tear the modal down. destroy() is forceful (no onClose hooks) —
	// correct for a throwaway confirm whose content was created detached.
	_resolve(confirmed) {
		if(this._done) return;
		this._done = true;
		this.dialog.destroy();
		const cb = confirmed ? this.opts.onConfirm : this.opts.onCancel;
		if(typeof cb === 'function') cb();
	}

	// Convenience: build + open in one call (mirrors the old confirmPopup ergonomics).
	static open(opts) {
		return new CerbUI.Confirm(opts).open();
	}
};
