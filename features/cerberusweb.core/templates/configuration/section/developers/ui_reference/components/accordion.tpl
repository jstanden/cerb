	<div class="cerb-uiref-component" id="accordion">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-chevron-right"></span>Accordion</div>

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

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
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
})();
</script>
