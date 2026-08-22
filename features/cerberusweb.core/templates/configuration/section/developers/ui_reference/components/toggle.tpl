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

		{* Example: the six palette hues, emitted by cerb-tag-color-modifiers() *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Hue modifiers &mdash; ON is green by default; one modifier per <code>--cerb-color-tag-*</code> palette color changes it. OFF stays neutral either way</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-flex-wrap">
				<label class="cerb-ui-toggle cerb-ui-toggle--red" title="red">
					<input type="checkbox" checked>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label class="cerb-ui-toggle cerb-ui-toggle--blue" title="blue">
					<input type="checkbox" checked>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label class="cerb-ui-toggle cerb-ui-toggle--green" title="green">
					<input type="checkbox" checked>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label class="cerb-ui-toggle cerb-ui-toggle--gray" title="gray">
					<input type="checkbox" checked>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label class="cerb-ui-toggle cerb-ui-toggle--orange" title="orange">
					<input type="checkbox" checked>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label class="cerb-ui-toggle cerb-ui-toggle--purple" title="purple">
					<input type="checkbox" checked>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label class="cerb-ui-toggle" title="off">
					<input type="checkbox">
					<span class="cerb-ui-toggle--slider"></span>
				</label>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- For a switch where green would MISLEAD -- arming something destructive, say. --&gt;
&lt;label class="cerb-ui-toggle cerb-ui-toggle--red"&gt;
	&lt;input type="checkbox"&gt;
	&lt;span class="cerb-ui-toggle--slider"&gt;&lt;/span&gt;
&lt;/label&gt;

&lt;!-- A one-off outside the palette sets the variable the modifiers set: --&gt;
&lt;label class="cerb-ui-toggle" style="--cerb-ui-toggle-color:var(--cerb-color-action-primary);"&gt;…&lt;/label&gt;</pre>
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
