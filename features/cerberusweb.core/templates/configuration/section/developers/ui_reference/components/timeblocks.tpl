	<div class="cerb-uiref-component" id="timeblocks">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chart-timeblocks"></span>Timeblocks</div>

		{* GitHub-style activity calendar: rows = days, columns = hours 0-23, cells tinted by value *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Activity heatmap: days &times; hours; hover a cell for the built-in tooltip</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-timeblocks" id="uiref-timeblocks"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// same "timeblocks" data shape the widget backend emits: one entry per active day+hour bucket
new CerbUI.Timeblocks(el, {
	data: [
		{ date: 1719936000000, value: 27 },  // date = unix-ms or an ISO string (anything new Date() accepts)
		{ date: 1719939600000, value: 3 },
		// …
	],
	// cellSize: 22,               // px per cell (and per hour/day step)
	// colorTo: 'rgb(19,134,3)',   // ramp endpoint; the ramp starts at --cerb-color-background
	// tooltip: true,              // built-in hover tooltip (one shared instance); false = events-only
});{/literal}</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// or own the interaction: disable the built-in tooltip and subscribe to events
new CerbUI.Timeblocks(el, { tooltip: false, data: [/* … */] });
// detail = date (Date), value, hour (0-23), day (row offset), point:{x,y}
el.addEventListener('cerb-ui-timeblocks:hover', e =&gt; renderMyTooltip(e.detail));
el.addEventListener('cerb-ui-timeblocks:click', e =&gt; drillIntoHour(e.detail));{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.Timeblocks))
		return;

	const el = document.getElementById('uiref-timeblocks');
	if(!el)
		return;

	// 21 days of hourly buckets; a business-hours pattern with weekday emphasis and some noise
	const days = 21;
	const base = new Date();
	base.setHours(0, 0, 0, 0);
	base.setTime(base.getTime() - (days - 1) * 86400000);

	const data = [];
	for(let d = 0; d < days; d++) {
		const dayStart = base.getTime() + d * 86400000;
		const dow = new Date(dayStart).getDay(); // 0 = Sun
		const weekend = (dow === 0 || dow === 6);
		for(let h = 0; h < 24; h++) {
			const business = (h >= 8 && h <= 18) ? 1 : 0.15;
			const weight = weekend ? 0.35 : 1;
			const value = Math.round(Math.random() * 30 * business * weight);
			if(value > 0)
				data.push({ date: dayStart + h * 3600000, value: value });
		}
	}

	new CerbUI.Timeblocks(el, { data: data });
})();
</script>
