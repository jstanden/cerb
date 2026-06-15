	<div class="cerb-uiref-component" id="switcher">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-adjust"></span>Switcher</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-switcher" id="uiref-toggle-demo">
					<button type="button" class="cerb-ui-switcher--active" data-value="objects">Objects</button>
					<button type="button" data-value="size">Size</button>
				</div>
				<div class="cerb-uiref-result">Selected: <b id="uiref-toggle-out">objects</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-switcher"&gt;
	&lt;button type="button" class="cerb-ui-switcher--active" data-value="objects"&gt;Objects&lt;/button&gt;
	&lt;button type="button" data-value="size"&gt;Size&lt;/button&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// Optional: wire behavior with the component (bare HTML above works without it)
new CerbUI.Switcher(el, {
	onSelect: function(value, button, toggle) { /* … */ }, // fires on change
	// value: 'objects',                  // initial selection (default: stored / .cerb-ui-switcher--active / first button)
	// storageKey: 'cerb.storage.metric', // persist the selection in localStorage (default: none)
});</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const toggleEl = document.getElementById('uiref-toggle-demo');
	const toggleOut = document.getElementById('uiref-toggle-out');
	if(toggleEl && window.CerbUI && CerbUI.Switcher) {
		new CerbUI.Switcher(toggleEl, {
			onSelect: function(value) { toggleOut.textContent = value; }
		});
	}
})();
</script>
