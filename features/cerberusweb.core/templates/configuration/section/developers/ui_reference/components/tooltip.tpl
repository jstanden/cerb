	<div class="cerb-uiref-component" id="tooltip">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-comments"></span>Tooltip</div>

		{* Example: a floating panel positioned by JS (hover the target) — reused by charts + Menu later *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Floating panel positioned by JS (hover the target)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-tooltip-target">Hover me</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// Point mode (hover): one instance per trigger; content is an HTML string OR a built DOM node.
// CerbUI.Tooltip(options) — the constructor takes a single option:
const tip = new CerbUI.Tooltip({
	gap: 10, // px between the anchor point/element and the panel (default 10)
});

const el = document.querySelector('#my-target');
el.addEventListener('mouseenter', e =&gt; tip.show('&lt;b&gt;Tooltip&lt;/b&gt;&lt;br&gt;Floating, viewport-aware panel.', e.clientX, e.clientY));
el.addEventListener('mousemove',  e =&gt; tip.move(e.clientX, e.clientY)); // reposition, same content
el.addEventListener('mouseleave', () =&gt; tip.hide());</pre>
			</div>
		</div>

		{* Example: anchored to an element — auto flip-fit side + an SVG arrow that points at the target *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Anchored to an element (click to open the callout)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-gap-3 cerb-u-justify-center" id="uiref-tooltip-anchors">
					<button type="button" class="cerb-ui-button" data-my="right center" data-at="left center">Left</button>
					<button type="button" class="cerb-ui-button" data-my="center bottom" data-at="center top">Top</button>
					<button type="button" class="cerb-ui-button" data-my="center top" data-at="center bottom">Bottom</button>
					<button type="button" class="cerb-ui-button" data-my="left center" data-at="right center">Right</button>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// anchored mode: pin to a DOM element; an SVG arrow points at it. content is a string OR a DOM node.
const tip = new CerbUI.Tooltip({
	// gap: 10, // px between the target and the panel (default 10)
});

tip.anchor('&lt;b&gt;Heads up&lt;/b&gt;&lt;br&gt;Anchored callout with an arrow.', targetEl, {
	// my: 'center bottom', // point ON THE TOOLTIP (jQuery-UI style; "middle" == "center")
	// at: 'center top',    // point ON THE TARGET it aligns to — default: tooltip's bottom-middle
	//                      // at the target's top-middle; flips below + slides to stay on-screen
	interactive: true,      // clickable; dismiss on click / outside-click (default true)
});</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Tooltip: a floating panel that follows the cursor over the target
	const ttEl = document.getElementById('uiref-tooltip-target');
	if(ttEl && window.CerbUI && CerbUI.Tooltip) {
		const tip = new CerbUI.Tooltip();
		ttEl.addEventListener('mouseenter', function(e) { tip.show('<b>Tooltip</b><br>Floating, viewport-aware panel.', e.clientX, e.clientY); });
		ttEl.addEventListener('mousemove', function(e) { tip.move(e.clientX, e.clientY); });
		ttEl.addEventListener('mouseleave', function() { tip.hide(); });
	}

	// Tooltip (anchored): one shared callout reused across buttons that each request a different my/at
	const ttAnchorWrap = document.getElementById('uiref-tooltip-anchors');
	if(ttAnchorWrap && window.CerbUI && CerbUI.Tooltip) {
		const tip = new CerbUI.Tooltip();
		ttAnchorWrap.addEventListener('click', function(e) {
			const btn = e.target.closest('button[data-my]');
			if(!btn) return;
			tip.anchor('<b>' + btn.textContent + '</b><br>Anchored callout with an arrow.', btn, { my: btn.dataset.my, at: btn.dataset.at, interactive: true });
		});
	}
})();
</script>
