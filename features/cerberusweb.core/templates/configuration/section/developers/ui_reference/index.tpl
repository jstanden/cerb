<style nonce="{DevblocksPlatform::getRequestNonce()}">
/* Gallery chrome only — demos below use the shared cerb-ui-* classes from cerb.css */
.cerb-uiref-component { border-radius:1em; margin-top: 2.5em; margin-bottom: 5em; padding: 1.5em 1.5em 3em 1.5em; background-color: var(--cerb-color-background-contrast-240); }
.cerb-uiref-component--label { display:flex; align-items:center; gap:0.4em; font-size:2em; color:var(--cerb-color-text); border-bottom:1px solid var(--cerb-color-background-contrast-230); padding-bottom:0.4em; margin-bottom:1em; }

/* Each example = one demo + its code block(s); spaced apart within a component */
.cerb-uiref-example { padding-left: 1em; margin-bottom: 2.5em; border-left: 0.5em solid var(--cerb-color-background-contrast-230); }
.cerb-uiref-example:last-child { margin-bottom: 0; }

.cerb-uiref-demo { border-radius:8px; padding:1.25em; background:var(--cerb-color-background); }
.cerb-uiref-result { margin-top:0.75em; color:var(--cerb-color-background-contrast-150); }

/* Code reference: lives below the demo for full width (may go side-by-side for small components later) */
.cerb-uiref-code { position:relative; margin-top:0.85em; }
.cerb-uiref-code pre { margin:0; overflow:auto; padding:1.5em; border:1px dashed var(--cerb-color-background-contrast-220); border-radius:8px; color: var(--cerb-color-background-contrast-150); line-height:1.5; tab-size:2; }
.cerb-uiref-copy { position:absolute; top:0.5em; right:0.5em; display:inline-flex; align-items:center; gap:0.35em; }

/* Utilities: break the demo/code convention — a 2-col grid of clickable class name (click to copy) + live example, one per row */
.cerb-uiref-utils { display:grid; grid-template-columns:max-content 1fr; gap:1em 1.5em; align-items:center; }
.cerb-uiref-utils--name { justify-self:start; cursor:pointer; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:0.9em; padding:0.2em 0.5em; border:1px dashed var(--cerb-color-background-contrast-220); border-radius:6px; background:var(--cerb-color-background); white-space:nowrap; }
.cerb-uiref-utils--name:hover { background:var(--cerb-color-background-contrast-240); }
.cerb-uiref-utils--note { color:var(--cerb-color-background-contrast-150); font-style:italic; }
.cerb-uiref-utils--full { grid-column:1 / -1; }
.cerb-uiref-utils--bar { height:1em; background:var(--cerb-color-link); border-radius:4px; }
.cerb-uiref-utils--box { display:inline-flex; justify-content:center; background:var(--cerb-color-background-contrast-230); border-radius:4px; }
/*.cerb-uiref-utils--box > span { background:var(--cerb-color-background); border-radius:3px; }*/
/* Margin demos: a dashed reference frame so the box's margin reads as space pushing it in from its container */
.cerb-uiref-utils--frame { display:inline-flex; border:1px dashed var(--cerb-color-background-contrast-200); border-radius:6px; }
.cerb-uiref-utils--frame .cerb-uiref-utils--box { padding:0.15em 0.5em; }

/* Color-scale demo: rows of palette swatches + scale-keyed chips */
.cerb-uiref-swatches { display:flex; flex-wrap:wrap; gap:6px; align-items:center; margin-top:0.3em; }
.cerb-uiref-swatch { width:24px; height:24px; border-radius:4px; }
.cerb-uiref-swatches .cerb-uiref-key { display:inline-flex; align-items:center; gap:0.4em; margin-right:1em; }
.cerb-uiref-swatches .cerb-uiref-key > i { width:14px; height:14px; border-radius:3px; }
.cerb-uiref-mutelabel { color:var(--cerb-color-background-contrast-150); font-size:0.85em; }

/* Sortable demo: a gapped column of draggable rows + an inline grip handle */
.cerb-uiref-sortlist { display:flex; flex-direction:column; gap:6px; max-width:320px; }
.cerb-uiref-sortlist .cerb-ui-panel { display:flex; align-items:center; gap:0.5em; margin:0; padding:0.6em 0.9em; }
.uiref-grip { color:var(--cerb-color-background-contrast-180); }

/* Table of contents: wrapped chip links to each component */
.cerb-uiref-toc { display:flex; flex-wrap:wrap; gap:0.5em; margin-bottom:3em; }
.cerb-uiref-toc a { padding:0.25em 0.7em; border:1px solid var(--cerb-color-background-contrast-220); border-radius:999px; color:var(--cerb-color-link); text-decoration:none; font-size:1.2em; }
.cerb-uiref-toc a:hover { background:var(--cerb-color-background-contrast-240); }

/* Icon browser: a tight grid by default; --labeled shows names + widens the cells */
.cerb-uiref-icons-bar { display:flex; align-items:center; gap:1em; flex-wrap:wrap; margin-bottom:1em; }
.cerb-uiref-icons-bar input[type=search] { padding:0.4em 0.6em; }
.cerb-uiref-icons { display:grid; grid-template-columns:repeat(auto-fill, minmax(42px, 1fr)); gap:0.4em; }
.cerb-uiref-icons--labeled { grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); }
.cerb-uiref-icon { display:flex; flex-direction:column; align-items:center; gap:0.3em; padding:0.5em 0.3em; border-radius:6px; cursor:pointer; color:var(--cerb-color-text); }
.cerb-uiref-icon:hover { background:var(--cerb-color-background); color:var(--cerb-color-link); box-shadow:0 1px 4px rgba(0,0,0,0.15); }
.cerb-uiref-icon > .cerb-icons { font-size:1.6em; }
.cerb-uiref-icon--label { display:none; font-size:0.7em; color:var(--cerb-color-background-contrast-150); text-align:center; word-break:break-word; }
.cerb-uiref-icons--labeled .cerb-uiref-icon--label { display:block; }
</style>

<div class="cerb-ui-page">
	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title">UI Reference</div>
			<div class="cerb-ui-header--subtitle">Reusable <code>cerb-ui-*</code> components for Cerb's design system</div>
		</div>
	</div>

	<nav class="cerb-uiref-toc">
		<a href="#uiref-c-page">Page</a>
		<a href="#uiref-c-icon">Icon</a>
		<a href="#uiref-c-button">Button</a>
		<a href="#uiref-c-header">Header</a>
		<a href="#uiref-c-panel">Panel</a>
		<a href="#uiref-c-chip">Chip</a>
		<a href="#uiref-c-tile">Tile</a>
		<a href="#uiref-c-pill">Pill</a>
		<a href="#uiref-c-separator">Separator</a>
		<a href="#uiref-c-toggle">Toggle</a>
		<a href="#uiref-c-switcher">Switcher</a>
		<a href="#uiref-c-color-scale">Color scale</a>
		<a href="#uiref-c-legend">Legend</a>
		<a href="#uiref-c-distribution-bar">Distribution bar</a>
		<a href="#uiref-c-tooltip">Tooltip</a>
		<a href="#uiref-c-sparkchart">Sparkchart</a>
		<a href="#uiref-c-timering">TimeRing</a>
		<a href="#uiref-c-pip">Pip</a>
		<a href="#uiref-c-menu">Menu</a>
		<a href="#uiref-c-spinner">Spinner</a>
		<a href="#uiref-c-sortable">Sortable</a>
		<a href="#uiref-c-tabs">Tabs</a>
		<a href="#uiref-c-utilities">Utilities</a>
	</nav>

	<div class="cerb-uiref-component" id="uiref-c-page">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Page</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-page cerb-ui-page--max-width">
					...Page content...
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-page cerb-ui-page--max-width"&gt;
	...Page content...
&lt;/div&gt;</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-icon">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Icon</div>

		<div class="cerb-uiref-icons-bar">
			<input type="search" id="uiref-icon-filter" placeholder="Filter icons…">
			<label style="display:inline-flex;align-items:center;gap:0.4em;cursor:pointer;"><input type="checkbox" id="uiref-icon-labels"> Show labels</label>
			<span class="cerb-uiref-utils--note">Use <code>&lt;span class="cerb-icons cerb-icon-NAME"&gt;&lt;/span&gt;</code> — click any icon to copy its markup.</span>
		</div>

		<div class="cerb-uiref-icons" id="uiref-icon-grid">
		{foreach from=$icons_cerb item=icon}
			<div class="cerb-uiref-icon" data-icon-name="{$icon}" title="Click to copy">
				<span class="cerb-icons cerb-icon-{$icon}"></span>
				<span class="cerb-uiref-icon--label">{$icon}</span>
			</div>
		{/foreach}
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-button">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Button</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button"><span class="cerb-icons cerb-icon-circle-ok"></span> Save changes</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;button type="button" class="cerb-ui-button"&gt;&lt;span class="cerb-icons cerb-icon-circle-ok"&gt;&lt;/span&gt; Save changes&lt;/button&gt;</pre>
			</div>
		</div>

		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Subtle (secondary) &mdash; de-emphasized next to a primary button</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button"><span class="cerb-icons cerb-icon-edit"></span> Edit</button>
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-play"></span> Run now</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;button type="button" class="cerb-ui-button cerb-ui-button--subtle"&gt;&lt;span class="cerb-icons cerb-icon-play"&gt;&lt;/span&gt; Run now&lt;/button&gt;</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-header">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Header</div>

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

	<div class="cerb-uiref-component" id="uiref-c-panel">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Panel</div>

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

	<div class="cerb-uiref-component" id="uiref-c-chip">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Chip</div>

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
	</div>

	<div class="cerb-uiref-component" id="uiref-c-tile">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Tile</div>

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
	</div>

	<div class="cerb-uiref-component" id="uiref-c-pill">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Pill</div>

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
	</div>

	<div class="cerb-uiref-component" id="uiref-c-separator">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Separator</div>

		{* Example: labeled (solid) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Solid line with text</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-separator">archive after 7 days</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-separator"&gt;archive after 7 days&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: labeled (dashed) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Dashed line with text</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-separator cerb-ui-separator--dashed">archive after 7 days</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-separator cerb-ui-separator--dashed"&gt;archive after 7 days&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: empty = a plain rule *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Solid line</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-separator"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-separator"&gt;&lt;/div&gt;</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-toggle">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Toggle</div>

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

	<div class="cerb-uiref-component" id="uiref-c-switcher">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Switcher</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-switcher" id="uiref-toggle-demo">
					<button type="button" class="cerb-ui-switcher--active" data-value="objects">Objects</button>
					<button type="button" data-value="size">Size</button>
				</div>
				<div class="cerb-uiref-result">Selected: <b id="uiref-toggle-out">objects</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-switcher"&gt;
	&lt;button type="button" class="cerb-ui-switcher--active" data-value="objects"&gt;Objects&lt;/button&gt;
	&lt;button type="button" data-value="size"&gt;Size&lt;/button&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// Optional: wire behavior with the component (bare HTML above works without it)
new CerbUI.Switcher(el, {
	onSelect: function(value, button, toggle) { /* … */ }, // fires on change
	// value: 'objects',                  // initial selection (default: stored / .cerb-ui-switcher--active / first button)
	// storageKey: 'cerb.storage.metric', // persist the selection in localStorage (default: none)
});</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-color-scale">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-color-palette"></span>Color scale</div>

		{* The palettes + ordinal scale the charts/legends color from. Listed before Legend, since Legend uses it. *}
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-header--label">Palettes (colored by index)</div>
				<div class="cerb-uiref-mutelabel">category10</div>
				<div id="uiref-palette-category10" class="cerb-uiref-swatches"></div>
				<div class="cerb-uiref-mutelabel" style="margin-top:0.5em;">rainbow</div>
				<div id="uiref-palette-rainbow" class="cerb-uiref-swatches"></div>

				<div class="cerb-ui-header--label" style="margin-top:1em;"><code>colorScale()</code> &mdash; assigns the next color on first sight of a key, then memoizes it</div>
				<div id="uiref-colorscale" class="cerb-uiref-swatches"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Built-in palettes — arrays of hex, colored by index
CerbUI.palettes.category10;   // 10 colors (D3 category10)
CerbUI.palettes.rainbow;      // 12 colors

// An ordinal scale: color(key) assigns the next palette color on first sight of a key, then memoizes it.
// Share ONE scale across charts + legends so the same key is the same color everywhere.
const scale = CerbUI.colorScale();   // or CerbUI.colorScale('rainbow')
scale.color('invocations');    // -> palette[0]
scale.color('avg duration');   // -> palette[1]
scale.color('invocations');    // -> palette[0] again (memoized){/literal}</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-legend">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Legend</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-legend" id="uiref-legend">
					<div data-label="Disk" data-value="1234"></div>
					<div data-label="Database" data-value="560"></div>
					<div data-label="Amazon S3" data-value="89"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- each child div carries only data; JS builds the swatch / label / value / % --&gt;
&lt;div class="cerb-ui-legend"&gt;
	&lt;div data-label="Disk" data-value="1234"&gt;&lt;/div&gt;
	&lt;div data-label="Database" data-value="560"&gt;&lt;/div&gt;
	&lt;div data-label="Amazon S3" data-value="89"&gt;&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.Legend(el, {
	// key: 'objects',        // which data-value-* metric to show (default: data-value)
	// palette: 'category10', // swatch colors by item index (default category10)
	// scale: sharedScale,    // a CerbUI.colorScale() to color by key across charts (default: none)
	// percent: true,         // show each item's % of the sum (default true; false = value only)
});</pre>
			</div>
		</div>

		{* Example: a key, not a distribution — line/bar swatches, no values (matches a Sparkchart's series) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Chart key: <code>data-type="bar|line"</code> swatches, no values (<code>percent:false</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-legend" id="uiref-legend-types">
					<div data-label="invocations" data-type="bar"></div>
					<div data-label="avg duration" data-type="line"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-legend"&gt;
	&lt;div data-label="invocations" data-type="bar"&gt;&lt;/div&gt;
	&lt;div data-label="avg duration" data-type="line"&gt;&lt;/div&gt;
&lt;/div&gt;

&lt;script&gt;new CerbUI.Legend(el, { percent: false });&lt;/script&gt;</pre>
			</div>
		</div>

		{* Example: a vertical stats stack — label + value per row, no % *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Vertical stats stack (<code>--vertical</code>, value only)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-legend cerb-ui-legend--vertical" id="uiref-legend-stats">
					<div data-label="invocations" data-type="bar" data-value="842" data-text="842"></div>
					<div data-label="avg duration" data-type="line" data-value="172" data-text="172ms"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-legend cerb-ui-legend--vertical"&gt;
	&lt;div data-label="invocations" data-type="bar"  data-value="842" data-text="842"&gt;&lt;/div&gt;
	&lt;div data-label="avg duration" data-type="line" data-value="172" data-text="172ms"&gt;&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.Legend(el, { percent: false });</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-distribution-bar">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Distribution bar</div>

		{* Example: a bar with a metric toggle + an auto-generated matching legend *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Bar with a metric toggle and matching legend</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-panel">
					<div class="cerb-ui-header">
						<div class="cerb-ui-header--label">Distribution</div>
						<div class="cerb-ui-header--right">
							<div class="cerb-ui-switcher" id="uiref-dist-toggle">
								<button type="button" class="cerb-ui-switcher--active" data-value="objects">Objects</button>
								<button type="button" data-value="size">Size</button>
							</div>
						</div>
					</div>

					<div class="cerb-ui-distbar" id="uiref-distbar">
						<span data-label="Disk" data-value-objects="1234" data-value-size="2254857830" data-text-size="2.1 GB"></span>
						<span data-label="Database" data-value-objects="560" data-value-size="104857600" data-text-size="100 MB"></span>
						<span data-label="Amazon S3" data-value-objects="89" data-value-size="5368709120" data-text-size="5.0 GB"></span>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-distbar"&gt;
	&lt;span data-label="Disk" data-value-objects="1234" data-value-size="2254857830" data-text-size="2.1 GB"&gt;&lt;/span&gt;
	&lt;span data-label="Database" data-value-objects="560" data-value-size="104857600" data-text-size="100 MB"&gt;&lt;/span&gt;
	&lt;span data-label="Amazon S3" data-value-objects="89" data-value-size="5368709120" data-text-size="5.0 GB"&gt;&lt;/span&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const bar = new CerbUI.Distbar(el, {
	key: 'objects',          // which data-value-* metric to size by (default: data-value)
	legend: true,            // also build a matching CerbUI.Legend below it (default: false)
	// palette: 'category10', // colors segments by index (default category10)
	// scale: sharedScale,    // a CerbUI.colorScale() to color by key across charts (default: none)
});

// a Toggle can re-key the bar (which forwards to its legend)
new CerbUI.Switcher(toggleEl, { onSelect: function(value) { bar.setKey(value); } });</pre>
			</div>
		</div>

		{* Example: two bars sharing one color scale — the same key keeps the same color across charts *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Two bars sharing one color scale (consistent colors + legends)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-distbar" id="uiref-distbar-a">
					<span data-label="Disk" data-value="1234"></span>
					<span data-label="Database" data-value="560"></span>
					<span data-label="Amazon S3" data-value="89"></span>
				</div>
				<br>
				<div class="cerb-ui-distbar" id="uiref-distbar-b">
					<span data-label="Amazon S3" data-value="89"></span>
					<span data-label="Database" data-value="560"></span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- segments keyed by data-color-key, falling back to data-label --&gt;
&lt;div class="cerb-ui-distbar" id="bar-a"&gt;
	&lt;span data-label="Disk" data-value="1234"&gt;&lt;/span&gt;
	&lt;span data-label="Database" data-value="560"&gt;&lt;/span&gt;
	&lt;span data-label="Amazon S3" data-value="89"&gt;&lt;/span&gt;
&lt;/div&gt;
&lt;div class="cerb-ui-distbar" id="bar-b"&gt;
	&lt;span data-label="Amazon S3" data-value="89"&gt;&lt;/span&gt;
	&lt;span data-label="Database" data-value="560"&gt;&lt;/span&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// Share one scale across bars/legends -&gt; same key, same color everywhere
const scale = CerbUI.colorScale();
new CerbUI.Distbar(document.getElementById('bar-a'), { scale, legend: true });
new CerbUI.Distbar(document.getElementById('bar-b'), { scale, legend: true });</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-tooltip">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Tooltip</div>

		{* Example: a floating panel positioned by JS (hover the target) — reused by charts + Menu later *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Floating panel positioned by JS (hover the target)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-tooltip-target">Hover me</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// one instance per trigger; content is a string OR a built DOM node
const tip = new CerbUI.Tooltip({
	// gap: 10, // px between the point and the panel (default 10)
});

el.addEventListener('mouseenter', e =&gt; tip.show('&lt;b&gt;Tooltip&lt;/b&gt;&lt;br&gt;Floating, viewport-aware panel.', e.clientX, e.clientY));
el.addEventListener('mousemove',  e =&gt; tip.move(e.clientX, e.clientY)); // reposition, same content
el.addEventListener('mouseleave', () =&gt; tip.hide());</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-sparkchart">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Sparkchart</div>

		{* Roomy (popups): bars + line over shared categories; built-in tooltip on hover *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Roomy (popups); hover a column for the built-in tooltip</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-sparkchart" id="uiref-sparkchart" style="max-width:340px;"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// categorical (not time-based); the backend pre-bins labels + preformats `text`
const chart = new CerbUI.Sparkchart(el, {
	categories: ['10:00', '11:00', /* … */ 'now'],  // x labels (tooltip + extents)
	series: [
		{ type:'bar',  label:'invocations',  values:[27, /* … */], text:['27 runs', /* … */] },
		{ type:'line', label:'avg duration', values:[172, /* … */], text:['172ms', /* … */] },
	],
	caption: ['24h ago', 'now'],  // extents below the plot: [start,end] at the ends, a single string centered, omit = none
	height: 56,                   // plot height, px
	barWidth: 0.6,                // bar width as a fraction of the category band (gap = the rest)
	// ticks: true,               // one tick per category below the bars (default); false = a bare sparkline
	// tooltip: true,             // built-in hover tooltip (one shared instance); set false for events-only
	// palette: 'category10',     // series colored by index; or set series[].color
	// scale: sharedScale,        // a CerbUI.colorScale() to color series by label (matches a legend)
});{/literal}</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// or fully own the interaction: disable the built-in tooltip and subscribe to events
new CerbUI.Sparkchart(el, { tooltip: false, categories: [/* … */], series: [/* … */] });
// detail = index, category, point:{x,y}, series:[{color,text,label,value}]
el.addEventListener('cerb-ui-sparkchart:hover', e =&gt; renderMyTooltip(e.detail));
el.addEventListener('cerb-ui-sparkchart:click', e =&gt; openJob(e.detail));{/literal}</pre>
			</div>
		</div>

		{* Compact bare sparkline (worklists / panels): smaller, thinner bars, ticks + caption off *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Compact, bare sparkline (worklists / panels): <code>ticks:false</code>, no caption</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-sparkchart" id="uiref-sparkchart-compact" style="max-width:260px;"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.Sparkchart(el, { height: 40, barWidth: 0.5, ticks: false, categories: [/* … */], series: [/* … */] });{/literal}</pre>
			</div>
		</div>

		{* Chart + a Legend sharing one color scale — same series name -> same color in both *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Paired with a Legend via a shared <code>scale</code> (same series name &rarr; same color)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex;align-items:center;gap:1.5em;">
					<div class="cerb-ui-sparkchart" id="uiref-spark-legend-chart" style="flex:1 1 auto;"></div>
					<div class="cerb-ui-legend cerb-ui-legend--vertical" id="uiref-spark-legend-stats">
						<div data-label="invocations" data-type="bar" data-value="842" data-text="842"></div>
						<div data-label="avg duration" data-type="line" data-value="172" data-text="172ms"></div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// One shared scale: the chart and the legend color the same series identically (they aren't wired together)
const scale = CerbUI.colorScale();
new CerbUI.Sparkchart(chartEl, { scale, categories: [/* … */], series: [
	{ type: 'bar',  label: 'invocations',  values: [/* … */], text: [/* … */] },
	{ type: 'line', label: 'avg duration', values: [/* … */], text: [/* … */] },
]});
new CerbUI.Legend(statsEl, { scale, percent: false });{/literal}</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-timering">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>TimeRing</div>

		{* Example: a countdown ring the page drives via setters (no internal timer) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Countdown ring (the page drives it via setters)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-time-ring" id="uiref-timering"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- empty container; CerbUI.TimeRing builds the SVG + center text --&gt;
&lt;div class="cerb-ui-time-ring"&gt;&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// options are optional initial values:
const ring = new CerbUI.TimeRing(el, {
	value: '60s',   // center value (big)
	key: 'next',    // static sub-label under it (small; CSS-uppercased -> NEXT)
	fraction: 0,    // initial fill, 0..1
	size: 38,       // ring diameter, px
	stroke: 3.5,    // arc width, px
});

// the page updates it (e.g. once a second); the ring has no timer of its own
ring.setFraction((total - remaining) / total); // 0..1 of the ring filled
ring.setValue(remaining + 's');</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-pip">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Pip</div>

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

	<div class="cerb-uiref-component" id="uiref-c-menu">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-menu-hamburger"></span>Menu</div>

		{* Example: small cascading menu — icons via onRenderItem, an empty-LI separator, submenus *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Cascading menu: icons (onRenderItem), a separator, submenus &mdash; keyboard nav (&uarr;&darr; &rarr;&larr; Enter Esc Home End)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-menu-small-trigger"><span class="cerb-icons cerb-icon-menu-hamburger"></span> Open menu</button>
				<ul id="uiref-menu-small" hidden>
					<li data-id="new" data-icon="file-document">New
						<ul>
							<li data-id="new.doc">Document</li>
							<li data-id="new.folder">Folder</li>
						</ul>
					</li>
					<li data-id="open" data-icon="folder">Open</li>
					<li data-id="save" data-icon="inbox">Save</li>
					<li></li>
					<li data-id="export" data-icon="download">Export
						<ul>
							<li data-id="export.pdf">PDF</li>
							<li data-id="export.csv">CSV</li>
							<li data-id="export.json">JSON</li>
						</ul>
					</li>
					<li data-id="quit" data-icon="sign-out">Quit</li>
				</ul>
				<div class="cerb-uiref-result">Selected: <b id="uiref-menu-small-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- progressive enhancement: an authored UL&gt;LI; submenu = a nested UL; empty LI = separator. --&gt;
&lt;!-- Only data-* is mirrored onto rendered items; icons are injected via onRenderItem (not markup). --&gt;
&lt;button type="button" id="trigger"&gt;Open menu&lt;/button&gt;
&lt;ul id="my-menu" hidden&gt;
	&lt;li data-id="new" data-icon="file-document"&gt;New
		&lt;ul&gt;
			&lt;li data-id="new.doc"&gt;Document&lt;/li&gt;
			&lt;li data-id="new.folder"&gt;Folder&lt;/li&gt;
		&lt;/ul&gt;
	&lt;/li&gt;
	&lt;li data-id="open" data-icon="folder"&gt;Open&lt;/li&gt;
	&lt;li&gt;&lt;/li&gt;
	&lt;li data-id="quit" data-icon="sign-out"&gt;Quit&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const ul = document.getElementById('my-menu');
const trigger = document.getElementById('trigger');

const menu = new CerbUI.Menu(ul, {
	onSelect:        function(li, src, e) { out.textContent = src.dataset.id; }, // leaf click / Enter
	onClose:         function() {},        // menu fully closed (all panels removed)
	// closeOnSelect: true,                // false = stay open after a pick (add several in a row)
	onRenderItem:    function(li, src) {   // after the label, before the arrow — inject icons here
		const icon = src.dataset.icon;       // bare name e.g. "folder"; ".my-icon" = raw class(es) for non-cerb icons
		if(icon) {
			const ico = document.createElement('span');
			ico.className = icon.charAt(0) === '.' ? icon.slice(1).split('.').join(' ') : ('cerb-icons cerb-icon-' + icon);
			ico.style.marginRight = '0.5em';
			li.insertBefore(ico, li.firstChild);
		}
	},
	itemHeight:      28,    // px; MUST match the .cerb-ui-menu--item height
	maxHeight:       380,   // px before a panel scrolls
	virtThreshold:   60,    // virtualize panels larger than this
	openDelay:       80,    // ms hover delay before a submenu opens
	virtBuffer:      6,     // extra rows above/below the visible window
	inline:          false, // render the root in document flow vs. floating
	hoverTrigger:    null,  // element that opens on mouseenter / closes on mouseleave
	hoverGroup:      null,  // links sibling hover menus (only one open per group)
	hoverCloseDelay: 150,   // ms before a hover menu closes after the mouse leaves
	fixed:           false, // position:fixed instead of absolute
	filter:          false, // type-to-filter: start typing to reveal a search box that narrows the list
	filterPlaceholder: 'Filter…',
	filterEmptyText: 'No matches'
});

// open from a trigger (toggle); menu.open(anchor) floats below the anchor
trigger.addEventListener('click', () => menu.isOpen() ? menu.close() : menu.open(trigger));
// CerbUI.Menu.from(ul) -> the instance for a source UL{/literal}</pre>
			</div>
		</div>

		{* Example: the headline — a virtualized 100,000-item menu *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Virtualized: 100,000 items, ~25 DOM nodes (opens instantly, scrolls smoothly). With <code>filter: true</code>, just start typing &mdash; a search box appears, narrows the list, and tucks away when emptied (arrows/Enter to pick)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-menu-huge-trigger"><span class="cerb-icons cerb-icon-database"></span> Open 100k menu</button>
				<ul id="uiref-menu-huge" hidden></ul>
				<div class="cerb-uiref-result">Selected: <b id="uiref-menu-huge-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Build a big source UL once (the menu parses it into a data model; only ~25 LIs ever render)
const frag = document.createDocumentFragment();
for(let i = 0; i &lt; 100000; i++) {
	const li = document.createElement('li');
	li.dataset.id = 'item-' + i;
	li.textContent = 'Item ' + i.toLocaleString();
	frag.appendChild(li);
}
ul.appendChild(frag);

// virtThreshold (60) / itemHeight (28) / maxHeight (380) govern the windowed scroll
// filter:true — start typing to reveal a search box that narrows the flat model (re-windows the matches);
// it hides again when emptied, so it's unobtrusive enough to enable on any menu
const menu = new CerbUI.Menu(ul, { filter: true, onSelect: (li, src) => { out.textContent = src.dataset.id; } });
trigger.addEventListener('click', () => menu.isOpen() ? menu.close() : menu.open(trigger));{/literal}</pre>
			</div>
		</div>

		{* Example: deep cascade (viewport flip/clamp) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Deep cascade &mdash; submenus flip left / clamp when they'd leave the viewport. With <code>filter: true</code>, typing flattens the whole tree: type <code>blank</code> to jump straight to <em>File &rsaquo; New &rsaquo; Document &rsaquo; Blank</em></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-menu-deep-trigger"><span class="cerb-icons cerb-icon-hierarchy"></span> Open deep menu</button>
				<ul id="uiref-menu-deep" hidden>
					<li data-id="file">File
						<ul>
							<li data-id="file.new">New
								<ul>
									<li data-id="file.new.doc">Document
										<ul>
											<li data-id="file.new.doc.blank">Blank</li>
											<li data-id="file.new.doc.tpl">From template</li>
										</ul>
									</li>
									<li data-id="file.new.folder">Folder</li>
								</ul>
							</li>
							<li data-id="file.open">Open</li>
						</ul>
					</li>
					<li data-id="edit">Edit
						<ul>
							<li data-id="edit.cut">Cut</li>
							<li data-id="edit.copy">Copy</li>
						</ul>
					</li>
				</ul>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Same API — depth is just nested ULs. Positioning flips/clamps automatically near edges.
const menu = new CerbUI.Menu(ul, { onSelect: (li, src) => console.log(src.dataset.id) });
trigger.addEventListener('click', () => menu.isOpen() ? menu.close() : menu.open(trigger));{/literal}</pre>
			</div>
		</div>

		{* Example: inline mode — root renders in document flow *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Inline mode &mdash; the root panel renders in document flow (submenus still float)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul id="uiref-menu-inline" hidden>
					<li data-id="dashboards">Dashboards</li>
					<li data-id="reports">Reports
						<ul>
							<li data-id="reports.daily">Daily</li>
							<li data-id="reports.weekly">Weekly</li>
						</ul>
					</li>
					<li></li>
					<li data-id="settings">Settings</li>
				</ul>
				<div class="cerb-uiref-result">Selected: <b id="uiref-menu-inline-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// inline:true mounts the root after the source UL and opens on construction (no trigger).
// Selecting a leaf collapses submenus but leaves the root in place.
new CerbUI.Menu(ul, { inline: true, onSelect: (li, src) => { out.textContent = src.dataset.id; } });{/literal}</pre>
			</div>
		</div>

		{* Example: hover navbar — linked menus that swap on hover *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Hover navbar &mdash; a shared <code>hoverGroup</code> swaps menus as you move between triggers</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-menu-nav1">Configure</button>
				<button type="button" class="cerb-ui-button" id="uiref-menu-nav2">Records</button>
				<ul id="uiref-menu-nav1-src" hidden>
					<li data-id="cfg.mail">Mail</li>
					<li data-id="cfg.bots">Bots</li>
					<li data-id="cfg.plugins">Plugins</li>
				</ul>
				<ul id="uiref-menu-nav2-src" hidden>
					<li data-id="rec.tickets">Tickets</li>
					<li data-id="rec.orgs">Organizations</li>
					<li data-id="rec.workers">Workers</li>
				</ul>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Each trigger gets its own Menu; a shared hoverGroup means hovering one closes the others.
new CerbUI.Menu(ul1, { hoverTrigger: btn1, hoverGroup: 'nav' });
new CerbUI.Menu(ul2, { hoverTrigger: btn2, hoverGroup: 'nav' });{/literal}</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-spinner">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-spinner"></span>Spinner</div>

		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<svg class="cerb-ui-spinner" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45"></circle></svg>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- author the markup (CSS animates it) --&gt;
&lt;svg class="cerb-ui-spinner" viewBox="0 0 100 100"&gt;&lt;circle cx="50" cy="50" r="45"&gt;&lt;/circle&gt;&lt;/svg&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// or build it in JS
el.appendChild(CerbUI.Spinner.create());   // one-shot element
const s = new CerbUI.Spinner(); el.appendChild(s.el);</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-sortable">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-move-vertical"></span>Sortable</div>

		{* Example: a vertical list, drag any row to reorder (helper = the row itself) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Vertical list &mdash; drag a row to reorder; <code>Esc</code> cancels, drop outside snaps back</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-uiref-sortlist" id="uiref-sortable-basic">
					<div class="cerb-ui-panel">First &mdash; Inbox</div>
					<div class="cerb-ui-panel">Second &mdash; Spam</div>
					<div class="cerb-ui-panel">Third &mdash; Sent</div>
					<div class="cerb-ui-panel">Fourth &mdash; Drafts</div>
				</div>
				<span class="cerb-uiref-result" style="margin-top:0.7em;display:inline-block;">Last move: <b id="uiref-sortable-basic-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- any block with child items; give it a gap so placeholders are visible --&gt;
&lt;div id="list" style="display:flex; flex-direction:column; gap:6px;"&gt;
	&lt;div class="cerb-ui-panel"&gt;First&lt;/div&gt;
	&lt;div class="cerb-ui-panel"&gt;Second&lt;/div&gt;
	&lt;div class="cerb-ui-panel"&gt;Third&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const sortable = new CerbUI.Sortable(el, {
	items:      '&gt; *',         // CSS selector for sortable children (default '&gt; *')
	handle:     '',            // selector for a drag handle within each item (default: whole item)
	helper:     'original',    // 'original' | 'clone' | (item) =&gt; HTMLElement  (default 'original')
	distance:   5,             // px the pointer must move before a drag starts (default 5)
	tolerance:  'pointer',     // 'pointer' (midpoint) | 'intersect' (max overlap) (default 'pointer')
	connectWith: [],           // other Sortable container elements for cross-list dragging
	// placeholderClass: '',   // extra class added to both placeholder elements
	// ghostOrigin: false,     // true = origin slot shows a dimmed clone of the item (vs. a dashed box)
	onStart:  function(info) { /* drag activated */ },
	onStop:   function(info) { /* released, before DOM commit */ },
	onSorted: function(info) { /* info = { item, from, to, fromIndex, toIndex } */ },
});

// also fires a DOM event on the container:
el.addEventListener('cerb-ui-sortable:sorted', e =&gt; console.log(e.detail));</pre>
			</div>
		</div>

		{* Example: two lists wired together with connectWith + a drag handle *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Drag handle + two connected lists (<code>handle</code> + <code>connectWith</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div style="display:flex; gap:1em; align-items:flex-start; flex-wrap:wrap;">
					<div class="cerb-uiref-sortlist" id="uiref-sortable-conn-a" style="min-width:200px;">
						<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Apples</div>
						<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Oranges</div>
						<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Pears</div>
					</div>
					<div class="cerb-uiref-sortlist" id="uiref-sortable-conn-b" style="min-width:200px;">
						<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Carrots</div>
						<div class="cerb-ui-panel"><span class="cerb-icons cerb-icon-menu-hamburger uiref-grip"></span>Peas</div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div id="list-a"&gt;
	&lt;div class="cerb-ui-panel"&gt;&lt;span class="grip"&gt;&lt;/span&gt;Apples&lt;/div&gt;
	&lt;div class="cerb-ui-panel"&gt;&lt;span class="grip"&gt;&lt;/span&gt;Oranges&lt;/div&gt;
&lt;/div&gt;
&lt;div id="list-b"&gt;
	&lt;div class="cerb-ui-panel"&gt;&lt;span class="grip"&gt;&lt;/span&gt;Carrots&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// connectWith takes the OTHER list's container element — order doesn't matter,
// instances cross-link lazily via CerbUI.Sortable.from(el)
const a = document.getElementById('list-a');
const b = document.getElementById('list-b');
new CerbUI.Sortable(a, { handle: '.grip', connectWith: [b] });
new CerbUI.Sortable(b, { handle: '.grip', connectWith: [a] });</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-tabs">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-window-top"></span>Tabs</div>

		{* Static panels (#anchor href -> a sibling div), remembered across reloads *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Static tabs &mdash; #anchor panels, <code>remember</code> (localStorage), <code>onTabSelected</code>; keyboard &larr;/&rarr; Home/End</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul id="uiref-tabs-static">
					<li><a href="#uiref-tabs-p1">Overview</a></li>
					<li><a href="#uiref-tabs-p2">Details</a></li>
					<li><a href="#uiref-tabs-p3">History</a></li>
				</ul>
				<div id="uiref-tabs-p1">Overview panel &mdash; static content.</div>
				<div id="uiref-tabs-p2">Details panel &mdash; static content.</div>
				<div id="uiref-tabs-p3">History panel &mdash; static content.</div>
				<div class="cerb-uiref-result">Active tab: <b id="uiref-tabs-static-out">&mdash;</b> <span style="color:var(--cerb-color-background-contrast-150);">(reload the page &mdash; it's remembered)</span></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- #anchor tabs toggle sibling panel divs by id --&gt;
&lt;ul id="my-tabs"&gt;
	&lt;li&gt;&lt;a href="#p1"&gt;Overview&lt;/a&gt;&lt;/li&gt;
	&lt;li&gt;&lt;a href="#p2"&gt;Details&lt;/a&gt;&lt;/li&gt;
&lt;/ul&gt;
&lt;div id="p1"&gt;Overview panel&lt;/div&gt;
&lt;div id="p2"&gt;Details panel&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// every option + method (defaults shown)
const tabs = new CerbUI.Tabs(ul, {
	active:          0,            // initial 0-based index (overrides `remember`)
	remember:        'myTabs',     // persist active tab; localStorage key = `${storagePrefix}[remember]`
	storagePrefix:   'cerb-tabs',  // default 'cerb-tabs'
	onTabSelected:   function(i, tab) {},  // after a tab is shown
	onBeforeTabLoad: function(i, tab) {},  // before activation; return false to cancel
	onAfterTabLoad:  function(i, tab) {},  // panel ready (static/cached now, dynamic after fetch)
	onTabLoadError:  function(i, tab, status) {}, // dynamic fetch failed; return false to suppress the message
});
tabs.select(1);   // activate by index
tabs.refresh();   // re-fetch the active dynamic tab (refresh(i) for a specific one)
tabs.sync();      // re-parse the <ul> after adding/removing <li>
tabs.active; tabs.activeTab; tabs.allTabs; tabs.el;  // getters
tabs.destroy();
CerbUI.Tabs.from(ul);   // -> the instance for a source UL{/literal}</pre>
			</div>
		</div>

		{* Dynamic AJAX panels via genericAjaxGet (proves a nonce'd <script> in the fragment runs) *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Dynamic tabs &mdash; an ajax-args href loads via <code>genericAjaxGet</code> on first activation (spinner &rarr; content); the fragment's nonce'd &lt;script&gt; runs (no CSP error)</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul id="uiref-tabs-dyn">
					<li><a href="#uiref-tabs-dyn-static">Local</a></li>
					<li><a href="c=config&amp;a=invoke&amp;module=ui_reference&amp;action=tabFragment&amp;which=1">Activity (AJAX)</a></li>
					<li><a href="c=config&amp;a=invoke&amp;module=ui_reference&amp;action=tabFragment&amp;which=2">Audit (AJAX)</a></li>
				</ul>
				<div id="uiref-tabs-dyn-static">A static panel alongside the dynamic ones.</div>
				<div class="cerb-uiref-result">Last load: <b id="uiref-tabs-dyn-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- a non-#  href = a dynamic tab: the href is the ajax args (query after ajax.php?). --&gt;
&lt;!-- The panel div is auto-created after the &lt;ul&gt;; content loads via genericAjaxGet on first click. --&gt;
&lt;ul id="my-tabs"&gt;
	&lt;li&gt;&lt;a href="#local"&gt;Local&lt;/a&gt;&lt;/li&gt;
	&lt;li&gt;&lt;a href="c=config&amp;a=invoke&amp;module=…&amp;action=…"&gt;Activity (AJAX)&lt;/a&gt;&lt;/li&gt;
&lt;/ul&gt;
&lt;div id="local"&gt;A static panel alongside dynamic ones.&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Dynamic tabs load via Cerb's genericAjaxGet (href = the ajax args). It injects the fragment with
// jQuery, so any &lt;script&gt; in the returned HTML runs under the page CSP nonce — no extra wiring needed.
new CerbUI.Tabs(ul, {
	onAfterTabLoad: function(i, tab) { /* tab.isDynamic, tab.href, tab.panel */ },
	onTabLoadError: function(i, tab, status) { /* status = HTTP code or null */ },
});{/literal}</pre>
			</div>
		</div>

		{* Wrapping — a long tab set (common on custom workspaces/dashboards) flows onto multiple rows *}
		<div class="cerb-ui-header"><div class="cerb-ui-header--label">Wrapping &mdash; a long tab set (custom workspaces/dashboards do this) flows onto multiple rows; the active tab stays legible on any row. No extra config &mdash; the strip is a <code>flex-wrap</code> row</div></div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				{assign var=uiref_many_tabs value=['Overview','Inbox','My Tickets','Team Queue','Assigned','Watching','Mentions','Drafts','Sent','Snoozed','SLA Breaches','Escalations','Open','Pending','Waiting','Closed','Spam','Reports','Activity','Calendar','Tasks','Notes','Files','Contacts','Settings']}
				<ul id="uiref-tabs-many">
				{foreach $uiref_many_tabs as $i => $label}
					<li><a href="#uiref-tabs-many-{$i}">{$label}</a></li>
				{/foreach}
				</ul>
				{foreach $uiref_many_tabs as $i => $label}
				<div id="uiref-tabs-many-{$i}">&ldquo;{$label}&rdquo; panel &mdash; static content.</div>
				{/foreach}
				<div class="cerb-uiref-result">Active tab: <b id="uiref-tabs-many-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Nothing special: 25 #anchor tabs. The strip wraps to as many rows as needed. --&gt;
&lt;ul id="my-tabs"&gt;
	&lt;li&gt;&lt;a href="#p1"&gt;Overview&lt;/a&gt;&lt;/li&gt;
	&lt;li&gt;&lt;a href="#p2"&gt;Inbox&lt;/a&gt;&lt;/li&gt;
	&lt;!-- …23 more… --&gt;
&lt;/ul&gt;
&lt;div id="p1"&gt;Overview panel&lt;/div&gt;
&lt;div id="p2"&gt;Inbox panel&lt;/div&gt;</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-utilities">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Utilities</div>

		{* Utilities break the demo/code convention: a 2-col grid — click the class name (left) to copy it; live example on the right *}
		<div class="cerb-uiref-utils">
			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-hide</code>
			<div><span class="cerb-uiref-utils--note">an element with cerb-u-hide sits here &mdash;</span><span class="cerb-u-hide"> you can't see me</span> and isn't rendered.</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-block</code>
			<div><span class="cerb-u-block">first block span</span><span class="cerb-u-block">second block span (stacks below)</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-bold</code>
			<div><span class="cerb-u-bold">bold text</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-w-25</code>
			<div><div class="cerb-uiref-utils--bar cerb-u-w-25"></div></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-w-50</code>
			<div><div class="cerb-uiref-utils--bar cerb-u-w-50"></div></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-w-75</code>
			<div><div class="cerb-uiref-utils--bar cerb-u-w-75"></div></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-w-100</code>
			<div><div class="cerb-uiref-utils--bar cerb-u-w-100"></div></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-border-1</code>
			<div><span class="cerb-ui-tile cerb-u-border-1">1px</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-border-3</code>
			<div><span class="cerb-ui-tile cerb-u-border-3">3px</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-border-5</code>
			<div><span class="cerb-ui-tile cerb-u-border-5">5px (border-1 … border-5)</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-flex-1</code>
			<div style="display:flex;gap:0.5em;"><span class="cerb-uiref-utils--box cerb-u-flex-1"><span>flex-1</span></span><span class="cerb-uiref-utils--box cerb-u-flex-1"><span>flex-1</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-flex-2</code>
			<div style="display:flex;gap:0.5em;"><span class="cerb-uiref-utils--box cerb-u-flex-1"><span>flex-1</span></span><span class="cerb-uiref-utils--box cerb-u-flex-2"><span>flex-2 (twice the width — third + two-thirds)</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-p-1</code>
			<div><span class="cerb-uiref-utils--box cerb-u-p-1"><span>padding</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-p-3</code>
			<div><span class="cerb-uiref-utils--box cerb-u-p-3"><span>padding</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-p-5</code>
			<div><span class="cerb-uiref-utils--box cerb-u-p-5"><span>padding (p-1 &hellip; p-5)</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-m-1</code>
			<div><span class="cerb-uiref-utils--frame"><span class="cerb-uiref-utils--box cerb-u-m-1">margin</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-m-3</code>
			<div><span class="cerb-uiref-utils--frame"><span class="cerb-uiref-utils--box cerb-u-m-3">margin</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-m-5</code>
			<div><span class="cerb-uiref-utils--frame"><span class="cerb-uiref-utils--box cerb-u-m-5">margin (m-1 &hellip; m-5)</span></span></div>

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">Per-side variants: <code>cerb-u-pt-</code> / <code>pr-</code> / <code>pb-</code> / <code>pl-</code> / <code>px-</code> / <code>py-</code> (and <code>cerb-u-mt-</code>, etc.). Steps 0&ndash;5 multiply <code>--cerb-u-spacer</code> (1rem): 0, .25, .5, 1, 1.5, 3&times;.</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	$('[data-cerb-uiref-copy]').on('click', function() {
		const $btn = $(this);
		// The trigger may BE the source (utilities: a clickable class name) or wrap a code block's source
		const $source = $btn.is('[data-cerb-uiref-source]') ? $btn : $btn.closest('.cerb-uiref-code').find('[data-cerb-uiref-source]');
		if(!$source.length) return;
		navigator.clipboard.writeText($source.get(0).textContent);
		Devblocks.createAlert('Copied to clipboard!');
	});

	// Live demo of the Toggle component
	// Toggle (on/off switch)
	const toggleSwitch = document.getElementById('uiref-toggle-switch');
	const toggleState = document.getElementById('uiref-toggle-state');
	if(toggleSwitch && window.CerbUI && CerbUI.Toggle) {
		new CerbUI.Toggle(toggleSwitch, { onChange: function(checked) { if(toggleState) toggleState.textContent = checked ? 'on' : 'off'; } });
	}

	const toggleEl = document.getElementById('uiref-toggle-demo');
	const toggleOut = document.getElementById('uiref-toggle-out');
	if(toggleEl && window.CerbUI && CerbUI.Switcher) {
		new CerbUI.Switcher(toggleEl, {
			onSelect: function(value) { toggleOut.textContent = value; }
		});
	}

	// Standalone Legend
	const legendEl = document.getElementById('uiref-legend');
	if(legendEl && window.CerbUI && CerbUI.Legend) {
		new CerbUI.Legend(legendEl);
	}

	// Distbar with legend:true (auto-generates a matching legend); a Toggle drives both via setKey
	const distbarEl = document.getElementById('uiref-distbar');
	const distToggleEl = document.getElementById('uiref-dist-toggle');
	if(distbarEl && window.CerbUI && CerbUI.Distbar) {
		const bar = new CerbUI.Distbar(distbarEl, { key: 'objects', legend: true });
		if(distToggleEl && CerbUI.Switcher) {
			new CerbUI.Switcher(distToggleEl, {
				onSelect: function(value) { bar.setKey(value); }
			});
		}
	}

	// Sparkchart: bars (invocations) + line (avg duration) over 24 sample categories; built-in tooltip on hover
	if(window.CerbUI && CerbUI.Sparkchart) {
		const cats = [], inv = [], invText = [], dur = [], durText = [];
		for(let h = 0; h < 24; h++) {
			cats.push((h < 10 ? '0' + h : h) + ':00');
			const runs = 8 + Math.round(Math.random() * 24);
			inv.push(runs); invText.push(runs + ' runs');
			const ms = 60 + Math.round(Math.random() * 180);
			dur.push(ms); durText.push(ms + 'ms avg');
		}
		cats[cats.length - 1] = 'now';
		const data = {
			categories: cats,
			series: [
				{ type: 'bar',  label: 'invocations',  values: inv, text: invText },
				{ type: 'line', label: 'avg duration', values: dur, text: durText },
			],
		};

		const roomy = document.getElementById('uiref-sparkchart');
		if(roomy) new CerbUI.Sparkchart(roomy, Object.assign({ caption: ['24h ago', 'now'] }, data));

		const compact = document.getElementById('uiref-sparkchart-compact');
		if(compact) new CerbUI.Sparkchart(compact, Object.assign({ height: 40, barWidth: 0.5, ticks: false }, data));
	}

	// Tooltip: a floating panel that follows the cursor over the target
	const ttEl = document.getElementById('uiref-tooltip-target');
	if(ttEl && window.CerbUI && CerbUI.Tooltip) {
		const tip = new CerbUI.Tooltip();
		ttEl.addEventListener('mouseenter', function(e) { tip.show('<b>Tooltip</b><br>Floating, viewport-aware panel.', e.clientX, e.clientY); });
		ttEl.addEventListener('mousemove', function(e) { tip.move(e.clientX, e.clientY); });
		ttEl.addEventListener('mouseleave', function() { tip.hide(); });
	}

	// TimeRing: a countdown ring driven by the page (no internal timer) — fills as the next fire approaches
	const ringEl = document.getElementById('uiref-timering');
	if(ringEl && window.CerbUI && CerbUI.TimeRing) {
		const ring = new CerbUI.TimeRing(ringEl, { key: 'next' });
		const total = 60;
		let remaining = total;
		const tick = function() {
			ring.setFraction((total - remaining) / total);
			ring.setValue(remaining + 's');
			remaining = (remaining <= 0) ? total : remaining - 1;
		};
		tick();
		setInterval(tick, 1000);
	}

	// Two distbars sharing one color scale — the same key keeps its color across both
	if(window.CerbUI && CerbUI.Distbar && CerbUI.colorScale) {
		const sharedScale = CerbUI.colorScale();
		['uiref-distbar-a', 'uiref-distbar-b'].forEach(function(id) {
			const el = document.getElementById(id);
			if(el) new CerbUI.Distbar(el, { scale: sharedScale, legend: true });
		});
	}

	// Color scale: paint the built-in palettes as swatches + a live colorScale() keyed demo
	if(window.CerbUI && CerbUI.palettes) {
		const paintPalette = function(id, colors) {
			const host = document.getElementById(id);
			if(!host) return;
			colors.forEach(function(c) {
				const sw = document.createElement('span');
				sw.className = 'cerb-uiref-swatch';
				sw.style.backgroundColor = c;
				sw.title = c;
				host.appendChild(sw);
			});
		};
		paintPalette('uiref-palette-category10', CerbUI.palettes.category10);
		paintPalette('uiref-palette-rainbow', CerbUI.palettes.rainbow);

		const scaleHost = document.getElementById('uiref-colorscale');
		if(scaleHost && CerbUI.colorScale) {
			const scale = CerbUI.colorScale();
			// note "invocations" repeats → it memoizes to the same color (not a new one)
			['invocations', 'avg duration', 'invocations', 'cadence'].forEach(function(key) {
				const color = scale.color(key);
				const chip = document.createElement('span');
				chip.className = 'cerb-uiref-key';
				const dot = document.createElement('i');
				dot.style.backgroundColor = color;
				const label = document.createElement('span');
				label.textContent = key + ' → ' + color;
				chip.append(dot, label);
				scaleHost.appendChild(chip);
			});
		}
	}

	// Legend: chart-key variant — line/bar swatches, no values (percent:false)
	const legendTypesEl = document.getElementById('uiref-legend-types');
	if(legendTypesEl && window.CerbUI && CerbUI.Legend) {
		new CerbUI.Legend(legendTypesEl, { percent: false });
	}

	// Legend: a standalone vertical stats stack (colors by index — invocations=0, avg=1)
	const legendStatsEl = document.getElementById('uiref-legend-stats');
	if(legendStatsEl && window.CerbUI && CerbUI.Legend) {
		new CerbUI.Legend(legendStatsEl, { percent: false });
	}

	// Sparkchart paired with a Legend via one shared scale — same series name colors identically in both
	if(window.CerbUI && CerbUI.Legend && CerbUI.colorScale) {
		const statScale = CerbUI.colorScale();
		const sparkEl = document.getElementById('uiref-spark-legend-chart');
		if(sparkEl && CerbUI.Sparkchart) {
			const cats = [], inv = [], dur = [];
			for(let h = 0; h < 24; h++) {
				cats.push((h < 10 ? '0' + h : h) + ':00');
				inv.push(8 + Math.round(Math.random() * 24));
				dur.push(60 + Math.round(Math.random() * 180));
			}
			cats[cats.length - 1] = 'now';
			new CerbUI.Sparkchart(sparkEl, {
				scale: statScale,
				categories: cats, height: 46, barWidth: 0.55,
				series: [
					{ type: 'bar',  label: 'invocations',  values: inv },
					{ type: 'line', label: 'avg duration', values: dur },
				],
			});
		}
		const statsEl = document.getElementById('uiref-spark-legend-stats');
		if(statsEl) new CerbUI.Legend(statsEl, { scale: statScale, percent: false });
	}

	// Menu: small cascading menu — icons via onRenderItem, a separator, submenus
	(function() {
		const ul = document.getElementById('uiref-menu-small');
		const trigger = document.getElementById('uiref-menu-small-trigger');
		const out = document.getElementById('uiref-menu-small-out');
		if(ul && trigger && window.CerbUI && CerbUI.Menu) {
			const menu = new CerbUI.Menu(ul, {
				onSelect: function(li, src) { if(out) out.textContent = src.dataset.id || li.textContent; },
				onRenderItem: function(li, src) {
					const icon = src.dataset.icon; // bare name -> a cerb-icon; ".foo" -> raw class(es)
					if(icon) {
						const ico = document.createElement('span');
						ico.className = icon.charAt(0) === '.' ? icon.slice(1).split('.').join(' ') : ('cerb-icons cerb-icon-' + icon);
						ico.style.marginRight = '0.5em';
						li.insertBefore(ico, li.firstChild);
					}
				}
			});
			trigger.addEventListener('click', function() { menu.isOpen() ? menu.close() : menu.open(trigger); });
		}
	})();

	// Menu: 100,000 items, virtualized — built lazily on first open
	(function() {
		const ul = document.getElementById('uiref-menu-huge');
		const trigger = document.getElementById('uiref-menu-huge-trigger');
		const out = document.getElementById('uiref-menu-huge-out');
		let menu = null;
		if(ul && trigger && window.CerbUI && CerbUI.Menu) {
			trigger.addEventListener('click', function() {
				if(!menu) {
					const frag = document.createDocumentFragment();
					for(let i = 0; i < 100000; i++) {
						const li = document.createElement('li');
						li.dataset.id = 'item-' + i;
						li.textContent = 'Item ' + i.toLocaleString();
						frag.appendChild(li);
					}
					ul.appendChild(frag);
					menu = new CerbUI.Menu(ul, { filter: true, onSelect: function(li, src) { if(out) out.textContent = src.dataset.id; } });
				}
				menu.isOpen() ? menu.close() : menu.open(trigger);
			});
		}
	})();

	// Menu: deep cascade (viewport flip/clamp)
	(function() {
		const ul = document.getElementById('uiref-menu-deep');
		const trigger = document.getElementById('uiref-menu-deep-trigger');
		if(ul && trigger && window.CerbUI && CerbUI.Menu) {
			const menu = new CerbUI.Menu(ul, { filter: true });
			trigger.addEventListener('click', function() { menu.isOpen() ? menu.close() : menu.open(trigger); });
		}
	})();

	// Menu: inline mode — auto-opens in document flow
	(function() {
		const ul = document.getElementById('uiref-menu-inline');
		const out = document.getElementById('uiref-menu-inline-out');
		if(ul && window.CerbUI && CerbUI.Menu) {
			new CerbUI.Menu(ul, { inline: true, onSelect: function(li, src) { if(out) out.textContent = src.dataset.id; } });
		}
	})();

	// Menu: hover navbar — linked menus via a shared hoverGroup
	(function() {
		if(!(window.CerbUI && CerbUI.Menu)) return;
		[['uiref-menu-nav1', 'uiref-menu-nav1-src'], ['uiref-menu-nav2', 'uiref-menu-nav2-src']].forEach(function(p) {
			const btn = document.getElementById(p[0]);
			const ul = document.getElementById(p[1]);
			if(btn && ul) new CerbUI.Menu(ul, { hoverTrigger: btn, hoverGroup: 'uiref-nav' });
		});
	})();
	// Sortable: a vertical list, reporting each move
	(function() {
		const el = document.getElementById('uiref-sortable-basic');
		const out = document.getElementById('uiref-sortable-basic-result');
		if(el && window.CerbUI && CerbUI.Sortable) {
			new CerbUI.Sortable(el, {
				onSorted: function(info) {
					if(out) out.textContent = (info.item.textContent || '').trim() + ': ' + info.fromIndex + ' → ' + info.toIndex;
				},
			});
		}
	})();

	// Sortable: two lists wired together with connectWith + a drag handle
	(function() {
		const a = document.getElementById('uiref-sortable-conn-a');
		const b = document.getElementById('uiref-sortable-conn-b');
		if(a && b && window.CerbUI && CerbUI.Sortable) {
			new CerbUI.Sortable(a, { handle: '.uiref-grip', connectWith: [b] });
			new CerbUI.Sortable(b, { handle: '.uiref-grip', connectWith: [a] });
		}
	})();

	// Tabs: static panels + remember + onTabSelected
	(function() {
		const ul = document.getElementById('uiref-tabs-static');
		const out = document.getElementById('uiref-tabs-static-out');
		if(ul && window.CerbUI && CerbUI.Tabs) {
			new CerbUI.Tabs(ul, {
				remember: 'uirefStatic',
				onTabSelected: function(i, tab) { if(out) out.textContent = i + ' — ' + (tab.li.textContent || '').trim(); },
			});
		}
	})();

	// Tabs: dynamic AJAX via genericAjaxGet (proves the fragment's nonce'd inline script runs)
	(function() {
		const ul = document.getElementById('uiref-tabs-dyn');
		const out = document.getElementById('uiref-tabs-dyn-out');
		if(ul && window.CerbUI && CerbUI.Tabs) {
			new CerbUI.Tabs(ul, {
				onAfterTabLoad: function(i, tab) { if(out) out.textContent = tab.isDynamic ? ('fetched #' + i) : ('static #' + i); },
				onTabLoadError: function(i, tab, status) { if(out) out.textContent = 'error ' + status; },
			});
		}
	})();

	// Tabs: many static tabs that wrap onto multiple rows (custom workspace / dashboard)
	(function() {
		const ul = document.getElementById('uiref-tabs-many');
		const out = document.getElementById('uiref-tabs-many-out');
		if(ul && window.CerbUI && CerbUI.Tabs) {
			new CerbUI.Tabs(ul, {
				onTabSelected: function(i, tab) { if(out) out.textContent = i + ' — ' + (tab.li.textContent || '').trim(); },
			});
		}
	})();

	// Icon browser: filter by name, click-to-copy markup, toggle labels (hidden by default)
	const $iconGrid = $('#uiref-icon-grid');
	if($iconGrid.length) {
		const $iconItems = $iconGrid.find('[data-icon-name]');

		$('#uiref-icon-filter').on('input search', function() {
			const q = $(this).val().trim().toLowerCase();
			$iconItems.each(function() {
				$(this).toggle(!q || $(this).attr('data-icon-name').indexOf(q) !== -1);
			});
		});

		$iconItems.on('click', function() {
			const markup = '<span class="cerb-icons cerb-icon-' + $(this).attr('data-icon-name') + '"></span>';
			navigator.clipboard.writeText(markup);
			Devblocks.createAlert('Copied icon to clipboard!');
		});

		$('#uiref-icon-labels').on('change', function() {
			$iconGrid.toggleClass('cerb-uiref-icons--labeled', this.checked);
		});
	}
})();
</script>
