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

		{* Example: idle/off — a static dot in the default gray *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Idle / off (static)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<span class="cerb-ui-pip"></span> disabled
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;span class="cerb-ui-pip"&gt;&lt;/span&gt;</pre>
			</div>
		</div>

		{* Example: the six palette hues, emitted by cerb-tag-color-modifiers() *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Hue modifiers &mdash; one per <code>--cerb-color-tag-*</code> palette color. Combine with <code>--live</code> to pulse in that hue</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<span class="cerb-ui-pip cerb-ui-pip--red"></span> red
				&nbsp;&nbsp;
				<span class="cerb-ui-pip cerb-ui-pip--green"></span> green
				&nbsp;&nbsp;
				<span class="cerb-ui-pip cerb-ui-pip--blue"></span> blue
				&nbsp;&nbsp;
				<span class="cerb-ui-pip cerb-ui-pip--gray"></span> gray
				&nbsp;&nbsp;
				<span class="cerb-ui-pip cerb-ui-pip--orange"></span> orange
				&nbsp;&nbsp;
				<span class="cerb-ui-pip cerb-ui-pip--purple"></span> purple
				&nbsp;&nbsp;
				<span class="cerb-ui-pip cerb-ui-pip--live cerb-ui-pip--blue"></span> live (blue)
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;span class="cerb-ui-pip cerb-ui-pip--red"&gt;&lt;/span&gt;

&lt;!-- --live keeps its pulse and takes the hue; the ring is currentColor, so it follows --&gt;
&lt;span class="cerb-ui-pip cerb-ui-pip--live cerb-ui-pip--blue"&gt;&lt;/span&gt;

&lt;!-- When the hue is DATA rather than a palette choice (calendar/datepicker pips are
     arbitrary hex), set `color` inline instead -- there's no class for it: --&gt;
&lt;span class="cerb-ui-pip" style="color:#8a3ffc;"&gt;&lt;/span&gt;</pre>
			</div>
		</div>
	</div>
