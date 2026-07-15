	<div class="cerb-uiref-component" id="splitpane">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-split-pane"></span>SplitPane</div>

		{* Example 1: horizontal (side-by-side) split with a draggable divider *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Two panes separated by a <b>draggable divider</b> &mdash; enhances a container of exactly two element children. <code>orientation:'horizontal'</code> lays them side-by-side (left/right) with a vertical divider you drag horizontally. The divider is an <b>always-visible seam line</b> (so it marks the boundary even when both panes share the page background &mdash; no shading needed); the grip handle appears on hover/focus. Drag the seam, or focus it and use &larr;/&rarr;/Home/End; double-click resets to the default ratio</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-splitpane-basic" style="height:170px; border:1px solid var(--cerb-color-background-contrast-230); border-radius:6px; overflow:hidden;">
					<div style="padding:12px;">
						<b>Left pane</b>
						<div class="cerb-u-fgg-6" style="margin-top:4px;">Your own markup &mdash; no background shading. The seam line alone marks the boundary.</div>
					</div>
					<div style="padding:12px;">
						<b>Right pane</b>
						<div class="cerb-u-fgg-6" style="margin-top:4px;">The second pane fills whatever the first leaves.</div>
					</div>
				</div>
				<div class="cerb-uiref-result" style="margin-top:0.7em;">First pane: <b id="uiref-splitpane-basic-result">50%</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- a container with exactly two element children (the panes); give it a size --&gt;
&lt;div id="sp" style="height:170px;"&gt;
	&lt;div&gt;Left pane&lt;/div&gt;
	&lt;div&gt;Right pane&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const sp = new CerbUI.SplitPane(el, {
	orientation: 'horizontal', // 'horizontal' = L/R (vertical divider) | 'vertical' = T/B (horizontal divider)
	ratio: 0.5,                // initial size of the FIRST pane (0..1)
	min: 0.1,                  // min for either pane (0..1 ratio, or a px number resolved vs the container)
	onResize:    (ratio) => { /* live while dragging (rAF-throttled) */ },
	onResizeEnd: (ratio) => { /* on release — persist / re-render a preview here */ },
});

// also fires a DOM event on the container:
el.addEventListener('cerb-ui-splitpane:resize', e =&gt; console.log(e.detail)); // { ratio, phase }

// public API: sp.setRatio(0.33); sp.getRatio(); sp.setOrientation('vertical'); sp.destroy();
// collapse a pane entirely (ratio preserved): sp.collapse('second'); sp.expand(); sp.toggle('second'); sp.isCollapsed();{/literal}</pre>
			</div>
		</div>

		{* Example 2: vertical (stacked) split, off-center start, snap points, persisted ratio *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Vertical (stacked top/bottom) with <code>orientation:'vertical'</code>, a one-third start (<code>ratio:0.33</code>), <code>snap</code> points the divider catches near, and a <code>storageKey</code> that persists the ratio across reloads</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-splitpane-vertical" style="height:240px; border:1px solid var(--cerb-color-background-contrast-230); border-radius:6px; overflow:hidden;">
					<div style="padding:12px; background:var(--cerb-color-background-contrast-250);">
						<b>Top pane</b>
						<div class="cerb-u-fgg-6" style="margin-top:4px;">Drag the horizontal divider; it snaps near &#8531;, &#189;, and &#8532;.</div>
					</div>
					<div style="padding:12px;">
						<b>Bottom pane</b>
						<div class="cerb-u-fgg-6" style="margin-top:4px;">Reload the page &mdash; the divider remembers where you left it.</div>
					</div>
				</div>
				<div class="cerb-uiref-result" style="margin-top:0.7em;">Top pane: <b id="uiref-splitpane-vertical-result">33%</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.SplitPane(el, {
	orientation: 'vertical',
	ratio: 0.33,
	snap: [0.33, 0.5, 0.67],   // catch near these ratios (within snapThreshold, default 0.02)
	storageKey: 'uiref-splitpane-vertical',
});{/literal}</pre>
			</div>
		</div>

		{* Example 3: flagship — generated KATA on one side, a live visual preview on the other *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><b>KATA + live preview</b> &mdash; the intended use: a <code>CerbUI.KataEditor</code> on one side, a rendered preview on the other, re-rendered as you type (debounced). Edit <code>title:</code>, <code>color:</code> (a Cerb tag color), or <code>bars:</code> and watch the right pane update; drag the divider to trade editing room for preview room</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-splitpane-preview" style="height:260px; border:1px solid var(--cerb-color-background-contrast-230); border-radius:6px; overflow:hidden;">
					<div style="padding:6px;">
						<textarea id="uiref-splitpane-preview-kata" data-editor-lines="12" spellcheck="false"># edit me — the preview updates live
title: Weekly volume
color: blue
bars: 5
</textarea>
					</div>
					<div id="uiref-splitpane-preview-out" style="padding:16px;"></div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const ed = new CerbUI.KataEditor(editorEl, { minLines: 6, maxLines: 12 });
let t;
ed.onChange((value) =&gt; {          // debounce, then re-render the preview pane
	clearTimeout(t);
	t = setTimeout(() =&gt; renderPreview(value), 150);
});
new CerbUI.SplitPane(splitEl, { ratio: 0.5, min: 0.2 });{/literal}</pre>
			</div>
		</div>

		{* Example 4: collapse a pane entirely — start with one side hidden, reveal it from a toolbar toggle *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><b>Collapse a pane</b> &mdash; <code>collapse('first'|'second')</code> / <code>expand()</code> / <code>toggle()</code> hide a pane <b>entirely</b> (with the divider) so the other fills 100%; the ratio is preserved and restored on expand. Start with <code>collapsed:'second'</code> and reveal the second pane from a toolbar button &mdash; the reply-form / automation-editor "open the preview" pattern. (A tiny <code>ratio</code> like&nbsp;<code>0</code>&nbsp;won't hide a pane &mdash; it clamps to <code>min</code>; use these methods.)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<button type="button" class="cerb-ui-button" id="uiref-splitpane-toggle-btn"><span class="cerb-icons cerb-icon-split-pane"></span> Show preview</button>
				<div id="uiref-splitpane-toggle" style="height:190px; margin-top:8px; border:1px solid var(--cerb-color-background-contrast-230); border-radius:6px; overflow:hidden;">
					<div style="padding:12px;">
						<b>Editor</b>
						<div class="cerb-u-fgg-6" style="margin-top:4px;">Full width until you toggle the preview &mdash; no wasted space when the second pane is hidden.</div>
					</div>
					<div style="padding:12px;">
						<b>Preview</b>
						<div class="cerb-u-fgg-6" style="margin-top:4px;">Hidden by default (<code>collapsed:'second'</code>); revealed by the button, then resizable.</div>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const sp = new CerbUI.SplitPane(el, {
	ratio: 0.5,
	collapsed: 'second',   // start with the second pane hidden (editor fills the container)
});

// a toolbar button reveals / hides the preview pane; onToggle keeps the button label in sync
btn.addEventListener('click', () =&gt; sp.toggle('second'));
el.addEventListener('cerb-ui-splitpane:toggle', e =&gt; {
	btn.textContent = e.detail.collapsed ? 'Show preview' : 'Hide preview';
});{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
{literal}
(function() {
	// SplitPane #1 — horizontal, reporting the live ratio
	(function() {
		const el = document.getElementById('uiref-splitpane-basic');
		const out = document.getElementById('uiref-splitpane-basic-result');
		if(el && window.CerbUI && CerbUI.SplitPane) {
			const show = function(ratio) { if(out) out.textContent = Math.round(ratio * 100) + '%'; };
			new CerbUI.SplitPane(el, { ratio: 0.5, min: 0.15, onResize: show, onResizeEnd: show });
		}
	})();

	// SplitPane #2 — vertical, snap points, persisted ratio
	(function() {
		const el = document.getElementById('uiref-splitpane-vertical');
		const out = document.getElementById('uiref-splitpane-vertical-result');
		if(el && window.CerbUI && CerbUI.SplitPane) {
			const show = function(ratio) { if(out) out.textContent = Math.round(ratio * 100) + '%'; };
			const sp = new CerbUI.SplitPane(el, {
				orientation: 'vertical',
				ratio: 0.33,
				snap: [0.33, 0.5, 0.67],
				storageKey: 'uiref-splitpane-vertical',
				onResize: show,
				onResizeEnd: show,
			});
			show(sp.getRatio());
		}
	})();

	// SplitPane #3 — flagship: KataEditor + a live, client-side visual preview
	(function() {
		const el = document.getElementById('uiref-splitpane-preview');
		const edEl = document.getElementById('uiref-splitpane-preview-kata');
		const outEl = document.getElementById('uiref-splitpane-preview-out');
		if(!el || !edEl || !outEl || !window.CerbUI || !CerbUI.SplitPane || !CerbUI.KataEditor) return;

		const TAG_COLORS = ['red', 'orange', 'green', 'blue', 'purple', 'gray'];

		// Trivial line-based parse (key: value) into a tiny config — stands in for a real KATA→config step.
		const parse = function(text) {
			const cfg = { title: 'Preview', color: 'blue', bars: 3 };
			text.split('\n').forEach(function(line) {
				const m = line.match(/^\s*([a-z_]+)\s*:\s*(.*)$/i);
				if(!m) return;
				const k = m[1].toLowerCase();
				const v = m[2].trim();
				if(k === 'title') cfg.title = v;
				else if(k === 'color') cfg.color = v.toLowerCase();
				else if(k === 'bars') cfg.bars = Math.max(0, Math.min(12, parseInt(v, 10) || 0));
			});
			return cfg;
		};

		const render = function(text) {
			const cfg = parse(text);
			const color = TAG_COLORS.indexOf(cfg.color) >= 0 ? ('var(--cerb-color-tag-' + cfg.color + ')') : cfg.color;
			outEl.textContent = '';

			const h = document.createElement('div');
			h.style.cssText = 'font-weight:600; margin-bottom:10px;';
			h.textContent = cfg.title || '(untitled)'; // textContent — never trust the typed title as HTML

			const accent = document.createElement('div');
			accent.style.cssText = 'height:4px; border-radius:999px; margin-bottom:14px; background:' + color + ';';

			const row = document.createElement('div');
			row.style.cssText = 'display:flex; align-items:flex-end; gap:6px; height:120px;';
			for(let i = 0; i < cfg.bars; i++) {
				const bar = document.createElement('div');
				const pct = 30 + Math.round(70 * ((i + 1) / Math.max(1, cfg.bars)));
				bar.style.cssText = 'flex:1 1 0; min-width:8px; border-radius:3px 3px 0 0; height:' + pct + '%; background:' + color + '; opacity:0.85;';
				row.appendChild(bar);
			}

			outEl.appendChild(h);
			outEl.appendChild(accent);
			outEl.appendChild(row);
		};

		const ed = new CerbUI.KataEditor(edEl, { minLines: 6, maxLines: 12 });
		let timer;
		ed.onChange(function(value) {
			clearTimeout(timer);
			timer = setTimeout(function() { render(value); }, 150);
		});
		render(ed.getValue());

		new CerbUI.SplitPane(el, { ratio: 0.5, min: 0.2 });
	})();

	// SplitPane #4 — start collapsed, reveal the second pane from a toolbar toggle
	(function() {
		const el = document.getElementById('uiref-splitpane-toggle');
		const btn = document.getElementById('uiref-splitpane-toggle-btn');
		if(!el || !btn || !window.CerbUI || !CerbUI.SplitPane) return;
		const sp = new CerbUI.SplitPane(el, { ratio: 0.5, collapsed: 'second' });
		btn.addEventListener('click', function() { sp.toggle('second'); });
		el.addEventListener('cerb-ui-splitpane:toggle', function(e) {
			btn.lastChild.textContent = e.detail.collapsed ? ' Show preview' : ' Hide preview';
		});
	})();
})();
{/literal}
</script>
