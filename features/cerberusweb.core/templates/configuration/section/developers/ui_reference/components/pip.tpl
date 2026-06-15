	<div class="cerb-uiref-component" id="pip">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dot"></span>Pip</div>

		{* Example: live — a pulsing status dot (green) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Live (pulsing)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<span class="cerb-ui-pip cerb-ui-pip--live"></span> active
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;span class="cerb-ui-pip cerb-ui-pip--live"&gt;&lt;/span&gt;</pre>
			</div>
		</div>

		{* Example: idle/off — a static dot; default gray, or set color for another hue *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Idle / off (static); set <code>color</code> for another hue</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<span class="cerb-ui-pip"></span> disabled
				&nbsp;&nbsp;
				<span class="cerb-ui-pip cerb-ui-pip--live" style="color:var(--cerb-color-tag-blue);"></span> live (blue)
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;span class="cerb-ui-pip"&gt;&lt;/span&gt;
&lt;span class="cerb-ui-pip cerb-ui-pip--live" style="color:var(--cerb-color-tag-blue);"&gt;&lt;/span&gt;</pre>
			</div>
		</div>
	</div>
