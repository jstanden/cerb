	<div class="cerb-uiref-component" id="value-picker">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-checked"></span>ValuePicker</div>

		{* A local *Picker* (small fixed option set) vs a server *Chooser*. It enhances a set of checkboxes
		   into a RecordChooser-style tag field — ideal for "Multiple Checkbox" fields with dozens of
		   options that would otherwise scroll the page. Selections post as hidden inputs with the same
		   name, so it's drop-in. The dropdown shows every option (filtered by typing) and DIMS the picked
		   ones (with a check) so you can toggle them off too. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Multiple &mdash; enhances a checkbox set into a tag field; focus opens the menu, type to filter, click to toggle (picked rows dim + check); tags render in the options' defined order</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-value-picker" id="uiref-valuepicker-multi">
					<label><input type="checkbox" name="ingredients[]" value="anchovy"> Anchovy</label>
					<label><input type="checkbox" name="ingredients[]" value="basil" checked> Basil</label>
					<label><input type="checkbox" name="ingredients[]" value="chili"> Chili</label>
					<label><input type="checkbox" name="ingredients[]" value="garlic" checked> Garlic</label>
					<label><input type="checkbox" name="ingredients[]" value="mozzarella"> Mozzarella</label>
					<label><input type="checkbox" name="ingredients[]" value="mushroom"> Mushroom</label>
					<label><input type="checkbox" name="ingredients[]" value="olive"> Olive</label>
					<label><input type="checkbox" name="ingredients[]" value="onion"> Onion</label>
					<label><input type="checkbox" name="ingredients[]" value="pepper"> Pepper</label>
					<label><input type="checkbox" name="ingredients[]" value="tomato"> Tomato</label>
				</div>
				<div class="cerb-uiref-result">Posts <code>ingredients[]</code>: <b id="uiref-valuepicker-multi-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Enhance a checkbox set (the multi-checkbox shape). Option order = display order; `checked` resumes. --&gt;
&lt;div class="cerb-ui-value-picker"&gt;
	&lt;label&gt;&lt;input type="checkbox" name="ingredients[]" value="basil" checked&gt; Basil&lt;/label&gt;
	&lt;label&gt;&lt;input type="checkbox" name="ingredients[]" value="garlic" checked&gt; Garlic&lt;/label&gt;
	&lt;label&gt;&lt;input type="checkbox" name="ingredients[]" value="tomato"&gt; Tomato&lt;/label&gt;
	&lt;!-- … --&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const vp = new CerbUI.ValuePicker(el, {
	// multiple: true,                   // default; false = single (dropdown closes on pick)
	// name: 'ingredients',              // optional; else read from the checkboxes' name (sans []) / data-name
	searchPlaceholder: 'Filter…',
	// value: ['basil','garlic'],        // optional; else the `checked` source checkboxes seed the selection
	onSelect: function(value, picked) { /* fired per toggle; vp.getValue() returns the array */ },
});

// API: vp.getValue(); vp.setValue(['basil']); vp.clear(); vp.destroy(); CerbUI.ValuePicker.from(el).
// Posts hidden inputs name="ingredients[]" per selection (single mode → one name="ingredients").</pre>
			</div>
		</div>

		{* With icons/avatars *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">With icons &mdash; options can carry <code>data-icon</code> (+ <code>data-color</code>) or <code>data-image</code> to show a leading glyph/avatar in the tiles and rows</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-value-picker" id="uiref-valuepicker-icons">
					<label data-icon="circle-exclamation-mark" data-color="red"><input type="checkbox" name="labels[]" value="urgent" checked> Urgent</label>
					<label data-icon="star" data-color="orange"><input type="checkbox" name="labels[]" value="starred"> Starred</label>
					<label data-icon="clock" data-color="blue"><input type="checkbox" name="labels[]" value="waiting"> Waiting</label>
					<label data-icon="checked" data-color="green"><input type="checkbox" name="labels[]" value="done"> Done</label>
					<label data-icon="bug" data-color="purple"><input type="checkbox" name="labels[]" value="bug"> Bug</label>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;label data-icon="star" data-color="orange"&gt;&lt;input type="checkbox" name="labels[]" value="starred"&gt; Starred&lt;/label&gt;
&lt;!-- or an avatar: data-image="…&amp;c=avatars&amp;context=worker&amp;context_id=5" --&gt;</pre>
			</div>
		</div>

		{* Single mode *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Single &mdash; one selection; the dropdown closes on pick and a new pick replaces the value</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-value-picker" id="uiref-valuepicker-single" data-single="1">
					<label><input type="checkbox" name="size" value="s"> Small</label>
					<label><input type="checkbox" name="size" value="m" checked> Medium</label>
					<label><input type="checkbox" name="size" value="l"> Large</label>
					<label><input type="checkbox" name="size" value="xl"> Extra large</label>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-value-picker" data-single="1"&gt; … &lt;/div&gt;
new CerbUI.ValuePicker(el, { multiple:false });  // posts one hidden name="size"</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.ValuePicker))
		return;

	const elMulti = document.getElementById('uiref-valuepicker-multi');
	const out = document.getElementById('uiref-valuepicker-multi-out');
	if(elMulti) {
		const vp = new CerbUI.ValuePicker(elMulti, {
			searchPlaceholder: 'Filter…',
			onSelect: function() { if(out) out.textContent = vp.getValue().join(', ') || '—'; }
		});
		if(out) out.textContent = vp.getValue().join(', ') || '—';
	}

	const elIcons = document.getElementById('uiref-valuepicker-icons');
	if(elIcons) new CerbUI.ValuePicker(elIcons, { searchPlaceholder: 'Filter labels…' });

	const elSingle = document.getElementById('uiref-valuepicker-single');
	if(elSingle) new CerbUI.ValuePicker(elSingle, { searchPlaceholder: 'Choose a size…' });
})();
</script>
