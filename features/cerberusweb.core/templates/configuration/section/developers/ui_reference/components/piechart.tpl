	<div class="cerb-uiref-component" id="piechart">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chart-pie"></span>Pie / donut</div>

		{* Pie + donut sharing the CerbUI.Chart base; hover a slice for the value + percent, click to drill through *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Pie &amp; donut; hover a slice for <code>value (pct)</code>, legend below</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex;gap:2em;flex-wrap:wrap;">
					<div class="cerb-ui-chart" id="uiref-piechart" style="width:280px;"></div>
					<div class="cerb-ui-chart" id="uiref-donutchart" style="width:280px;"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.PieChart(el, {
	type: 'pie',                       // or 'donut' (center hole)
	slices: [
		{ label: 'Open',    value: 42 },
		{ label: 'Waiting', value: 18 },
		{ label: 'Closed',  value: 27 },
		// { label, value, text?, color?, click?:{ context, query } }
	],
	legend: true,                      // renders a matching CerbUI.Legend below
	tooltip: { ratios: true },         // 'value (pct%)' on hover (default on)
	// valueFormat: ',',               // CerbUI.num.format pattern for values
	height: 260,                       // palette / scale also accepted
});{/literal}</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// drill-through + events (bubbling on el)
new CerbUI.PieChart(el, { type:'donut', slices:[
	{ label:'Open', value:42, click:{ context:'cerberusweb.contexts.ticket', query:'status:o' } },
	/* … */
]});
el.addEventListener('cerb-ui-chart:click', e =&gt; console.log(e.detail)); // {index,label,value,click}{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.PieChart))
		return;

	const slices = [
		{ label: 'Open',        value: 42 },
		{ label: 'Waiting',     value: 18 },
		{ label: 'In progress', value: 33 },
		{ label: 'Closed',      value: 27 },
		{ label: 'Deleted',     value: 6 },
	];

	const pie = document.getElementById('uiref-piechart');
	if(pie) new CerbUI.PieChart(pie, { type: 'pie', slices: slices, legend: true, height: 260 });

	const donut = document.getElementById('uiref-donutchart');
	if(donut) new CerbUI.PieChart(donut, { type: 'donut', slices: slices, legend: true, height: 260 });
})();
</script>
