	<div class="cerb-uiref-component" id="distribution-bar">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chart-bar"></span>Distribution bar</div>

		{* Example: a bar with a metric toggle + an auto-generated matching legend *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Bar with a metric toggle and matching legend</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-panel">
					<div class="cerb-ui-header">
						<div class="cerb-ui-header--label">Distribution</div>
						<div class="cerb-ui-header--right">
							<div class="cerb-ui-switcher" id="uiref-dist-toggle">
								<button type="button" class="cerb-ui-switcher--active" data-value="objects">Objects</button>
								<button type="button" data-value="size">Size</button>
							</div>
						</div>
					</div>

					<div class="cerb-ui-distbar" id="uiref-distbar">
						<span data-label="Disk" data-value-objects="1234" data-value-size="2254857830" data-text-size="2.1 GB"></span>
						<span data-label="Database" data-value-objects="560" data-value-size="104857600" data-text-size="100 MB"></span>
						<span data-label="Amazon S3" data-value-objects="89" data-value-size="5368709120" data-text-size="5.0 GB"></span>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-distbar"&gt;
	&lt;span data-label="Disk" data-value-objects="1234" data-value-size="2254857830" data-text-size="2.1 GB"&gt;&lt;/span&gt;
	&lt;span data-label="Database" data-value-objects="560" data-value-size="104857600" data-text-size="100 MB"&gt;&lt;/span&gt;
	&lt;span data-label="Amazon S3" data-value-objects="89" data-value-size="5368709120" data-text-size="5.0 GB"&gt;&lt;/span&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const bar = new CerbUI.Distbar(el, {
	key: 'objects',          // which data-value-* metric to size by (default: data-value)
	legend: true,            // also build a matching CerbUI.Legend below it (default: false)
	// hideZeros: true,       // also hide zero-valued items from the legend (default: false)
	// palette: 'category10', // colors segments by index (default category10)
	// scale: sharedScale,    // a CerbUI.colorScale() to color by key across charts (default: none)
});

// a Toggle can re-key the bar (which forwards to its legend)
new CerbUI.Switcher(toggleEl, { onSelect: function(value) { bar.setKey(value); } });</pre>
			</div>
		</div>

		{* Example: two bars sharing one color scale — the same key keeps the same color across charts *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Two bars sharing one color scale (consistent colors + legends)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-distbar" id="uiref-distbar-a">
					<span data-label="Disk" data-value="1234"></span>
					<span data-label="Database" data-value="560"></span>
					<span data-label="Amazon S3" data-value="89"></span>
				</div>
				<br>
				<div class="cerb-ui-distbar" id="uiref-distbar-b">
					<span data-label="Amazon S3" data-value="89"></span>
					<span data-label="Database" data-value="560"></span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- segments keyed by data-color-key, falling back to data-label --&gt;
&lt;div class="cerb-ui-distbar" id="bar-a"&gt;
	&lt;span data-label="Disk" data-value="1234"&gt;&lt;/span&gt;
	&lt;span data-label="Database" data-value="560"&gt;&lt;/span&gt;
	&lt;span data-label="Amazon S3" data-value="89"&gt;&lt;/span&gt;
&lt;/div&gt;
&lt;div class="cerb-ui-distbar" id="bar-b"&gt;
	&lt;span data-label="Amazon S3" data-value="89"&gt;&lt;/span&gt;
	&lt;span data-label="Database" data-value="560"&gt;&lt;/span&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// Share one scale across bars/legends -&gt; same key, same color everywhere
const scale = CerbUI.colorScale();
new CerbUI.Distbar(document.getElementById('bar-a'), { scale, legend: true });
new CerbUI.Distbar(document.getElementById('bar-b'), { scale, legend: true });</pre>
			</div>
		</div>

		{* Example: zero-valued segments are hidden in the bar but still listed in the legend *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Zero-valued segments (hidden in the bar; hideZeros hides them from the legend too)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-distbar" id="uiref-distbar-zeros">
					<span data-label="Done" data-value="10"></span>
					<span data-label="Error" data-value="0"></span>
					<span data-label="In Progress" data-value="0"></span>
					<span data-label="Retrying" data-value="0"></span>
					<span data-label="Available" data-value="0"></span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-distbar"&gt;
	&lt;span data-label="Done" data-value="10"&gt;&lt;/span&gt;
	&lt;span data-label="Error" data-value="0"&gt;&lt;/span&gt;
	&lt;span data-label="In Progress" data-value="0"&gt;&lt;/span&gt;
	&lt;span data-label="Retrying" data-value="0"&gt;&lt;/span&gt;
	&lt;span data-label="Available" data-value="0"&gt;&lt;/span&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// Zero-valued segments never render slivers in the bar; hideZeros also hides
// them from the legend (default: false, shown with their 0 counts)
new CerbUI.Distbar(el, { legend: true, hideZeros: true });</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Distbar with legend:true (auto-generates a matching legend); a Toggle drives both via setKey
	const distbarEl = document.getElementById('uiref-distbar');
	const distToggleEl = document.getElementById('uiref-dist-toggle');
	if(distbarEl && window.CerbUI && CerbUI.Distbar) {
		const bar = new CerbUI.Distbar(distbarEl, { key: 'objects', legend: true });
		if(distToggleEl && CerbUI.Switcher) {
			new CerbUI.Switcher(distToggleEl, {
				onSelect: function(value) { bar.setKey(value); }
			});
		}
	}

	// Zero-valued segments: hidden in the bar (no sliver/gap); hideZeros hides them from the legend too
	const distbarZerosEl = document.getElementById('uiref-distbar-zeros');
	if(distbarZerosEl && window.CerbUI && CerbUI.Distbar) {
		new CerbUI.Distbar(distbarZerosEl, { legend: true, hideZeros: true });
	}

	// Two distbars sharing one color scale — the same key keeps its color across both
	if(window.CerbUI && CerbUI.Distbar && CerbUI.colorScale) {
		const sharedScale = CerbUI.colorScale();
		['uiref-distbar-a', 'uiref-distbar-b'].forEach(function(id) {
			const el = document.getElementById(id);
			if(el) new CerbUI.Distbar(el, { scale: sharedScale, legend: true });
		});
	}
})();
</script>
