	<div class="cerb-uiref-component" id="draggable">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-move"></span>Draggable</div>

		{* Example: drag a clone out of a palette onto a droppable canvas (original stays) *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Drag a <em>copy</em> out (clone helper, tilted) onto a <code>CerbUI.Droppable</code> &mdash; the source stays put (unlike Sortable, which reorders); <code>Esc</code> cancels, dropping outside reverts. The payload is the item's <code>data-*</code></div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex;gap:1em;align-items:flex-start;flex-wrap:wrap;">
					<div id="uiref-drag-src" style="display:flex;flex-direction:column;gap:6px;min-width:170px;">
						<div class="cerb-ui-tile cerb-ui-tile--block" data-name="Text" data-kind="field"><span class="cerb-ui-tile--icon" style="background:var(--cerb-color-tag-blue);"><span class="cerb-icons cerb-icon-text"></span></span><div class="cerb-ui-tile--text"><div class="cerb-ui-tile--kind">field</div><div class="cerb-ui-tile--name">Text</div></div></div>
						<div class="cerb-ui-tile cerb-ui-tile--block" data-name="Number" data-kind="field"><span class="cerb-ui-tile--icon" style="background:var(--cerb-color-tag-green);"><span class="cerb-icons cerb-icon-hash"></span></span><div class="cerb-ui-tile--text"><div class="cerb-ui-tile--kind">field</div><div class="cerb-ui-tile--name">Number</div></div></div>
						<div class="cerb-ui-tile cerb-ui-tile--block" data-name="Date" data-kind="field"><span class="cerb-ui-tile--icon" style="background:var(--cerb-color-tag-purple);"><span class="cerb-icons cerb-icon-calendar"></span></span><div class="cerb-ui-tile--text"><div class="cerb-ui-tile--kind">field</div><div class="cerb-ui-tile--name">Date</div></div></div>
					</div>
					<div id="uiref-drag-canvas" class="cerb-ui-panel" style="flex:1;min-width:200px;min-height:160px;">Drop fields here&hellip;</div>
				</div>
				<span class="cerb-uiref-result" style="margin-top:0.7em;display:inline-block;">Last drop: <b id="uiref-drag-out">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- any container of items + any drop zone (give the palette gaps so it reads as a list) --&gt;
&lt;div id="palette"&gt;
	&lt;div class="cerb-ui-tile" data-name="Text"&gt;…&lt;/div&gt;
	&lt;div class="cerb-ui-tile" data-name="Date"&gt;…&lt;/div&gt;
&lt;/div&gt;
&lt;div id="canvas"&gt;&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Make a container's children draggable; each drag floats a clone (the original never moves).
const drag = new CerbUI.Draggable(document.getElementById('palette'), {
	items:    '.cerb-ui-tile',  // selector for draggable children (null = the element itself)
	handle:   '',               // drag-handle selector within an item (default: the whole item)
	helper:   'clone',          // 'clone' | 'original' | (item) => node  (palette default: clone)
	tilt:     true,             // rotate+scale the floating helper (the Sortable pickup look)
	distance: 5,                // px the pointer must move before a drag starts
	data:     function(item) { return { ...item.dataset }; },  // the drop payload (default: the item's data-*)
	onStart:  function(item, e) {},
	onMove:   function(item, e) {},
	onStop:   function(item, info) { /* info = { dropped, droppable, payload } */ },
});

// A drop zone. accept filters which drags it takes; onDrop gets the payload (return false to reject).
const drop = new CerbUI.Droppable(document.getElementById('canvas'), {
	accept:        '.cerb-ui-tile', // selector | (item, payload) => bool | null = accept all
	overlay:       true,            // built-in highlight while a valid (accepted) drag hovers
	rejectOverlay: false,           // true = also show a red wash while an UNacceptable drag hovers
	// hoverClass:  '',             // extra class on the zone while a valid drag is over it
	onOver:        function(info) {},
	onReject:      function(info) {},  // an unacceptable drag is hovering (pair with rejectOverlay)
	onOut:         function(info) {},
	onDrop:        function(info) { addField(info.payload); },  // return false to reject
});
drag.refresh(); drag.destroy();        // re-scan items / tear down
CerbUI.Draggable.from(el); CerbUI.Droppable.from(el);   // -> instances{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Draggable: drag a clone of a palette tile onto a droppable canvas (original stays)
	(function() {
		const src = document.getElementById('uiref-drag-src');
		const canvas = document.getElementById('uiref-drag-canvas');
		const out = document.getElementById('uiref-drag-out');
		if(src && canvas && window.CerbUI && CerbUI.Draggable && CerbUI.Droppable) {
			let n = 0;
			new CerbUI.Draggable(src, { items: '.cerb-ui-tile' });
			new CerbUI.Droppable(canvas, {
				accept: '.cerb-ui-tile',
				onDrop: function(info) {
					if(!n++) canvas.textContent = '';
					const chip = document.createElement('span');
					chip.className = 'cerb-ui-pill cerb-u-mr-2 cerb-u-mb-2';
					chip.textContent = info.payload.name;
					canvas.appendChild(chip);
					if(out) out.textContent = info.payload.name;
				}
			});
		}
	})();
})();
</script>
