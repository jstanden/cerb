	<div class="cerb-uiref-component" id="droppable">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-download"></span>Droppable</div>

		{* Example: two zones with different accept rules — one takes any tile, one only blue *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Two zones, different <code>accept</code> rules &mdash; the left takes any tile, the right only blue ones. A valid hover shows the overlay; a rejected drop reverts (the zone never highlights)</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex;gap:1em;align-items:flex-start;flex-wrap:wrap;">
					<div id="uiref-drop-src" style="display:flex;gap:6px;flex-wrap:wrap;min-width:150px;">
						<div class="cerb-ui-tile" data-color="blue" data-name="Blue"><span class="cerb-ui-tile--icon" style="background:var(--cerb-color-tag-blue);"><span class="cerb-icons cerb-icon-tag"></span></span><div class="cerb-ui-tile--text"><div class="cerb-ui-tile--name">Blue</div></div></div>
						<div class="cerb-ui-tile" data-color="red" data-name="Red"><span class="cerb-ui-tile--icon" style="background:var(--cerb-color-tag-red);"><span class="cerb-icons cerb-icon-tag"></span></span><div class="cerb-ui-tile--text"><div class="cerb-ui-tile--name">Red</div></div></div>
					</div>
					<div id="uiref-drop-any" class="cerb-ui-panel" style="flex:1;min-width:140px;min-height:110px;">Accepts any</div>
					<div id="uiref-drop-blue" class="cerb-ui-panel" style="flex:1;min-width:140px;min-height:110px;">Only blue</div>
				</div>
				<span class="cerb-uiref-result" style="margin-top:0.7em;display:inline-block;">Status: <b id="uiref-drop-out">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.Draggable(palette, { items: '.cerb-ui-tile' });

new CerbUI.Droppable(anyZone, {
	onOver: function(info) {}, onOut: function(info) {},   // valid drag enter / leave
	onDrop: function(info) { log('took ' + info.payload.name); },
});

new CerbUI.Droppable(blueZone, {
	accept:        function(item, payload) { return payload.color === 'blue'; }, // others rejected → drop reverts
	rejectOverlay: true,                                  // show a red wash while an unacceptable drag hovers
	onReject:      function(info) { log('nope: ' + info.payload.name); },
	onDrop:        function(info) { log('took ' + info.payload.name); },
});{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Droppable: two zones with different accept rules (any vs. blue only); rejected drops revert
	(function() {
		const src = document.getElementById('uiref-drop-src');
		const any = document.getElementById('uiref-drop-any');
		const blue = document.getElementById('uiref-drop-blue');
		const out = document.getElementById('uiref-drop-out');
		if(src && any && blue && window.CerbUI && CerbUI.Draggable && CerbUI.Droppable) {
			new CerbUI.Draggable(src, { items: '.cerb-ui-tile' });
			new CerbUI.Droppable(any, {
				onDrop: function(info) { if(out) out.textContent = 'Accepts any: took ' + info.payload.name; }
			});
			new CerbUI.Droppable(blue, {
				accept: function(item, payload) { return payload.color === 'blue'; },
				rejectOverlay: true,
				onReject: function(info) { if(out) out.textContent = 'Only blue: rejects ' + info.payload.name; },
				onDrop: function(info) { if(out) out.textContent = 'Only blue: took ' + info.payload.name; }
			});
		}
	})();
})();
</script>
