	<div class="cerb-uiref-component" id="text-chooser">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-search"></span>TextChooser</div>

		{* The freeform sibling of RecordChooser/ContextChooser: a plain text input with optional endpoint-backed
		   suggestions. Accepting a suggestion just writes text into the field (no tile/chip), and typing anything
		   is always allowed — suggestions are hints, not a forced match (unlike Menu/SelectMenu). The input keeps
		   its own name/value, so the form still posts whatever the user typed. Replaces the jQuery-UI
		   .autocomplete() on plain inputs (country, org, subroutine, …). *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Static source &mdash; an array of strings; type to filter, click or Enter to write the text. Freeform: a value with no match is kept as typed</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-textchooser-basic" name="fruit" placeholder="Type a fruit&hellip;" style="width:280px;">
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;input type="text" name="fruit" placeholder="Type a fruit&hellip;"&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const tc = new CerbUI.TextChooser(el, {
	source: ['Apple','Apricot','Banana','Cherry','Date','Fig','Grape'],
	// minLength: 0,                            // default; also suggests on empty focus (endpoint can recommend)
	// onSelect: function(item, input) { ... }, // override how a pick is applied (default writes value ?? label)
});

// API: tc.getValue(); tc.setValue('Kiwi'); tc.destroy(); CerbUI.TextChooser.from(el).</pre>
			</div>
		</div>

		{* Leading icon hint + server source *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Icon hint + endpoint &mdash; <code>icon</code> floats a glyph in the field; <code>source</code> can be bare ajax args (fetched via <code>genericAjaxGet</code> with <code>&amp;term=&hellip;</code>) returning either a <b>plain string array</b> (like this country list) or <code>[{literal}{label, value}{/literal}]</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-textchooser-icon" name="country" placeholder="Country&hellip;" style="width:280px;">
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.TextChooser(el, {
	icon: 'globe',
	source: 'c=profiles&amp;a=invoke&amp;module=org&amp;action=autocompleteCountry',
});

// We append &amp;term=&lt;what the user typed&gt; and GET it via genericAjaxGet:
//   REQUEST   ajax.php?c=profiles&amp;a=invoke&amp;module=org&amp;action=autocompleteCountry&amp;term=alba
//   RESPONSE  a JSON array of strings  ["Albania", "Alban (region)", &hellip;]
//        or   of objects  [{ "label":"Albania", "value":"AL", "sublabel":"Europe", "icon":"globe" }, &hellip;]
// (label = shown + written on select; value overrides the written text; sublabel = muted 2nd line;
//  icon = a cerb-icons NAME, not a URL; a meta map is joined with " &middot; " into the sublabel.)
// The endpoint filters by term server-side and caps its own row count.</pre>
			</div>
		</div>

		{* Function source with sublabel meta *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Function source &mdash; <code>source</code> may be a function returning items (or a Promise); an item may carry a <code>sublabel</code> (muted second line) and an <code>icon</code> (cerb-icons name)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-textchooser-fn" name="timezone" placeholder="Timezone&hellip;" style="width:280px;">
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.TextChooser(el, {
	icon: 'clock',
	source: function(term) {
		return [
			{literal}{ label:'America/New York', value:'America/New_York', sublabel:'UTC-5' },{/literal}
			{literal}{ label:'Europe/London', value:'Europe/London', sublabel:'UTC+0' },{/literal}
		].filter(function(it) { return it.label.toLowerCase().indexOf(term.toLowerCase()) !== -1; });
	},
	onSelect: function(item, input) { input.value = item.value; },  // override default (writes value ?? label)
});</pre>
			</div>
		</div>

		{* Avatar thumbnails *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Avatars &mdash; <code>avatars:true</code> + <code>context</code> shows a lazy-loaded thumbnail per row (instant monogram fallback, real image only for visible rows). A record-autocomplete endpoint returns the avatar URL in <code>icon</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-textchooser-avatars" name="worker" placeholder="Worker&hellip;" style="width:280px;">
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.TextChooser(el, {
	avatars: true,
	context: 'worker',  // monogram seed
	source: 'c=internal&amp;a=invoke&amp;module=records&amp;action=autocomplete&amp;context=worker',
	onSelect: function(item, input) { input.value = item.label; },
});</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.TextChooser))
		return;

	const elBasic = document.getElementById('uiref-textchooser-basic');
	if(elBasic) new CerbUI.TextChooser(elBasic, {
		source: ['Apple','Apricot','Banana','Cherry','Date','Fig','Grape','Kiwi','Lemon','Mango','Orange','Peach','Pear'],
		minLength: 0,
	});

	const elIcon = document.getElementById('uiref-textchooser-icon');
	if(elIcon) new CerbUI.TextChooser(elIcon, {
		icon: 'globe',
		source: 'c=profiles&a=invoke&module=org&action=autocompleteCountry',
	});

	const elFn = document.getElementById('uiref-textchooser-fn');
	if(elFn) new CerbUI.TextChooser(elFn, {
		icon: 'clock',
		source: function(term) {
			const tz = [
				{ label:'America/New York', value:'America/New_York', sublabel:'UTC-5' },
				{ label:'America/Los Angeles', value:'America/Los_Angeles', sublabel:'UTC-8' },
				{ label:'Europe/London', value:'Europe/London', sublabel:'UTC+0' },
				{ label:'Europe/Paris', value:'Europe/Paris', sublabel:'UTC+1' },
				{ label:'Asia/Tokyo', value:'Asia/Tokyo', sublabel:'UTC+9' }
			];
			const needle = term.toLowerCase();
			return tz.filter(function(it) { return it.label.toLowerCase().indexOf(needle) !== -1; });
		},
		onSelect: function(item, input) { input.value = item.value; }
	});

	const elAv = document.getElementById('uiref-textchooser-avatars');
	if(elAv) new CerbUI.TextChooser(elAv, {
		avatars: true,
		context: 'worker',
		source: 'c=internal&a=invoke&module=records&action=autocomplete&context=worker',
		onSelect: function(item, input) { input.value = item.label; }
	});
})();
</script>
