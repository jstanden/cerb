	<div class="cerb-uiref-component" id="panel">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-text"></span>Panel</div>

		{* Example: a card — a --title-sm head with controls (--center aligns title + button) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Card-style title with icon, toolbar, and body</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-panel cerb-ui-panel--spaced">
					<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
						<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-database"></span> Card-style title with icon</div>
						<div class="cerb-ui-header--right">
							<span class="cerb-ui-header--summary">3 objects</span>
							<button type="button" class="cerb-ui-button"><span class="cerb-icons cerb-icon-edit"></span> edit</button>
						</div>
					</div>
					<div>A panel with a medium (<code>--title-sm</code>) head -- the basis for cards. Add <code>--center</code> when the head pairs a title with controls (e.g. a button) so they align on center, not baseline.</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-panel"&gt;
	&lt;div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center"&gt;
		&lt;div class="cerb-ui-header--title-sm"&gt;&lt;span class="cerb-icons cerb-icon-database"&gt;&lt;/span&gt;Card-style title&lt;/div&gt;
		&lt;div class="cerb-ui-header--right"&gt;
			&lt;span class="cerb-ui-header--summary"&gt;3 objects&lt;/span&gt;
			&lt;button type="button" class="cerb-ui-button"&gt;&lt;span class="cerb-icons cerb-icon-edit"&gt;&lt;/span&gt; edit&lt;/button&gt;
		&lt;/div&gt;
	&lt;/div&gt;
	&lt;div&gt;A panel with a medium (title-sm) head; --center aligns a title with its controls.&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: a panel with a --label head + --summary text *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Small label, summary, and body</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-panel cerb-ui-panel--spaced">
					<div class="cerb-ui-header cerb-ui-header--tight">
						<div class="cerb-ui-header--label">Storage distribution</div>
						<div class="cerb-ui-header--summary">12 objects &middot; 3 stores</div>
					</div>
					<div>Panel body -- a distribution bar, legend, or any content.</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-panel cerb-ui-panel--spaced"&gt;
	&lt;div class="cerb-ui-header cerb-ui-header--tight"&gt;
		&lt;div class="cerb-ui-header--label"&gt;Storage distribution&lt;/div&gt;
		&lt;div class="cerb-ui-header--summary"&gt;12 objects &middot; 3 stores&lt;/div&gt;
	&lt;/div&gt;
	&lt;div&gt;Panel body&hellip;&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: an accent-bordered panel *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Accent border</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-panel cerb-ui-panel--accent" style="--cerb-ui-accent: #d62728;">
					<div class="cerb-ui-header cerb-ui-header--tight">
						<div class="cerb-ui-header--label">Accent border</div>
					</div>
					<div>A panel with a left accent -- set <code>--cerb-ui-accent</code> to a palette color.</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-panel cerb-ui-panel--accent" style="--cerb-ui-accent: #d62728;"&gt;
	&lt;div class="cerb-ui-header cerb-ui-header--tight"&gt;
		&lt;div class="cerb-ui-header--label"&gt;Accent border&lt;/div&gt;
	&lt;/div&gt;
	&lt;div&gt;Set --cerb-ui-accent to a palette color.&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>
		</div>
	</div>
