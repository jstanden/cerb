	<div class="cerb-uiref-component" id="tile">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-square"></span>Tile</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-tile">
					<span class="cerb-ui-tile--icon" style="background:var(--cerb-color-tag-green);"><span class="cerb-icons cerb-icon-folder"></span></span>
					<div class="cerb-ui-tile--text">
						<div class="cerb-ui-tile--kind">active</div>
						<div class="cerb-ui-tile--name">Disk</div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-tile"&gt;
	&lt;span class="cerb-ui-tile--icon" style="background:var(--cerb-color-tag-green);"&gt;&lt;span class="cerb-icons cerb-icon-folder"&gt;&lt;/span&gt;&lt;/span&gt;
	&lt;div class="cerb-ui-tile--text"&gt;
		&lt;div class="cerb-ui-tile--kind"&gt;active&lt;/div&gt;
		&lt;div class="cerb-ui-tile--name"&gt;Disk&lt;/div&gt;
	&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: --lg roomier icon + --body (a sentence/link instead of a short bold name) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Large icon with a description body (<code>--lg</code> + <code>--body</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-tile cerb-ui-tile--lg cerb-ui-tile--block">
					<span class="cerb-ui-tile--icon" style="background:var(--cerb-color-background-contrast-230);color:var(--cerb-color-background-contrast-150);"><span class="cerb-icons cerb-icon-console"></span></span>
					<div class="cerb-ui-tile--text">
						<div class="cerb-ui-tile--kind">Advanced</div>
						<div class="cerb-ui-tile--body">Hit the cron endpoint from an external scheduler with cURL or wget</div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-tile cerb-ui-tile--lg cerb-ui-tile--block"&gt;
	&lt;span class="cerb-ui-tile--icon" style="background:var(--cerb-color-background-contrast-230);color:var(--cerb-color-background-contrast-150);"&gt;&lt;span class="cerb-icons cerb-icon-console"&gt;&lt;/span&gt;&lt;/span&gt;
	&lt;div class="cerb-ui-tile--text"&gt;
		&lt;div class="cerb-ui-tile--kind"&gt;Advanced&lt;/div&gt;
		&lt;div class="cerb-ui-tile--body"&gt;Hit the cron endpoint from an external scheduler with cURL or wget&lt;/div&gt;
	&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: tile-grid — a flex-wrap grid of selectable .cerb-ui-tile-grid--cell tiles with a
		   .cerb-ui-selectall button. The column / field chooser pattern shared by the worklist-widget
		   configs and the record-fields picker. Unselected cells dim to opacity 0.4. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Selectable tile grid (<code>.cerb-ui-tile-grid</code> + <code>--cell</code>) with a select-all button (<code>.cerb-ui-selectall</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-form--label" style="margin:0;">Columns</label>
					<span class="cerb-u-text-muted cerb-u-fs-n1">3 of 5</span>
					<button type="button" class="cerb-ui-selectall" title="Select all"><span class="cerb-icons cerb-icon-checked"></span></button>
				</div>
				<div class="cerb-ui-tile-grid">
					<label class="cerb-ui-tile cerb-ui-tile--block cerb-ui-tile-grid--cell is-selected" data-token="subject">
						<input type="checkbox" class="cerb-u-flex-shrink-0" checked="checked">
						<span class="cerb-u-truncate">Subject</span>
					</label>
					<label class="cerb-ui-tile cerb-ui-tile--block cerb-ui-tile-grid--cell is-selected" data-token="status">
						<input type="checkbox" class="cerb-u-flex-shrink-0" checked="checked">
						<span class="cerb-u-truncate">Status</span>
					</label>
					<label class="cerb-ui-tile cerb-ui-tile--block cerb-ui-tile-grid--cell is-selected" data-token="updated">
						<input type="checkbox" class="cerb-u-flex-shrink-0" checked="checked">
						<span class="cerb-u-truncate">Updated</span>
					</label>
					<label class="cerb-ui-tile cerb-ui-tile--block cerb-ui-tile-grid--cell" data-token="owner">
						<input type="checkbox" class="cerb-u-flex-shrink-0">
						<span class="cerb-u-truncate">Owner</span>
					</label>
					<label class="cerb-ui-tile cerb-ui-tile--block cerb-ui-tile-grid--cell" data-token="org">
						<input type="checkbox" class="cerb-u-flex-shrink-0">
						<span class="cerb-u-truncate">Organization</span>
					</label>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"&gt;
	&lt;label class="cerb-ui-form--label" style="margin:0;"&gt;Columns&lt;/label&gt;
	&lt;span class="cerb-u-text-muted cerb-u-fs-n1"&gt;3 of 5&lt;/span&gt;
	&lt;button type="button" class="cerb-ui-selectall" title="Select all"&gt;&lt;span class="cerb-icons cerb-icon-checked"&gt;&lt;/span&gt;&lt;/button&gt;
&lt;/div&gt;
&lt;div class="cerb-ui-tile-grid"&gt;
	&lt;label class="cerb-ui-tile cerb-ui-tile--block cerb-ui-tile-grid--cell is-selected" data-token="subject"&gt;
		&lt;input type="checkbox" class="cerb-u-flex-shrink-0" checked="checked"&gt;
		&lt;span class="cerb-u-truncate"&gt;Subject&lt;/span&gt;
	&lt;/label&gt;
	&lt;label class="cerb-ui-tile cerb-ui-tile--block cerb-ui-tile-grid--cell" data-token="owner"&gt;
		&lt;input type="checkbox" class="cerb-u-flex-shrink-0"&gt;
		&lt;span class="cerb-u-truncate"&gt;Owner&lt;/span&gt;
	&lt;/label&gt;
&lt;/div&gt;</pre>
			</div>
		</div>
	</div>
