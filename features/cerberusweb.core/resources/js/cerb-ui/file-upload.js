/*
 * CerbUI.FileUpload — inline file uploads (drag/drop + click-to-browse), no popup. Exchanges the bytes
 * for an attachment file_id (the same chooserOpenFileAjaxUpload endpoint the legacy chooserFile used),
 * then posts those ids through hidden inputs so the surrounding form just sees file_id(s). Like
 * CerbUI.RecordChooser the selections render as chips inside a "well", each opening the attachment's
 * card peek; and it can be defaulted from server-rendered [data-file-id] seed markup.
 *
 *   - Single (default): one file; remove the chip to replace it.
 *   - Multiple (`multiple:true`): files stack as chips; optional maxFiles cap.
 *
 * Usage:
 *   new CerbUI.FileUpload(el, {
 *     name:      'file_ids',            // hidden input (single) / name[] (multiple) so it posts
 *     multiple:  false,
 *     accept:    'image/*,.pdf',        // set on <input accept> AND validated client-side (advisory UX)
 *     maxSize:   10485760,              // bytes; 0 = no limit
 *     maxFiles:  0,                     // cap for multiple; 0 = no cap
 *     emptyIcon: 'paperclip',           // empty-state leading glyph (cerb-icons name)
 *     value:     [{id,name,size}],      // PREFER server-rendered [data-file-id] seed markup over this
 *     onChange:  (values) => { ... },   // fired after add/remove
 *     onError:   (msg, file) => { ... },// validation/upload failure
 *   });
 *
 * API: fu.getValue(); fu.setValue(v); fu.add(items); fu.clear(); fu.destroy().
 *   fu.add([{id,name,size}]) appends already-uploaded attachments (e.g. an inline image pasted into an editor).
 * Requires a <meta name="_csrf_token"> in the page (used for the upload CSRF header) + (for the peek) jQuery.
 */
CerbUI.FileUpload = class {
	static _instances = new WeakMap();
	static from(el) { return CerbUI.FileUpload._instances.get(el); }

	static ENDPOINT = 'c=internal&a=invoke&module=records&action=chooserOpenFileAjaxUpload';
	static CONTEXT = 'cerberusweb.contexts.attachment';

	constructor(el, opts = {}) {
		this.el = (typeof el === 'string') ? document.querySelector(el) : el;
		if(!this.el) return;

		this.opts = Object.assign({
			name:      null,
			multiple:  false,
			accept:    '',
			maxSize:   0,
			maxFiles:  0,
			emptyIcon: 'paperclip',
			value:     null,
			onChange:  null,
			onError:   null,
		}, opts);

		// Initial value(s): an explicit `value` option (an item or array of {id,name,size}), else enhance
		// authored `[data-file-id]` seed markup inside the element (server-render the resolved file metadata).
		this.values = (this.opts.value != null)
			? this._normalizeValues(this.opts.value)
			: this._readMarkupValues();

		this.el.replaceChildren(); // the component owns the element's content; drop the read seed markup

		this.el.classList.add('cerb-ui-file-upload');
		if(this.opts.multiple) this.el.classList.add('cerb-ui-file-upload--multiple');

		// Leading icon (empty state only)
		this.iconEl = document.createElement('span');
		this.iconEl.className = 'cerb-icons cerb-icon-' + this.opts.emptyIcon + ' cerb-ui-file-upload--empty-icon';
		this.iconEl.setAttribute('aria-hidden', 'true');

		// Tiles flow inline (display:contents) so they wrap with the prompt
		this.tilesEl = document.createElement('span');
		this.tilesEl.className = 'cerb-ui-file-upload--tiles';

		this.promptEl = document.createElement('span');
		this.promptEl.className = 'cerb-ui-file-upload--prompt';
		this.promptEl.textContent = this.opts.multiple ? 'Drop files here or click to upload' : 'Drop a file here or click to upload';

		// Wrapping field region: icon + tiles + prompt. The upload button stays outside it (pinned right).
		this.fieldEl = document.createElement('span');
		this.fieldEl.className = 'cerb-ui-file-upload--field';
		this.fieldEl.appendChild(this.iconEl);
		this.fieldEl.appendChild(this.tilesEl);
		this.fieldEl.appendChild(this.promptEl);

		this.uploadBtn = document.createElement('button');
		this.uploadBtn.type = 'button';
		this.uploadBtn.className = 'cerb-ui-file-upload--upload-btn';
		this.uploadBtn.setAttribute('aria-label', 'Upload');
		this.uploadBtn.innerHTML = '<span class="cerb-icons cerb-icon-upload" aria-hidden="true"></span>';

		// The real <input type=file> is visually hidden; clicking the well or the button proxies to it
		this.fileInput = document.createElement('input');
		this.fileInput.type = 'file';
		this.fileInput.className = 'cerb-ui-file-upload--input';
		// Visually hidden but still focusable (it's clipped, not display:none), so it would be a dead tab stop
		// after the upload button. The button/well proxy clicks to it, so keep it out of the tab order.
		this.fileInput.tabIndex = -1;
		this.fileInput.setAttribute('aria-hidden', 'true');
		if(this.opts.multiple) this.fileInput.multiple = true;
		if(this.opts.accept) this.fileInput.accept = this.opts.accept;

		// Hidden form fields — kept INSIDE the element (display:none) so they travel with the component and
		// post unambiguously inside the surrounding form (mirrors CerbUI.RecordChooser).
		this.hiddenWrap = document.createElement('span');
		this.hiddenWrap.style.display = 'none';

		this.el.appendChild(this.fieldEl);
		this.el.appendChild(this.uploadBtn);
		this.el.appendChild(this.fileInput);
		this.el.appendChild(this.hiddenWrap);

		// ── Listeners ──
		this._onElClick = (e) => {
			if(e.target.closest('.cerb-ui-file-upload--tile')) return;
			if(e.target.closest('.cerb-ui-file-upload--upload-btn')) return;
			if(this._canAdd()) this.fileInput.click();
		};
		// Flag so a button-initiated upload can return keyboard focus to the button when it completes (the
		// OS file dialog otherwise leaves focus on <body>). Not set for drag/drop or well clicks — no button.
		this._onUploadClick = (e) => { e.stopPropagation(); if(this._canAdd()) { this._refocusBtnAfterUpload = true; this.fileInput.click(); } };
		this._onInputChange = () => { this._addFiles(this.fileInput.files); this.fileInput.value = ''; };
		this._onDragOver = (e) => { e.preventDefault(); if(this._canAdd()) this.el.classList.add('is-dragover'); };
		this._onDragLeave = (e) => { e.preventDefault(); this.el.classList.remove('is-dragover'); };
		this._onDrop = (e) => {
			e.preventDefault();
			this.el.classList.remove('is-dragover');
			if(this._canAdd() && e.dataTransfer && e.dataTransfer.files) this._addFiles(e.dataTransfer.files);
		};

		this.el.addEventListener('click', this._onElClick);
		this.uploadBtn.addEventListener('click', this._onUploadClick);
		this.fileInput.addEventListener('change', this._onInputChange);
		this.el.addEventListener('dragover', this._onDragOver);
		this.el.addEventListener('dragenter', this._onDragOver);
		this.el.addEventListener('dragleave', this._onDragLeave);
		this.el.addEventListener('drop', this._onDrop);

		this._syncState();
		CerbUI.FileUpload._instances.set(this.el, this);
	}

	// Single-filled accepts no more (remove the chip to replace); multiple always can.
	_canAdd() { return this.opts.multiple || this.tilesEl.children.length === 0; }

	// ── Initial-value normalization (option) + markup enhancement (data-* seed) ──
	_normalizeValues(v) {
		if(v == null) return [];
		return (Array.isArray(v) ? v : [v]).map((item) => this._normalizeValue(item)).filter(Boolean);
	}
	_normalizeValue(item) {
		if(item == null || item.id == null || item.id === '') return null;
		return { id: item.id, name: item.name || ('#' + item.id), size: item.size || 0 };
	}
	// Read seed values from authored `[data-file-id]` elements (data-file-name/-size). Server-render these.
	// No URL is needed — the chip opens the attachment's card peek by context+id.
	_readMarkupValues() {
		const out = [];
		this.el.querySelectorAll('[data-file-id]').forEach((node) => {
			out.push({
				id:   node.getAttribute('data-file-id'),
				name: node.getAttribute('data-file-name') || '',
				size: parseInt(node.getAttribute('data-file-size'), 10) || 0,
			});
		});
		return out;
	}

	// ── Validation (client-side, advisory UX — not a security boundary) ──
	_validate(file) {
		if(this.opts.maxSize > 0 && file.size > this.opts.maxSize)
			return file.name + ' is too large (max ' + this._prettyBytes(this.opts.maxSize) + ')';
		if(this.opts.accept && !this._acceptMatches(file))
			return file.name + ' is not an accepted file type';
		return null;
	}
	// Match against the same syntax as <input accept>: extensions (".pdf"), exact mimes ("image/png"),
	// and wildcard mimes ("image/*").
	_acceptMatches(file) {
		const name = (file.name || '').toLowerCase();
		const type = (file.type || '').toLowerCase();
		return this.opts.accept.split(',').map((s) => s.trim().toLowerCase()).filter(Boolean).some((token) => {
			if(token.charAt(0) === '.') return name.endsWith(token);
			if(token.endsWith('/*')) return type.startsWith(token.slice(0, -1));
			return type === token;
		});
	}

	_error(msg, file) {
		if(typeof this.opts.onError === 'function') this.opts.onError(msg, file);
	}

	// ── Upload ──
	_addFiles(fileList) {
		const files = Array.from(fileList || []);
		if(!files.length) return;

		for(const file of files) {
			if(!this.opts.multiple) { this.values = []; this.tilesEl.replaceChildren(); }

			if(this.opts.multiple && this.opts.maxFiles > 0 && this.values.length >= this.opts.maxFiles) {
				this._error('You can upload at most ' + this.opts.maxFiles + ' file(s)', file);
				break;
			}

			const err = this._validate(file);
			if(err) { this._error(err, file); if(!this.opts.multiple) this._reflectFilled(); continue; }

			this._upload(file);
			if(!this.opts.multiple) break; // single mode: only the first valid file
		}
	}

	_upload(file) {
		const tile = this._buildTile({ id: null, name: file.name, size: file.size }, true);
		this.tilesEl.appendChild(tile);
		this._reflectFilled();

		const bar = tile.querySelector('.cerb-ui-file-upload--progress-bar');
		const size = tile.querySelector('.cerb-ui-file-upload--size');

		const xhr = new XMLHttpRequest();
		tile._xhr = xhr;
		const csrf = document.querySelector('meta[name="_csrf_token"]');
		xhr.open('POST', DevblocksAppPath + 'ajax.php?' + CerbUI.FileUpload.ENDPOINT, true);
		xhr.setRequestHeader('X-File-Name', encodeURIComponent(file.name));
		xhr.setRequestHeader('X-File-Type', file.type || 'application/octet-stream');
		xhr.setRequestHeader('X-File-Size', file.size);
		if(csrf) xhr.setRequestHeader('X-CSRF-Token', csrf.getAttribute('content'));

		xhr.upload.addEventListener('progress', (e) => {
			if(!e.lengthComputable) return;
			const pct = Math.round(e.loaded / e.total * 100);
			if(bar) bar.style.width = pct + '%';
			if(size) size.textContent = pct + '%';
		});

		xhr.onreadystatechange = () => {
			if(xhr.readyState !== 4) return;
			if(!tile.isConnected) return; // the chip was removed (cancelled) mid-flight — ignore the result
			let json = null;
			try { json = JSON.parse(xhr.responseText); } catch(ex) { json = null; }

			if(xhr.status === 200 && json && json.id) {
				const item = { id: json.id, name: json.name || file.name, size: json.size || file.size };
				this.values.push(item);
				this._finishTile(tile, item);
				this._syncHidden();
				if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getValue());

				// Keyboard nav: return focus to the upload button (multiple), or — when single-filled hides
				// it — to the new chip's × so focus stays in the component instead of dropping to <body>.
				if(this._refocusBtnAfterUpload) {
					this._refocusBtnAfterUpload = false;
					requestAnimationFrame(() => {
						if(!this.uploadBtn.hidden) {
							this.uploadBtn.focus();
						} else {
							const clear = tile.querySelector('.cerb-ui-file-upload--clear');
							if(clear) clear.focus();
						}
					});
				}
			} else {
				tile.remove();
				this._reflectFilled();
				this._error((json && json.error) ? json.error : ('Upload failed for ' + file.name), file);
			}
		};

		xhr.send(file);
	}

	// ── Chips (file icon + peek label + size + remove); used for both single and multiple ──
	_buildTile(item, pending) {
		const tile = document.createElement('span');
		tile.className = 'cerb-ui-file-upload--tile';

		const icon = document.createElement('span');
		icon.className = 'cerb-icons cerb-icon-file cerb-ui-file-upload--tile-icon';
		icon.setAttribute('aria-hidden', 'true');
		tile.appendChild(icon);

		// Label: while pending (no id yet) a plain span; once uploaded, a peek-trigger link to the attachment
		const label = document.createElement('span');
		label.className = 'cerb-ui-file-upload--label';
		label.textContent = item.name;
		tile.appendChild(label);

		const size = document.createElement('span');
		size.className = 'cerb-ui-file-upload--size';
		size.textContent = item.size ? this._prettyBytes(item.size) : '';
		tile.appendChild(size);

		const clear = document.createElement('button');
		clear.type = 'button';
		clear.className = 'cerb-ui-file-upload--clear';
		clear.setAttribute('aria-label', 'Remove');
		clear.innerHTML = '<span class="cerb-icons cerb-icon-circle-remove" aria-hidden="true"></span>';
		clear.addEventListener('click', (e) => { e.stopPropagation(); this._removeTile(tile); });
		tile.appendChild(clear);

		if(pending) {
			const progress = document.createElement('span');
			progress.className = 'cerb-ui-file-upload--progress';
			const bar = document.createElement('span');
			bar.className = 'cerb-ui-file-upload--progress-bar';
			progress.appendChild(bar);
			tile.appendChild(progress);
		}

		return tile;
	}

	// Promote a pending chip to a finished one: drop the progress bar, turn the name into a card-peek link.
	_finishTile(tile, item) {
		tile._fileId = item.id;
		tile._xhr = null;
		const progress = tile.querySelector('.cerb-ui-file-upload--progress');
		if(progress) progress.remove();

		const size = tile.querySelector('.cerb-ui-file-upload--size');
		if(size) size.textContent = item.size ? this._prettyBytes(item.size) : '';

		const label = tile.querySelector('.cerb-ui-file-upload--label');
		if(label) {
			const link = document.createElement('a');
			link.className = 'cerb-peek-trigger no-underline cerb-ui-file-upload--label';
			link.setAttribute('data-context', CerbUI.FileUpload.CONTEXT);
			link.setAttribute('data-context-id', item.id);
			// The peek-trigger anchor has no href, so it isn't a tab stop and cerbPeekTrigger only binds click.
			// Make the chip keyboard-reachable (it opens the attachment peek) and map Enter/Space → click.
			link.setAttribute('tabindex', '0');
			link.setAttribute('role', 'button');
			link.textContent = item.name;
			if(window.jQuery && jQuery.fn.cerbPeekTrigger) jQuery(link).cerbPeekTrigger();
			link.addEventListener('keydown', (e) => {
				if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); link.click(); }
			});
			label.replaceWith(link);
		}
	}

	_removeTile(tile) {
		if(tile._xhr && tile._fileId == null) tile._xhr.abort(); // cancel an in-flight upload
		if(tile._fileId != null)
			this.values = this.values.filter((v) => String(v.id) !== String(tile._fileId));
		tile.remove();
		this._syncHidden();
		this._reflectFilled();
		if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getValue());
	}

	// ── State ──
	_syncState() {
		this.tilesEl.replaceChildren();
		this.values.forEach((item) => {
			const tile = this._buildTile(item, false);
			this._finishTile(tile, item);
			this.tilesEl.appendChild(tile);
		});
		this._syncHidden();
		this._reflectFilled();
	}

	// Empty state shows the leading icon + prompt; the upload button hides only when single-filled.
	_reflectFilled() {
		const filled = this.tilesEl.children.length > 0;
		this.el.classList.toggle('cerb-ui-file-upload--has-tiles', filled);
		this.iconEl.hidden = filled;
		this.promptEl.hidden = filled;
		this.uploadBtn.hidden = !this.opts.multiple && filled;
	}

	// Hidden form fields. Multi → one `name[]` per value (none when empty). Single → ALWAYS one hidden
	// `name` field (so it posts through even when cleared): its value, or '' when empty.
	_syncHidden() {
		this.hiddenWrap.replaceChildren();
		if(!this.opts.name) return;
		if(this.opts.multiple) {
			const name = this.opts.name + '[]';
			this.values.forEach((item) => {
				const h = document.createElement('input');
				h.type = 'hidden';
				h.name = name;
				h.value = item.id;
				this.hiddenWrap.appendChild(h);
			});
		} else {
			const h = document.createElement('input');
			h.type = 'hidden';
			h.name = this.opts.name;
			h.value = this.values.length ? this.values[0].id : '';
			this.hiddenWrap.appendChild(h);
		}
	}

	_prettyBytes(bytes) {
		bytes = parseInt(bytes, 10) || 0;
		if(bytes < 1024) return bytes + ' B';
		const units = ['KB', 'MB', 'GB', 'TB'];
		let i = -1;
		do { bytes /= 1024; i++; } while(bytes >= 1024 && i < units.length - 1);
		return (bytes >= 10 ? Math.round(bytes) : Math.round(bytes * 10) / 10) + ' ' + units[i];
	}

	// ── Public API ──
	getValue() { return this.opts.multiple ? this.values.slice() : (this.values[0] || null); }

	setValue(value) {
		this.values = this._normalizeValues(value);
		this._syncState();
	}

	// Append already-uploaded attachment(s) by id (e.g. an inline image just pasted into an editor). Dedups
	// by id, respects single mode, updates chips + hidden inputs, fires onChange. Items: {id,name,size}.
	add(items) {
		(Array.isArray(items) ? items : [items]).forEach((raw) => {
			const item = this._normalizeValue(raw);
			if(!item) return;
			if(!this.opts.multiple) { this.values = []; this.tilesEl.replaceChildren(); }
			if(this.values.some((v) => String(v.id) === String(item.id))) return;
			this.values.push(item);
			const tile = this._buildTile(item, false);
			this._finishTile(tile, item);
			this.tilesEl.appendChild(tile);
		});
		this._syncHidden();
		this._reflectFilled();
		if(typeof this.opts.onChange === 'function') this.opts.onChange(this.getValue());
	}

	clear() {
		this.values = [];
		this._syncState();
	}

	destroy() {
		CerbUI.FileUpload._instances.delete(this.el);
		this.el.removeEventListener('click', this._onElClick);
		this.uploadBtn.removeEventListener('click', this._onUploadClick);
		this.fileInput.removeEventListener('change', this._onInputChange);
		this.el.removeEventListener('dragover', this._onDragOver);
		this.el.removeEventListener('dragenter', this._onDragOver);
		this.el.removeEventListener('dragleave', this._onDragLeave);
		this.el.removeEventListener('drop', this._onDrop);
		this.hiddenWrap.remove();
		this.el.classList.remove('cerb-ui-file-upload', 'cerb-ui-file-upload--multiple', 'cerb-ui-file-upload--has-tiles');
	}
};
