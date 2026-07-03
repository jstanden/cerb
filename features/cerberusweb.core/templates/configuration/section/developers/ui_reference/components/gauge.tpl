	<div class="cerb-uiref-component" id="gauge">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-gauge"></span>Gauge</div>

		{* Single-value radial gauge: value across [min,max], threshold colors, formatted center *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Single value across a range; thresholds recolor the fill</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex;gap:2em;flex-wrap:wrap;align-items:center;">
					<div class="cerb-ui-gauge" id="uiref-gauge-a" style="width:170px;"></div>
					<div class="cerb-ui-gauge" id="uiref-gauge-b" style="width:170px;"></div>
					<div class="cerb-ui-gauge" id="uiref-gauge-c" style="width:170px;"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const g = new CerbUI.Gauge(el, {
	value: 72, min: 0, max: 100,
	label: 'CPU', valueText: '72%',
	thresholds: [ { value: 60, color: '#e0a800' }, { value: 80, color: '#d62728' } ], // recolor at/above
	// color: '#2ca02c', arc: 270, thickness: 14, size: 170, format: ',',
});
g.setValue(88);   // update (no animation){/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.Gauge))
		return;

	const thresholds = [ { value: 60, color: '#e0a800' }, { value: 80, color: '#d62728' } ];

	const a = document.getElementById('uiref-gauge-a');
	if(a) new CerbUI.Gauge(a, { value: 42, min: 0, max: 100, label: 'CPU', valueText: '42%', thresholds: thresholds, height: 150 });

	const b = document.getElementById('uiref-gauge-b');
	if(b) new CerbUI.Gauge(b, { value: 74, min: 0, max: 100, label: 'Memory', valueText: '74%', thresholds: thresholds, height: 150 });

	const c = document.getElementById('uiref-gauge-c');
	if(c) new CerbUI.Gauge(c, { value: 91, min: 0, max: 100, label: 'Disk', valueText: '91%', thresholds: thresholds, arc: 180, height: 150 });
})();
</script>
