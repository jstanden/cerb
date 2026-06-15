	<div class="cerb-uiref-component" id="colorpicker">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-color-palette"></span>ColorPicker</div>

		{* Example: default — a color well (swatch + hex input) opening a Photoshop-style panel; rainbow palette *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Color well &mdash; click the swatch or focus the input to open; drag the SV square / hue strip, or pick a palette swatch; type a hex (<code>#rgb</code>/<code>#rrggbb</code>, <code>#</code> optional), <code>Esc</code> closes</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-colorpicker-basic" value="#6a87db" size="10">
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Value: <b id="uiref-colorpicker-basic-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;input type="text" name="color" value="#6a87db"&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const picker = new CerbUI.ColorPicker(el, {
	// palette: 'rainbow',   // swatch row: a CerbUI.palettes name or an array of hex (default 'rainbow')
	// alpha:   false,       // show an opacity strip + emit #rrggbbaa / rgba() (default false)
	onChange: function(hex, rgba, input) { /* hex = '#rrggbb', rgba = 'rgba(r, g, b, a)' */ },
	// onOpen:  function() { },
	// onClose: function() { },
});

// also fires a DOM event on the input:
el.addEventListener('cerb-ui-colorpicker:change', e =&gt; console.log(e.detail.hex, e.detail.rgba));

// public API: picker.getValue() -&gt; '#rrggbb'; picker.getRgba() -&gt; 'rgba(...)';
//             picker.setValue('#ff8800'); picker.open(); picker.close(); picker.destroy();</pre>
			</div>
		</div>

		{* Example: opacity enabled + a different named palette *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Opacity strip + <code>category10</code> palette (<code>alpha:true</code>) &mdash; the swatch shows transparency over a checkerboard</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-colorpicker-alpha" value="#2ca02c" size="12">
				<span class="cerb-uiref-result" style="margin-left:0.7em;">rgba: <b id="uiref-colorpicker-alpha-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.ColorPicker(el, {
	palette: 'category10',
	alpha:   true,   // getValue() now returns #rrggbbaa when opacity &lt; 100%
});</pre>
			</div>
		</div>

		{* Example: swatch-only — hide the hex field, keep the input as the (hidden) value holder *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Swatch only (<code>showInput:false</code>) &mdash; just the swatch chip; the <code>&lt;input&gt;</code> stays hidden in the DOM so it still seeds the color and posts the value, and the popup grows its own hex field for copy/paste</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-colorpicker-swatchonly" value="#e377c2" size="10">
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Posted value: <b id="uiref-colorpicker-swatchonly-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.ColorPicker(el, {
	showInput: false,   // render only the swatch; the hidden &lt;input&gt; still holds/posts the value
});</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// ColorPicker: default rainbow palette, reporting the hex value
	(function() {
		const el = document.getElementById('uiref-colorpicker-basic');
		const out = document.getElementById('uiref-colorpicker-basic-result');
		if(el && window.CerbUI && CerbUI.ColorPicker) {
			const picker = new CerbUI.ColorPicker(el, {
				onChange: function(hex) { if(out) out.textContent = hex; },
			});
			if(out) out.textContent = picker.getValue();
		}
	})();

	// ColorPicker: opacity strip + category10 palette, reporting the rgba string
	(function() {
		const el = document.getElementById('uiref-colorpicker-alpha');
		const out = document.getElementById('uiref-colorpicker-alpha-result');
		if(el && window.CerbUI && CerbUI.ColorPicker) {
			const picker = new CerbUI.ColorPicker(el, {
				palette: 'category10',
				alpha: true,
				onChange: function(hex, rgba) { if(out) out.textContent = rgba; },
			});
			if(out) out.textContent = picker.getRgba();
		}
	})();

	// ColorPicker: swatch-only (hex field hidden), reporting the still-posted input value
	(function() {
		const el = document.getElementById('uiref-colorpicker-swatchonly');
		const out = document.getElementById('uiref-colorpicker-swatchonly-result');
		if(el && window.CerbUI && CerbUI.ColorPicker) {
			const picker = new CerbUI.ColorPicker(el, {
				showInput: false,
				onChange: function(hex) { if(out) out.textContent = hex; },
			});
			if(out) out.textContent = picker.getValue();
		}
	})();
})();
</script>
