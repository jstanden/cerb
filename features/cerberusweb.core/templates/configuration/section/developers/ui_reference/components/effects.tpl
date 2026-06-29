	<div class="cerb-uiref-component" id="effects">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-sparkles"></span>Effects</div>

		{* CerbUI.effects — one-shot animation helpers (the jQuery-UI .effect() replacement) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><code>CerbUI.effects</code> &mdash; one-shot animation helpers (lowercase: a utility, not a component) that replace the legacy jQuery-UI <code>.effect()</code> calls. All three are <b>reduced-motion aware</b> (skip the animation, still run <code>onEnd</code>) and callback-safe. For looping or hover animations use the <a href="#utilities"><code>cerb-u-anim-*</code></a> utilities instead.</div>
		</div>

		{* flash *}
		<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm"><code>flash</code> &mdash; brief background flash (validation cue / anchor jump)</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<span id="uiref-fx-flash" style="display:inline-block;padding:0.4em 0.8em;border:1px solid var(--cerb-color-border);border-radius:0.3em;margin-right:0.7em;">This field needs a value</span>
				<button type="button" class="cerb-ui-button" id="uiref-fx-flash-btn">Flash</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.effects.flash(el);                 // accepts a DOM node or a jQuery object
CerbUI.effects.flash(el, { onEnd: fn }); // optional callback after the flash{/literal}</pre>
			</div>
		</div>

		{* transfer *}
		<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm"><code>transfer</code> &mdash; fly a ghost box from one element to another</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo" style="display:flex;align-items:center;gap:2em;">
				<span id="uiref-fx-xfer-from" style="display:inline-block;padding:0.4em 0.8em;border:1px solid var(--cerb-color-border);border-radius:0.3em;">Source</span>
				<button type="button" class="cerb-ui-button" id="uiref-fx-xfer-btn">Transfer &rarr;</button>
				<span id="uiref-fx-xfer-to" style="display:inline-block;padding:0.4em 0.8em;border:1px dashed var(--cerb-color-border);border-radius:0.3em;">Target</span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.effects.transfer(fromEl, toEl, {
	duration: 500,                // ms (default 500)
	onEnd: function() { /* … */ }, // after the ghost lands
});{/literal}</pre>
			</div>
		</div>

		{* pulse *}
		<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm"><code>pulse</code> &mdash; pulse an element a few times, then run <code>onEnd</code> (reuses the <code>cerb-u-anim-pulse</code> keyframe)</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-fx-pulse-btn">Pulse me</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.effects.pulse(el, {
	times: 3,                     // pulse count (default 3)
	onEnd: function() { /* … */ },
});{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.effects)) return;

	const flashBtn = document.getElementById('uiref-fx-flash-btn');
	const flashBox = document.getElementById('uiref-fx-flash');
	if(flashBtn) flashBtn.addEventListener('click', function() { CerbUI.effects.flash(flashBox); });

	const xferBtn = document.getElementById('uiref-fx-xfer-btn');
	const xferFrom = document.getElementById('uiref-fx-xfer-from');
	const xferTo = document.getElementById('uiref-fx-xfer-to');
	if(xferBtn) xferBtn.addEventListener('click', function() { CerbUI.effects.transfer(xferFrom, xferTo); });

	const pulseBtn = document.getElementById('uiref-fx-pulse-btn');
	if(pulseBtn) pulseBtn.addEventListener('click', function() { CerbUI.effects.pulse(pulseBtn, { times: 3 }); });
})();
</script>
