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

		{* Example: status callouts — tinted panel variants replacing the legacy .help-box / .error-box *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Status callouts: <code>--note</code> / <code>--warn</code> / <code>--alert</code> / <code>--success</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--warn">
					<div class="cerb-ui-header">
						<div class="cerb-ui-callout">
							<span class="cerb-icons cerb-icon-clock cerb-ui-callout--icon"></span>
							<div>
								<div class="cerb-ui-header--title-sm">Unfinished Tasks Found</div>
								<div class="cerb-ui-header--subtitle">You have 241 unfinished tasks from previous days across your selected projects</div>
							</div>
						</div>
						<div class="cerb-ui-header--right">
							<button type="button" class="cerb-ui-button"><span class="cerb-icons cerb-icon-right-arrow"></span> Move to Today</button>
						</div>
					</div>
				</div>
				<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note">
					<div class="cerb-ui-header">
						<div class="cerb-ui-callout">
							<span class="cerb-icons cerb-icon-clock cerb-ui-callout--icon"></span>
							<div>
								<div class="cerb-ui-header--title-sm">Scheduled Tasks Ready</div>
								<div class="cerb-ui-header--subtitle">You have 1 stashed task scheduled for now or earlier across your selected projects</div>
							</div>
						</div>
						<div class="cerb-ui-header--right">
							<button type="button" class="cerb-ui-button"><span class="cerb-icons cerb-icon-right-arrow"></span> Move to Today</button>
						</div>
					</div>
				</div>
				<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert">
					<div class="cerb-ui-header">
						<div class="cerb-ui-callout">
							<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
							<div>
								<div class="cerb-ui-header--title-sm">Something went wrong</div>
								<div class="cerb-ui-header--subtitle">A red callout for errors and destructive consequences. The right-side action is optional.</div>
							</div>
						</div>
					</div>
				</div>
				<div class="cerb-ui-panel cerb-ui-panel--success">
					<div class="cerb-ui-header">
						<div class="cerb-ui-callout">
							<span class="cerb-icons cerb-icon-circle-ok cerb-ui-callout--icon"></span>
							<div>
								<div class="cerb-ui-header--title-sm">All set</div>
								<div class="cerb-ui-header--subtitle">A green callout confirming a successful state.</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-panel cerb-ui-panel--warn"&gt;
	&lt;div class="cerb-ui-header"&gt;
		&lt;div class="cerb-ui-callout"&gt;
			&lt;span class="cerb-icons cerb-icon-clock cerb-ui-callout--icon"&gt;&lt;/span&gt;
			&lt;div&gt;
				&lt;div class="cerb-ui-header--title-sm"&gt;Unfinished Tasks Found&lt;/div&gt;
				&lt;div class="cerb-ui-header--subtitle"&gt;You have 241 unfinished tasks&hellip;&lt;/div&gt;
			&lt;/div&gt;
		&lt;/div&gt;
		&lt;div class="cerb-ui-header--right"&gt; {* optional — auto-styled solid in the accent *}
			&lt;button type="button" class="cerb-ui-button"&gt;&lt;span class="cerb-icons cerb-icon-right-arrow"&gt;&lt;/span&gt; Move to Today&lt;/button&gt;
		&lt;/div&gt;
	&lt;/div&gt;
&lt;/div&gt;
{* swap --warn for --note (blue), --alert (red), or --success (green) *}</pre>
			</div>
		</div>
	</div>
