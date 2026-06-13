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
		<a href="#uiref-c-accordion">Accordion</a>
		<a href="#uiref-c-chip">Chip</a>
		<a href="#uiref-c-tile">Tile</a>
		<a href="#uiref-c-pill">Pill</a>
		<a href="#uiref-c-separator">Separator</a>
		<a href="#uiref-c-toggle">Toggle</a>
		<a href="#uiref-c-form">Form</a>
		<a href="#uiref-c-switcher">Switcher</a>
		<a href="#uiref-c-color-scale">Color scale</a>
		<a href="#uiref-c-datepicker">Datepicker</a>
		<a href="#uiref-c-colorpicker">ColorPicker</a>
		<a href="#uiref-c-legend">Legend</a>
		<a href="#uiref-c-distribution-bar">Distribution bar</a>
		<a href="#uiref-c-tooltip">Tooltip</a>
		<a href="#uiref-c-sparkchart">Sparkchart</a>
		<a href="#uiref-c-timering">TimeRing</a>
		<a href="#uiref-c-pip">Pip</a>
		<a href="#uiref-c-menu">Menu</a>
		<a href="#uiref-c-selectmenu">SelectMenu</a>
		<a href="#uiref-c-searchquery">SearchQuery</a>
		<a href="#uiref-c-spinner">Spinner</a>
		<a href="#uiref-c-sortable">Sortable</a>
		<a href="#uiref-c-tabs">Tabs</a>
		<a href="#uiref-c-dialog">Dialog</a>
		<a href="#uiref-c-confirm">Confirm</a>
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
			<div class="cerb-uiref-icon" data-icon-name="{$icon}" title="{$icon}">
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

	<div class="cerb-uiref-component" id="uiref-c-accordion">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chevron-down"></span>Accordion</div>

		{* Example: default single-open — clicking a header opens it and collapses the previous one *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Single-open &mdash; one section at a time; chevron rotates; &uarr;/&darr;/Home/End move between headers</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-accordion-basic" style="max-width:420px;">
					<h3>Getting started</h3>
					<div>Open a section by clicking its header. Opening one collapses the previously open section.</div>
					<h3>Configuration</h3>
					<div>Each header is a button; the following block is its panel. The chevron rotates when expanded.</div>
					<h3>Advanced</h3>
					<div>Use the keyboard: focus a header and press the arrow keys, Home, or End to move between them.</div>
				</div>
				<span class="cerb-uiref-result" style="margin-top:0.7em;display:inline-block;">Expanded section: <b id="uiref-accordion-basic-result">0</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- a container of alternating &lt;h3&gt; (header) / &lt;div&gt; (panel) children --&gt;
&lt;div id="acc"&gt;
	&lt;h3&gt;Getting started&lt;/h3&gt;
	&lt;div&gt;Panel body for the first section.&lt;/div&gt;
	&lt;h3&gt;Configuration&lt;/h3&gt;
	&lt;div&gt;Panel body for the second section.&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const acc = new CerbUI.Accordion(el, {
	active:      0,       // 0-based index open on init; -1 = all collapsed (default 0)
	collapsible: false,   // clicking the open header collapses it (allow all-closed) (default false)
	scrollable:  false,   // height-cap panels (--cerb-ui-accordion-max-height, 300px) (default false)
	onExpand:   function(index, info) { /* info = { index, header, panel } */ },
	onCollapse: function(index, info) { /* ... */ },
});

// also fires a DOM event on the container:
el.addEventListener('cerb-ui-accordion:toggle', e =&gt; console.log(e.detail)); // { index, expanded }

// public API: acc.expand(i); acc.collapse(i); acc.expanded; acc.destroy();</pre>
			</div>
		</div>

		{* Example: collapsible + scrollable *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Collapsible + scrollable (<code>collapsible:true</code>, <code>scrollable:true</code>) &mdash; click an open header to close it; long bodies scroll</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-accordion-scroll" style="max-width:420px; --cerb-ui-accordion-max-height:120px;">
					<h3>Release notes</h3>
					<div>
						<p>Line one of a long changelog.</p>
						<p>Line two — more detail about a change.</p>
						<p>Line three — and yet more text to overflow the cap.</p>
						<p>Line four — this body scrolls within its height cap.</p>
						<p>Line five — keep scrolling to see the rest.</p>
						<p>Line six — the last entry.</p>
					</div>
					<h3>Known issues</h3>
					<div>A shorter section. Click this header while it's open to collapse everything.</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// cap the scroll height per-instance with the CSS variable on the container
// &lt;div id="acc" style="--cerb-ui-accordion-max-height:120px;"&gt; … &lt;/div&gt;
new CerbUI.Accordion(el, { active: -1, collapsible: true, scrollable: true });</pre>
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

		{* Example: arrows (flow direction) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Arrows: flow direction (--arrow-start-right --arrow-end-right)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-separator cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-right">then</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-separator cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-right"&gt;then&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: arrows (inward) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Arrows: inward (--arrow-start-right --arrow-end-left)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-separator cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-left">merge</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-separator cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-left"&gt;merge&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: arrows (outward) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Arrows: outward (--arrow-start-left --arrow-end-right)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-separator cerb-ui-separator--arrow-start-left cerb-ui-separator--arrow-end-right">fan out</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-separator cerb-ui-separator--arrow-start-left cerb-ui-separator--arrow-end-right"&gt;fan out&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: arrow on one end only *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Arrow on one end (--arrow-end-right)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-separator cerb-ui-separator--arrow-end-right">continue</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-separator cerb-ui-separator--arrow-end-right"&gt;continue&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: thick chevron arrows *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Chevron arrows (--thick --arrow-start-right --arrow-end-right)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-separator cerb-ui-separator--thick cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-right">then</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-separator cerb-ui-separator--thick cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-right"&gt;then&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: thick chevron arrows (inward) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Chevron arrows, inward (--thick --arrow-start-right --arrow-end-left)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-separator cerb-ui-separator--thick cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-left">merge</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-separator cerb-ui-separator--thick cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-left"&gt;merge&lt;/div&gt;</pre>
			</div>
		</div>

		{* Example: dashed with arrows *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Dashed with arrows (--dashed --arrow-start-right --arrow-end-right)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-separator cerb-ui-separator--dashed cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-right">async</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-separator cerb-ui-separator--dashed cerb-ui-separator--arrow-start-right cerb-ui-separator--arrow-end-right"&gt;async&lt;/div&gt;</pre>
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

	<div class="cerb-uiref-component" id="uiref-c-form">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>Form</div>

		{* cerb-ui-form on a <form> (or any wrapper) auto-styles the controls inside. For anything beyond a
		   couple of fields, group them into --section panels: the form's gap spaces the sections, and each
		   --section-body re-establishes the field gap *inside* a section. --field = label-above + full-width
		   control; --row = responsive columns; --control = leading icon. *}

		{* Minimal — a bare form whose own gap spaces the fields (no section needed for a small form). *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Minimal — a few fields, no sections</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<form class="cerb-ui-form" style="max-width:420px;">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Name <span class="cerb-ui-form--required">*</span></label>
						<input type="text" value="Kim Li">
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Email <span class="cerb-ui-form--hint">optional</span></label>
						<input type="email" placeholder="name@example.com">
					</div>
				</form>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;form class="cerb-ui-form"&gt;
	&lt;div class="cerb-ui-form--field"&gt;
		&lt;label class="cerb-ui-form--label"&gt;Name &lt;span class="cerb-ui-form--required"&gt;*&lt;/span&gt;&lt;/label&gt;
		&lt;input type="text" name="name"&gt;
	&lt;/div&gt;
	&lt;div class="cerb-ui-form--field"&gt;
		&lt;label class="cerb-ui-form--label"&gt;Email &lt;span class="cerb-ui-form--hint"&gt;optional&lt;/span&gt;&lt;/label&gt;
		&lt;input type="email" name="email"&gt;
	&lt;/div&gt;
&lt;/form&gt;</pre>
			</div>
		</div>

		{* Full — multiple --section panels in one form (gap between them), with rows, leading-icon inputs,
		   a SelectMenu, a Toggle, and help text. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Full — titled sections, rows, icons, SelectMenu, Toggle</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<form class="cerb-ui-form" style="max-width:560px;">
					<div class="cerb-ui-form--section">
						<div class="cerb-ui-form--section-head">Identity</div>
						<div class="cerb-ui-form--section-body">
							<div class="cerb-ui-form--row">
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">First name <span class="cerb-ui-form--required">*</span></label>
									<input type="text" value="Kim">
								</div>
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">Last name</label>
									<input type="text" value="Li">
								</div>
							</div>
							<div class="cerb-ui-form--field">
								<label class="cerb-ui-form--label">Location</label>
								<label class="cerb-ui-form--control">
									<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-location"></span>
									<input type="text" value="San Francisco, CA">
								</label>
							</div>
						</div>
					</div>

					<div class="cerb-ui-form--section">
						<div class="cerb-ui-form--section-head">Contact details</div>
						<div class="cerb-ui-form--section-body">
							<div class="cerb-ui-form--field">
								<label class="cerb-ui-form--label">Primary email <span class="cerb-ui-form--required">*</span></label>
								<label class="cerb-ui-form--control">
									<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mail"></span>
									<input type="email" value="kim.li@acme.example">
								</label>
							</div>
							<div class="cerb-ui-form--field">
								<label class="cerb-ui-form--label">Secondary email <span class="cerb-ui-form--hint">optional</span></label>
								<input type="email" placeholder="alias@company.com">
							</div>
							<div class="cerb-ui-form--row">
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">Phone</label>
									<label class="cerb-ui-form--control">
										<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-phone-handset"></span>
										<input type="tel" value="+1 (415) 555-0182">
									</label>
								</div>
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">Mobile <span class="cerb-ui-form--hint">optional</span></label>
									<input type="tel" placeholder="+1 (555) 000-0000">
								</div>
							</div>
						</div>
					</div>

					<div class="cerb-ui-form--section">
						<div class="cerb-ui-form--section-head">Localization</div>
						<div class="cerb-ui-form--section-body">
							<div class="cerb-ui-form--row">
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">Owner</label>
									<select id="uiref-form-owner">
										<option value="">Unassigned</option>
										<option value="ada" selected>Ada Greene</option>
										<option value="kim">Kim Li</option>
										<option value="sam">Sam Patel</option>
									</select>
								</div>
								<div class="cerb-ui-form--field">
									<label class="cerb-ui-form--label">Timezone <span class="cerb-ui-form--hint">native &lt;select&gt;</span></label>
									<select>
										<option>Pacific (PT) &mdash; US/Canada</option>
										<option>Eastern (ET) &mdash; US/Canada</option>
									</select>
								</div>
							</div>
							<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
								<label class="cerb-ui-toggle"><input type="checkbox" checked><span class="cerb-ui-toggle--slider"></span></label>
								<span class="cerb-ui-form--label">Notify watchers on changes</span>
							</div>
						</div>
					</div>

					<div class="cerb-ui-form--section">
						<div class="cerb-ui-form--section-head">Internal notes <span class="cerb-icons cerb-icon-chevron-down"></span></div>
						<div class="cerb-ui-form--section-body">
							<div class="cerb-ui-form--field">
								<textarea rows="3">Primary technical contact. Escalate P0s directly.</textarea>
								<div class="cerb-ui-form--help">Only visible to team members. Not included in replies.</div>
							</div>
						</div>
					</div>
				</form>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;form class="cerb-ui-form"&gt;
	&lt;div class="cerb-ui-form--section"&gt;
		&lt;div class="cerb-ui-form--section-head"&gt;Identity&lt;/div&gt;
		&lt;div class="cerb-ui-form--section-body"&gt;
			&lt;!-- --row: side-by-side, collapses to one column when narrow --&gt;
			&lt;div class="cerb-ui-form--row"&gt;
				&lt;div class="cerb-ui-form--field"&gt;
					&lt;label class="cerb-ui-form--label"&gt;First name &lt;span class="cerb-ui-form--required"&gt;*&lt;/span&gt;&lt;/label&gt;
					&lt;input type="text" name="first_name"&gt;
				&lt;/div&gt;
				&lt;div class="cerb-ui-form--field"&gt;
					&lt;label class="cerb-ui-form--label"&gt;Last name&lt;/label&gt;
					&lt;input type="text" name="last_name"&gt;
				&lt;/div&gt;
			&lt;/div&gt;
			&lt;!-- --control: a leading icon inside the input --&gt;
			&lt;div class="cerb-ui-form--field"&gt;
				&lt;label class="cerb-ui-form--label"&gt;Location&lt;/label&gt;
				&lt;label class="cerb-ui-form--control"&gt;
					&lt;span class="cerb-ui-form--control-icon cerb-icons cerb-icon-location"&gt;&lt;/span&gt;
					&lt;input type="text" name="location"&gt;
				&lt;/label&gt;
			&lt;/div&gt;
		&lt;/div&gt;
	&lt;/div&gt;

	&lt;div class="cerb-ui-form--section"&gt;
		&lt;div class="cerb-ui-form--section-head"&gt;Contact details&lt;/div&gt;
		&lt;div class="cerb-ui-form--section-body"&gt;
			&lt;div class="cerb-ui-form--field"&gt;
				&lt;label class="cerb-ui-form--label"&gt;Primary email &lt;span class="cerb-ui-form--required"&gt;*&lt;/span&gt;&lt;/label&gt;
				&lt;label class="cerb-ui-form--control"&gt;
					&lt;span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mail"&gt;&lt;/span&gt;
					&lt;input type="email" name="email"&gt;
				&lt;/label&gt;
			&lt;/div&gt;
			&lt;!-- a SelectMenu enhances the native select (wired in JS below) --&gt;
			&lt;div class="cerb-ui-form--field"&gt;
				&lt;label class="cerb-ui-form--label"&gt;Owner&lt;/label&gt;
				&lt;select id="owner"&gt;
					&lt;option value="ada"&gt;Ada Greene&lt;/option&gt;
				&lt;/select&gt;
			&lt;/div&gt;
			&lt;!-- a Toggle works bare (CSS only) --&gt;
			&lt;div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2"&gt;
				&lt;label class="cerb-ui-toggle"&gt;&lt;input type="checkbox" name="notify" checked&gt;&lt;span class="cerb-ui-toggle--slider"&gt;&lt;/span&gt;&lt;/label&gt;
				&lt;span class="cerb-ui-form--label"&gt;Notify watchers on changes&lt;/span&gt;
			&lt;/div&gt;
			&lt;div class="cerb-ui-form--field"&gt;
				&lt;label class="cerb-ui-form--label"&gt;Internal notes&lt;/label&gt;
				&lt;textarea name="notes" rows="3"&gt;&lt;/textarea&gt;
				&lt;div class="cerb-ui-form--help"&gt;Only visible to team members.&lt;/div&gt;
			&lt;/div&gt;
		&lt;/div&gt;
	&lt;/div&gt;
&lt;/form&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// SelectMenu enhances the native &lt;select&gt;; full-width inside a --field. Toggle is optional (CSS works bare).
new CerbUI.SelectMenu(document.getElementById('owner'));</pre>
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

	<div class="cerb-uiref-component" id="uiref-c-datepicker">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-calendar"></span>Datepicker</div>

		{* Example: auto trigger — calendar opens on focus/click; type to live-navigate; Enter confirms *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Auto trigger &mdash; opens on focus/click; type a date to navigate, <code>Enter</code> confirms, <code>Esc</code> closes</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-datepicker-auto" placeholder="YYYY-MM-DD" size="20">
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Selected: <b id="uiref-datepicker-auto-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;input type="text" id="when" placeholder="YYYY-MM-DD"&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const picker = new CerbUI.DatePicker(el, {
	startOfWeek:  'mon',         // 'mon' | 'sun'  (default 'mon')
	outputFormat: 'YYYY-MM-DD',  // tokens: YYYY YY MMM MM M DDD DD D  (default 'YYYY-MM-DD')
	// parseFormat: 'MM/DD/YYYY', // format of a pre-existing input value (default: outputFormat)
	trigger:      'auto',        // 'auto' (focus/click) | 'button' (toggle button) (default 'auto')
	onSelect: function(date, formatted) { /* date = Date, formatted = string */ },
});

// also fires a DOM event on the input:
el.addEventListener('cerb-ui-datepicker:select', e =&gt; console.log(e.detail.formatted));

// public API: picker.setDate(dateOrString|null); picker.getDate(); picker.destroy();</pre>
			</div>
		</div>

		{* Example: button trigger + a custom output format *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Button trigger + custom format (<code>trigger:'button'</code>, <code>outputFormat:'MMM D, YYYY'</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-datepicker-button" placeholder="Pick a date" size="20">
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// a toggle button is inserted after the input; the calendar opens only via it
// (use this when the input has its own autocomplete to avoid conflicts)
new CerbUI.DatePicker(el, {
	trigger:      'button',
	outputFormat: 'MMM D, YYYY',   // e.g. "Jun 8, 2026"
});</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-colorpicker">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-dashboard"></span>ColorPicker</div>

		{* Example: default — a color well (swatch + hex input) opening a Photoshop-style panel; rainbow palette *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Color well &mdash; click the swatch or focus the input to open; drag the SV square / hue strip, or pick a palette swatch; type a hex (<code>#rgb</code>/<code>#rrggbb</code>, <code>#</code> optional), <code>Esc</code> closes</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-colorpicker-basic" value="#6a87db" size="10">
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Value: <b id="uiref-colorpicker-basic-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;input type="text" name="color" value="#6a87db"&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const picker = new CerbUI.ColorPicker(el, {
	// palette: 'rainbow',   // swatch row: a CerbUI.palettes name or an array of hex (default 'rainbow')
	// alpha:   false,       // show an opacity strip + emit #rrggbbaa / rgba() (default false)
	onChange: function(hex, rgba, input) { /* hex = '#rrggbb', rgba = 'rgba(r, g, b, a)' */ },
	// onOpen:  function() { },
	// onClose: function() { },
});

// also fires a DOM event on the input:
el.addEventListener('cerb-ui-colorpicker:change', e =&gt; console.log(e.detail.hex, e.detail.rgba));

// public API: picker.getValue() -&gt; '#rrggbb'; picker.getRgba() -&gt; 'rgba(...)';
//             picker.setValue('#ff8800'); picker.open(); picker.close(); picker.destroy();</pre>
			</div>
		</div>

		{* Example: opacity enabled + a different named palette *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Opacity strip + <code>category10</code> palette (<code>alpha:true</code>) &mdash; the swatch shows transparency over a checkerboard</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-colorpicker-alpha" value="#2ca02c" size="12">
				<span class="cerb-uiref-result" style="margin-left:0.7em;">rgba: <b id="uiref-colorpicker-alpha-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.ColorPicker(el, {
	palette: 'category10',
	alpha:   true,   // getValue() now returns #rrggbbaa when opacity &lt; 100%
});</pre>
			</div>
		</div>

		{* Example: swatch-only — hide the hex field, keep the input as the (hidden) value holder *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Swatch only (<code>showInput:false</code>) &mdash; just the swatch chip; the <code>&lt;input&gt;</code> stays hidden in the DOM so it still seeds the color and posts the value, and the popup grows its own hex field for copy/paste</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<input type="text" id="uiref-colorpicker-swatchonly" value="#e377c2" size="10">
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Posted value: <b id="uiref-colorpicker-swatchonly-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.ColorPicker(el, {
	showInput: false,   // render only the swatch; the hidden &lt;input&gt; still holds/posts the value
});</pre>
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
	// hideZeros: true,       // hide zero-valued items per the current key (default: false)
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
	// hideZeros: true,       // also hide zero-valued items from the legend (default: false)
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

		{* Example: zero-valued segments are hidden in the bar but still listed in the legend *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Zero-valued segments (hidden in the bar; hideZeros hides them from the legend too)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-distbar" id="uiref-distbar-zeros">
					<span data-label="Done" data-value="10"></span>
					<span data-label="Error" data-value="0"></span>
					<span data-label="In Progress" data-value="0"></span>
					<span data-label="Retrying" data-value="0"></span>
					<span data-label="Available" data-value="0"></span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-distbar"&gt;
	&lt;span data-label="Done" data-value="10"&gt;&lt;/span&gt;
	&lt;span data-label="Error" data-value="0"&gt;&lt;/span&gt;
	&lt;span data-label="In Progress" data-value="0"&gt;&lt;/span&gt;
	&lt;span data-label="Retrying" data-value="0"&gt;&lt;/span&gt;
	&lt;span data-label="Available" data-value="0"&gt;&lt;/span&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// Zero-valued segments never render slivers in the bar; hideZeros also hides
// them from the legend (default: false, shown with their 0 counts)
new CerbUI.Distbar(el, { legend: true, hideZeros: true });</pre>
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
				<pre data-cerb-uiref-source>// Point mode (hover): one instance per trigger; content is an HTML string OR a built DOM node.
// CerbUI.Tooltip(options) — the constructor takes a single option:
const tip = new CerbUI.Tooltip({
	gap: 10, // px between the anchor point/element and the panel (default 10)
});

const el = document.querySelector('#my-target');
el.addEventListener('mouseenter', e =&gt; tip.show('&lt;b&gt;Tooltip&lt;/b&gt;&lt;br&gt;Floating, viewport-aware panel.', e.clientX, e.clientY));
el.addEventListener('mousemove',  e =&gt; tip.move(e.clientX, e.clientY)); // reposition, same content
el.addEventListener('mouseleave', () =&gt; tip.hide());</pre>
			</div>
		</div>

		{* Example: anchored to an element — auto flip-fit side + an SVG arrow that points at the target *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Anchored to an element (click to open the callout)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-gap-3 cerb-u-justify-center" id="uiref-tooltip-anchors">
					<button type="button" class="cerb-ui-button" data-my="right center" data-at="left center">Left</button>
					<button type="button" class="cerb-ui-button" data-my="center bottom" data-at="center top">Top</button>
					<button type="button" class="cerb-ui-button" data-my="center top" data-at="center bottom">Bottom</button>
					<button type="button" class="cerb-ui-button" data-my="left center" data-at="right center">Right</button>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// anchored mode: pin to a DOM element; an SVG arrow points at it. content is a string OR a DOM node.
const tip = new CerbUI.Tooltip({
	// gap: 10, // px between the target and the panel (default 10)
});

tip.anchor('&lt;b&gt;Heads up&lt;/b&gt;&lt;br&gt;Anchored callout with an arrow.', targetEl, {
	// my: 'center bottom', // point ON THE TOOLTIP (jQuery-UI style; "middle" == "center")
	// at: 'center top',    // point ON THE TARGET it aligns to — default: tooltip's bottom-middle
	//                      // at the target's top-middle; flips below + slides to stay on-screen
	interactive: true,      // clickable; dismiss on click / outside-click (default true)
});</pre>
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

	<div class="cerb-uiref-component" id="uiref-c-selectmenu">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chevron-down"></span>SelectMenu</div>

		{* Example: a searchable <select> — open and type to filter (e.g. "Los") *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Searchable select &mdash; enhances a native <code>&lt;select&gt;</code>; open and type to filter (try <code>Los</code>); reuses CerbUI.Menu</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<select id="uiref-selectmenu-tz">
					<option value="">Select a timezone&hellip;</option>
					<option value="America/Anchorage">America/Anchorage</option>
					<option value="America/Chicago">America/Chicago</option>
					<option value="America/Denver">America/Denver</option>
					<option value="America/Los_Angeles">America/Los_Angeles</option>
					<option value="America/New_York">America/New_York</option>
					<option value="America/Phoenix">America/Phoenix</option>
					<option value="America/Sao_Paulo">America/Sao_Paulo</option>
					<option value="Asia/Kolkata">Asia/Kolkata</option>
					<option value="Asia/Shanghai">Asia/Shanghai</option>
					<option value="Asia/Tokyo">Asia/Tokyo</option>
					<option value="Australia/Sydney">Australia/Sydney</option>
					<option value="Europe/Berlin">Europe/Berlin</option>
					<option value="Europe/London">Europe/London</option>
					<option value="Europe/Paris">Europe/Paris</option>
					<option value="Pacific/Auckland">Pacific/Auckland</option>
					<option value="UTC">UTC</option>
				</select>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Value: <b id="uiref-selectmenu-tz-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- a normal &lt;select&gt; — it stays in the DOM (hidden) and still submits --&gt;
&lt;select id="tz"&gt;
    &lt;option value=""&gt;Select a timezone…&lt;/option&gt;
    &lt;option value="America/Los_Angeles"&gt;America/Los_Angeles&lt;/option&gt;
    &lt;option value="UTC"&gt;UTC&lt;/option&gt;
&lt;/select&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const sm = new CerbUI.SelectMenu(el, {
	placeholder: 'Select a timezone…', // shown when the empty option is selected
	filter:      true,                 // type-to-filter (default true; forwarded to CerbUI.Menu)
	onSelect:  function(value, text, option) { /* fired on choose; the &lt;select&gt; also gets a change event */ },
	// onRender: function(li, option) { /* advanced: build custom item markup */ },
});

// Long lists (timezones, etc.) virtualize automatically via CerbUI.Menu.
// API: sm.getValue(); sm.setValue('UTC'); sm.open(); sm.close(); sm.destroy();</pre>
			</div>
		</div>

		{* Example: per-option icons via data-cerb-ui-icon *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Per-option icons (<code>data-cerb-ui-icon</code> on each <code>&lt;option&gt;</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<select id="uiref-selectmenu-icons">
					<option value="open" data-cerb-ui-icon="inbox" selected>Open</option>
					<option value="waiting" data-cerb-ui-icon="clock">Waiting</option>
					<option value="closed" data-cerb-ui-icon="check">Closed</option>
					<option value="deleted" data-cerb-ui-icon="trash" disabled>Deleted</option>
				</select>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- data-cerb-ui-icon="&lt;name&gt;" renders a leading cerb-icons glyph --&gt;
&lt;select id="status"&gt;
    &lt;option value="open" data-cerb-ui-icon="inbox"&gt;Open&lt;/option&gt;
    &lt;option value="closed" data-cerb-ui-icon="check"&gt;Closed&lt;/option&gt;
    &lt;option value="deleted" data-cerb-ui-icon="trash" disabled&gt;Deleted&lt;/option&gt;
&lt;/select&gt;

&lt;script&gt;new CerbUI.SelectMenu(document.getElementById('status'));&lt;/script&gt;</pre>
			</div>
		</div>

		{* Example: custom renderer — a CerbUI.Pip presence dot before each label (shown in the trigger too) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Custom renderer (<code>onRender</code>) &mdash; a <a href="#uiref-c-pip">Pip</a> presence dot per option; the selected one shows in the trigger</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<select id="uiref-selectmenu-presence">
					<option value="available" selected>Available</option>
					<option value="busy">Busy</option>
					<option value="dnd">Do Not Disturb</option>
					<option value="invisible">Invisible</option>
				</select>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// onRender runs for each menu item AND for the selected option in the trigger.
// `el` already contains the label — prepend your adornment before el.firstChild.
const COLORS = { available: 'green', busy: 'orange', dnd: 'red', invisible: 'gray' };

new CerbUI.SelectMenu(el, {
	onRender: function(el, option) {
		const pip = document.createElement('span');
		pip.className = 'cerb-ui-pip';
		pip.style.color = 'var(--cerb-color-tag-' + (COLORS[option.value] || 'gray') + ')';
		pip.style.marginRight = '0.5em';
		el.insertBefore(pip, el.firstChild);
	},
});{/literal}</pre>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-searchquery">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-search"></span>SearchQuery</div>

		{* Example 1 (the common case): the ready-made adapter against a real record context — fully documented *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A textarea editor for Cerb search syntax &mdash; live highlighting (filter names, strings, AND/OR). <b>The usual setup:</b> <code>queryFieldSource(context)</code> wires autocomplete to Cerb's real endpoints (lazy-loaded fields + values; a nested context inserts <code>field:()</code> and keeps suggesting). Enter searches, Shift+Enter / &#8984;+Enter = newline; type or Ctrl/&#8984;+Space to suggest, &darr; to pick. Try <code>group:</code>, <code>bucket:</code>, or <code>sender:</code> &rarr; <code>org:</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-searchquery" id="uiref-searchquery-adapter">
					<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
					<div class="cerb-ui-searchquery--field">
						<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-searchquery--input" rows="1" placeholder="Search tickets…"></textarea>
						<span class="cerb-ui-searchquery--caret-anchor"></span>
					</div>
					<div class="cerb-ui-searchquery--right">
						<a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-sparkles"></span></a>
					</div>
				</div>
				<div class="cerb-uiref-result">Search: <b id="uiref-searchquery-adapter-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- A search icon on the left, the field in the middle, an arbitrary --right toolbar of icon actions. --&gt;
&lt;div class="cerb-ui-searchquery" id="q"&gt;
	&lt;span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"&gt;&lt;/span&gt;
	&lt;div class="cerb-ui-searchquery--field"&gt;
		&lt;div class="cerb-ui-searchquery--highlight" aria-hidden="true"&gt;&lt;/div&gt;
		&lt;textarea class="cerb-ui-searchquery--input" rows="1" placeholder="Search…"&gt;&lt;/textarea&gt;
		&lt;span class="cerb-ui-searchquery--caret-anchor"&gt;&lt;/span&gt;
	&lt;/div&gt;
	&lt;div class="cerb-ui-searchquery--right"&gt;
		&lt;a data-action="autocomplete" title="Suggestions"&gt;&lt;span class="cerb-icons cerb-icon-sparkles"&gt;&lt;/span&gt;&lt;/a&gt;
	&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const el = document.getElementById('q');

const sq = new CerbUI.SearchQuery(el, {
	// Enter ALWAYS submits; Shift+Enter / ⌘·Ctrl+Enter = newline. Autocomplete is opt-in: type (debounced) or
	// Ctrl/⌘+Space to open the menu, ↓ to step into it (or click); Enter selects only once you're in the menu.
	onSearch:          (query) => { console.log('search:', query); },

	// queryFieldSource(context, opts?) — ready-made source: lazy-loads fields + values from Cerb's endpoints,
	// caches per scope, inserts a nested context as `field:()` then keeps suggesting.
	// opts.filterMode: 'subsequence' (default) | 'substring' | 'prefix'   (see the toolbar example below)
	onAutocomplete:    CerbUI.SearchQuery.queryFieldSource('cerberusweb.contexts.ticket'),
	context:           'cerberusweb.contexts.ticket', // root context alias, passed through to onAutocomplete

	autocompleteDelay: 200,                // ms debounce for typing-triggered suggestions
	minChars:          0,                  // min total query length before typing fires suggestions
	maxHeight:         160,                // px the field grows to before it scrolls (auto-grow)
	// placeholder:    null,               // overrides the textarea's own placeholder when set
});

// Optional: a --right toolbar button to force the menu open.
el.querySelector('[data-action=autocomplete]').addEventListener('click', () => sq.openAutocomplete());
// CerbUI.SearchQuery.from(el) -> the instance; sq.getValue() / setValue(str) / focus(){/literal}</pre>
			</div>
		</div>

		{* Example 2: a custom onAutocomplete — return your own items (local / Ajax / hybrid) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Custom autocomplete &mdash; return your own items from <code>onAutocomplete(ctx)</code> for the current path + prefix (local, Ajax, or hybrid). A nested-context item uses a <code>snippet</code> with <code>$0</code> to insert <code>field:()</code> and chain. Walk <code>sender:</code> &rarr; <code>org:</code> &rarr; <code>name:</code>; or open <code>created:(…)</code> &mdash; inside the parens the path's final segment is tagged <code>created:()</code>, so the source serves the date sub-keys (<code>since:</code>/<code>until:</code>/<code>days:</code>/<code>time:</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-searchquery" id="uiref-searchquery-custom">
					<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
					<div class="cerb-ui-searchquery--field">
						<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-searchquery--input" rows="1" placeholder="Search…">status:[open,waiting] sender:(org:(name:"Fiaflux Games"))</textarea>
						<span class="cerb-ui-searchquery--caret-anchor"></span>
					</div>
					<div class="cerb-ui-searchquery--right">
						<a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-sparkles"></span></a>
					</div>
				</div>
				<div class="cerb-uiref-result">Search: <b id="uiref-searchquery-custom-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// onAutocomplete(ctx) returns items (or a Promise of items) — only the source changes from the first example.
// ctx  = { path:['sender:','org:'], prefix, context, query, caret }
// item = { caption, value, snippet?, hint?, icon?, suppressAutocomplete? }
//   snippet overrides value; a single `$0` marks the caret — a nested context inserts `sender:($0)` and chains
//   suppressAutocomplete = don't re-open suggestions after this pick (a terminal value like `open`)
// In GROUP-KEY position (caret inside `field:(…)`) the path's final segment is tagged with a trailing `()`
// (e.g. 'created:()') — serve that group's sub-keys there, and fall back to the plain key otherwise.
const FIELDS = [
	{ caption: 'status:',        value: 'status:',        hint: 'list' },
	{ caption: 'subject:',       value: 'subject:',       hint: 'text' },
	{ caption: 'sender:',        value: 'sender:',        snippet: 'sender:($0)', hint: 'contact' },
	{ caption: 'links.address:', value: 'links.address:', hint: 'text' },
	{ caption: 'created:',       value: 'created:',       snippet: 'created:($0)', hint: 'date' },
];
const NESTED = {
	'status:':     [{ caption:'open', value:'open', suppressAutocomplete:true } /* , waiting, closed, deleted */],
	'sender:':     [{ caption:'org:', value:'org:', snippet:'org:($0)', hint:'org' }, { caption:'email:', value:'email:' }],
	'sender:org:': [{ caption:'name:', value:'name:' }],
	'created:()':  [{ caption:'since:', snippet:'since:"$0"' }, { caption:'until:', snippet:'until:"$0"' }, { caption:'days:', snippet:'days:[$0]' }, { caption:'time:', snippet:'time:$0' }],
};
function localSource(ctx) {
	let key = ctx.path.join('');
	if(key.endsWith('()') && !NESTED[key]) key = key.slice(0, -2); // group position falls back to the plain key
	const items = (key === '') ? FIELDS : (NESTED[key] || []);
	return CerbUI.SearchQuery.filterItems(items, ctx.prefix); // default 'subsequence'; or pass 'substring' | 'prefix'
}

new CerbUI.SearchQuery(el, {
	onAutocomplete: localSource,
	onSearch:       (query) => { /* … */ },
	// […same options as the first example…]
});{/literal}</pre>
			</div>
		</div>

		{* Example 3: a custom --right action — a filter-mode config menu persisted to localStorage *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Custom toolbar action &mdash; the <code>--right</code> slot holds arbitrary per-instance controls. Here a <span class="cerb-icons cerb-icon-gear"></span> opens a <code>CerbUI.Menu</code> to choose the filter mode (the source reads it via <code>CerbUI.SearchQuery.filterItems</code>), persisted in <code>localStorage</code>. Clear the field and type <code>linadd</code> with <em>Subsequence</em> on</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-searchquery" id="uiref-searchquery-toolbar">
					<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
					<div class="cerb-ui-searchquery--field">
						<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-searchquery--input" rows="1" placeholder="Search…"></textarea>
						<span class="cerb-ui-searchquery--caret-anchor"></span>
					</div>
					<div class="cerb-ui-searchquery--right">
						<a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-sparkles"></span></a>
						<a style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Saved queries"><span class="cerb-icons cerb-icon-bookmark"></span></a>
						<a data-action="config" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Filter mode"><span class="cerb-icons cerb-icon-gear"></span></a>
					</div>
				</div>
				<ul id="uiref-searchquery-modemenu" hidden>
					<li data-value="substring">Substring</li>
					<li data-value="prefix">Prefix</li>
					<li data-value="subsequence">Subsequence</li>
				</ul>
				<div class="cerb-uiref-result">Search: <b id="uiref-searchquery-toolbar-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Add a gear to the --right toolbar… --&gt;
&lt;a data-action="config" title="Filter mode"&gt;&lt;span class="cerb-icons cerb-icon-gear"&gt;&lt;/span&gt;&lt;/a&gt;
&lt;!-- …and a hidden menu of the modes it picks from --&gt;
&lt;ul id="mode-menu" hidden&gt;
	&lt;li data-value="substring"&gt;Substring&lt;/li&gt;
	&lt;li data-value="prefix"&gt;Prefix&lt;/li&gt;
	&lt;li data-value="subsequence"&gt;Subsequence&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// The mode lives in localStorage; your onAutocomplete reads it via CerbUI.SearchQuery.filterItems(items, prefix, mode).
const STORE_KEY = 'myapp.searchquery.filterMode';
let mode = CerbUI.SearchQuery.MATCH_MODES.indexOf(localStorage.getItem(STORE_KEY)) !== -1
	? localStorage.getItem(STORE_KEY) : 'subsequence';

// A gear in the --right toolbar opens a CerbUI.Menu of the modes, with a check on the active one.
const cfgMenu = new CerbUI.Menu(document.getElementById('mode-menu'), {
	onRenderItem: (li, src) => {
		const ico = document.createElement('span');
		ico.className = src.dataset.value === mode ? 'cerb-icons cerb-icon-check' : '';
		ico.style.cssText = 'width:1.2em;display:inline-block;margin-right:0.3em;';
		li.insertBefore(ico, li.firstChild);
	},
	onSelect: (li, src) => {
		mode = src.dataset.value;
		localStorage.setItem(STORE_KEY, mode);
		sq.focus(); sq.openAutocomplete();
	},
});
const cfgBtn = el.querySelector('[data-action=config]');
cfgBtn.addEventListener('click', () => cfgMenu.isOpen() ? cfgMenu.close() : cfgMenu.open(cfgBtn));{/literal}</pre>
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

	<div class="cerb-uiref-component" id="uiref-c-dialog">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-window-top"></span>Dialog</div>

		{* Example: classic Cerb title bar — draggable, resizable, Esc closes *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Classic title bar (<code>header:'bar'</code>) &mdash; drag the bar, resize from the edges, <code>Esc</code> closes the topmost</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-classic-btn">Open ticket dialog</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- the content element; CerbUI.Dialog moves it into a floating shell --&gt;
&lt;div id="dlg"&gt;…your content…&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>const dlg = new CerbUI.Dialog(el, {
	title:      'Ticket',     // shown in the bar (header:'bar')
	header:     'bar',        // 'bar' | 'floating' | 'none'  (default 'bar')
	draggable:  true,         // default true
	resizable:  true,         // default true
	closable:   true,         // show the × button (default true)
	minimizable: true,        // show the minimize caret (default: true only for 'bar')
	modal:      false,        // dim the page behind a backdrop (default false)
	width:      480,          // px (default 400); minWidth 200, minHeight 80
	// position: { x: 100, y: 80 },  // explicit; else centered (or namespace-inherited)
	namespace:  'ticket',     // siblings share position + close each other (default: per-instance)
	fixed:      false,        // position:fixed instead of absolute (default false)
	closeOnEscape: true,      // topmost dialog only (default true)
	closeWarnOnUnsavedChanges: false, // warn before closing once a control is actually changed (default false)
	// dragHandle: '[data-cerb-ui-dialog-drag]', // drag region for header:'floating'|'none'
	onOpen:    function() {},
	onClose:   function() { /* return false to veto the close */ },
	onMinimize: function(min) {},
	onDragged:  function(x, y) {},
	onResized:  function(w, h) {},
});
dlg.open();   // also: dlg.close(); dlg.isOpen(); dlg.setTitle('…'); dlg.isDirty(); dlg.markClean(); dlg.destroy();

// also fires DOM events on the content element:
el.addEventListener('cerb-ui-dialog:open',  () =&gt; {});
el.addEventListener('cerb-ui-dialog:close', () =&gt; {});
// look an instance up later: CerbUI.Dialog.from(el)</pre>
			</div>
		</div>

		{* Example: chromeless / floating — content owns its header (cerb-ui-header), only a floating × *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Chromeless (<code>header:'floating'</code>) &mdash; no blue bar; the content supplies its own <a href="#uiref-c-header">cerb-ui-header</a>; a × floats top-right</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-floating-btn">Open record dialog</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- mark a region with data-cerb-ui-dialog-drag to keep it draggable --&gt;
&lt;div id="dlg"&gt;
	&lt;div class="cerb-ui-header" data-cerb-ui-dialog-drag&gt;
		&lt;div class="cerb-ui-header--title-sm"&gt;Helio Inc&lt;/div&gt;
		&lt;div class="cerb-ui-header--right"&gt;&lt;button class="cerb-ui-button"&gt;Edit&lt;/button&gt;&lt;/div&gt;
	&lt;/div&gt;
	…body…
&lt;/div&gt;

new CerbUI.Dialog(el, { header: 'floating', title: 'Helio Inc' }); // no titlebar, but title labels it in the minimize tray</pre>
			</div>
		</div>

		{* Example: modal with a footer button bar (cerb-ui-header--right) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Modal + footer button bar (<code>modal:true</code>) &mdash; a backdrop dims the page; actions use <code>cerb-ui-header--right</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-modal-btn">Open modal form</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.Dialog(el, { title: 'Edit snippet', modal: true, width: 460 });

&lt;!-- a Cancel button can close its own dialog --&gt;
&lt;button class="cerb-ui-button" onclick="CerbUI.Dialog.from(el).close()"&gt;Cancel&lt;/button&gt;</pre>
			</div>
		</div>

		{* Example: minimize / drag / resize, reporting callbacks *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Minimize, drag &amp; resize &mdash; the caret docks the window into the top-right tray (a window icon + count); click the tray to restore, or pick <b>Close all</b> when several are docked. Open a few and minimize them; <code>onMinimize</code>/<code>onResized</code> fire</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-resize-btn">Open resizable dialog</button>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Last: <b id="uiref-dialog-resize-result">&mdash;</b></span>
			</div>
		</div>

		{* Example: namespace / position reuse *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Namespace &amp; position reuse (<code>namespace</code>) &mdash; reopening reuses the last position; opening a sibling closes the other</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-ns-a-btn">Open “A”</button>
				<button type="button" class="cerb-ui-button" id="uiref-dialog-ns-b-btn">Open “B”</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>// both share a namespace -> moving one and reopening reuses its position;
// opening the sibling closes the first (one open per namespace)
new CerbUI.Dialog(elA, { title: 'A', namespace: 'demo' });
new CerbUI.Dialog(elB, { title: 'B', namespace: 'demo' });</pre>
			</div>
		</div>

		{* Example: simple alert — not draggable/resizable *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Simple alert (<code>draggable:false, resizable:false</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-alert-btn">Show alert</button>
			</div>
		</div>

		{* Example: unsaved-changes guard — warn before closing once a control is actually changed *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Unsaved-changes guard (<code>closeWarnOnUnsavedChanges</code>) &mdash; warns before closing <b>only after</b> a tracked control actually changes (typing, a toggle, a menu); merely having inputs never nags. Fires on every close path: the ×, <b>Esc</b>, and the tray's <b>Close all</b>. Add <code>data-cerb-ui-dialog-no-dirty</code> to a control (or any ancestor) to exclude it. Left dirty &amp; minimized, it also guards a page reload / back-forward / close (native browser prompt)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-dirty-btn">Open edit form</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.Dialog(el, { title: 'Edit ticket', closeWarnOnUnsavedChanges: true });

&lt;!-- exclude an already-persisted control (or a whole region) from dirty-tracking --&gt;
&lt;input type="search" data-cerb-ui-dialog-no-dirty&gt;

// a successful save should clear the flag so closing doesn't re-warn:
function onSaved(el) {
	const dlg = CerbUI.Dialog.from(el);
	dlg.markClean();   // also: dlg.isDirty()
	dlg.close();
}{/literal}</pre>
			</div>
		</div>

		{* Example: fromAjax — fetch HTML into a popup (the genericAjaxPopup replacement) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">AJAX content (<code>CerbUI.Dialog.fromAjax</code>) &mdash; spinner while loading, then the fetched HTML; response <code>&lt;script&gt;</code> runs under the nonce. A tall dialog grows and the <b>page</b> scrolls to it; pass <code>scrollBody:true</code> to cap it to the viewport and scroll the <b>body</b> instead</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-ajax-btn">Load help popup (page scroll)</button>
				<button type="button" class="cerb-ui-button" id="uiref-dialog-ajax-scroll-btn">Load help popup (scrollBody)</button>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// string ⇒ GET (ajax args); FormData ⇒ POST. opts are the constructor options + onLoad.
CerbUI.Dialog.fromAjax('c=profiles&amp;a=invoke&amp;module=snippet&amp;action=helpPopup', {
	title: 'Snippet help', // titlebar text
	// width: 600,         // omit → 75% of the viewport, capped at 1100 (mobile: always 95%)
	// scrollBody: true,   // cap to the viewport + scroll the body (default: grow + page scroll)
	// namespace: 'peek',  // siblings share position + auto-close each other (supersedes the old layer/reuse)
	// onLoad: function(content, html) { /* runs after the HTML is injected */ },
});

// POST a form's data instead of GET args:
// CerbUI.Dialog.fromAjax(new FormData(document.getElementById('myForm')), { title: 'Edit' });

// loaded content finds its own dialog from any element inside it (replaces genericAjaxPopupFind):
// CerbUI.Dialog.from(thisFormEl).close();{/literal}</pre>
			</div>
		</div>

		{* Hidden templates — CerbUI.Dialog relocates each into a floating shell (and restores here on destroy) *}
		<div id="uiref-dialog-templates" style="display:none;">
			<div id="uiref-dialog-classic-content" style="line-height:1.5;">
				<p>This is a classic Cerb dialog with the accent title bar &mdash; the look you know.</p>
				<p>Drag it by the bar, resize from any edge or corner, minimize with the caret (it docks to the top-right tray), or press <b>Esc</b> to close.</p>
			</div>

			<div id="uiref-dialog-floating-content" style="line-height:1.5;">
				<div class="cerb-ui-header cerb-ui-header--center" data-cerb-ui-dialog-drag style="cursor:move;">
					<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-building-apartments"></span>Helio Inc</div>
					<div class="cerb-ui-header--right">
						<span class="cerb-ui-chip">Growth</span>
						<button type="button" class="cerb-ui-button">Edit</button>
					</div>
				</div>
				<p>No blue bar here &mdash; the content provides its own header via <code>cerb-ui-header</code>, and only a small × floats in the top-right corner. Drag from the header (marked <code>data-cerb-ui-dialog-drag</code>).</p>
			</div>

			<div id="uiref-dialog-modal-content" style="line-height:1.5;">
				<p style="margin-top:0;">Editing this record is blocked behind a modal backdrop until you act.</p>
				<input type="text" value="My snippet" style="width:100%; box-sizing:border-box; margin-bottom:1em;">
				<div class="cerb-ui-header cerb-ui-header--tight" style="margin-bottom:0;">
					<div></div>
					<div class="cerb-ui-header--right">
						<button type="button" class="cerb-ui-button" id="uiref-dialog-modal-cancel">Cancel</button>
						<button type="button" class="cerb-ui-button">Save</button>
					</div>
				</div>
			</div>

			<div id="uiref-dialog-resize-content" style="line-height:1.5;">
				<p>Drag the title bar to move me. Grab an edge or corner to resize (I won't shrink below the minimums). Use the caret to minimize me to the top-right tray, then click the tray to restore me.</p>
			</div>

			<div id="uiref-dialog-ns-a-content" style="line-height:1.5;">
				<p>Dialog <b>A</b>. Move me somewhere, close me, and reopen &mdash; I'll come back where you left me. Open <b>B</b> and I'll step aside.</p>
			</div>
			<div id="uiref-dialog-ns-b-content" style="line-height:1.5;">
				<p>Dialog <b>B</b>, sharing A's namespace. Only one of us is open at a time, and we share a position.</p>
			</div>

			<div id="uiref-dialog-alert-content" style="line-height:1.5;">
				<p style="margin-top:0;">Your changes have been saved.</p>
				<div class="cerb-ui-header cerb-ui-header--tight" style="margin-bottom:0;">
					<div></div>
					<div class="cerb-ui-header--right">
						<button type="button" class="cerb-ui-button" id="uiref-dialog-alert-ok">OK</button>
					</div>
				</div>
			</div>

			<div id="uiref-dialog-dirty-content" style="line-height:1.5;">
				<p style="margin-top:0;">Change any field, then try to close (the ×, <b>Esc</b>, or minimize then <b>Close all</b>) &mdash; you'll be asked to confirm. Close it untouched and it just closes.</p>
				<form class="cerb-ui-form" style="max-width:360px;">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Subject</label>
						<input type="text" placeholder="Type to make me dirty…">
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Status</label>
						<select id="uiref-dialog-dirty-select">
							<option>Open</option>
							<option>Waiting</option>
							<option>Closed</option>
						</select>
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Notify subscribers</label>
						<label class="cerb-ui-toggle" id="uiref-dialog-dirty-toggle"><input type="checkbox"><span class="cerb-ui-toggle--slider"></span></label>
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Search <span class="cerb-ui-form--hint">excluded via data-cerb-ui-dialog-no-dirty</span></label>
						<input type="search" data-cerb-ui-dialog-no-dirty placeholder="Already persisted — won't warn">
					</div>
				</form>
				<div class="cerb-ui-header cerb-ui-header--tight" style="margin-bottom:0;">
					<div></div>
					<div class="cerb-ui-header--right">
						<button type="button" class="cerb-ui-button" id="uiref-dialog-dirty-save">Save</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="cerb-uiref-component" id="uiref-c-confirm">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-checked"></span>Confirm</div>

		{* CerbUI.Confirm — a forced-modal confirmation (the confirmPopup replacement) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><code>CerbUI.Confirm</code> &mdash; a forced-modal confirmation built on <a href="#uiref-c-dialog">Dialog</a>: centered in the viewport, no ×/minimize/resize/drag, <b>Esc</b> ignored, always on top of other dialogs. Cancel/OK only; labels are customizable; title &amp; body default. The design-system replacement for the legacy <code>confirmPopup()</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-dialog-confirm-btn">Ask to confirm</button>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Last: <b id="uiref-dialog-confirm-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.Confirm.open({
	title:       'Delete snippet',    // default 'Confirm'
	body:        'This can\'t be undone.', // default 'Are you sure?' (string or DOM node)
	confirmText: 'Delete',            // default 'OK'
	cancelText:  'Keep',              // default 'Cancel'
	onConfirm:   function() { /* proceed */ },
	onCancel:    function() { /* optional */ },
});{/literal}</pre>
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

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-rounded-2</code>
			<div><span class="cerb-uiref-utils--box cerb-u-p-3 cerb-u-rounded-2"><span>6px (inputs/buttons)</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-rounded-4</code>
			<div><span class="cerb-uiref-utils--box cerb-u-p-3 cerb-u-rounded-4"><span>10px (panels) — rounded-0 … rounded-4 (0/4/6/8/10px)</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-rounded-full</code>
			<div><span class="cerb-uiref-utils--box cerb-u-px-3 cerb-u-rounded-full"><span>999px (pills)</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-flex-1</code>
			<div style="display:flex;gap:0.5em;"><span class="cerb-uiref-utils--box cerb-u-flex-1"><span>flex-1</span></span><span class="cerb-uiref-utils--box cerb-u-flex-1"><span>flex-1</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-flex-2</code>
			<div style="display:flex;gap:0.5em;"><span class="cerb-uiref-utils--box cerb-u-flex-1"><span>flex-1</span></span><span class="cerb-uiref-utils--box cerb-u-flex-2"><span>flex-2 (twice the width — third + two-thirds)</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-justify-center</code>
			<div class="cerb-u-flex cerb-u-justify-center cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>centered in the row</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-justify-between</code>
			<div class="cerb-u-flex cerb-u-justify-between" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>left</span></span><span class="cerb-uiref-utils--box"><span>right</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-ml-auto</code>
			<div class="cerb-u-flex cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>start</span></span><span class="cerb-uiref-utils--box cerb-u-ml-auto"><span>ml-auto pushes me right</span></span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-flex-shrink-0</code>
			<div class="cerb-u-flex cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box cerb-u-flex-shrink-0"><span>fixed</span></span><span class="cerb-uiref-utils--note">…this filler shrinks while the fixed box keeps its size…</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-items-stretch</code>
			<div class="cerb-u-flex cerb-u-items-stretch cerb-u-gap-2" style="border:1px dashed var(--cerb-color-background-contrast-200);"><span class="cerb-uiref-utils--box"><span>tall<br>box</span></span><span class="cerb-uiref-utils--box"><span>stretches to match height</span></span></div>

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

			<div class="cerb-uiref-utils--note cerb-uiref-utils--full">Per-side variants: <code>cerb-u-pt-</code> / <code>pr-</code> / <code>pb-</code> / <code>pl-</code> / <code>px-</code> / <code>py-</code> (and <code>cerb-u-mt-</code>, etc.). Steps 0&ndash;5 multiply <code>--cerb-u-spacer</code> (1rem): 0, .25, .5, 1, 1.5, 3&times;. Auto margins: <code>cerb-u-mr-auto</code> / <code>cerb-u-mx-auto</code> too.</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-text-center</code>
			<div class="cerb-u-text-center">centered text</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-text-uppercase</code>
			<div class="cerb-u-text-uppercase">uppercased text</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-nowrap</code>
			<div class="cerb-u-nowrap" style="max-width:14em;overflow:hidden;">this long line stays on one row and never wraps</div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-relative</code>
			<div class="cerb-u-relative" style="border:1px dashed var(--cerb-color-background-contrast-200);height:2.6em;"><span class="cerb-uiref-utils--note" style="position:absolute;right:0.4em;bottom:0.3em;">positioning context for an absolute child</span></div>

			<code class="cerb-uiref-utils--name" data-cerb-uiref-copy data-cerb-uiref-source title="Copy to clipboard">cerb-u-cursor-pointer</code>
			<div><span class="cerb-u-cursor-pointer cerb-uiref-utils--note">hover me — pointer cursor on a non-button</span></div>
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

	// Tooltip (anchored): one shared callout reused across buttons that each request a different my/at
	const ttAnchorWrap = document.getElementById('uiref-tooltip-anchors');
	if(ttAnchorWrap && window.CerbUI && CerbUI.Tooltip) {
		const tip = new CerbUI.Tooltip();
		ttAnchorWrap.addEventListener('click', function(e) {
			const btn = e.target.closest('button[data-my]');
			if(!btn) return;
			tip.anchor('<b>' + btn.textContent + '</b><br>Anchored callout with an arrow.', btn, { my: btn.dataset.my, at: btn.dataset.at, interactive: true });
		});
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

	// Zero-valued segments: hidden in the bar (no sliver/gap); hideZeros hides them from the legend too
	const distbarZerosEl = document.getElementById('uiref-distbar-zeros');
	if(distbarZerosEl && window.CerbUI && CerbUI.Distbar) {
		new CerbUI.Distbar(distbarZerosEl, { legend: true, hideZeros: true });
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

	// SelectMenu: searchable timezone select, reporting the chosen value
	(function() {
		const el = document.getElementById('uiref-selectmenu-tz');
		const out = document.getElementById('uiref-selectmenu-tz-result');
		if(el && window.CerbUI && CerbUI.SelectMenu) {
			new CerbUI.SelectMenu(el, {
				placeholder: 'Select a timezone…',
				onSelect: function(value) { if(out) out.textContent = value; },
			});
		}
	})();

	// SelectMenu: per-option icons via data-cerb-ui-icon
	(function() {
		const el = document.getElementById('uiref-selectmenu-icons');
		if(el && window.CerbUI && CerbUI.SelectMenu) {
			new CerbUI.SelectMenu(el);
		}
	})();

	// SelectMenu: custom renderer — a Pip presence dot per option (and in the trigger)
	(function() {
		const el = document.getElementById('uiref-selectmenu-presence');
		if(el && window.CerbUI && CerbUI.SelectMenu) {
			const COLORS = { available: 'green', busy: 'orange', dnd: 'red', invisible: 'gray' };
			new CerbUI.SelectMenu(el, {
				onRender: function(target, option) {
					const pip = document.createElement('span');
					pip.className = 'cerb-ui-pip';
					pip.style.color = 'var(--cerb-color-tag-' + (COLORS[option.value] || 'gray') + ')';
					pip.style.marginRight = '0.5em';
					target.insertBefore(pip, target.firstChild);
				},
			});
		}
	})();

	// SearchQuery #1 — the adapter against a real record context (lazy-loads from the worklist endpoints)
	(function() {
		const el = document.getElementById('uiref-searchquery-adapter');
		const out = document.getElementById('uiref-searchquery-adapter-out');
		if(el && window.CerbUI && CerbUI.SearchQuery) {
			const sq = new CerbUI.SearchQuery(el, {
				onSearch: function(query) { if(out) out.textContent = query; },
				onAutocomplete: CerbUI.SearchQuery.queryFieldSource('cerberusweb.contexts.ticket'),
				context: 'cerberusweb.contexts.ticket',
			});
			const acBtn = el.querySelector('[data-action=autocomplete]');
			if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());
		}
	})();

	// SearchQuery #2 — a custom onAutocomplete (local static source with a nested sender:org:name: path)
	(function() {
		const el = document.getElementById('uiref-searchquery-custom');
		const out = document.getElementById('uiref-searchquery-custom-out');
		if(el && window.CerbUI && CerbUI.SearchQuery) {
			const FIELDS = [
				{ caption: 'status:',        value: 'status:',        hint: 'list' },
				{ caption: 'subject:',       value: 'subject:',       hint: 'text' },
				{ caption: 'sender:',        value: 'sender:',        snippet: 'sender:($0)', hint: 'contact' },
				{ caption: 'links.address:', value: 'links.address:', hint: 'text' },
				{ caption: 'created:',       value: 'created:',       snippet: 'created:($0)', hint: 'date' },
			];
			const NESTED = {
				'status:': [
					{ caption: 'open',    value: 'open',    suppressAutocomplete: true },
					{ caption: 'waiting', value: 'waiting', suppressAutocomplete: true },
					{ caption: 'closed',  value: 'closed',  suppressAutocomplete: true },
					{ caption: 'deleted', value: 'deleted', suppressAutocomplete: true },
				],
				'sender:': [
					{ caption: 'org:',   value: 'org:', snippet: 'org:($0)', hint: 'org' },
					{ caption: 'email:', value: 'email:', hint: 'text' },
				],
				'sender:org:': [{ caption: 'name:', value: 'name:', hint: 'text' }],
				// Parameterized-group sub-keys, keyed by the group-position scope (a trailing '()'):
				'created:()': [
					{ caption: 'since:', value: 'since:', snippet: 'since:"$0"' },
					{ caption: 'until:', value: 'until:', snippet: 'until:"$0"' },
					{ caption: 'days:',  value: 'days:',  snippet: 'days:[$0]' },
					{ caption: 'time:',  value: 'time:',  snippet: 'time:$0' },
				],
			};
			const localSource = function(ctx) {
				let key = ctx.path.join('');
				// In group-key position the final segment is tagged with '()' (e.g. 'created:()', 'sender:()').
				// Use the group's sub-keys if defined; otherwise fall back to the plain sibling key.
				if(key.endsWith('()') && !NESTED[key]) key = key.slice(0, -2);
				const items = (key === '') ? FIELDS : (NESTED[key] || []);
				return CerbUI.SearchQuery.filterItems(items, ctx.prefix); // default 'subsequence'
			};
			const sq = new CerbUI.SearchQuery(el, {
				onSearch: function(query) { if(out) out.textContent = query; },
				onAutocomplete: localSource,
			});
			const acBtn = el.querySelector('[data-action=autocomplete]');
			if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());
		}
	})();

	// SearchQuery #3 — a custom --right action: gear opens a filter-mode menu, persisted in localStorage
	(function() {
		const el = document.getElementById('uiref-searchquery-toolbar');
		const out = document.getElementById('uiref-searchquery-toolbar-out');
		if(el && window.CerbUI && CerbUI.SearchQuery && CerbUI.Menu) {
			// A focused flat field list — enough to feel the filter modes (try `linadd` with Subsequence).
			const FIELDS = [
				{ caption: 'status:',        value: 'status:' },
				{ caption: 'subject:',       value: 'subject:' },
				{ caption: 'sender:',        value: 'sender:' },
				{ caption: 'links.address:', value: 'links.address:' },
				{ caption: 'created:',       value: 'created:' },
			];
			const STORE_KEY = 'cerb.uiref.searchquery.filterMode';
			let mode = localStorage.getItem(STORE_KEY);
			if(CerbUI.SearchQuery.MATCH_MODES.indexOf(mode) === -1) mode = 'subsequence';

			const sq = new CerbUI.SearchQuery(el, {
				onSearch: function(query) { if(out) out.textContent = query; },
				onAutocomplete: function(ctx) {
					if(ctx.path.length) return []; // this demo only suggests top-level fields
					return CerbUI.SearchQuery.filterItems(FIELDS, ctx.prefix, mode);
				},
			});
			const acBtn = el.querySelector('[data-action=autocomplete]');
			if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());

			const cfgBtn = el.querySelector('[data-action=config]');
			const modeUl = document.getElementById('uiref-searchquery-modemenu');
			if(cfgBtn && modeUl) {
				const cfgMenu = new CerbUI.Menu(modeUl, {
					onRenderItem: function(li, src) {
						const ico = document.createElement('span');
						ico.className = src.dataset.value === mode ? 'cerb-icons cerb-icon-check' : '';
						ico.style.cssText = 'width:1.2em;display:inline-block;margin-right:0.3em;';
						li.insertBefore(ico, li.firstChild);
					},
					onSelect: function(li, src) {
						mode = src.dataset.value;
						localStorage.setItem(STORE_KEY, mode);
						sq.focus();
						sq.openAutocomplete();
					},
				});
				cfgBtn.addEventListener('click', () => cfgMenu.isOpen() ? cfgMenu.close() : cfgMenu.open(cfgBtn));
			}
		}
	})();

	// Form: SelectMenu enhancing the Owner select inside the form example
	(function() {
		const el = document.getElementById('uiref-form-owner');
		if(el && window.CerbUI && CerbUI.SelectMenu)
			new CerbUI.SelectMenu(el);
	})();

	// Accordion: default single-open, reporting the expanded index
	(function() {
		const el = document.getElementById('uiref-accordion-basic');
		const out = document.getElementById('uiref-accordion-basic-result');
		if(el && window.CerbUI && CerbUI.Accordion) {
			new CerbUI.Accordion(el, {
				onExpand: function(index) { if(out) out.textContent = index; },
				onCollapse: function() { if(out) out.textContent = '—'; },
			});
		}
	})();

	// Accordion: collapsible + scrollable, all collapsed initially
	(function() {
		const el = document.getElementById('uiref-accordion-scroll');
		if(el && window.CerbUI && CerbUI.Accordion) {
			new CerbUI.Accordion(el, { active: -1, collapsible: true, scrollable: true });
		}
	})();

	// Datepicker: auto trigger, reporting the selected value
	(function() {
		const el = document.getElementById('uiref-datepicker-auto');
		const out = document.getElementById('uiref-datepicker-auto-result');
		if(el && window.CerbUI && CerbUI.DatePicker) {
			new CerbUI.DatePicker(el, {
				onSelect: function(date, formatted) { if(out) out.textContent = formatted; },
			});
		}
	})();

	// Datepicker: button trigger + custom output format
	(function() {
		const el = document.getElementById('uiref-datepicker-button');
		if(el && window.CerbUI && CerbUI.DatePicker) {
			new CerbUI.DatePicker(el, { trigger: 'button', outputFormat: 'MMM D, YYYY' });
		}
	})();

	// ColorPicker: default rainbow palette, reporting the hex value
	(function() {
		const el = document.getElementById('uiref-colorpicker-basic');
		const out = document.getElementById('uiref-colorpicker-basic-result');
		if(el && window.CerbUI && CerbUI.ColorPicker) {
			const picker = new CerbUI.ColorPicker(el, {
				onChange: function(hex) { if(out) out.textContent = hex; },
			});
			if(out) out.textContent = picker.getValue();
		}
	})();

	// ColorPicker: opacity strip + category10 palette, reporting the rgba string
	(function() {
		const el = document.getElementById('uiref-colorpicker-alpha');
		const out = document.getElementById('uiref-colorpicker-alpha-result');
		if(el && window.CerbUI && CerbUI.ColorPicker) {
			const picker = new CerbUI.ColorPicker(el, {
				palette: 'category10',
				alpha: true,
				onChange: function(hex, rgba) { if(out) out.textContent = rgba; },
			});
			if(out) out.textContent = picker.getRgba();
		}
	})();

	// ColorPicker: swatch-only (hex field hidden), reporting the still-posted input value
	(function() {
		const el = document.getElementById('uiref-colorpicker-swatchonly');
		const out = document.getElementById('uiref-colorpicker-swatchonly-result');
		if(el && window.CerbUI && CerbUI.ColorPicker) {
			const picker = new CerbUI.ColorPicker(el, {
				showInput: false,
				onChange: function(hex) { if(out) out.textContent = hex; },
			});
			if(out) out.textContent = picker.getValue();
		}
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

	// Dialog: build each example once and open it from its trigger button
	(function() {
		if(!(window.CerbUI && CerbUI.Dialog)) return;

		const wire = function(btnId, contentId, opts) {
			const btn = document.getElementById(btnId);
			const content = document.getElementById(contentId);
			if(!btn || !content) return null;
			const dlg = new CerbUI.Dialog(content, opts);
			btn.addEventListener('click', function() { dlg.open(); });
			return dlg;
		};

		wire('uiref-dialog-classic-btn', 'uiref-dialog-classic-content', { title: 'Ticket', width: 480 });
		wire('uiref-dialog-floating-btn', 'uiref-dialog-floating-content', { header: 'floating', title: 'Helio Inc' }); // default width; title = tray label
		wire('uiref-dialog-modal-btn', 'uiref-dialog-modal-content', { title: 'Edit snippet', modal: true, width: 460 });

		const resizeOut = document.getElementById('uiref-dialog-resize-result');
		wire('uiref-dialog-resize-btn', 'uiref-dialog-resize-content', {
			title: 'Resizable',
			width: 420,
			onMinimize: function(min) { if(resizeOut) resizeOut.textContent = min ? 'minimized' : 'restored'; },
			onResized: function(w, h) { if(resizeOut) resizeOut.textContent = 'resized to ' + Math.round(w) + '×' + (h ? Math.round(h) : 'auto'); },
		});

		wire('uiref-dialog-ns-a-btn', 'uiref-dialog-ns-a-content', { title: 'A', namespace: 'uiref-dlg-ns', width: 360 });
		wire('uiref-dialog-ns-b-btn', 'uiref-dialog-ns-b-content', { title: 'B', namespace: 'uiref-dlg-ns', width: 360 });

		wire('uiref-dialog-alert-btn', 'uiref-dialog-alert-content', { title: 'Heads up', draggable: false, resizable: false, width: 360 });

		// Unsaved-changes guard: a form dialog that warns once a tracked control is actually changed
		const dirtyDlg = wire('uiref-dialog-dirty-btn', 'uiref-dialog-dirty-content', { title: 'Edit ticket', width: 420, closeWarnOnUnsavedChanges: true });
		if(dirtyDlg) {
			const dirtySelect = document.getElementById('uiref-dialog-dirty-select');
			if(dirtySelect && CerbUI.SelectMenu) new CerbUI.SelectMenu(dirtySelect);
			const dirtyToggle = document.getElementById('uiref-dialog-dirty-toggle');
			if(dirtyToggle && CerbUI.Toggle) new CerbUI.Toggle(dirtyToggle);
			// Save clears the dirty flag before closing, so a successful save never trips the warning
			const dirtySave = document.getElementById('uiref-dialog-dirty-save');
			if(dirtySave) dirtySave.addEventListener('click', function() { dirtyDlg.markClean(); dirtyDlg.close(); });
		}

		// CerbUI.Confirm: a forced-modal confirmation with custom button labels
		const confirmBtn = document.getElementById('uiref-dialog-confirm-btn');
		const confirmOut = document.getElementById('uiref-dialog-confirm-result');
		if(confirmBtn && CerbUI.Confirm) confirmBtn.addEventListener('click', function() {
			CerbUI.Confirm.open({
				title: 'Delete snippet',
				body: "This can't be undone.",
				confirmText: 'Delete',
				cancelText: 'Keep',
				onConfirm: function() { if(confirmOut) confirmOut.textContent = 'confirmed'; },
				onCancel: function() { if(confirmOut) confirmOut.textContent = 'cancelled'; },
			});
		});

		// Footer buttons that close their own dialog
		['uiref-dialog-modal-cancel', 'uiref-dialog-alert-ok'].forEach(function(id) {
			const b = document.getElementById(id);
			if(b) b.addEventListener('click', function() {
				const content = b.closest('[id$="-content"]');
				const dlg = content && CerbUI.Dialog.from(content);
				if(dlg) dlg.close();
			});
		});

		// fromAjax: build the dialog procedurally and load real HTML (the snippet help popup) into it
		const ajaxBtn = document.getElementById('uiref-dialog-ajax-btn');
		if(ajaxBtn) ajaxBtn.addEventListener('click', function() {
			CerbUI.Dialog.fromAjax('c=profiles&a=invoke&module=snippet&action=helpPopup', { title: 'Snippet help' }); // default width
		});
		// same content, but capped to the viewport with an internally-scrolling body
		const ajaxScrollBtn = document.getElementById('uiref-dialog-ajax-scroll-btn');
		if(ajaxScrollBtn) ajaxScrollBtn.addEventListener('click', function() {
			CerbUI.Dialog.fromAjax('c=profiles&a=invoke&module=snippet&action=helpPopup', { title: 'Snippet help', scrollBody: true }); // default width
		});
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
