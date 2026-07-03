	<div class="cerb-uiref-component" id="scatter-chart">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chart-scatterplot"></span>Scatterplot</div>

		{* XY point clouds on two continuous linear axes; hover snaps to the nearest point *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Two continuous axes, one point cloud per series; hover for the nearest point</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-chart" id="uiref-scatter" style="max-width:420px;"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.ScatterChart(el, {
	x: { label: 'Replies', tickFormat: CerbUI.num.format(',') },
	y: { label: 'Minutes', tickFormat: CerbUI.num.format(','), grid: true },
	series: [
		{ key: 'a', name: 'Team A', x: [1, 4, 7, 9], values: [12, 30, 22, 41] },
		{ key: 'b', name: 'Team B', x: [2, 3, 6, 10], values: [18, 9, 33, 27] },
		// { key, name, color?, x:[…], values:[…] }  (or points:[[x,y],…]); click?:[{context,query}]
	],
	// legend: true, height: 300, palette / scale,
});{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.ScatterChart))
		return;

	const el = document.getElementById('uiref-scatter');
	if(!el)
		return;

	const cloud = (n, cx, cy, spread) => {
		const x = [], y = [];
		for(let i = 0; i < n; i++) { x.push(cx + (Math.random() - 0.5) * spread); y.push(cy + (Math.random() - 0.5) * spread); }
		return { x: x, values: y };
	};

	const a = cloud(24, 8, 25, 12), b = cloud(24, 16, 40, 16);
	new CerbUI.ScatterChart(el, {
		x: { label: 'Replies', tickFormat: CerbUI.num.format(',') },
		y: { label: 'Minutes', tickFormat: CerbUI.num.format(','), grid: true },
		series: [
			{ key: 'a', name: 'Team A', x: a.x, values: a.values },
			{ key: 'b', name: 'Team B', x: b.x, values: b.values },
		],
		legend: true,
		height: 300,
	});
})();
</script>
