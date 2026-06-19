	<div class="cerb-uiref-component" id="sortable">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-move-vertical"></span>Sortable</div>

		{* Example: a vertical list, drag any row to reorder (helper = the row itself) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Vertical list &mdash; drag a row to reorder; <code>Esc</code> cancels, drop outside snaps back</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-uiref-sortlist" id="uiref-sortable-basic">
					<div class="cerb-ui-panel">First &mdash; Inbox</div>
					<div class="cerb-ui-panel">Second &mdash; Spam</div>
					<div class="cerb-ui-panel">Third &mdash; Sent</div>
					<div class="cerb-ui-panel">Fourth &mdash; Drafts</div>
				</div>
				<span class="cerb-uiref-result" style="margin-top:0.7em;display:inline-block;">Last move: <b id="uiref-sortable-basic-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- any block with child items; give it a gap so placeholders are visible --&gt;
&lt;div id="list" style="display:flex; flex-direction:column; gap:6px;"&gt;
	&lt;div class="cerb-ui-panel"&gt;First&lt;/div&gt;
	&lt;div class="cerb-ui-panel"&gt;Second&lt;/div&gt;
	&lt;div class="cerb-ui-panel"&gt;Third&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const sortable = new CerbUI.Sortable(el, {
	items:      '&gt; *',         // CSS selector for sortable children (default '&gt; *')
	handle:     '',            // selector for a drag handle within each item (default: whole item)
	helper:     'original',    // 'original' | 'clone' | (item) =&gt; HTMLElement  (default 'original')
	distance:   5,             // px the pointer must move before a drag starts (default 5)
	disabled:   false,         // disable drag-and-drop functionality (default false)
	tolerance:  'pointer',     // 'pointer' (midpoint) | 'intersect' (max overlap) (default 'pointer')
	connectWith: [],           // other Sortable container elements for cross-list dragging
	// placeholderClass: '',   // extra class added to both placeholder elements
	// ghostOrigin: false,     // true = origin slot shows a dimmed clone of the item (vs. a dashed box)
	onStart:  function(info) { /* drag activated */ },
	onStop:   function(info) { /* released, before DOM commit */ },
	onSorted: function(info) { /* info = { item, from, to, fromIndex, toIndex } */ },
});

// also fires a DOM event on the container:
el.addEventListener('cerb-ui-sortable:sorted', e =&gt; console.log(e.detail));</pre>
			</div>
		</div>

		{* Example: two lists wired together with connectWith + a drag handle *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Drag handle + two connected lists (<code>handle</code> + <code>connectWith</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex; gap:1em; align-items:flex-start; flex-wrap:wrap;">
					<div class="cerb-uiref-sortlist" id="uiref-sortable-conn-a" style="min-width:200px;">
						<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Apples</div>
						<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Oranges</div>
						<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Pears</div>
					</div>
					<div class="cerb-uiref-sortlist" id="uiref-sortable-conn-b" style="min-width:200px;">
						<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Carrots</div>
						<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Peas</div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div id="list-a"&gt;
	&lt;div class="cerb-ui-panel"&gt;&lt;span class="grip"&gt;&lt;/span&gt;Apples&lt;/div&gt;
	&lt;div class="cerb-ui-panel"&gt;&lt;span class="grip"&gt;&lt;/span&gt;Oranges&lt;/div&gt;
&lt;/div&gt;
&lt;div id="list-b"&gt;
	&lt;div class="cerb-ui-panel"&gt;&lt;span class="grip"&gt;&lt;/span&gt;Carrots&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// connectWith takes the OTHER list's container element — order doesn't matter,
// instances cross-link lazily via CerbUI.Sortable.from(el)
const a = document.getElementById('list-a');
const b = document.getElementById('list-b');
new CerbUI.Sortable(a, { handle: '.grip', connectWith: [b] });
new CerbUI.Sortable(b, { handle: '.grip', connectWith: [a] });</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Sortable: a vertical list, reporting each move
	(function() {
		const el = document.getElementById('uiref-sortable-basic');
		const out = document.getElementById('uiref-sortable-basic-result');
		if(el && window.CerbUI && CerbUI.Sortable) {
			new CerbUI.Sortable(el, {
				onSorted: function(info) {
					if(out) out.textContent = (info.item.textContent || '').trim() + ': ' + info.fromIndex + ' → ' + info.toIndex;
				},
			});
		}
	})();

	// Sortable: two lists wired together with connectWith + a drag handle
	(function() {
		const a = document.getElementById('uiref-sortable-conn-a');
		const b = document.getElementById('uiref-sortable-conn-b');
		if(a && b && window.CerbUI && CerbUI.Sortable) {
			new CerbUI.Sortable(a, { handle: '.uiref-grip', connectWith: [b] });
			new CerbUI.Sortable(b, { handle: '.uiref-grip', connectWith: [a] });
		}
	})();
})();
</script>
