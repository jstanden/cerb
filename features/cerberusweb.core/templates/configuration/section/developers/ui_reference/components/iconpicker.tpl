	<div class="cerb-uiref-component" id="iconpicker">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-sparkles"></span>IconPicker</div>

		{* A small icon "well" (like ColorPicker) bound to a text input: the button shows the chosen cerb-icon;
		   clicking opens a floating panel with a filterable grid of every icon + name. The input holds the
		   icon name so forms submit it. The icon list is fetched once from c=ui&a=iconsJson and cached. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Icon well + filterable popup grid &mdash; pick a <a href="#icon">cerb-icon</a> by name. The hidden input holds the chosen name; reusable in toolbars, forms, the image editor, etc.</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-3">
					<input type="text" id="uiref-iconpicker" name="uiref_icon" value="rocket">
					<span class="cerb-uiref-result">Selected: <b id="uiref-iconpicker-result">rocket</b></span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;input type="text" name="icon" value="rocket"&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const ip = new CerbUI.IconPicker(el, {
	value:     'rocket',     // initial icon name (else read from the input's value)
	emptyIcon: 'picture',    // placeholder glyph when there's no value
	allowClear: true,        // show a "Clear" button in the panel
	onChange:  function(name) { /* name = the chosen cerb-icon, or '' when cleared */ },
});

// API: ip.getValue(); ip.setValue(name); ip.open(); ip.close(); ip.destroy();
// The input fires input + change (and a cerb-ui-iconpicker:change CustomEvent) on every pick.
// Icon names come from c=ui&a=iconsJson (CerbUI.IconPicker.loadIcons(), cached across instances).</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const el = document.getElementById('uiref-iconpicker');
	const out = document.getElementById('uiref-iconpicker-result');
	if(el && window.CerbUI && CerbUI.IconPicker) {
		new CerbUI.IconPicker(el, {
			emptyIcon: 'picture',
			onChange: function(name) { if(out) out.textContent = name || '(none)'; },
		});
	}
})();
</script>
