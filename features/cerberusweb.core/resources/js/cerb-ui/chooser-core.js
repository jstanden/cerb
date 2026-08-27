/*
 * CerbUI.chooserCore — shared engine for server-backed "Choosers" (RecordChooser, ContextChooser…).
 *
 * Unlike a *Picker (local data: Date, Color, Priority), a *Chooser never materializes the full set in
 * the DOM — it searches the server. chooserCore supplies the floating popup, a debounced search box,
 * a paged result list, keyboard nav, and — crucially — avatars without a stampede: each row paints an
 * instant client-side monogram (initials + a stable hashed color), then the REAL avatar lazy-loads
 * only for rows actually scrolled into view, through a small global concurrency queue. Opening a list
 * of 1,000 workers never fires 1,000 image requests.
 *
 * A Chooser component (e.g. CerbUI.RecordChooser) composes this:
 *
 *   const core = CerbUI.chooserCore.create({
 *     anchor: triggerEl,
 *     placeholder: 'Search workers…',
 *     search: async (query, page) => ({ results: [{context, id, label, image_url}], more: false }),
 *     pinned: async () => [{context, id, label, image_url}],   // optional empty-query shortcuts
 *     onSelect: (item) => { ... },
 *   });
 *   core.open(); core.close(); core.isOpen(); core.destroy();
 *
 * Requires CerbUI.palettes (for monogram colors).
 */
CerbUI.chooserCore = (function() {
	'use strict';

	// ── Lazy-avatar loading ────────────────────────────────────────────────────
	// Delegated to CerbUI.Avatar's bounded loader (batched, per-request timeout, retries) so a chooser
	// and a plain avatar list share one budget against the server rather than two. The returned job is
	// still the cancellation handle for a row that scrolls away.
	function _avatarEnqueue(url, onload) {
		return CerbUI.Avatar._enqueue(url, onload);
	}

	// ── Client-side monogram — delegated to the shared CerbUI.Avatar (hash-locked color) ──
	function _initials(label) { return CerbUI.Avatar.initials(label); }
	function _monogramColor(seed) { return CerbUI.Avatar.color(seed); }

	// Build the avatar cell: instant monogram now; the row wires lazy-load of the real image.
	function _buildAvatar(item) {
		const wrap = document.createElement('span');
		wrap.className = 'cerb-ui-chooser--avatar';

		const seed = (item.context || '') + ':' + (item.id != null ? item.id : item.label);
		wrap.style.backgroundColor = _monogramColor(seed);
		wrap.textContent = _initials(item.label);
		wrap.dataset.imageUrl = item.image_url || '';
		return wrap;
	}

	// ── Factory ─────────────────────────────────────────────────────────────────
	function create(opts) {
		const o = Object.assign({
			anchor:      null,           // element the popup positions against
			search:      null,           // async (query, page) => { results:[], more:bool }
			pinned:      null,           // optional () => items | Promise<items> for the empty query
			placeholder: 'Search…',
			emptyText:   'No matches',
			pageSize:    25,
			closeOnSelect: true,         // false = stay open after a pick (multi-select: add several)
			hideOnEmpty: null,           // 0 matches → hide the panel (no placeholder); null = auto (on for inline autocomplete, off for the self-contained popup)
			plain:       false,          // true = plain text rows (no monogram avatar); an optional per-item `icon` (cerb-icons name) renders a leading glyph instead. For text suggestions (CerbUI.TextChooser) where a record avatar would be meaningless.
			onSelect:    null,           // (item) => {}
			onClose:     null,
			onResults:   null,           // (items, query) => {} after each search renders (e.g. an adder that
			                             // hides itself when the empty query returns nothing left to add)
		}, opts);

		let open = false;
		let query = '';
		let page = 0;
		let more = false;
		let loading = false;
		let activeIndex = -1;       // keyboard-highlighted row
		let items = [];             // current result models
		let io = null;              // IntersectionObserver for lazy avatars
		const avatarJobs = new WeakMap(); // row -> queued avatar job (to cancel on recycle)

		// Two modes: bind to the caller's field (inline autocomplete — the Chooser model), or build our
		// own search box inside the popup (self-contained). anchorEl is what the results float against.
		const useExternalInput = !!o.inputEl;
		// Inline autocomplete (external input) hides the dropdown when nothing matches; the self-contained
		// popup keeps its "No matches" line. Override with opts.hideOnEmpty.
		const hideOnEmpty = (o.hideOnEmpty != null) ? !!o.hideOnEmpty : useExternalInput;
		const anchorEl = o.anchor || o.inputEl;

		const panel = document.createElement('div');
		panel.className = 'cerb-ui-chooser--panel';
		if(useExternalInput) panel.classList.add('cerb-ui-chooser--panel-inline'); // results-only (no head)
		panel.setAttribute('role', 'dialog');
		panel.hidden = true;

		let search;
		if(useExternalInput) {
			search = o.inputEl; // the visible field drives the search
		} else {
			const head = document.createElement('div');
			head.className = 'cerb-ui-chooser--head';
			search = document.createElement('input');
			search.type = 'text';
			search.className = 'cerb-ui-chooser--search';
			search.setAttribute('placeholder', o.placeholder);
			search.setAttribute('autocomplete', 'off');
			head.appendChild(search);
			panel.appendChild(head);
		}

		const list = document.createElement('ul');
		list.className = 'cerb-ui-chooser--list';
		panel.appendChild(list);
		document.body.appendChild(panel);

		// ── Lazy avatars: load only rows scrolled into view ──
		function ensureObserver() {
			if(io) return;
			io = new IntersectionObserver(function(entries) {
				entries.forEach(function(entry) {
					if(!entry.isIntersecting) return;
					const row = entry.target;
					io.unobserve(row);
					const av = row.querySelector('.cerb-ui-chooser--avatar');
					const url = av && av.dataset.imageUrl;
					if(!url) return;
					const job = _avatarEnqueue(url, function(src) {
						av.style.backgroundImage = 'url("' + src + '")';
						av.style.backgroundColor = 'transparent';
						av.textContent = '';
					});
					avatarJobs.set(row, job);
				});
			}, { root: list, rootMargin: '120px' });
		}

		function cancelAvatars() {
			list.querySelectorAll('.cerb-ui-chooser--item').forEach(function(row) {
				const job = avatarJobs.get(row);
				if(job) job.cancelled = true;
			});
			if(io) { io.disconnect(); io = null; }
		}

		// ── Rendering ──
		function renderRows(append) {
			if(!append) {
				cancelAvatars();
				list.replaceChildren();
				activeIndex = -1;
			}
			ensureObserver();

			items.forEach(function(item, i) {
				if(append && i < list.children.length) return; // skip already-rendered
				const row = document.createElement('li');
				row.className = 'cerb-ui-chooser--item';
				if(o.plain) {
					// Plain text suggestion: no monogram. Optional per-item `swatch` (a color) or `icon` (a glyph)
					// paints a leading indicator.
					if(item.swatch) {
						const sw = document.createElement('span');
						sw.className = 'cerb-ui-chooser--swatch';
						sw.style.backgroundColor = item.swatch; // the browser ignores an invalid color string
						sw.setAttribute('aria-hidden', 'true');
						row.appendChild(sw);
					} else if(item.icon) {
						const ic = document.createElement('span');
						ic.className = 'cerb-icons cerb-icon-' + item.icon + ' cerb-ui-chooser--icon';
						ic.setAttribute('aria-hidden', 'true');
						row.appendChild(ic);
					}
				} else {
					row.appendChild(_buildAvatar(item));
				}
				// Vertical text stack: label, plus an optional muted second line (e.g. a worker's title)
				const text = document.createElement('span');
				text.className = 'cerb-ui-chooser--text';
				const label = document.createElement('span');
				label.className = 'cerb-ui-chooser--label';
				label.textContent = item.label; // textContent — never innerHTML for record data
				text.appendChild(label);
				if(item.sublabel) {
					const sub = document.createElement('span');
					sub.className = 'cerb-ui-chooser--sublabel';
					sub.textContent = item.sublabel;
					text.appendChild(sub);
				}
				row.appendChild(text);
				// Optional right-aligned hint (e.g. a hex value beside a color swatch) — one row, not a second line.
				if(item.hint) {
					const hint = document.createElement('span');
					hint.className = 'cerb-ui-chooser--hint';
					hint.textContent = item.hint;
					row.appendChild(hint);
				}
				// Keep focus on the search field while picking (standard autocomplete behavior) — a row
				// click otherwise blurs an inline input, misfiring host blur handlers (e.g. date parse-on-blur).
				row.addEventListener('mousedown', function(e) { e.preventDefault(); });
				row.addEventListener('click', function() { choose(item); });
				row.addEventListener('mousemove', function() { setActive(i); });
				list.appendChild(row);
				if(!o.plain) io.observe(row); // no avatars to lazy-load in plain mode
			});

			if(!items.length) {
				// No matches → hide the dropdown entirely (no '…'/placeholder) for inline autocomplete
				if(hideOnEmpty) {
					panel.hidden = true;
					return;
				}
				const empty = document.createElement('li');
				empty.className = 'cerb-ui-chooser--empty';
				empty.textContent = loading ? '…' : o.emptyText;
				list.appendChild(empty);
			} else if(open && hideOnEmpty && panel.hidden) {
				panel.hidden = false; // results returned after a hide → show again (positioned below)
			}

			if(open && !append) position(); // height changed — re-anchor so it doesn't drift/overlap
		}

		function setActive(i) {
			const rows = list.querySelectorAll('.cerb-ui-chooser--item');
			if(activeIndex >= 0 && rows[activeIndex]) rows[activeIndex].classList.remove('cerb-ui-chooser--item-active');
			activeIndex = Math.max(-1, Math.min(i, rows.length - 1));
			if(activeIndex >= 0 && rows[activeIndex]) {
				rows[activeIndex].classList.add('cerb-ui-chooser--item-active');
				rows[activeIndex].scrollIntoView({ block: 'nearest' });
			}
		}

		// ── Search ──
		async function runSearch(append) {
			if(loading) return;
			loading = true;
			const thisPage = append ? page + 1 : 0;
			try {
				let res;
				if(!query && !append && typeof o.pinned === 'function') {
					const pinned = await o.pinned();
					res = { results: pinned || [], more: false };
				} else if(typeof o.search === 'function') {
					res = await o.search(query, thisPage) || { results: [], more: false };
				} else {
					res = { results: [], more: false };
				}
				page = thisPage;
				more = !!res.more;
				items = append ? items.concat(res.results || []) : (res.results || []);
				renderRows(append);
				if(typeof o.onResults === 'function') o.onResults(items, query);
			} catch(e) {
				items = append ? items : [];
				renderRows(false);
				if(typeof o.onResults === 'function') o.onResults(items, query);
			} finally {
				loading = false;
			}
		}

		let _debounce = null;
		function onInput() {
			query = search.value.trim();
			if(_debounce) clearTimeout(_debounce);
			_debounce = setTimeout(function() { runSearch(false); }, 250);
		}
		search.addEventListener('input', onInput);

		// Infinite scroll for the long tail
		list.addEventListener('scroll', function() {
			if(more && !loading && (list.scrollTop + list.clientHeight >= list.scrollHeight - 80))
				runSearch(true);
		});

		function choose(item) {
			if(typeof o.onSelect === 'function') o.onSelect(item);
			if(o.closeOnSelect) close(); // multi-select keeps it open (the caller resets the field + refreshes)
		}

		// ── Keyboard ──
		function onKey(e) {
			if(e.key === 'ArrowDown')      { e.preventDefault(); setActive(activeIndex + 1); }
			else if(e.key === 'ArrowUp')   { e.preventDefault(); setActive(activeIndex - 1); }
			else if(e.key === 'Enter')     {
				e.preventDefault();
				if(activeIndex >= 0 && items[activeIndex]) choose(items[activeIndex]);
			}
			else if(e.key === 'Escape')    { e.preventDefault(); close(); }
			// Tabbing out of the field closes the menu (don't preventDefault — let focus move on)
			else if(e.key === 'Tab')       { close(); }
		}
		search.addEventListener('keydown', onKey);

		// ── Positioning (anchor below; flip up when cramped; clamp to viewport) ──
		function position() {
			if(!anchorEl) return;
			const r = anchorEl.getBoundingClientRect();
			const h = panel.offsetHeight || 320;
			const w = panel.offsetWidth || 320;
			const below = (window.innerHeight - r.bottom >= h + 8) || (r.top < h + 8);
			const top = below ? r.bottom + 4 : r.top - h - 4;
			const left = Math.min(r.left, document.documentElement.clientWidth - w - 8);
			panel.style.top  = (top + window.scrollY) + 'px';
			panel.style.left = (Math.max(8, left) + window.scrollX) + 'px';
		}

		// ── Open / close ──
		let _docDown = null;
		function doOpen() {
			if(open) return;
			open = true;
			// Inline autocomplete (hideOnEmpty) stays hidden until the first results arrive — an empty
			// query or a 0-match search then never flashes a blank menu (renderRows reveals it). The
			// self-contained popup (shows a "No matches" line) still opens immediately.
			if(!hideOnEmpty) panel.hidden = false;
			position();
			if(!useExternalInput) search.value = ''; // our own box starts empty; an external field keeps its text
			query = search.value.trim();
			runSearch(false);
			requestAnimationFrame(function() { search.focus(); });

			_docDown = function(e) {
				if(panel.contains(e.target)) return;
				if(anchorEl && anchorEl.contains(e.target)) return;
				close();
			};
			document.addEventListener('pointerdown', _docDown, true);
			window.addEventListener('resize', position);
			window.addEventListener('scroll', position, true);
		}

		function close() {
			if(!open) return;
			open = false;
			panel.hidden = true;
			cancelAvatars();
			if(_docDown) { document.removeEventListener('pointerdown', _docDown, true); _docDown = null; }
			window.removeEventListener('resize', position);
			window.removeEventListener('scroll', position, true);
			if(typeof o.onClose === 'function') o.onClose();
		}

		function destroy() {
			close();
			search.removeEventListener('input', onInput);
			search.removeEventListener('keydown', onKey);
			panel.remove();
		}

		return {
			open:    doOpen,
			close:   close,
			isOpen:  function() { return open; },
			refresh: function() { if(open) { query = search.value.trim(); runSearch(false); } }, // re-sync the query (e.g. caller cleared the field) so the filter resets
			panel:   panel,
			destroy: destroy,
		};
	}

	return {
		create:        create,
		monogramColor: _monogramColor,
		initials:      _initials,
	};
})();
