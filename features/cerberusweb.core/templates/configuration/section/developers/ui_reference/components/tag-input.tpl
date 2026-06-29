	<div class="cerb-uiref-component" id="tag-input">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-tag"></span>TagInput</div>

		{* The freeform sibling of ValuePicker: no presets, no dropdown — you type arbitrary text and press
		   Enter to make a removable chip. Ideal for "List" fields whose values are short words/tags but
		   that today render as a tall stack of full-width text inputs. Selections post as hidden name[]
		   inputs (entry order), so it drops into the same save handlers. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Freeform &mdash; type text and press Enter to add a chip; double-click a chip to edit it inline (Enter/blur commits, Esc cancels); &times; or Backspace (on an empty input) removes; blur commits a pending word; duplicates are skipped</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-tag-input" id="uiref-taginput-freeform" data-name="keywords" data-placeholder="Add a keyword&hellip;"></div>
				<div class="cerb-uiref-result">Posts <code>keywords[]</code>: <b id="uiref-taginput-freeform-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- An empty container; data-name sets the posted field name --&gt;
&lt;div class="cerb-ui-tag-input" data-name="keywords" data-placeholder="Add a keyword&hellip;"&gt;&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const ti = new CerbUI.TagInput(el, {
	// name: 'keywords',                // optional; else data-name / the seed inputs' name (sans [])
	placeholder: 'Add a keyword&hellip;',
	// value: ['alpha','beta'],         // optional; else read from the seed text inputs
	// separators: ['comma'],           // also commit on ',' (default: Enter only)
	// allowDuplicates: false,          // default false (skip an exact-duplicate tag)
	// editable: true,                  // default true; double-click a chip to rename it (Esc cancels). data-editable="false" opts out
	onChange: function(values) { /* ti.getValue() returns the array */ },
});

// API: ti.getValue(); ti.setValue(['alpha']); ti.addTag('gamma'); ti.clear(); ti.destroy(); CerbUI.TagInput.from(el).
// Posts hidden inputs name="keywords[]" per tag, in entry order.</pre>
			</div>
		</div>

		{* Resume from existing values *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Resume &mdash; seed the field with existing values as child text inputs (the legacy repeating-input shape, which also serves as a no-JS fallback); they load as chips, and (like any chip) double-click to edit a defaulted value in place</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-tag-input" id="uiref-taginput-resume" data-name="ingredients">
					<input type="text" name="ingredients[]" value="basil">
					<input type="text" name="ingredients[]" value="garlic">
					<input type="text" name="ingredients[]" value="tomato">
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-tag-input" data-name="ingredients"&gt;
	&lt;input type="text" name="ingredients[]" value="basil"&gt;
	&lt;input type="text" name="ingredients[]" value="garlic"&gt;
	&lt;input type="text" name="ingredients[]" value="tomato"&gt;
&lt;/div&gt;</pre>
			</div>
		</div>

		{* Comma separator + paste-split *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Comma + paste &mdash; <code>data-separators="comma"</code> also commits on a comma; pasting a comma/newline list adds several chips at once (paste always splits on newlines)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-tag-input" id="uiref-taginput-comma" data-name="aliases" data-separators="comma" data-placeholder="Type or paste a comma list&hellip;"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-tag-input" data-name="aliases" data-separators="comma"&gt;&lt;/div&gt;
new CerbUI.TagInput(el, { separators:['comma'] });  // paste "a, b, c" → three chips</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.TagInput))
		return;

	const elFree = document.getElementById('uiref-taginput-freeform');
	const out = document.getElementById('uiref-taginput-freeform-out');
	if(elFree) {
		const ti = new CerbUI.TagInput(elFree, {
			onChange: function(values) { if(out) out.textContent = values.join(', ') || '—'; }
		});
		if(out) out.textContent = ti.getValue().join(', ') || '—';
	}

	const elResume = document.getElementById('uiref-taginput-resume');
	if(elResume) new CerbUI.TagInput(elResume);

	const elComma = document.getElementById('uiref-taginput-comma');
	if(elComma) new CerbUI.TagInput(elComma, { separators: ['comma'] });
})();
</script>
