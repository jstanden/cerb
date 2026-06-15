	<div class="cerb-uiref-component" id="color-scale">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-paintbrush"></span>Color scale</div>

		{* The palettes + ordinal scale the charts/legends color from. Listed before Legend, since Legend uses it. *}
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-header--label">Palettes (colored by index)</div>
				<div class="cerb-uiref-mutelabel">category10</div>
				<div id="uiref-palette-category10" class="cerb-uiref-swatches"></div>
				<div class="cerb-uiref-mutelabel" style="margin-top:0.5em;">rainbow</div>
				<div id="uiref-palette-rainbow" class="cerb-uiref-swatches"></div>

				<div class="cerb-ui-header--label" style="margin-top:1em;"><code>colorScale()</code> &mdash; assigns the next color on first sight of a key, then memoizes it</div>
				<div id="uiref-colorscale" class="cerb-uiref-swatches"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Built-in palettes — arrays of hex, colored by index
CerbUI.palettes.category10;   // 10 colors (D3 category10)
CerbUI.palettes.rainbow;      // 12 colors

// An ordinal scale: color(key) assigns the next palette color on first sight of a key, then memoizes it.
// Share ONE scale across charts + legends so the same key is the same color everywhere.
const scale = CerbUI.colorScale();   // or CerbUI.colorScale('rainbow')
scale.color('invocations');    // -> palette[0]
scale.color('avg duration');   // -> palette[1]
scale.color('invocations');    // -> palette[0] again (memoized){/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Color scale: paint the built-in palettes as swatches + a live colorScale() keyed demo
	if(window.CerbUI && CerbUI.palettes) {
		const paintPalette = function(id, colors) {
			const host = document.getElementById(id);
			if(!host) return;
			colors.forEach(function(c) {
				const sw = document.createElement('span');
				sw.className = 'cerb-uiref-swatch';
				sw.style.backgroundColor = c;
				sw.title = c;
				host.appendChild(sw);
			});
		};
		paintPalette('uiref-palette-category10', CerbUI.palettes.category10);
		paintPalette('uiref-palette-rainbow', CerbUI.palettes.rainbow);

		const scaleHost = document.getElementById('uiref-colorscale');
		if(scaleHost && CerbUI.colorScale) {
			const scale = CerbUI.colorScale();
			// note "invocations" repeats → it memoizes to the same color (not a new one)
			['invocations', 'avg duration', 'invocations', 'cadence'].forEach(function(key) {
				const color = scale.color(key);
				const chip = document.createElement('span');
				chip.className = 'cerb-uiref-key';
				const dot = document.createElement('i');
				dot.style.backgroundColor = color;
				const label = document.createElement('span');
				label.textContent = key + ' → ' + color;
				chip.append(dot, label);
				scaleHost.appendChild(chip);
			});
		}
	}
})();
</script>
