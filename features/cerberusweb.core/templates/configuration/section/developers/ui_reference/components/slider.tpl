<div class="cerb-uiref-component" id="slider">
	<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-slider"></span>Slider</div>

	<div class="cerb-uiref-example">
		<div class="cerb-uiref-demo" style="flex-direction:column;align-items:stretch;gap:1.5em;">
			{* Delta slider: green below the midpoint, gray at it, red above it *}
			<div>
				<div class="cerb-ui-slider" id="uiref-slider-delta" style="max-width:320px;">
					<input type="hidden" value="35">
				</div>
				<span class="cerb-uiref-result">Importance (delta, midpoint 50): <b id="uiref-slider-delta-val">35</b></span>
			</div>

			{* Plain accent slider (no midpoint) *}
			<div>
				<div class="cerb-ui-slider" id="uiref-slider-plain" style="max-width:320px;">
					<input type="hidden" value="60">
				</div>
				<span class="cerb-uiref-result">Value (0–100): <b id="uiref-slider-plain-val">60</b></span>
			</div>
		</div>

		<div class="cerb-uiref-code">
			<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
			<pre data-cerb-uiref-source>&lt;div class="cerb-ui-slider"&gt;
	&lt;input type="hidden" name="importance" value="50"&gt;
&lt;/div&gt;</pre>
		</div>

		<div class="cerb-uiref-code">
			<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
			<pre data-cerb-uiref-source>new CerbUI.Slider(el, {
	min: 0, max: 100, step: 1,
	midpoint: 50,   // optional: delta coloring (green &lt; mid, gray = mid, red &gt; mid) + center tick
	// invert: true, // swap low/high colors (below = red, above = green)
	onInput:  function(value) { /* live, while dragging */ },
	onChange: function(value) { /* committed */ },
});</pre>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Live demo of the Slider component
	if(!(window.CerbUI && CerbUI.Slider)) return;

	const deltaEl = document.getElementById('uiref-slider-delta');
	const deltaVal = document.getElementById('uiref-slider-delta-val');
	if(deltaEl) {
		new CerbUI.Slider(deltaEl, {
			min: 0, max: 100, step: 1, midpoint: 50,
			onInput: function(v) { if(deltaVal) deltaVal.textContent = v; },
			onChange: function(v) { if(deltaVal) deltaVal.textContent = v; }
		});
	}

	const plainEl = document.getElementById('uiref-slider-plain');
	const plainVal = document.getElementById('uiref-slider-plain-val');
	if(plainEl) {
		new CerbUI.Slider(plainEl, {
			min: 0, max: 100, step: 1,
			onInput: function(v) { if(plainVal) plainVal.textContent = v; },
			onChange: function(v) { if(plainVal) plainVal.textContent = v; }
		});
	}
})();
</script>
