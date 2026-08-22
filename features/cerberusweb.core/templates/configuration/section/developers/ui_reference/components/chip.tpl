	<div class="cerb-uiref-component" id="chip">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-cpu"></span>Chip</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-chip">
					<div class="cerb-ui-chip--head">Database</div>
					<div><div class="cerb-ui-chip--label">Data</div><div class="cerb-ui-chip--value">1.2 GB</div></div>
					<div><div class="cerb-ui-chip--label">Indexes</div><div class="cerb-ui-chip--value">340 MB</div></div>
					<div><div class="cerb-ui-chip--label">DB Disk</div><div class="cerb-ui-chip--value">1.6 GB</div></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-chip"&gt;
	&lt;div class="cerb-ui-chip--head"&gt;Database&lt;/div&gt;
	&lt;div&gt;&lt;div class="cerb-ui-chip--label"&gt;Data&lt;/div&gt;&lt;div class="cerb-ui-chip--value"&gt;1.2 GB&lt;/div&gt;&lt;/div&gt;
	&lt;div&gt;&lt;div class="cerb-ui-chip--label"&gt;Indexes&lt;/div&gt;&lt;div class="cerb-ui-chip--value"&gt;340 MB&lt;/div&gt;&lt;/div&gt;
	&lt;div&gt;&lt;div class="cerb-ui-chip--label"&gt;DB Disk&lt;/div&gt;&lt;div class="cerb-ui-chip--value"&gt;1.6 GB&lt;/div&gt;&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: the six palette hues, emitted by cerb-tag-color-modifiers() *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Hue modifiers &mdash; one per <code>--cerb-color-tag-*</code> palette color. Accents the frame and the head cell, for telling several chips apart in a row. The data cells stay neutral</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-flex-wrap">
				<div class="cerb-ui-chip cerb-ui-chip--blue">
					<div class="cerb-ui-chip--head">Database</div>
					<div><div class="cerb-ui-chip--label">Disk</div><div class="cerb-ui-chip--value">1.6 GB</div></div>
				</div>
				<div class="cerb-ui-chip cerb-ui-chip--green">
					<div class="cerb-ui-chip--head">Storage</div>
					<div><div class="cerb-ui-chip--label">Disk</div><div class="cerb-ui-chip--value">840 MB</div></div>
				</div>
				<div class="cerb-ui-chip cerb-ui-chip--purple">
					<div class="cerb-ui-chip--head">Cache</div>
					<div><div class="cerb-ui-chip--label">Keys</div><div class="cerb-ui-chip--value">12,408</div></div>
				</div>
				<div class="cerb-ui-chip cerb-ui-chip--orange">
					<div><div class="cerb-ui-chip--label">Headless</div><div class="cerb-ui-chip--value">frame only</div></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-chip cerb-ui-chip--blue"&gt;
	&lt;div class="cerb-ui-chip--head"&gt;Database&lt;/div&gt;
	&lt;div&gt;&lt;div class="cerb-ui-chip--label"&gt;Disk&lt;/div&gt;&lt;div class="cerb-ui-chip--value"&gt;1.6 GB&lt;/div&gt;&lt;/div&gt;
&lt;/div&gt;

&lt;!-- Frame and head fill are ONE hue, so both read --cerb-ui-chip-color and a modifier only sets
     it. With no --head cell the hue shows on the frame alone. A one-off outside the palette: --&gt;
&lt;div class="cerb-ui-chip" style="--cerb-ui-chip-color:var(--cerb-color-action-primary);"&gt;…&lt;/div&gt;</pre>
			</div>
		</div>
	</div>
