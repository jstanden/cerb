	<div class="cerb-uiref-component" id="sparkchart">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chart-line"></span>Sparkchart</div>

		{* Roomy (popups): bars + line over shared categories; built-in tooltip on hover *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Roomy (popups); hover a column for the built-in tooltip</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-sparkchart" id="uiref-sparkchart" style="max-width:340px;"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// categorical (not time-based); the backend pre-bins labels + preformats `text`
const chart = new CerbUI.Sparkchart(el, {
	categories: ['10:00', '11:00', /* … */ 'now'],  // x labels (tooltip + extents)
	series: [
		{ type:'bar',  label:'invocations',  values:[27, /* … */], text:['27 runs', /* … */] },
		{ type:'line', label:'avg duration', values:[172, /* … */], text:['172ms', /* … */] },
	],
	caption: ['24h ago', 'now'],  // extents below the plot: [start,end] at the ends, a single string centered, omit = none
	height: 56,                   // plot height, px
	barWidth: 0.6,                // bar width as a fraction of the category band (gap = the rest)
	// ticks: true,               // one tick per category below the bars (default); false = a bare sparkline
	// tooltip: true,             // built-in hover tooltip (one shared instance); set false for events-only
	// palette: 'category10',     // series colored by index; or set series[].color
	// scale: sharedScale,        // a CerbUI.colorScale() to color series by label (matches a legend)
});{/literal}</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// or fully own the interaction: disable the built-in tooltip and subscribe to events
new CerbUI.Sparkchart(el, { tooltip: false, categories: [/* … */], series: [/* … */] });
// detail = index, category, point:{x,y}, series:[{color,text,label,value}]
el.addEventListener('cerb-ui-sparkchart:hover', e =&gt; renderMyTooltip(e.detail));
el.addEventListener('cerb-ui-sparkchart:click', e =&gt; openJob(e.detail));{/literal}</pre>
			</div>
		</div>

		{* Compact bare sparkline (worklists / panels): smaller, thinner bars, ticks + caption off *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Compact, bare sparkline (worklists / panels): <code>ticks:false</code>, no caption</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-sparkchart" id="uiref-sparkchart-compact" style="max-width:260px;"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.Sparkchart(el, { height: 40, barWidth: 0.5, ticks: false, categories: [/* … */], series: [/* … */] });{/literal}</pre>
			</div>
		</div>

		{* Chart + a Legend sharing one color scale — same series name -> same color in both *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Paired with a Legend via a shared <code>scale</code> (same series name &rarr; same color)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex;align-items:center;gap:1.5em;">
					<div class="cerb-ui-sparkchart" id="uiref-spark-legend-chart" style="flex:1 1 auto;"></div>
					<div class="cerb-ui-legend cerb-ui-legend--vertical" id="uiref-spark-legend-stats">
						<div data-label="invocations" data-type="bar" data-value="842" data-text="842"></div>
						<div data-label="avg duration" data-type="line" data-value="172" data-text="172ms"></div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// One shared scale: the chart and the legend color the same series identically (they aren't wired together)
const scale = CerbUI.colorScale();
new CerbUI.Sparkchart(chartEl, { scale, categories: [/* … */], series: [
	{ type: 'bar',  label: 'invocations',  values: [/* … */], text: [/* … */] },
	{ type: 'line', label: 'avg duration', values: [/* … */], text: [/* … */] },
]});
new CerbUI.Legend(statsEl, { scale, percent: false });{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Sparkchart: bars (invocations) + line (avg duration) over 24 sample categories; built-in tooltip on hover
	if(window.CerbUI && CerbUI.Sparkchart) {
		const cats = [], inv = [], invText = [], dur = [], durText = [];
		for(let h = 0; h < 24; h++) {
			cats.push((h < 10 ? '0' + h : h) + ':00');
			const runs = 8 + Math.round(Math.random() * 24);
			inv.push(runs); invText.push(runs + ' runs');
			const ms = 60 + Math.round(Math.random() * 180);
			dur.push(ms); durText.push(ms + 'ms avg');
		}
		cats[cats.length - 1] = 'now';
		const data = {
			categories: cats,
			series: [
				{ type: 'bar',  label: 'invocations',  values: inv, text: invText },
				{ type: 'line', label: 'avg duration', values: dur, text: durText },
			],
		};

		const roomy = document.getElementById('uiref-sparkchart');
		if(roomy) new CerbUI.Sparkchart(roomy, Object.assign({ caption: ['24h ago', 'now'] }, data));

		const compact = document.getElementById('uiref-sparkchart-compact');
		if(compact) new CerbUI.Sparkchart(compact, Object.assign({ height: 40, barWidth: 0.5, ticks: false }, data));
	}

	// Sparkchart paired with a Legend via one shared scale — same series name colors identically in both
	if(window.CerbUI && CerbUI.Legend && CerbUI.colorScale) {
		const statScale = CerbUI.colorScale();
		const sparkEl = document.getElementById('uiref-spark-legend-chart');
		if(sparkEl && CerbUI.Sparkchart) {
			const cats = [], inv = [], dur = [];
			for(let h = 0; h < 24; h++) {
				cats.push((h < 10 ? '0' + h : h) + ':00');
				inv.push(8 + Math.round(Math.random() * 24));
				dur.push(60 + Math.round(Math.random() * 180));
			}
			cats[cats.length - 1] = 'now';
			new CerbUI.Sparkchart(sparkEl, {
				scale: statScale,
				categories: cats, height: 46, barWidth: 0.55,
				series: [
					{ type: 'bar',  label: 'invocations',  values: inv },
					{ type: 'line', label: 'avg duration', values: dur },
				],
			});
		}
		const statsEl = document.getElementById('uiref-spark-legend-stats');
		if(statsEl) new CerbUI.Legend(statsEl, { scale: statScale, percent: false });
	}
})();
</script>
