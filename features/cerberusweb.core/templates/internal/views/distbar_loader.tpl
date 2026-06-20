{* Shared inline-distbar loader for worklists. Renders one mini CerbUI.Distbar per row — a horizontal
   stacked bar of live counts (segments configurable) loaded async from a profile action so the worklist
   paints fast. Hovering a bar shows a CerbUI.Legend in a floating tooltip.
   Cells are .cerb-ui-distbar[<distbar_cell_attr>="<rowId>"]. Place this inside a <script nonce> block.

   Params:
     distbar_module     profiles module returning the JSON (e.g. 'queue_job')
     distbar_action     the profile action (e.g. 'viewMessagesJson')
     distbar_view_id    the view id (form ref)
     distbar_cell_attr  cell hook attribute (default 'data-cerb-messages')
     distbar_keys       comma-joined JSON keys, in segment order (default queue job set)
     distbar_labels     comma-joined legend labels, 1:1 with keys (no commas in a label)
     distbar_palette    comma-joined CSS colors, 1:1 with keys
     distbar_scope_keys optional scope switcher map 'value=key|key;value=key|key' (empty = no switcher).
                        A [data-cerb-distbar-switcher] CerbUI.Switcher toggles which segment keys show;
                        the bar re-scales client-side (no re-fetch), persisted per view in localStorage.
     distbar_scope_attr switcher element hook attribute (default 'data-cerb-distbar-switcher')
*}
{$distbar_cell_attr = $distbar_cell_attr|default:'data-cerb-messages'}
{$distbar_keys      = $distbar_keys|default:'done,failed,inflight,scheduled,available'}
{$distbar_labels    = $distbar_labels|default:'Done,Error,In progress,Retrying,Available'}
{$distbar_palette   = $distbar_palette|default:'var(--cerb-color-progress-done),var(--cerb-color-progress-failed),var(--cerb-color-progress-inflight),var(--cerb-color-progress-scheduled),var(--cerb-color-progress-available)'}
{$distbar_scope_keys = $distbar_scope_keys|default:''}
{$distbar_scope_attr = $distbar_scope_attr|default:'data-cerb-distbar-switcher'}
(function() {
	const distbarFrm = $('#viewForm{$distbar_view_id}');
	let distbarReq = 0; // generation token; a newer load drops a slower earlier response

	// Segment order + colors, configured by the caller (defaults match the queue job progress bar).
	const DISTBAR_PALETTE = '{$distbar_palette}'.split(',');
	const DISTBAR_KEYS = '{$distbar_keys}'.split(',');
	const DISTBAR_LABELS = '{$distbar_labels}'.split(',');
	// Each segment carries its own color so series colors stay put when the scope hides some segments
	// (the Distbar colors by palette index, which would otherwise shift onto a hidden segment's color).
	const DISTBAR_SEGMENTS = DISTBAR_KEYS.map((k, i) => ({ label: DISTBAR_LABELS[i], key: k, color: DISTBAR_PALETTE[i] }));

	// Optional scope switcher: value -> Set of visible segment keys. Empty unless the caller opts in.
	const DISTBAR_SCOPES = Object.create(null);
	'{$distbar_scope_keys}'.split(';').forEach(function(pair) {
		if(!pair) return;
		const eq = pair.indexOf('=');
		DISTBAR_SCOPES[pair.slice(0, eq)] = new Set(pair.slice(eq + 1).split('|').filter(Boolean));
	});
	let distbarActiveKeys = null; // null = show every segment
	let distbarLastRows = Object.create(null); // cache so a scope toggle re-renders without re-fetching

	// Per-cell legend node (built alongside the bar), shown in the shared hover tooltip. WeakMap so cells
	// swapped out by a worklist refresh don't leak.
	const distbarLegends = new WeakMap();
	const distbarTip = (window.CerbUI && CerbUI.Tooltip) ? new CerbUI.Tooltip() : null;

	const distbarRender = function(el, d) {
		if(!window.CerbUI || !CerbUI.Distbar) return;
		el.innerHTML = '';
		distbarLegends.delete(el);
		// No remaining messages (e.g. a finished job whose rows aged off) — leave the cell empty.
		if(!d || !d.total) return;

		// Honor the active scope (if any): only the in-scope segments paint, so the bar re-scales to them.
		const segs = distbarActiveKeys
			? DISTBAR_SEGMENTS.filter(function(seg) { return distbarActiveKeys.has(seg.key); })
			: DISTBAR_SEGMENTS;

		let html = '';
		segs.forEach(function(seg) {
			const v = parseInt(d[seg.key], 10) || 0;
			html += '<span data-label="' + seg.label + '" data-value="' + v + '" data-text="' + v.toLocaleString() + '"></span>';
		});
		el.innerHTML = html;

		// Build the bar; route its generated legend into a detached host for the tooltip (vertical layout).
		// Pass a palette aligned to the visible segments so each series keeps its color across scope toggles.
		const palette = segs.map(function(seg) { return seg.color; });
		const legendHost = document.createElement('div');
		new CerbUI.Distbar(el, { palette: palette, hideZeros: true, legend: legendHost });
		const legendEl = legendHost.querySelector('.cerb-ui-legend');
		if(legendEl) {
			legendEl.classList.add('cerb-ui-legend--vertical');
			distbarLegends.set(el, legendHost);
		}
	};

	// Render every cell from a rows map (used by both the initial load and scope toggles)
	const distbarRenderAll = function(rows) {
		distbarFrm.find('[{$distbar_cell_attr}]').toArray().forEach(function(el) {
			distbarRender(el, rows[el.getAttribute('{$distbar_cell_attr}')]);
		});
	};

	const distbarLoad = function(showLoading) {
		const cells = distbarFrm.find('[{$distbar_cell_attr}]').toArray();
		if(!cells.length) return;
		const req = ++distbarReq;
		if(showLoading && window.CerbUI && CerbUI.Spinner) {
			cells.forEach(function(el) {
				if(el.childElementCount) return; // keep an existing bar visible while reloading
				const sp = CerbUI.Spinner.create();
				sp.style.width = sp.style.height = '18px';
				el.appendChild(sp);
			});
		}
		const distbarData = new FormData();
		distbarData.set('c', 'profiles');
		distbarData.set('a', 'invoke');
		distbarData.set('module', '{$distbar_module}');
		distbarData.set('action', '{$distbar_action}');
		cells.forEach(function(el) { distbarData.append('ids[]', el.getAttribute('{$distbar_cell_attr}')); });
		genericAjaxPost(distbarData, '', '', function(rows) {
			if(req !== distbarReq) return; // a newer load superseded this response
			distbarLastRows = rows; // cache for instant scope re-renders
			distbarRenderAll(rows);
		}, { dataType: 'json' });
	};

	// Header scope switcher (segmented; persists per view in localStorage). A toggle just re-renders the
	// cached rows with a different visible-key set — no server round-trip.
	const distbarSwitcherEl = distbarFrm.find('[{$distbar_scope_attr}]').get(0);
	if(distbarSwitcherEl && Object.keys(DISTBAR_SCOPES).length && window.CerbUI && CerbUI.Switcher) {
		const sw = new CerbUI.Switcher(distbarSwitcherEl, {
			storageKey: 'cerb.distbar.{$distbar_module}:{$distbar_view_id}',
			onSelect: function(value) { distbarActiveKeys = DISTBAR_SCOPES[value] || null; distbarRenderAll(distbarLastRows); },
		});
		distbarActiveKeys = DISTBAR_SCOPES[sw.getValue()] || null; // apply persisted/default scope to first paint
	}

	// Hover shows the per-cell legend in a floating tooltip (pointer-events disabled, so it never steals hover).
	if(distbarTip) {
		distbarFrm.on('mouseenter', '[{$distbar_cell_attr}]', function(e) {
			const host = distbarLegends.get(this);
			if(host && host.firstChild) distbarTip.show(host, e.clientX, e.clientY, this);
		});
		distbarFrm.on('mousemove', '[{$distbar_cell_attr}]', function(e) {
			distbarTip.move(e.clientX, e.clientY);
		});
		distbarFrm.on('mouseleave', '[{$distbar_cell_attr}]', function() {
			distbarTip.hide();
		});
	}

	distbarLoad(true);
})();
