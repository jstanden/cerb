	<div class="cerb-uiref-component" id="toggle">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-toggle"></span>Toggle</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<label class="cerb-ui-toggle" id="uiref-toggle-switch">
					<input type="checkbox" checked>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">State: <b id="uiref-toggle-state">on</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;label class="cerb-ui-toggle"&gt;
	&lt;input type="checkbox"&gt;
	&lt;span class="cerb-ui-toggle--slider"&gt;&lt;/span&gt;
&lt;/label&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// optional wiring (the CSS works bare); pass the input or the label
new CerbUI.Toggle(el, {
	onChange: function(checked, input) { /* … */ }, // fires on change
	// checked: true, // set the initial state
});</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// Live demo of the Toggle component
	// Toggle (on/off switch)
	const toggleSwitch = document.getElementById('uiref-toggle-switch');
	const toggleState = document.getElementById('uiref-toggle-state');
	if(toggleSwitch && window.CerbUI && CerbUI.Toggle) {
		new CerbUI.Toggle(toggleSwitch, { onChange: function(checked) { if(toggleState) toggleState.textContent = checked ? 'on' : 'off'; } });
	}
})();
</script>
