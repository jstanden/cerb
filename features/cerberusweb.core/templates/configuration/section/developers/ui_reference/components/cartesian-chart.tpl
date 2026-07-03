	<div class="cerb-uiref-component" id="cartesian-chart">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chart-bar"></span>Bar / line</div>

		{* Cartesian chart on the CerbUI.Chart base: band + value axes, per-series type (mix bar + line), stacking *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Vertical bar, horizontal stacked bar, and a mixed bar + line on one plot</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex;gap:2em;flex-wrap:wrap;">
					<div class="cerb-ui-chart" id="uiref-bar" style="width:320px;"></div>
					<div class="cerb-ui-chart" id="uiref-stacked" style="width:340px;"></div>
					<div class="cerb-ui-chart" id="uiref-mixed" style="width:360px;"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.CartesianChart(el, {
	orientation: 'vertical',                 // or 'horizontal' (rotated bars, categories down the left)
	x: { categories: ['Mon','Tue','Wed','Thu','Fri'] },   // band axis
	y: { tickFormat: CerbUI.num.format(','), grid: true }, // value axis (linear)
	series: [
		{ key: 'opened', name: 'Opened', type: 'bar', values: [12, 19, 7, 22, 15] },
		// { key, name, type:'bar'|'line', color?, stack?, values, click?:[{context,query}] }
	],
	// legend: true, tooltip: {…}, height: 260, palette / scale,
});{/literal}</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Mix marks on one plot + stack either: two bar series share a stack, a line rides on top
new CerbUI.CartesianChart(el, {
	x: { categories: [/* … */] },
	series: [
		{ key:'a', name:'Web',   type:'bar',  stack:'src', values:[/* … */] },
		{ key:'b', name:'Email', type:'bar',  stack:'src', values:[/* … */] }, // stacks on Web
		{ key:'avg', name:'Avg', type:'line',              values:[/* … */] },
	],
	legend: true,
});
el.addEventListener('cerb-ui-chart:click', e =&gt; console.log(e.detail)); // {series,category,value,click}{/literal}</pre>
			</div>
		</div>

		{* Continuous time x-axis: line, stacked area, spline — date-formatted ticks + nearest-point tooltip *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Continuous <b>time</b> axis (line, stacked area, spline) with date-formatted ticks</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex;gap:2em;flex-wrap:wrap;">
					<div class="cerb-ui-chart" id="uiref-ts-line" style="width:340px;"></div>
					<div class="cerb-ui-chart" id="uiref-ts-area" style="width:340px;"></div>
					<div class="cerb-ui-chart" id="uiref-ts-spline" style="width:340px;"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Continuous time x-axis: pass parsed timestamps (ms) + a date tick formatter
new CerbUI.CartesianChart(el, {
	x: { scale: 'time', timestamps: days.map(d =&gt; d.getTime()), tickFormat: CerbUI.date.strftime('%b %e') },
	y: { tickFormat: CerbUI.num.format(','), grid: true },
	series: [
		{ key: 'web',   name: 'Web',   type: 'area', stack: 'g', values: [/* … */] },
		{ key: 'email', name: 'Email', type: 'area', stack: 'g', values: [/* … */] }, // stacked area
	],
	legend: true, points: true,
});{/literal}</pre>
			</div>
		</div>

		{* Dual value axes: bars on y (left), a line on y2 (right) — each with its own scale + ticks *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Two value axes: bars on <b>y</b> (left) + a line on <b>y2</b> (right)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-chart" id="uiref-y2" style="max-width:460px;"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.CartesianChart(el, {
	x: { categories: ['Mon','Tue','Wed','Thu','Fri'] },
	y:  { label: 'Volume',   tickFormat: CerbUI.num.format(','), grid: true },
	y2: { label: 'Rate (%)', tickFormat: CerbUI.num.format('.0%') },   // series with axis:'y2' plot here
	series: [
		{ key: 'volume', name: 'Volume', type: 'bar',              values: [1200, 1900, 700, 2200, 1500] },
		{ key: 'rate',   name: 'Rate',   type: 'line', axis: 'y2', values: [0.62, 0.55, 0.71, 0.48, 0.66] },
	],
	legend: true,
});{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.CartesianChart))
		return;

	const cats = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

	const bar = document.getElementById('uiref-bar');
	if(bar) new CerbUI.CartesianChart(bar, {
		orientation: 'vertical',
		x: { categories: cats },
		y: { grid: true },
		series: [ { key: 'opened', name: 'Opened', type: 'bar', values: [12, 19, 7, 22, 15] } ],
		height: 240,
	});

	const stacked = document.getElementById('uiref-stacked');
	if(stacked) new CerbUI.CartesianChart(stacked, {
		orientation: 'horizontal',
		x: { categories: cats, width: 60 },
		y: { grid: true },
		series: [
			{ key: 'web',   name: 'Web',   type: 'bar', stack: 'src', values: [8, 11, 4, 14, 9] },
			{ key: 'email', name: 'Email', type: 'bar', stack: 'src', values: [4, 6, 3, 8, 5] },
		],
		legend: true,
		height: 240,
	});

	const mixed = document.getElementById('uiref-mixed');
	if(mixed) new CerbUI.CartesianChart(mixed, {
		orientation: 'vertical',
		x: { categories: cats },
		y: { grid: true },
		series: [
			{ key: 'web',   name: 'Web',   type: 'bar',  stack: 'src', values: [8, 11, 4, 14, 9] },
			{ key: 'email', name: 'Email', type: 'bar',  stack: 'src', values: [4, 6, 3, 8, 5] },
			{ key: 'avg',   name: 'Avg',   type: 'line', values: [10, 14, 6, 19, 12] },
		],
		legend: true,
		height: 240,
	});

	// Continuous time-axis demos (14 days of daily samples)
	const days = [];
	const base = new Date(); base.setHours(0, 0, 0, 0); base.setTime(base.getTime() - 13 * 86400000);
	for(let i = 0; i < 14; i++) days.push(new Date(base.getTime() + i * 86400000));
	const tms = days.map(d => d.getTime());
	const rand = (n) => Math.round(5 + Math.random() * n);
	const web = days.map(() => rand(20)), email = days.map(() => rand(12));
	const xfmt = CerbUI.date.strftime('%b %e');

	const tsLine = document.getElementById('uiref-ts-line');
	if(tsLine) new CerbUI.CartesianChart(tsLine, {
		x: { scale: 'time', timestamps: tms, tickFormat: xfmt, rotate: -60 },
		y: { grid: true },
		series: [ { key: 'web', name: 'Web', type: 'line', values: web } ],
		points: true, height: 240,
	});

	const tsArea = document.getElementById('uiref-ts-area');
	if(tsArea) new CerbUI.CartesianChart(tsArea, {
		x: { scale: 'time', timestamps: tms, tickFormat: xfmt, rotate: -60 },
		y: { grid: true },
		series: [
			{ key: 'web',   name: 'Web',   type: 'area', stack: 'g', values: web },
			{ key: 'email', name: 'Email', type: 'area', stack: 'g', values: email },
		],
		legend: true, height: 240,
	});

	const tsSpline = document.getElementById('uiref-ts-spline');
	if(tsSpline) new CerbUI.CartesianChart(tsSpline, {
		x: { scale: 'time', timestamps: tms, tickFormat: xfmt, rotate: -60 },
		y: { grid: true },
		series: [ { key: 'email', name: 'Email', type: 'spline', values: email } ],
		points: true, height: 240,
	});

	const y2 = document.getElementById('uiref-y2');
	if(y2) new CerbUI.CartesianChart(y2, {
		x: { categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'] },
		y: { label: 'Volume', tickFormat: CerbUI.num.format(','), grid: true },
		y2: { label: 'Rate (%)', tickFormat: CerbUI.num.format('.0%') },
		series: [
			{ key: 'volume', name: 'Volume', type: 'bar', values: [1200, 1900, 700, 2200, 1500] },
			{ key: 'rate', name: 'Rate', type: 'line', axis: 'y2', values: [0.62, 0.55, 0.71, 0.48, 0.66] },
		],
		legend: true, height: 260,
	});
})();
</script>
