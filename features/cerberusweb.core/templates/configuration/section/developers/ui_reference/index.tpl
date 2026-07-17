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

/* Keyboard-shortcut reference: one binding per row, keys as cerb-ui-kbd caps */
.cerb-uiref-keys { list-style:none; margin:0; padding:0; display:grid; gap:0.6em; line-height:1.9; color:var(--cerb-color-text); }

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
/* Grayscale swatch — NO background of its own (so cerb-u-bgg-* shows through); a border keeps near-bg steps
   visible. Pair with a label beside it (a number stays legible regardless of the swatch's darkness). */
.cerb-uiref-grayswatch { display:inline-block; width:1.7em; height:1.7em; border-radius:5px; border:1px solid var(--cerb-color-background-contrast-200); }
/* Hover-demo chip — bordered + padded but NO background, so the element's own cerb-u-bgg-* (and the hover) show. */
.cerb-uiref-hoverchip { display:inline-block; padding:0.4em 0.7em; border-radius:6px; border:1px solid var(--cerb-color-background-contrast-200); }

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

/* Persistent left nav (CerbUI.Sidebar) — replaces the old wrapped-chip jump TOC. Sized to clear the footer in JS. */
.cerb-uiref-layout { align-items: flex-start; margin-top: 1em; }
#uiref-nav .cerb-ui-sidebar--head strong { font-size: 0.78em; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--cerb-color-background-contrast-150); }

/* Content group divider — mirrors the sidebar's functional sections */
.cerb-uiref-grouplabel { font-size:1.4em; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--cerb-color-background-contrast-150); margin:2.5em 0 0; padding-bottom:0.3em; border-bottom:2px solid var(--cerb-color-background-contrast-230); }
.cerb-uiref-grouplabel:first-child { margin-top:0.5em; }

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
.cerb-uiref-anim-row { display:flex; gap:1em; flex-wrap:wrap; margin:0.5em 0 1em; }
.cerb-uiref-anim-row .cerb-icons { font-size:1.8em; }
.cerb-uiref-anim-row .cerb-uiref-icon--label { display:block; font-family:monospace; }
</style>

<div class="cerb-ui-page">
	<div class="cerb-ui-header">
		<div>
			<div class="cerb-ui-header--title">UI Reference</div>
			<div class="cerb-ui-header--subtitle">Reusable <code>cerb-ui-*</code> components for Cerb's design system</div>
		</div>
	</div>

	<div class="cerb-ui-sidebar-layout cerb-uiref-layout">
		<aside class="cerb-ui-sidebar" id="uiref-nav" style="--cerb-ui-sidebar-width:230px;">
			<div class="cerb-ui-sidebar--head"><strong>Components</strong></div>
			<div class="cerb-ui-sidebar--body">
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Foundations</div>
					<ul>
						<li data-target="icon" data-icon="picture">Icon</li>
						<li data-target="utilities" data-icon="wrench">Utilities</li>
						<li data-target="effects" data-icon="sparkles">Effects</li>
						<li data-target="async" data-icon="spinner">Async</li>
					</ul>
				</div>
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Layout</div>
					<ul>
						<li data-target="page" data-icon="file-document">Page</li>
						<li data-target="header" data-icon="header">Header</li>
						<li data-target="panel" data-icon="text">Panel</li>
						<li data-target="agent-transcript" data-icon="bot-message">AgentTranscript</li>
						<li data-target="separator" data-icon="minus">Separator</li>
					</ul>
				</div>
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Labels &amp; status</div>
					<ul>
						<li data-target="chip" data-icon="cpu">Chip</li>
						<li data-target="tile" data-icon="square">Tile</li>
						<li data-target="avatar" data-icon="user">Avatar</li>
						<li data-target="qrcode" data-icon="qr-code">QR code</li>
						<li data-target="pill" data-icon="rect-rounded">Pill</li>
						<li data-target="pip" data-icon="dot">Pip</li>
						<li data-target="kbd" data-icon="keyboard">Kbd</li>
					</ul>
				</div>
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Inputs &amp; editors</div>
					<ul>
						<li data-target="button" data-icon="pointer">Button</li>
						<li data-target="toggle" data-icon="toggle">Toggle</li>
						<li data-target="slider" data-icon="slider">Slider</li>
						<li data-target="switcher" data-icon="adjust">Switcher</li>
						<li data-target="form" data-icon="form">Form</li>
						<li data-target="datepicker" data-icon="calendar">Datepicker</li>
						<li data-target="colorpicker" data-icon="color-palette">ColorPicker</li>
						<li data-target="iconpicker" data-icon="sparkles">IconPicker</li>
						<li data-target="priority-picker" data-icon="collection">PriorityPicker</li>
						<li data-target="selectmenu" data-icon="chevron-down">SelectMenu</li>
						<li data-target="record-chooser" data-icon="search">RecordChooser</li>
						<li data-target="context-chooser" data-icon="search">ContextChooser</li>
						<li data-target="text-chooser" data-icon="search">TextChooser</li>
						<li data-target="value-picker" data-icon="checked">ValuePicker</li>
						<li data-target="tag-input" data-icon="tag">TagInput</li>
						<li data-target="file-upload" data-icon="upload">FileUpload</li>
						<li data-target="image-editor" data-icon="picture">ImageEditor</li>
						<li data-target="searchquery" data-icon="search">SearchQuery</li>
						<li data-target="kataeditor" data-icon="placeholders">KataEditor</li>
						<li data-target="markdowneditor" data-icon="quote">MarkdownEditor</li>
						<li data-target="jsoneditor" data-icon="console">JsonEditor</li>
						<li data-target="scriptingeditor" data-icon="function">ScriptingEditor</li>
						<li data-target="dataquery" data-icon="database">DataQuery</li>
						<li data-target="diffviewer" data-icon="step-forward">DiffViewer</li>
						<li data-target="node-editor" data-icon="branch">NodeEditor</li>
					</ul>
				</div>
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Navigation</div>
					<ul>
						<li data-target="menu" data-icon="menu-hamburger">Menu</li>
						<li data-target="toolbar" data-icon="toolbox">Toolbar</li>
						<li data-target="sidebar" data-icon="window-left">Sidebar</li>
						<li data-target="tabs" data-icon="folder-open">Tabs</li>
						<li data-target="accordion" data-icon="chevron-right">Accordion</li>
						<li data-target="splitpane" data-icon="split-pane">SplitPane</li>
					</ul>
				</div>
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Overlays &amp; feedback</div>
					<ul>
						<li data-target="tooltip" data-icon="comments">Tooltip</li>
						<li data-target="dialog" data-icon="window-top">Dialog</li>
						<li data-target="confirm" data-icon="checked">Confirm</li>
						<li data-target="spinner" data-icon="spinner">Spinner</li>
					</ul>
				</div>
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Data viz</div>
					<ul>
						<li data-target="color-scale" data-icon="paintbrush">Color scale</li>
						<li data-target="legend" data-icon="key">Legend</li>
						<li data-target="distribution-bar" data-icon="chart-bar">Distribution bar</li>
						<li data-target="sparkchart" data-icon="chart-line">Sparkchart</li>
						<li data-target="piechart" data-icon="chart-pie">Pie / donut</li>
						<li data-target="cartesian-chart" data-icon="chart-bar">Bar / line</li>
						<li data-target="scatter-chart" data-icon="chart-scatterplot">Scatterplot</li>
						<li data-target="timeblocks" data-icon="chart-timeblocks">Timeblocks</li>
						<li data-target="gauge" data-icon="gauge">Gauge</li>
						<li data-target="map" data-icon="map">Map</li>
						<li data-target="timering" data-icon="clock">TimeRing</li>
						<li data-target="calendar" data-icon="calendar">Calendar</li>
					</ul>
				</div>
				<div class="cerb-ui-sidebar--section">
					<div class="cerb-ui-sidebar--label">Drag &amp; drop</div>
					<ul>
						<li data-target="sortable" data-icon="move-vertical">Sortable</li>
						<li data-target="draggable" data-icon="move">Draggable</li>
						<li data-target="droppable" data-icon="download">Droppable</li>
					</ul>
				</div>
			</div>
		</aside>

		<div class="cerb-ui-sidebar-layout--content cerb-uiref-content">

			<h2 class="cerb-uiref-grouplabel" id="group-foundations">Foundations</h2>
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/icon.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/utilities.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/effects.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/async.tpl"}

			<h2 class="cerb-uiref-grouplabel" id="group-layout">Layout</h2>
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/page.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/header.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/panel.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/agent-transcript.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/separator.tpl"}

			<h2 class="cerb-uiref-grouplabel" id="group-labels">Labels &amp; status</h2>
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/chip.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/tile.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/avatar.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/qrcode.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/pill.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/pip.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/kbd.tpl"}

			<h2 class="cerb-uiref-grouplabel" id="group-inputs">Inputs &amp; editors</h2>
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/button.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/toggle.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/slider.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/switcher.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/form.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/datepicker.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/colorpicker.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/iconpicker.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/priority-picker.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/selectmenu.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/record-chooser.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/context-chooser.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/text-chooser.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/value-picker.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/tag-input.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/file-upload.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/image-editor.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/searchquery.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/kataeditor.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/markdowneditor.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/jsoneditor.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/scriptingeditor.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/dataquery.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/diffviewer.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/node-editor.tpl"}

			<h2 class="cerb-uiref-grouplabel" id="group-navigation">Navigation</h2>
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/menu.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/toolbar.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/sidebar.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/tabs.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/accordion.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/splitpane.tpl"}

			<h2 class="cerb-uiref-grouplabel" id="group-overlays">Overlays &amp; feedback</h2>
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/tooltip.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/dialog.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/confirm.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/spinner.tpl"}

			<h2 class="cerb-uiref-grouplabel" id="group-dataviz">Data viz</h2>
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/color-scale.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/legend.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/distribution-bar.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/sparkchart.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/piechart.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/cartesian-chart.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/scatter-chart.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/timeblocks.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/gauge.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/map.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/timering.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/calendar.tpl"}

			<h2 class="cerb-uiref-grouplabel" id="group-dragdrop">Drag &amp; drop</h2>
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/sortable.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/draggable.tpl"}
			{include file="devblocks:cerberusweb.core::configuration/section/developers/ui_reference/components/droppable.tpl"}

		</div><!-- /.cerb-uiref-content -->
	</div><!-- /.cerb-uiref-layout -->
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

	// Component nav: a persistent CerbUI.Sidebar (grouped) — smooth-scroll + scrollspy + filter + collapse.
	// Each component's own demo wiring lives in its components/<slug>.tpl partial.
	(function() {
		const nav = document.getElementById('uiref-nav');
		const content = document.querySelector('.cerb-uiref-content');
		if(!nav || !content || !(window.CerbUI && CerbUI.Sidebar)) return;

		const sectionFor = function(id) {
			return id ? content.querySelector('#' + ((window.CSS && CSS.escape) ? CSS.escape(id) : id)) : null;
		};
		const goTo = function(id, push) {
			const el = sectionFor(id);
			if(!el) return;
			el.scrollIntoView({ behavior: 'smooth', block: 'start' });
			if(push) history.replaceState(null, '', '#' + id);
		};

		const sb = new CerbUI.Sidebar(nav, {
			fullHeight: true,
			filter: true,
			storageKey: 'uirefNavCollapsed',
			filterPlaceholder: 'Filter components…',
			onSelect: function(li) { goTo(li.dataset.target, true); return true; }
		});

		// Pin the rail to the viewport MINUS the page footer, so its filter never slips at the very bottom.
		const footer = document.getElementById('footer');
		const fit = function() { nav.style.height = 'calc(100vh - ' + ((footer && footer.offsetHeight) || 0) + 'px)'; };
		fit();
		window.addEventListener('resize', fit);

		// Keep the active item visible by scrolling only the rail's own body (never the window).
		const railBody = nav.querySelector('.cerb-ui-sidebar--body');
		const keepVisible = function(li) {
			if(!railBody) return;
			const lr = li.getBoundingClientRect(), br = railBody.getBoundingClientRect();
			if(lr.top < br.top) railBody.scrollTop -= (br.top - lr.top) + 8;
			else if(lr.bottom > br.bottom) railBody.scrollTop += (lr.bottom - br.bottom) + 8;
		};

		// Scrollspy: the section nearest the top of the viewport is the active one.
		const byId = new Map();
		nav.querySelectorAll('.cerb-ui-sidebar--item').forEach(function(li) { byId.set(li.dataset.target, li); });
		const markActive = function(id) {
			const li = byId.get(id);
			if(!li) return;
			sb.setActive(li);
			keepVisible(li);
		};
		const visible = new Set();
		const io = new IntersectionObserver(function(entries) {
			entries.forEach(function(e) {
				if(e.isIntersecting) visible.add(e.target); else visible.delete(e.target);
			});
			let top = null;
			visible.forEach(function(el) {
				if(!top || el.getBoundingClientRect().top < top.getBoundingClientRect().top) top = el;
			});
			if(top && top.id) markActive(top.id);
		}, { rootMargin: '0px 0px -75% 0px' });
		byId.forEach(function(li, id) { const el = sectionFor(id); if(el) io.observe(el); });

		// Deep link: scroll to + select the hash target. The editor components (Kata/Markdown/Json/
		// Scripting) init from collapsed textareas and grow tall AFTER this script runs, so a single
		// early (smooth) scroll commits to a stale offset and lands short — in an earlier section. Pin
		// the target on load, then re-pin while the content keeps resizing; bail on user input.
		const hash = (location.hash || '').replace(/^#/, '');
		if(hash && byId.has(hash)) {
			markActive(hash);
			const pin = function() {
				const el = sectionFor(hash);
				if(el) el.scrollIntoView({ block: 'start' });
			};
			const start = function() {
				pin();
				if(!window.ResizeObserver) return;
				let done = false;
				const stop = function() {
					if(done) return;
					done = true;
					ro.disconnect();
					window.removeEventListener('wheel', stop);
					window.removeEventListener('touchmove', stop);
					window.removeEventListener('keydown', stop);
				};
				const ro = new ResizeObserver(function() { if(!done) pin(); });
				ro.observe(content);
				window.addEventListener('wheel', stop, { passive: true });
				window.addEventListener('touchmove', stop, { passive: true });
				window.addEventListener('keydown', stop);
				setTimeout(stop, 1500);
			};
			if(document.readyState === 'complete') start();
			else window.addEventListener('load', start);
		}
	})();
})();
</script>
