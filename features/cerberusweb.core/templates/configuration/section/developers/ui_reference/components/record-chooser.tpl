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

		{* Default the value(s) — seed markup the chooser enhances *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Default the value(s) &mdash; you usually have a record id (or several) from the record. Server-render the resolved label/avatar into a <code>[data-context-id]</code> child and the chooser enhances it (then clears the markup). The fixed <code>context</code> is implied, so no <code>data-context</code> is needed. No id&rarr;label endpoint exists, so resolve label/avatar via <code>Extension_DevblocksContext::get($ctx)-&gt;getMeta($id)</code> (sanctioned in-template &mdash; that class is in Smarty's <code>registerClass</code> allow-list) plus the <code>c=avatars</code> URL</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-recordchooser-seed" class="cerb-ui-record-chooser">
					<li data-context-id="{$active_worker->id}" data-label="{$active_worker->getName()}" data-image="{devblocks_url}c=avatars&context=worker&context_id={$active_worker->id}{/devblocks_url}?v={$smarty.const.APP_BUILD}"></li>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Seed markup: one [data-context-id] child per value (the old &lt;ul&gt;&lt;li&gt; idea). Server-render the
     resolved label/avatar; the chooser reads these, builds the chip(s), then clears the markup.
     The fixed `context` is implied — only id/label/image are needed. --&gt;
&lt;div class="cerb-ui-record-chooser"&gt;
	&lt;li data-context-id="5"
	    data-label="Kim Li"
	    data-image="…&amp;c=avatars&amp;context=worker&amp;context_id=5"&gt;&lt;/li&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.RecordChooser(el, { context:'worker', name:'worker_id' });  // no `value` → reads the seed markup
// multiple: true reads several [data-context-id] children and stacks them as tiles</pre>
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

		{* Picker link — a static helper that attaches a search popup to a trigger and writes the picked id into a sibling field *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Picker link (<code>CerbUI.RecordChooser.pickerLink</code>) &mdash; a <strong>static helper</strong> (no instance/chip). Attach a record-search popup to a clickable trigger (e.g. an &ldquo;ID&rdquo; label); on pick it inserts the chosen record&rsquo;s id into a sibling text field. For <strong>dual-purpose fields</strong> that also accept render-time placeholders (<code>{literal}{{…}}{/literal}</code>) &mdash; it only writes a literal id when the user explicitly picks one. The context comes from the link&rsquo;s <code>data-context</code> (read live, so a coupled &ldquo;Type&rdquo; select can drive it) unless <code>opts.context</code> is given. Default insert format is <code>{literal}id{# label #}{/literal}</code> &mdash; a Cerb placeholder comment that keeps the human label visible while the value resolves to the id</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<a href="javascript:;" id="uiref-recordchooser-pickerlink" data-context="worker" class="no-underline"><span class="cerb-icons cerb-icon-search"></span> Pick a worker&hellip;</a>
					<input type="text" id="uiref-recordchooser-pickerlink-input" size="40" placeholder="picked id appears here (or type {literal}{{placeholders}}{/literal})">
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- the trigger carries the context; the input is the dual-purpose field it writes into --&gt;
&lt;a href="javascript:;" data-context="worker"&gt;&lt;span class="cerb-icons cerb-icon-search"&gt;&lt;/span&gt; Pick a worker&hellip;&lt;/a&gt;
&lt;input type="text" name="worker_id" size="40"&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const core = CerbUI.RecordChooser.pickerLink(linkEl, {
	input:   inputEl,                  // the sibling field to write into (DOM node or jQuery)
	context: 'worker',                 // optional: overrides the link's data-context (read live if omitted)
	query:   'group:(id:5)',           // optional: scope query appended to the autocomplete request
	onPick:  function(item) {          // optional: custom handler. Default writes `id{# label #}` to `input`.
		// item = { context, id, label, image_url, sublabel }
		inputEl.value = item.id;       // …e.g. write the bare id instead
	},
});

// Returns the chooserCore instance (core.open()/close()/isOpen()). The link click toggles it.
// If `input` is a CerbUI.ScriptingEditor (legacy-bot .placeholders fields), the default onPick drives the
// editor so the pick shows immediately.{/literal}</pre>
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
			searchPlaceholder: 'Search workers...', // the autocomplete input's placeholder
			emptyIcon: 'user',                    // empty-state glyph (default 'file'); a record type can pick its own
			onSelect: function(item) { if(out) out.textContent = item.label; },
		});
	}

	const elSeed = document.getElementById('uiref-recordchooser-seed');
	if(elSeed && window.CerbUI && CerbUI.RecordChooser) {
		new CerbUI.RecordChooser(elSeed, {
			context: 'worker',
			searchPlaceholder: 'Search workers...',
			emptyIcon: 'user',
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

	// Picker link: a static helper — search popup on the trigger writes the picked id into the sibling field
	const elPickerLink = document.getElementById('uiref-recordchooser-pickerlink');
	const elPickerInput = document.getElementById('uiref-recordchooser-pickerlink-input');
	if(elPickerLink && elPickerInput && window.CerbUI && CerbUI.RecordChooser && CerbUI.RecordChooser.pickerLink) {
		CerbUI.RecordChooser.pickerLink(elPickerLink, { input: elPickerInput });
	}
})();
</script>
