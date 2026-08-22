	<div class="cerb-uiref-component" id="pill">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-rect-rounded"></span>Pill</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<span class="cerb-ui-pill">every 2 minutes</span>
				<span class="cerb-ui-pill">disabled</span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;span class="cerb-ui-pill"&gt;every 2 minutes&lt;/span&gt;</pre>
			</div>
		</div>

		{* Example: the six palette hues, emitted by cerb-tag-color-modifiers() *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Solid variants &mdash; one per <code>--cerb-color-tag-*</code> palette color. Fill, border and white text in one class; use for statuses (sent / received / draft / comment)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-flex-wrap">
				<span class="cerb-ui-pill cerb-ui-pill--red">red</span>
				<span class="cerb-ui-pill cerb-ui-pill--green">green</span>
				<span class="cerb-ui-pill cerb-ui-pill--blue">blue</span>
				<span class="cerb-ui-pill cerb-ui-pill--gray">gray</span>
				<span class="cerb-ui-pill cerb-ui-pill--orange">orange</span>
				<span class="cerb-ui-pill cerb-ui-pill--purple">purple</span>
				<span class="cerb-ui-pill" style="--cerb-ui-pill-color:var(--cerb-color-action-primary);color:rgb(255,255,255);">inline</span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;span class="cerb-ui-pill cerb-ui-pill--green"&gt;Sent&lt;/span&gt;

&lt;!-- Fill and border are ONE hue, so both read --cerb-ui-pill-color and a modifier only sets it.
     A one-off outside the palette sets the same var inline (white text isn't implied there): --&gt;
&lt;span class="cerb-ui-pill" style="--cerb-ui-pill-color:var(--cerb-color-action-primary);color:rgb(255,255,255);"&gt;inline&lt;/span&gt;</pre>
			</div>
		</div>

		{* Example: bare inline marker — strip the fill with utilities, set an accent color inline *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Bare marker: strip the fill with utilities, accent color inline</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<span class="cerb-ui-pill cerb-u-bg-none cerb-u-border-0" style="color:var(--cerb-color-link);"><span class="cerb-icons cerb-icon-branch"></span> 2 parallel</span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;span class="cerb-ui-pill cerb-u-bg-none cerb-u-border-0" style="color:var(--cerb-color-link);"&gt;&lt;span class="cerb-icons cerb-icon-branch"&gt;&lt;/span&gt; 2 parallel&lt;/span&gt;</pre>
			</div>
		</div>

		{* Example: circle — an icon-only pill rendered as a perfect circle (a status badge) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Circle &mdash; add <code>cerb-ui-pill--circle</code> for an icon-only perfect circle. Pair with <a href="#avatar">cerb-avatar-badged</a> to pin a type indicator (sent / received / draft / comment) to an avatar's corner</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--red" title="Received"><span class="cerb-icons cerb-icon-download"></span></span>
				<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--green" title="Sent"><span class="cerb-icons cerb-icon-upload"></span></span>
				<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--gray" title="Draft"><span class="cerb-icons cerb-icon-edit"></span></span>
				<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--blue" title="Comment"><span class="cerb-icons cerb-icon-comments"></span></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--green" title="Sent"&gt;&lt;span class="cerb-icons cerb-icon-upload"&gt;&lt;/span&gt;&lt;/span&gt;</pre>
			</div>
		</div>
	</div>
