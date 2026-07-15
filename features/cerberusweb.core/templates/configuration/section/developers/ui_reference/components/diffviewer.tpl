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
	lines: 16,                        // fixed visible height in rows (both panes scroll internally)
	mode: 'kata',                     // only 'kata' today (the panes are KataEditors); reserved
	editableCurrent: false,           // true → the RIGHT pane is editable, diff re-computes live (see below)
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
viewer.restore();                     // invoke onRestore with the current left document{/literal}</pre>

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
