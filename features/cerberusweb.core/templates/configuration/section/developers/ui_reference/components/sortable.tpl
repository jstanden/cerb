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
	connectWith: [],           // other Sortable containers for cross-list dragging (array OR a CSS selector)
	// anchor: 'pointer',      // point that decides the drop: 'pointer' | 'top-left' | 'top-right' | 'center'
	//                         //   (handy when the drag handle isn't where you visually aim)
	// placeholderClass: '',   // extra class added to both placeholder elements
	onStart:  function(info) { /* drag activated */ },
	onStop:   function(info) { /* released, before the DOM commit (valid drops only) */ },
	onSorted: function(info) { /* committed; info = { item, from, to, fromIndex, toIndex } */ },
	onEnd:    function(info) { /* drag finished — commit OR cancel; use for state cleanup */ },
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

		{* Example: a single-row horizontal strip (inline-block items) — placeholders must stay in-row *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Horizontal strip &mdash; single-row reorder; placeholders stay in-row (no vertical wrap)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul class="cerb-uiref-hstrip" id="uiref-sortable-hstrip">
					<li class="cerb-ui-panel">Inbox</li>
					<li class="cerb-ui-panel">Spam</li>
					<li class="cerb-ui-panel">Sent</li>
					<li class="cerb-ui-panel">Drafts</li>
					<li class="cerb-ui-panel">Trash</li>
				</ul>
				<span class="cerb-uiref-result" style="margin-top:0.7em;display:inline-block;">Last move: <b id="uiref-sortable-hstrip-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;ul id="strip" style="white-space:nowrap;"&gt;
	&lt;li style="display:inline-block;"&gt;Inbox&lt;/li&gt;
	&lt;li style="display:inline-block;"&gt;Spam&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// orientation is auto-detected; placeholders copy the item's display/float so an
// inline-block / floated row never breaks to a vertical stack mid-drag.
new CerbUI.Sortable(strip, { items: 'li' });</pre>
			</div>
		</div>

		{* Example: a wrapping tag/bubble field — row-aware insertion across multiple rows *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Wrapping tags &mdash; multi-row field; insertion is row-aware (drop into the row under the cursor)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul class="cerb-uiref-tags" id="uiref-sortable-tags" style="max-width:360px;">
					<li>apples</li>
					<li>oranges</li>
					<li>pears</li>
					<li>bananas</li>
					<li>grapes</li>
					<li>peaches</li>
					<li>plums</li>
					<li>cherries</li>
					<li>mangoes</li>
				</ul>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;ul id="tags"&gt; {* inline-block chips that wrap to several rows *}
	&lt;li class="cerb-ui-chip"&gt;apples&lt;/li&gt;
	&lt;li class="cerb-ui-chip"&gt;oranges&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.Sortable(tags, { items: 'li' });</pre>
			</div>
		</div>

		{* Example: clone helper — a bare clone gets a solid background so the page can't show through *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Clone helper (<code>helper:'clone'</code>) &mdash; the floating copy is opaque even if the item has no background</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-uiref-sortlist" id="uiref-sortable-clone">
					<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>First rule</div>
					<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Second rule</div>
					<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Third rule</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// clone helper (single moving placeholder; matches the legacy fieldset reorders)
new CerbUI.Sortable(list, { handle: '.grip', helper: 'clone' });</pre>
			</div>
		</div>

		{* Example: 2D wrap grid — grid mode: absolute insertion bar (no per-move reflow), ghostOrigin holds slot *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Wrap grid (<code>grid:true, ghostOrigin:true</code>) &mdash; the grid stays static; an insertion bar marks the drop and the lifted tile's slot is held</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-uiref-grid" id="uiref-sortable-grid">
					<div class="cerb-uiref-tile">Alpha</div>
					<div class="cerb-uiref-tile">Bravo</div>
					<div class="cerb-uiref-tile">Charlie</div>
					<div class="cerb-uiref-tile">Delta</div>
					<div class="cerb-uiref-tile">Echo</div>
					<div class="cerb-uiref-tile">Foxtrot</div>
					<div class="cerb-uiref-tile">Golf</div>
					<div class="cerb-uiref-tile">Hotel</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// 2D wrap layout: the drop spot is an absolute insertion bar (no per-move reflow);
// ghostOrigin holds the lifted tile's slot so the grid stays static while dragging.
new CerbUI.Sortable(grid, { items: '.tile', grid: true, ghostOrigin: true });</pre>
			</div>
		</div>

		{* Example: table rows — placeholders + the float helper are valid table elements (a <div> would be hoisted out) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Table rows (<code>items:'tbody', helper:'clone'</code>) &mdash; reorder rows by the grip; placeholders are real <code>&lt;tbody&gt;</code> and the float is wrapped in a <code>&lt;table&gt;</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<table class="cerb-uiref-sorttable" id="uiref-sortable-table">
					<tbody><tr><td><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span></td><td>Subject</td><td>varchar</td></tr></tbody>
					<tbody><tr><td><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span></td><td>Status</td><td>enum</td></tr></tbody>
					<tbody><tr><td><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span></td><td>Priority</td><td>number</td></tr></tbody>
					<tbody><tr><td><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span></td><td>Created</td><td>timestamp</td></tr></tbody>
				</table>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;table id="t"&gt;
	&lt;tbody&gt;&lt;tr&gt;&lt;td&gt;…grip…&lt;/td&gt;&lt;td&gt;Subject&lt;/td&gt;&lt;/tr&gt;&lt;/tbody&gt;
	&lt;tbody&gt;&lt;tr&gt;&lt;td&gt;…grip…&lt;/td&gt;&lt;td&gt;Status&lt;/td&gt;&lt;/tr&gt;&lt;/tbody&gt;
&lt;/table&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// table rows: items are &lt;tbody&gt;. Use helper:'clone' — placeholders are real &lt;tbody&gt;/&lt;tr&gt;
// and the floating row is wrapped in a &lt;table&gt; with the source column widths.
new CerbUI.Sortable(table, { items: 'tbody', handle: '.grip', helper: 'clone' });</pre>
			</div>
		</div>
	</div>

<style nonce="{DevblocksPlatform::getRequestNonce()}">
	{literal}
	.cerb-uiref-hstrip { list-style:none; margin:0; padding:0; white-space:nowrap; }
	.cerb-uiref-hstrip > li { display:inline-block; margin-right:8px; }
	.cerb-uiref-tags { list-style:none; margin:0; padding:0; }
	.cerb-uiref-tags > li {
		display:inline-block; margin:0 6px 6px 0; padding:4px 12px;
		border:1px solid var(--cerb-color-background-contrast-200);
		border-radius:999px; background:var(--cerb-color-background-contrast-245);
	}
	.cerb-uiref-grid { display:flex; flex-wrap:wrap; gap:8px; max-width:420px; }
	.cerb-uiref-grid > .cerb-uiref-tile {
		width:90px; height:60px; display:flex; align-items:center; justify-content:center;
		border:1px solid var(--cerb-color-background-contrast-200);
		border-radius:8px; background:var(--cerb-color-background-contrast-245);
	}
	.cerb-uiref-sorttable { border-collapse:collapse; width:360px; }
	.cerb-uiref-sorttable td { border:1px solid var(--cerb-color-background-contrast-200); padding:4px 8px; }
	.cerb-uiref-sorttable td:first-child { width:1%; white-space:nowrap; }
	{/literal}
</style>

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

	// Sortable: single-row horizontal strip (inline-block items)
	(function() {
		const el = document.getElementById('uiref-sortable-hstrip');
		const out = document.getElementById('uiref-sortable-hstrip-result');
		if(el && window.CerbUI && CerbUI.Sortable) {
			new CerbUI.Sortable(el, {
				items: 'li',
				onSorted: function(info) {
					if(out) out.textContent = (info.item.textContent || '').trim() + ': ' + info.fromIndex + ' → ' + info.toIndex;
				},
			});
		}
	})();

	// Sortable: wrapping tag field (row-aware insertion across rows)
	(function() {
		const el = document.getElementById('uiref-sortable-tags');
		if(el && window.CerbUI && CerbUI.Sortable)
			new CerbUI.Sortable(el, { items: 'li' });
	})();

	// Sortable: clone helper with a single moving placeholder
	(function() {
		const el = document.getElementById('uiref-sortable-clone');
		if(el && window.CerbUI && CerbUI.Sortable)
			new CerbUI.Sortable(el, { handle: '.uiref-grip', helper: 'clone' });
	})();

	// Sortable: 2D wrap grid (grid mode + ghostOrigin holds the lifted tile's slot)
	(function() {
		const el = document.getElementById('uiref-sortable-grid');
		if(el && window.CerbUI && CerbUI.Sortable)
			new CerbUI.Sortable(el, { items: '.cerb-uiref-tile', grid: true, ghostOrigin: true });
	})();

	// Sortable: table rows (tbody items; tbody/tr placeholders + table-wrapped float helper)
	(function() {
		const el = document.getElementById('uiref-sortable-table');
		if(el && window.CerbUI && CerbUI.Sortable)
			new CerbUI.Sortable(el, { items: 'tbody', handle: '.uiref-grip', helper: 'clone' });
	})();
})();
</script>
