	<div class="cerb-uiref-component" id="record-chooser">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-search"></span>RecordChooser</div>

		{* A server-backed Chooser (vs a local Picker): never materializes the full set; searches the
		   server (ACL-filtered). Monogram avatars paint instantly; real images lazy-load per visible row,
		   through a global concurrency queue — so opening 1,000 workers never fires 1,000 image requests. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Server-backed single-record picker &mdash; click and type to search (large-set safe; lazy avatars)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-recordchooser"></div>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Chosen: <b id="uiref-recordchooser-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- any element becomes the trigger; the component renders the selection into it --&gt;
&lt;div id="owner"&gt;&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const rc = new CerbUI.RecordChooser(el, {
	context:           'worker',          // context alias to search (server, ACL-filtered)
	searchPlaceholder: 'Search workers…', // the autocomplete input's placeholder
	emptyIcon:         'file',            // empty-state leading glyph (cerb-icons name); pick one per record type
	name:              'worker_id',       // optional: maintains a hidden &lt;input&gt; (value = record id) so it posts
	// value:          { id: 5, label: 'Kim Li', image_url: '' },   // optional initial selection
	onSelect:  function(item) { /* item = { context, id, label, image_url } */ },
});

// Empty: a leading icon (emptyIcon) + autocomplete input + magnifier (opens the full chooserOpen popup).
// Autocomplete rows show the avatar + a two-line stack (label + the endpoint's `meta`, e.g. a worker title).
// Single + value: a chip (click = card peek) with only × to clear — no search until cleared.

// API: rc.getValue(); rc.setValue(item); rc.clear(); rc.openSearch(); rc.destroy();
// Reuses the ACL-filtered record autocomplete; results show a monogram immediately, real avatar lazy-loads.</pre>
			</div>
		</div>

		{* Multiple mode — a tag/token input *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Multiple mode &mdash; a tag/token input: add via autocomplete or the popup; tiles stack; click a tile for its card; the search button stays at right</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-recordchooser-multi"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.RecordChooser(el, {
	context:  'worker',
	multiple: true,            // tag/token input — selections stack as tiles; search button stays at right
	name:     'worker_ids',    // posts as worker_ids[] (one hidden input per selection)
	onSelect: function(item) { /* fired per add; rc.getValue() returns the array */ },
});

// Add via autocomplete (popup stays open, input clears) OR the magnifier popup (multi-select).
// Click empty space to type; click a tile for its card peek; × removes a tile.</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const el = document.getElementById('uiref-recordchooser');
	const out = document.getElementById('uiref-recordchooser-result');
	if(el && window.CerbUI && CerbUI.RecordChooser) {
		new CerbUI.RecordChooser(el, {
			context: 'worker',
			searchPlaceholder: 'Search workers…', // the autocomplete input's placeholder
			emptyIcon: 'user',                    // empty-state glyph (default 'file'); a record type can pick its own
			onSelect: function(item) { if(out) out.textContent = item.label; },
		});
	}

	const elMulti = document.getElementById('uiref-recordchooser-multi');
	if(elMulti && window.CerbUI && CerbUI.RecordChooser) {
		new CerbUI.RecordChooser(elMulti, {
			context: 'worker',
			multiple: true,
			searchPlaceholder: 'Add workers…',
		});
	}
})();
</script>
