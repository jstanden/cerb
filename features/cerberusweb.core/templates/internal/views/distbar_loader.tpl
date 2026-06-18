{* Shared inline-distbar loader for worklists. Renders one mini CerbUI.Distbar per row — a horizontal
   stacked bar of live message counts (done / error / in progress / retrying / available) loaded async from
   a profile action so the worklist paints fast. Hovering a bar shows a CerbUI.Legend in a floating tooltip.
   Cells are .cerb-ui-distbar[data-cerb-messages="<rowId>"]. Place this inside a <script nonce> block.

   Params:
     distbar_module   profiles module returning the JSON (e.g. 'queue_job')
     distbar_action   the profile action (e.g. 'viewMessagesJson')
     distbar_view_id  the view id (form ref)
*}
(function() {
	const distbarFrm = $('#viewForm{$distbar_view_id}');
	let distbarReq = 0; // generation token; a newer load drops a slower earlier response

	// Segment order + semantic colors, matched 1:1 to the queue job monitor's progress bar.
	const DISTBAR_SEGMENTS = [
		{ label: 'Done', key: 'done' },
		{ label: 'Error', key: 'failed' },
		{ label: 'In progress', key: 'inflight' },
		{ label: 'Retrying', key: 'scheduled' },
		{ label: 'Available', key: 'available' },
	];
	const DISTBAR_PALETTE = [
		'var(--cerb-color-progress-done)',
		'var(--cerb-color-progress-failed)',
		'var(--cerb-color-progress-inflight)',
		'var(--cerb-color-progress-scheduled)',
		'var(--cerb-color-progress-available)',
	];

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

		let html = '';
		DISTBAR_SEGMENTS.forEach(function(seg) {
			const v = parseInt(d[seg.key], 10) || 0;
			html += '<span data-label="' + seg.label + '" data-value="' + v + '" data-text="' + v.toLocaleString() + '"></span>';
		});
		el.innerHTML = html;

		// Build the bar; route its generated legend into a detached host for the tooltip (vertical layout).
		const legendHost = document.createElement('div');
		new CerbUI.Distbar(el, { palette: DISTBAR_PALETTE, hideZeros: true, legend: legendHost });
		const legendEl = legendHost.querySelector('.cerb-ui-legend');
		if(legendEl) {
			legendEl.classList.add('cerb-ui-legend--vertical');
			distbarLegends.set(el, legendHost);
		}
	};

	const distbarLoad = function(showLoading) {
		const cells = distbarFrm.find('[data-cerb-messages]').toArray();
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
		cells.forEach(function(el) { distbarData.append('ids[]', el.getAttribute('data-cerb-messages')); });
		genericAjaxPost(distbarData, '', '', function(rows) {
			if(req !== distbarReq) return; // a newer load superseded this response
			cells.forEach(function(el) { distbarRender(el, rows[el.getAttribute('data-cerb-messages')]); });
		}, { dataType: 'json' });
	};

	// Hover shows the per-cell legend in a floating tooltip (pointer-events disabled, so it never steals hover).
	if(distbarTip) {
		distbarFrm.on('mouseenter', '[data-cerb-messages]', function(e) {
			const host = distbarLegends.get(this);
			if(host && host.firstChild) distbarTip.show(host, e.clientX, e.clientY, this);
		});
		distbarFrm.on('mousemove', '[data-cerb-messages]', function(e) {
			distbarTip.move(e.clientX, e.clientY);
		});
		distbarFrm.on('mouseleave', '[data-cerb-messages]', function() {
			distbarTip.hide();
		});
	}

	distbarLoad(true);
})();
