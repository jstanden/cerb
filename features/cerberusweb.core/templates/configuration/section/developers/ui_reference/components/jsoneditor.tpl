	<div class="cerb-uiref-component" id="jsoneditor">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-console"></span>JsonEditor</div>

		{* Example 1: editable — JSON syntax highlighting + bracket folding + a seeded gutter marker *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A multi-line textarea editor for <b>JSON</b> &mdash; a sibling of <code>KataEditor</code> with a much simpler tokenizer and <b>bracket-based folding</b>. Live highlighting (property names, string values, numbers, <code>true</code>/<code>false</code>/<code>null</code>), a line-number gutter, indentation guides, and Tab = 2 spaces / Shift+Tab dedent. <b>Folding:</b> click a chevron next to an object <code>{literal}{&hellip;}{/literal}</code> or array <code>[&hellip;]</code> header to collapse it (the closing bracket line stays visible); <kbd class="cerb-ui-kbd">Mod</kbd>+<kbd class="cerb-ui-kbd">[</kbd> / <kbd class="cerb-ui-kbd">]</kbd> fold/unfold at the caret. <b>Gutter markers</b> render left of the numbers (the hook for client-side JSON validation) &mdash; this demo seeds an <code>info</code> marker on line&nbsp;3</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-jsoneditor" id="uiref-jsoneditor-edit">
					<div class="cerb-ui-jsoneditor--gutter" aria-hidden="true"></div>
					<div class="cerb-ui-jsoneditor--field">
						<div class="cerb-ui-jsoneditor--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-jsoneditor--input" data-editor-lines="16" spellcheck="false">{literal}{
  "name": "Cerb",
  "version": 11.2,
  "enabled": true,
  "tags": ["helpdesk", "automation"],
  "owner": {
    "email": "team@cerb.ai",
    "active": true
  },
  "limit": null
}{/literal}</textarea>
						<span class="cerb-ui-jsoneditor--caret-anchor"></span>
					</div>
				</div>
				<div class="cerb-uiref-result">State &middot; cursor row: <b id="uiref-jsoneditor-edit-row">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- A left line-number gutter, then the field: a colored --highlight mirror under the transparent --input. --&gt;
&lt;div class="cerb-ui-jsoneditor" id="ed"&gt;
	&lt;div class="cerb-ui-jsoneditor--gutter" aria-hidden="true"&gt;&lt;/div&gt;
	&lt;div class="cerb-ui-jsoneditor--field"&gt;
		&lt;div class="cerb-ui-jsoneditor--highlight" aria-hidden="true"&gt;&lt;/div&gt;
		&lt;textarea class="cerb-ui-jsoneditor--input" name="json" data-editor-lines="16" spellcheck="false"&gt;&lt;/textarea&gt;
		&lt;span class="cerb-ui-jsoneditor--caret-anchor"&gt;&lt;/span&gt;
	&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const ed = new CerbUI.JsonEditor(document.getElementById('ed'), {
	minLines: 6,
	maxLines: 16,                // or set data-editor-lines on the textarea
	indentGuides: true,          // faint vertical rule per indent level (default true)
	tabSize: 2,                  // a Tab inserts this many spaces (default 2)
	// onGutterClick: (modelRow, e) => {}, // click the left marker margin (e.g. toggle a breakpoint)
});

// Folding is bracket-based: fold an object/array by its opening-bracket row.
ed.foldAll();                  // collapse every foldable object/array
ed.setMarker(2, { type:'info', title:'A gutter marker' });{/literal}</pre>
			</div>
		</div>

		{* Example 2: read-only viewer — highlighting + folding, no editing (for result/output payloads) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Read-only viewer &mdash; pass <code>readOnly: true</code> (or <code>data-editor-readonly</code> on the textarea) for an output/result payload: syntax highlighting and folding still work, but the text can't be edited (typing and the editing shortcuts are disabled)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-jsoneditor" id="uiref-jsoneditor-readonly">
					<div class="cerb-ui-jsoneditor--gutter" aria-hidden="true"></div>
					<div class="cerb-ui-jsoneditor--field">
						<div class="cerb-ui-jsoneditor--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-jsoneditor--input" data-editor-lines="14" data-editor-readonly spellcheck="false">{literal}{
  "results": [
    { "id": 1, "subject": "Order shipped", "open": false },
    { "id": 2, "subject": "Refund request", "open": true }
  ],
  "count": 2,
  "took_ms": 18.4
}{/literal}</textarea>
						<span class="cerb-ui-jsoneditor--caret-anchor"></span>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.JsonEditor(el, { readOnly: true });   // highlight + fold, no editing{/literal}</pre>
			</div>
		</div>

		{* Reference (no live demo): the public API, one method per line with a worked example *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">The API &mdash; value &amp; folding rows are <b>MODEL</b> space; <code>getValue()</code> returns the whole document even when lines are folded away</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}ed.getValue();                                  // full document text (incl. folded-away lines)
ed.setValue('{"a":1}');                         // replace the document (drops folds + markers)
ed.focus();
ed.getSelectedText();                           // the currently selected text
ed.clearSelection();                            // collapse the selection to the caret
ed.getCursorPosition();                         // {row, column} — 0-based, MODEL row
ed.setCursorPosition(2, 4);                     // 0-based row, column
ed.gotoLine(3, 0);                              // 1-based row, 0-based column (reveals a folded target)
ed.scrollToLine(10);                            // scroll a MODEL row into view
ed.insertSnippet('"key": $0');                  // $0 marks where the caret lands
ed.getLine(2);                                  // text of a MODEL row
ed.onChange((value) => { /* e.g. live-validate */ });

// Code folding (MODEL rows) — fold an object/array by its opening-bracket row:
ed.fold(4);                                     // collapse the object/array opening at row 4
ed.unfold(4);                                   // expand it
ed.toggleFold(4);
ed.foldAll();                                   // collapse every foldable object/array
ed.unfoldAll();
ed.isFolded(4);                                 // true if the bracket at row 4 is collapsed

// Keyboard shortcuts (e.g. to render a hint popup):
ed.getShortcuts();                              // [{id, label, keys:['⌘[', …]}, …]

// Gutter markers — LEFT of the line numbers (MODEL rows); the hook for JSON validation:
ed.setMarker(2, {type:'error', title:'Expected ","'});    // type: error | warning | info | breakpoint
ed.setMarker(4, {icon:'alert', color:'orange', title:'Custom'}); // any cerb-icon + a tag color
ed.clearMarker(2);                              // remove one row's marker
ed.getMarkers();                                // Map(modelRow -> {type,icon,color,title,pip})
ed.clearMarkers();                              // remove every marker{/literal}</pre>
			</div>
		</div>

		{* Reference (no live demo): the built-in keyboard shortcuts — also enumerable via getShortcuts() *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Keyboard shortcuts &mdash; <kbd class="cerb-ui-kbd">Mod</kbd> is <kbd class="cerb-ui-kbd">&#8984;</kbd> on macOS, <kbd class="cerb-ui-kbd">Ctrl</kbd> on Windows &amp; Linux. (In <code>readOnly</code> mode only the fold / resize shortcuts are active.)</div>
		</div>
		<div class="cerb-uiref-example">
			<ul class="cerb-uiref-keys">
				<li><kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">[</kbd> / <kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">]</kbd> &mdash; fold / unfold the object or array at the caret</li>
				<li><kbd class="cerb-ui-kbd">Tab</kbd> / <kbd class="cerb-ui-kbd">Shift</kbd> + <kbd class="cerb-ui-kbd">Tab</kbd> &mdash; indent / dedent the current line or selection</li>
				<li><kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">D</kbd> or <kbd class="cerb-ui-kbd">Alt</kbd> + <kbd class="cerb-ui-kbd">D</kbd> &mdash; delete the current line</li>
				<li><kbd class="cerb-ui-kbd">Alt</kbd> + <kbd class="cerb-ui-kbd">&uarr;</kbd> / <kbd class="cerb-ui-kbd">Alt</kbd> + <kbd class="cerb-ui-kbd">&darr;</kbd> &mdash; move the current line or selection up / down</li>
				<li><kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">Shift</kbd> + <kbd class="cerb-ui-kbd">&darr;</kbd> / <kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">Shift</kbd> + <kbd class="cerb-ui-kbd">&uarr;</kbd> &mdash; grow / shrink the editor height</li>
				<li><kbd class="cerb-ui-kbd">Enter</kbd> &mdash; new line, copying the indent (and one extra level after an open <code>{literal}{{/literal}</code> or <code>[</code>)</li>
			</ul>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// JsonEditor #1 — editable, with a seeded gutter marker and a live cursor-row readout
	(function() {
		const el = document.getElementById('uiref-jsoneditor-edit');
		const out = document.getElementById('uiref-jsoneditor-edit-row');
		if(el && window.CerbUI && CerbUI.JsonEditor) {
			const ed = new CerbUI.JsonEditor(el, {
				minLines: 6,
				maxLines: 16,
				// Click the left gutter margin to toggle a breakpoint pip (markers render left of the numbers).
				onGutterClick: function(row) {
					const mk = ed.getMarkers().get(row);
					if(mk && mk.type === 'breakpoint') ed.clearMarker(row);
					else ed.setMarker(row, { type:'breakpoint', title:'Breakpoint' });
				},
			});
			// A seeded marker so the gutter column shows an icon example.
			ed.setMarker(2, { type:'info', title:'Example marker' });
			const ta = el.querySelector('.cerb-ui-jsoneditor--input');
			const showRow = function() {
				if(out) out.textContent = ed.getCursorPosition().row;
			};
			ta.addEventListener('keyup', showRow);
			ta.addEventListener('click', showRow);
			showRow();
		}
	})();

	// JsonEditor #2 — a read-only result viewer (folding + highlighting still work)
	(function() {
		const el = document.getElementById('uiref-jsoneditor-readonly');
		if(el && window.CerbUI && CerbUI.JsonEditor) {
			new CerbUI.JsonEditor(el, { readOnly: true, minLines: 4, maxLines: 14 });
		}
	})();
})();
</script>
