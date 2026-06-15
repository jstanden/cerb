	<div class="cerb-uiref-component" id="header">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-header"></span>Header</div>

		{* Example: title + a --right toolbar of controls *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Title, subtitle, and toolbar</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-header">
					<div>
						<div class="cerb-ui-header--title">Title</div>
						<div class="cerb-ui-header--subtitle">A short descriptive subtitle</div>
					</div>
					<div class="cerb-ui-header--right">
						<button type="button" class="cerb-ui-button"><span class="cerb-icons cerb-icon-funnel"></span> Filter</button>
						<button type="button" class="cerb-ui-button"><span class="cerb-icons cerb-icon-download"></span> Export</button>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- --right: a flex toolbar for controls --&gt;
&lt;div class="cerb-ui-header"&gt;
	&lt;div&gt;
		&lt;div class="cerb-ui-header--title"&gt;Title&lt;/div&gt;
		&lt;div class="cerb-ui-header--subtitle"&gt;A short descriptive subtitle&lt;/div&gt;
	&lt;/div&gt;
	&lt;div class="cerb-ui-header--right"&gt;
		&lt;button type="button" class="cerb-ui-button"&gt;&lt;span class="cerb-icons cerb-icon-funnel"&gt;&lt;/span&gt; Filter&lt;/button&gt;
		&lt;button type="button" class="cerb-ui-button"&gt;&lt;span class="cerb-icons cerb-icon-download"&gt;&lt;/span&gt; Export&lt;/button&gt;
	&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: a --label section head with muted --summary text (--tight for in-panel heads) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Muted label and right-aligned text summary</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-header cerb-ui-header--tight">
					<div class="cerb-ui-header--label">Section label</div>
					<div class="cerb-ui-header--summary">12 objects &middot; 3 stores</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- --summary: muted right-aligned text --&gt;
&lt;div class="cerb-ui-header cerb-ui-header--tight"&gt;
	&lt;div class="cerb-ui-header--label"&gt;Section label&lt;/div&gt;
	&lt;div class="cerb-ui-header--summary"&gt;12 objects &middot; 3 stores&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>
		</div>
	</div>
