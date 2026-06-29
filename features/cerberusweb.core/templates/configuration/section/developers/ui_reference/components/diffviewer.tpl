	<div class="cerb-uiref-component" id="diffviewer">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-step-forward"></span>DiffViewer</div>

		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A read-only, side-by-side <b>KATA</b> diff &mdash; the plain-JS replacement for the old ace-diff changeset viewer. Two read-only <code>CerbUI.KataEditor</code> panes (left = a historical version, right = the current value) with per-line add/remove tints and IDEA-style bezier <b>connectors</b> drawn in a center gutter. It's a <b>viewer</b>, not a merge tool: no editing, no merge arrows/checkboxes. The diff is computed <b>client-side</b> (a line-level LCS &mdash; no server round-trip, no external library). A host <b>"Restore this version"</b> action copies the shown left document back to wherever the popup was opened from (<code>onRestore</code>). Use the <b>Step</b> button to walk the change blocks (<code>scrollToDiff</code>); swap the left version with <code>setLeft()</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-toolbar-strip" style="margin-bottom:6px;">
					<button type="button" class="cerb-ui-toolbar-button" id="uiref-diffviewer-step"><span class="cerb-icons cerb-icon-step-forward"></span> Step</button>
					<button type="button" class="cerb-ui-toolbar-button" id="uiref-diffviewer-restore"><span class="cerb-icons cerb-icon-history"></span> Restore this version</button>
				</div>
				<div id="uiref-diffviewer"></div>
				<div class="cerb-uiref-result">Last restore &middot; <b id="uiref-diffviewer-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- The component builds its own DOM (two .cerb-ui-kataeditor panes + a center SVG) into an empty host. --&gt;
&lt;div id="diff"&gt;&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const viewer = new CerbUI.DiffViewer(document.getElementById('diff'), {
	left:  historicalKata,            // the left (historical) document
	right: currentKata,               // the right (current) document
	lines: 16,                        // fixed visible height in rows (both panes scroll internally)
	onRestore: (content) => {         // wired to a host "Restore this version" button
		sourceEditor.setValue(content);
	},
});

viewer.setLeft(otherChangesetKata);   // swap the historical pane (e.g. on a changeset-row click)
viewer.setCurrent(liveFieldValue);    // set the current pane (the field's working value)
viewer.getDiffs();                    // [{leftStartLine,leftEndLine,rightStartLine,rightEndLine}, …]
viewer.scrollToDiff(0);               // step toolbar: scroll BOTH panes to change-block 0
viewer.restore();                     // invoke onRestore with the current left document{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const el = document.getElementById('uiref-diffviewer');
	if(!el || !window.CerbUI || !CerbUI.DiffViewer)
		return;
{literal}
	const left = [
		'type: bar',
		'title: Tickets by status',
		'x_axis:',
		'  field: status',
		'  label: Status',
		'y_axis:',
		'  field: count',
		'series/open:',
		'  metric: ticket.open',
		'  function: count',
		''
	].join('\n');

	const right = [
		'type: line',
		'title: Tickets by status',
		'subtitle: last 30 days',
		'x_axis:',
		'  field: status',
		'  label: Status',
		'series/open:',
		'  metric: ticket.open',
		'  function: count',
		'series/closed:',
		'  metric: ticket.closed',
		'  function: count',
		''
	].join('\n');

	const out = document.getElementById('uiref-diffviewer-out');

	const viewer = new CerbUI.DiffViewer(el, {
		left: left,
		right: right,
		lines: 16,
		onRestore: function(content) {
			if(out) out.textContent = content.split('\n').length + ' lines restored';
		}
	});

	let step = -1;
	const $step = document.getElementById('uiref-diffviewer-step');
	if($step) $step.addEventListener('click', function() {
		const count = viewer.getDiffs().length;
		if(!count) return;
		step = (step + 1) % count;
		viewer.scrollToDiff(step);
	});

	const $restore = document.getElementById('uiref-diffviewer-restore');
	if($restore) $restore.addEventListener('click', function() { viewer.restore(); });
{/literal}
})();
</script>
