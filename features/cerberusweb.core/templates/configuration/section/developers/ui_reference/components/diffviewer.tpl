	<div class="cerb-uiref-component" id="diffviewer">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-step-forward"></span>DiffViewer</div>

		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A read-only, side-by-side <b>KATA</b> diff &mdash; the plain-JS replacement for the old ace-diff changeset viewer. Two read-only <code>CerbUI.KataEditor</code> panes (left = a historical version, right = the current value) with per-line add/remove tints and IDEA-style bezier <b>connectors</b> drawn in a center gutter. It's a <b>viewer</b>, not a merge tool: read-only by default (see <code>editableCurrent</code> below to make the right pane editable), and no merge arrows/checkboxes either way. The diff is computed <b>client-side</b> (a line-level LCS &mdash; no server round-trip, no external library). A host <b>"Restore this version"</b> action copies the shown left document back to wherever the popup was opened from (<code>onRestore</code>). Use the <b>Step</b> button to walk the change blocks (<code>scrollToDiff</code>); swap the left version with <code>setLeft()</code></div>
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
	left:  historicalKata,            // the left (historical) document; CRLF/CR are folded to LF
	right: currentKata,               // the right (current) document
	mode: 'kata',                     // only 'kata' today (the panes are KataEditors); reserved
	lines: 24,                        // fixed visible height in rows (both panes scroll internally)
	editableCurrent: false,           // true → the RIGHT pane is editable, diff re-computes live (see below)
	collapseUnchanged: false,         // false | true | {context:3} — elide long identical runs (see below)
	onChange: (content) => {          // after an edit re-computes the diff (editableCurrent only)
		saveButton.disabled = ('' === content.trim());
	},
	onRestore: (content) => {         // wired to a host "Restore this version" button
		sourceEditor.setValue(content);
	},
});

// Documents
viewer.setLeft(otherChangesetKata);   // swap the historical pane (e.g. on a changeset-row click)
viewer.setCurrent(liveFieldValue);    // set the current pane (the field's working value)
viewer.getLeft();                     // the left document
viewer.getCurrent();                  // the right document — the edited text when editableCurrent

// Change blocks
viewer.getDiffs();                    // [{leftStartLine,leftEndLine,rightStartLine,rightEndLine}, …]
viewer.scrollToDiff(0);               // step toolbar: scroll BOTH panes to change-block 0

// Restore
viewer.onRestore(fn);                 // register/replace the handler after construction
viewer.hasRestore();                  // false → hide the host's "Restore this version" button
viewer.restore();                     // invoke onRestore with the current left document

viewer.destroy();                     // unbind scroll sync + both panes
CerbUI.DiffViewer.from(el);           // the instance built on a host element

// The panes are plain CerbUI.KataEditor instances — reach through for their APIs:
viewer.left; viewer.right;{/literal}</pre>
			</div>
		</div>

		{* Example 2: collapseUnchanged — elide the unchanged runs behind a clickable tear *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><b>collapseUnchanged</b> &mdash; on a long document that differs in a few places, the diff is mostly identical lines. This omits any run of unchanged lines longer than the context window, leaving a <b>perforated divider</b> in its place; <b>click a tear</b> to reveal just that run &mdash; it stays behind as a <b>solid</b> seam, so you can click again to re-collapse. (Perforated = a run is missing here; solid = nothing is.) The gutter keeps printing <b>real</b> line numbers, so they jump across a tear (1,&nbsp;2,&nbsp;3&hellip;&nbsp;47,&nbsp;48) &mdash; that's the signal that something's hidden. <b>Ctrl/&#8984;+F still finds text inside an elided run</b> and reveals it, because the elision reuses the editor's fold machinery rather than inventing a second way to hide a row. Both panes always elide the <em>same</em> rows: every unchanged line pairs 1:1 across the diff, so the two sides can't drift out of alignment</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-diffviewer-collapse"></div>
				<div class="cerb-uiref-result">40-line document, 3 changes &middot; <b id="uiref-diffviewer-collapse-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.DiffViewer(el, {
	left: before,
	right: after,
	lines: 18,
	collapseUnchanged: true,          // or { context: 3 } — lines to keep either side of each change
	dragKeys: true,                   // hover a key in the RIGHT pane to drag it out as a placeholder
});

// The popup that opens the viewer usually can't know what a dragged key should DO, so wire it after:
viewer.right.opts.onKeyClick = (payload) => scriptEditor.insertSnippet('{{' + payload.expr + '}}');{/literal}</pre>
			</div>
		</div>

		{* Example 3: editableCurrent — the read-only default, and how to opt out of it *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><b>editableCurrent</b> &mdash; both panes are <b>read-only</b> by default; this is a viewer. Pass <code>editableCurrent: true</code> to make the <b>right</b> (current) pane editable: the diff re-computes as you type, so the tints and connectors track your edits, and <code>onChange</code> relays the new value. The <b>left</b> pane stays read-only either way &mdash; it's a <em>before</em> document, and editing history isn't a thing. This is a light manual-merge affordance, not a merge tool: still no merge arrows or checkboxes, so the way you take a line from the left is to retype it on the right. Read the result back with <code>getCurrent()</code> when the host saves. The editable pane gets a <code>.cerb-ui-diffviewer--editable</code> class for styling. Used by the <b>automation policy generator</b>, where the generated policy is the left pane and you tweak the proposed one on the right before accepting</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-diffviewer-editable"></div>
				<div class="cerb-uiref-result">Edit the right pane &middot; <b id="uiref-diffviewer-editable-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const viewer = new CerbUI.DiffViewer(el, {
	left: generatedKata,              // read-only, always
	right: proposedKata,              // editable below
	lines: 14,
	editableCurrent: true,            // right pane becomes a live editor; the diff re-computes as you type
	onChange: (content) => {          // fired after each edit re-computes the diff (editableCurrent only)
		saveButton.disabled = ('' === content.trim());
	},
});

viewer.getCurrent();                 // the edited right document — read it back when the host saves{/literal}</pre>
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

// collapseUnchanged — a long, mostly-identical document, so the tears have something to hide
(function() {
	const el = document.getElementById('uiref-diffviewer-collapse');
	if(!el || !window.CerbUI || !CerbUI.DiffViewer)
		return;
{literal}
	const before = [];
	for(let i = 1; i <= 40; i++) before.push('key_' + String(i).padStart(2, '0') + ': value ' + i);

	const after = before.slice();
	after[4] = 'key_05: CHANGED value';
	after[21] = 'key_22: CHANGED value';
	after.splice(31, 0, 'key_31b: INSERTED');

	const viewer = new CerbUI.DiffViewer(el, {
		left: before.join('\n'),
		right: after.join('\n'),
		lines: 18,
		collapseUnchanged: true,
		dragKeys: true
	});

	const out = document.getElementById('uiref-diffviewer-collapse-out');
	if(out) out.textContent = viewer.getDiffs().length + ' change blocks — click a tear to expand that run';
{/literal}
})();

// editableCurrent — the right pane is a live editor; the diff re-computes on every keystroke
(function() {
	const el = document.getElementById('uiref-diffviewer-editable');
	if(!el || !window.CerbUI || !CerbUI.DiffViewer)
		return;
{literal}
	const generated = [
		'commands:',
		'  record.create:',
		'    deny/type@bool: {{inputs.record_type is not record type (\'ticket\')}}',
		'    allow@bool: yes',
		'  record.update:',
		'    deny/type@bool: {{inputs.record_type is not record type (\'ticket\')}}',
		'    allow@bool: yes'
	].join('\n');

	const proposed = [
		'commands:',
		'  record.create:',
		'    deny/type@bool: {{inputs.record_type is not record type (\'ticket\',\'task\')}}',
		'    allow@bool: yes',
		'  record.update:',
		'    deny/type@bool: {{inputs.record_type is not record type (\'ticket\')}}',
		'    allow@bool: yes'
	].join('\n');

	const out = document.getElementById('uiref-diffviewer-editable-out');
	const report = function(viewer) {
		if(out) out.textContent = viewer.getDiffs().length + ' change blocks · ' + viewer.getCurrent().split('\n').length + ' lines';
	};

	const viewer = new CerbUI.DiffViewer(el, {
		left: generated,
		right: proposed,
		lines: 14,
		editableCurrent: true,
		onChange: function() { report(viewer); }
	});

	report(viewer);
{/literal}
})();
</script>
