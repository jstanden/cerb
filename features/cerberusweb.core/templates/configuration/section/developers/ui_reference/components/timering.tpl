	<div class="cerb-uiref-component" id="timering">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-clock"></span>TimeRing</div>

		{* Example: a countdown ring the page drives via setters (no internal timer) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Countdown ring (the page drives it via setters)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-time-ring" id="uiref-timering"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- empty container; CerbUI.TimeRing builds the SVG + center text --&gt;
&lt;div class="cerb-ui-time-ring"&gt;&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// options are optional initial values:
const ring = new CerbUI.TimeRing(el, {
	value: '60s',   // center value (big)
	key: 'next',    // static sub-label under it (small; CSS-uppercased -> NEXT)
	fraction: 0,    // initial fill, 0..1
	size: 38,       // ring diameter, px
	stroke: 3.5,    // arc width, px
});

// the page updates it (e.g. once a second); the ring has no timer of its own
ring.setFraction((total - remaining) / total); // 0..1 of the ring filled
ring.setValue(remaining + 's');</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// TimeRing: a countdown ring driven by the page (no internal timer) — fills as the next fire approaches
	const ringEl = document.getElementById('uiref-timering');
	if(ringEl && window.CerbUI && CerbUI.TimeRing) {
		const ring = new CerbUI.TimeRing(ringEl, { key: 'next' });
		const total = 60;
		let remaining = total;
		const tick = function() {
			ring.setFraction((total - remaining) / total);
			ring.setValue(remaining + 's');
			remaining = (remaining <= 0) ? total : remaining - 1;
		};
		tick();
		setInterval(tick, 1000);
	}
})();
</script>
