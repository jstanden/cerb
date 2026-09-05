{* Shared inline-sparkline loader for worklists. Renders one sparkchart per row (multi-series — e.g. a
   duration bar with an invocations line in front), loaded async from a metrics.timeseries-backed profile
   action so the worklist paints fast. The 2h/1d/1mo header switcher
   (.cerb-ui-switcher[data-cerb-spark-switcher]) re-fetches and persists per view in localStorage. Cells
   are .cerb-ui-sparkchart[data-cerb-spark="<rowId>"]. Place this inside a <script nonce> block.

   Params:
     spark_module   profiles module returning the JSON (e.g. 'metric', 'automation')
     spark_action   the profile action (e.g. 'viewSparklinesJson')
     spark_view_id  the view id (form ref + localStorage key)
     spark_tooltip_labels  optional; force the series label in the tooltip even for a single series
     spark_key      optional column key, REQUIRED when a worklist has more than one sparkline column --
                    it scopes the cell selector, the switcher, and the localStorage window so the two
                    columns don't fight. Omitted = the single-column behavior every other worklist uses.
     spark_series   optional; POSTed as `series` so one profile action can serve several columns
*}
(function() {
	const sparkFrm = $('#viewForm{$spark_view_id}');
	{if !empty($spark_key)}
	const sparkCellSel = '[data-cerb-spark][data-cerb-spark-key="{$spark_key}"]';
	const sparkSwitcherSel = '[data-cerb-spark-switcher="{$spark_key}"]';
	{else}
	const sparkCellSel = '[data-cerb-spark]';
	const sparkSwitcherSel = '[data-cerb-spark-switcher]';
	{/if}
	const sparkScale = (window.CerbUI && CerbUI.colorScale) ? CerbUI.colorScale() : null;
	const sparkTooltipLabels = {if !empty($spark_tooltip_labels)}true{else}false{/if};
	let sparkWindow = '1d'; // 2h | 1d | 1w | 1mo (set from the header switcher below)
	let sparkReq = 0; // generation token; a newer load drops a slower earlier response

	// Single render path; destroy the prior chart first so toggling can't leave stale listeners/data behind
	const sparkRender = function(el, d) {
		if(!window.CerbUI || !CerbUI.Sparkchart) return;
		const prev = CerbUI.Sparkchart.from(el);
		if(prev) prev.destroy(); else el.innerHTML = '';
		if(!d || !d.series) return;
		new CerbUI.Sparkchart(el, {
			scale: sparkScale,
			categories: d.categories,
			series: d.series, // endpoint series array (type/label/values/text); bars first, lines in front
			ticks: false, height: 36, // bare, compact; caption omitted
			tooltipLabels: sparkTooltipLabels, // show the label even for single-series charts (opt-in)
		});
	};

	const sparkLoad = function(showLoading) {
		const cells = sparkFrm.find(sparkCellSel).toArray();
		if(!cells.length) return;
		const req = ++sparkReq;
		if(showLoading && window.CerbUI && CerbUI.Spinner) {
			cells.forEach(function(el) {
				if(el.childElementCount) return; // keep an existing chart visible while reloading
				const sp = CerbUI.Spinner.create();
				sp.style.width = sp.style.height = '18px';
				el.appendChild(sp);
			});
		}
		const sparkData = new FormData();
		sparkData.set('c', 'profiles');
		sparkData.set('a', 'invoke');
		sparkData.set('module', '{$spark_module}');
		sparkData.set('action', '{$spark_action}');
		sparkData.set('window', sparkWindow);
		{if !empty($spark_series)}sparkData.set('series', '{$spark_series}');{/if}
		cells.forEach(function(el) { sparkData.append('ids[]', el.getAttribute('data-cerb-spark')); });
		genericAjaxPost(sparkData, '', '', function(rows) {
			if(req !== sparkReq) return; // a newer window/load superseded this response
			cells.forEach(function(el) { sparkRender(el, rows[el.getAttribute('data-cerb-spark')]); });
		}, { dataType: 'json' });
	};

	// Header window toggle (segmented; persists per view in localStorage via the Switcher's storageKey)
	const sparkSwitcherEl = sparkFrm.find(sparkSwitcherSel).get(0);
	if(sparkSwitcherEl && window.CerbUI && CerbUI.Switcher) {
		const sw = new CerbUI.Switcher(sparkSwitcherEl, {
			storageKey: 'cerb.spark.{$spark_module}{if !empty($spark_key)}.{$spark_key}{/if}:{$spark_view_id}',
			onSelect: function(value) { sparkWindow = value; sparkLoad(true); },
		});
		sparkWindow = sw.getValue() || '1d';
	}

	sparkLoad(true);
	// Near-real-time (opt-in): setInterval(function() { sparkLoad(false); }, 60000);
})();
