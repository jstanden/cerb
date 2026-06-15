	<div class="cerb-uiref-component" id="legend">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-key"></span>Legend</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-legend" id="uiref-legend">
					<div data-label="Disk" data-value="1234"></div>
					<div data-label="Database" data-value="560"></div>
					<div data-label="Amazon S3" data-value="89"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- each child div carries only data; JS builds the swatch / label / value / % --&gt;
&lt;div class="cerb-ui-legend"&gt;
	&lt;div data-label="Disk" data-value="1234"&gt;&lt;/div&gt;
	&lt;div data-label="Database" data-value="560"&gt;&lt;/div&gt;
	&lt;div data-label="Amazon S3" data-value="89"&gt;&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.Legend(el, {
	// key: 'objects',        // which data-value-* metric to show (default: data-value)
	// palette: 'category10', // swatch colors by item index (default category10)
	// scale: sharedScale,    // a CerbUI.colorScale() to color by key across charts (default: none)
	// percent: true,         // show each item's % of the sum (default true; false = value only)
	// hideZeros: true,       // hide zero-valued items per the current key (default: false)
});</pre>
			</div>
		</div>

		{* Example: a key, not a distribution — line/bar swatches, no values (matches a Sparkchart's series) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Chart key: <code>data-type="bar|line"</code> swatches, no values (<code>percent:false</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-legend" id="uiref-legend-types">
					<div data-label="invocations" data-type="bar"></div>
					<div data-label="avg duration" data-type="line"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-legend"&gt;
	&lt;div data-label="invocations" data-type="bar"&gt;&lt;/div&gt;
	&lt;div data-label="avg duration" data-type="line"&gt;&lt;/div&gt;
&lt;/div&gt;

&lt;script&gt;new CerbUI.Legend(el, { percent: false });&lt;/script&gt;</pre>
			</div>
		</div>

		{* Example: a vertical stats stack — label + value per row, no % *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Vertical stats stack (<code>--vertical</code>, value only)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-legend cerb-ui-legend--vertical" id="uiref-legend-stats">
					<div data-label="invocations" data-type="bar" data-value="842" data-text="842"></div>
					<div data-label="avg duration" data-type="line" data-value="172" data-text="172ms"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-legend cerb-ui-legend--vertical"&gt;
	&lt;div data-label="invocations" data-type="bar"  data-value="842" data-text="842"&gt;&lt;/div&gt;
	&lt;div data-label="avg duration" data-type="line" data-value="172" data-text="172ms"&gt;&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.Legend(el, { percent: false });</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Standalone Legend
	const legendEl = document.getElementById('uiref-legend');
	if(legendEl && window.CerbUI && CerbUI.Legend) {
		new CerbUI.Legend(legendEl);
	}

	// Legend: chart-key variant — line/bar swatches, no values (percent:false)
	const legendTypesEl = document.getElementById('uiref-legend-types');
	if(legendTypesEl && window.CerbUI && CerbUI.Legend) {
		new CerbUI.Legend(legendTypesEl, { percent: false });
	}

	// Legend: a standalone vertical stats stack (colors by index — invocations=0, avg=1)
	const legendStatsEl = document.getElementById('uiref-legend-stats');
	if(legendStatsEl && window.CerbUI && CerbUI.Legend) {
		new CerbUI.Legend(legendStatsEl, { percent: false });
	}
})();
</script>
