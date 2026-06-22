	<div class="cerb-uiref-component" id="kataeditor">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-placeholders"></span>KataEditor</div>

		{* Example 1: schema-driven — the kataFieldSource adapter against a real cerbAutocompleteSuggestions schema *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A multi-line textarea editor for Cerb's <b>KATA</b> syntax (the eventual Ace replacement) &mdash; live highlighting (keys, <code>/identifiers</code>, <code>@annotations</code>, values, <code>{literal}{{ scripting }}{/literal}</code>, <code>#&nbsp;comments</code>), a line-number gutter, indentation guides (<code>indentGuides</code>, on by default), and Tab = 2 spaces / Shift+Tab dedent. <b>The usual setup:</b> <code>kataFieldSource(schema)</code> wires KATA autocomplete to a path-keyed suggestion map (static keys + dynamic types that hit Cerb's endpoints). Ctrl/&#8984;+Space to suggest, &darr; to pick. Try a new <code>series:</code> child, or <code>metric:</code> (dynamic names) and <code>filters:</code> (dynamic dimensions). <b>KataScript</b> autocomplete is built in wherever a tag is open (each suggestion shows a colored type icon): <code>{literal}{%{/literal} f</code> &rarr; <code>for</code> (command), <code>{literal}{{{/literal} </code> &rarr; functions, after a <code>|</code> pipe &rarr; filters, and inside a call's <code>(&hellip;</code> &rarr; that function/filter's <b>arguments</b> (try <code>{literal}{{ array_column({/literal}</code>). <b>Gutter markers</b> render left of the numbers &mdash; this demo seeds a warning on line&nbsp;3 and toggles a breakpoint pip when you click the left margin</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-kataeditor" id="uiref-kataeditor-schema">
					<div class="cerb-ui-kataeditor--gutter" aria-hidden="true"></div>
					<div class="cerb-ui-kataeditor--field">
						<div class="cerb-ui-kataeditor--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-kataeditor--input" data-editor-lines="16" spellcheck="false"># Metrics Explorer series — Ctrl/⌘+Space to suggest
series/opened:
  metric:
  function: count
  label: Opened tickets
</textarea>
						<span class="cerb-ui-kataeditor--caret-anchor"></span>
					</div>
				</div>
				<div class="cerb-uiref-result">State &middot; path at caret: <b id="uiref-kataeditor-schema-path">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- A left line-number gutter, then the field: a colored --highlight mirror under the transparent --input. --&gt;
&lt;div class="cerb-ui-kataeditor" id="ed"&gt;
	&lt;div class="cerb-ui-kataeditor--gutter" aria-hidden="true"&gt;&lt;/div&gt;
	&lt;div class="cerb-ui-kataeditor--field"&gt;
		&lt;div class="cerb-ui-kataeditor--highlight" aria-hidden="true"&gt;&lt;/div&gt;
		&lt;textarea class="cerb-ui-kataeditor--input" name="kata" data-editor-lines="16" spellcheck="false"&gt;&lt;/textarea&gt;
		&lt;span class="cerb-ui-kataeditor--caret-anchor"&gt;&lt;/span&gt;
	&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const el = document.getElementById('ed');

const ed = new CerbUI.KataEditor(el, {
	minLines: 6,
	maxLines: 16,                // or set data-editor-lines on the textarea
	indentGuides: true,          // faint vertical rule per indent level, continued across blank lines (default true)
	// kataFieldSource(map, opts?) — a ready-made source: looks the normalized key-path up in a path-keyed
	// suggestion map. Values are a static Array, or a dynamic { type, params? } that POSTs to Cerb's
	// c=ui&a=kataSuggestions<Type>Json endpoints (reading sibling values back out of the editor).
	onAutocomplete: CerbUI.KataEditor.kataFieldSource(cerbAutocompleteSuggestions.kataSchemaMetricsExplorerSeries),
	// onGutterClick: (modelRow, e) => {}, // click the left marker margin (e.g. toggle a breakpoint)
});{/literal}</pre>
			</div>
		</div>

		{* Reference (no live demo): the full Ace-parity API, one method per line with a worked example *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">The full API &mdash; every public method with a worked example (value &amp; folding rows are <b>MODEL</b> space; <code>getValue()</code> returns the whole document even when lines are folded away)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// The Ace-parity API the automation editor needs:
ed.getValue();                                  // full document text (incl. folded-away lines)
ed.setValue('series/opened:\n  metric: ticket.created');
ed.focus();
ed.getSelectedText();                           // the currently selected text
ed.clearSelection();                            // collapse the selection to the caret
ed.getCursorPosition();                         // {row, column} — 0-based, MODEL row
ed.setCursorPosition(2, 4);                     // 0-based row, column
ed.gotoLine(3, 0);                              // 1-based row, 0-based column (reveals a folded target)
ed.scrollToLine(10);                            // scroll a MODEL row into view
ed.insertSnippet('label: $0');                  // $0 marks where the caret lands
ed.getTokenPath();                              // ['series/opened:','metric:'] — KATA key-path at the caret
ed.getRowByPath('series:metric:');              // MODEL row for a colon-joined path, or false
ed.getLine(2);                                  // text of a MODEL row
ed.onChange((value) => { /* e.g. sync a diff editor */ });

// Code folding (MODEL rows):
ed.fold(1);                                     // collapse the subtree under the header at row 1
ed.unfold(1);                                   // expand it
ed.toggleFold(1);
ed.foldAll();                                   // collapse every foldable header
ed.unfoldAll();
ed.isFolded(1);                                 // true if the header at row 1 is collapsed

// Keyboard shortcuts (e.g. to render a hint popup):
ed.getShortcuts();                              // [{id, label, keys:['⌘D', …]}, …]

// Gutter markers — LEFT of the line numbers (MODEL rows):
ed.setMarker(2, {type:'error', title:'Unknown field'});   // type: error | warning | info | breakpoint
ed.setMarker(4, {type:'warning', title:'Deprecated'});
ed.setMarker(6, {icon:'stopwatch', color:'purple', title:'await:'}); // any cerb-icon + a tag color
ed.setMarker(8, {pip:true, color:'red', title:'Breakpoint'});        // a colored dot instead of an icon
ed.clearMarker(2);                              // remove one row's marker
ed.getMarkers();                                // Map(modelRow -> {type,icon,color,title,pip})
ed.clearMarkers();                              // remove every marker{/literal}</pre>
			</div>
		</div>

		{* Reference (no live demo): the built-in keyboard shortcuts — also enumerable at runtime via getShortcuts() *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Keyboard shortcuts &mdash; <kbd class="cerb-ui-kbd">Mod</kbd> is <kbd class="cerb-ui-kbd">&#8984;</kbd> on macOS, <kbd class="cerb-ui-kbd">Ctrl</kbd> on Windows &amp; Linux. The same set is enumerable at runtime via <code>ed.getShortcuts()</code> (with OS-appropriate labels) to build a hint popup</div>
		</div>
		<div class="cerb-uiref-example">
			<ul class="cerb-uiref-keys">
				<li><kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">Space</kbd> &mdash; show autocomplete suggestions (<kbd class="cerb-ui-kbd">&darr;</kbd> to pick, <kbd class="cerb-ui-kbd">Esc</kbd> to dismiss)</li>
				<li><kbd class="cerb-ui-kbd">Tab</kbd> / <kbd class="cerb-ui-kbd">Shift</kbd> + <kbd class="cerb-ui-kbd">Tab</kbd> &mdash; indent / dedent the current line or selection</li>
				<li><kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">/</kbd> &mdash; toggle <code>#&nbsp;comment</code> on the current line or the selected lines</li>
				<li><kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">D</kbd> or <kbd class="cerb-ui-kbd">Alt</kbd> + <kbd class="cerb-ui-kbd">D</kbd> &mdash; delete the current line</li>
				<li><kbd class="cerb-ui-kbd">Alt</kbd> + <kbd class="cerb-ui-kbd">&uarr;</kbd> / <kbd class="cerb-ui-kbd">Alt</kbd> + <kbd class="cerb-ui-kbd">&darr;</kbd> &mdash; move the current line or selection up / down</li>
				<li><kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">[</kbd> / <kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">]</kbd> &mdash; fold / unfold the subtree at the caret</li>
				<li><kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">Shift</kbd> + <kbd class="cerb-ui-kbd">&darr;</kbd> / <kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">Shift</kbd> + <kbd class="cerb-ui-kbd">&uarr;</kbd> &mdash; grow / shrink the editor height</li>
				<li><kbd class="cerb-ui-kbd">Enter</kbd> &mdash; new line, copying the KATA indent (and one extra level under a childless <code>key:</code>)</li>
			</ul>
		</div>

		{* Example 2: a custom onAutocomplete — return your own items for the current key-path + prefix *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Custom autocomplete &mdash; return your own items from <code>onAutocomplete(ctx)</code> for the current key-path + prefix (local, Ajax, or hybrid), no backend needed. A value with a <code>$0</code> marks the caret, and <code>\n</code> + spaces opens a child level. Type at the root, or under <code>options:</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-kataeditor" id="uiref-kataeditor-custom">
					<div class="cerb-ui-kataeditor--gutter" aria-hidden="true"></div>
					<div class="cerb-ui-kataeditor--field">
						<div class="cerb-ui-kataeditor--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-kataeditor--input" data-editor-lines="10" spellcheck="false">name: Status
color@text: green
options:
  multiple@bool: no
</textarea>
						<span class="cerb-ui-kataeditor--caret-anchor"></span>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// onAutocomplete(ctx) returns items (or a Promise of items) — only the source changes from the first example.
// ctx  = { path:['options:'], prefix, context, query, caret, editor }   // path = the KATA key-path at the caret
// item = { caption, value, snippet?, hint?, suppressAutocomplete? }      // a single `$0` in value marks the caret
const SCHEMA = {
	'':         [ { caption:'name:', value:'name: $0' }, { caption:'color:', value:'color@text: $0' }, { caption:'options:', value:'options:\n  $0' } ],
	'options:': [ { caption:'multiple:', value:'multiple@bool: $0' }, { caption:'icon:', value:'icon: $0' } ],
};
const localSource = function(ctx) {
	const items = SCHEMA[ctx.path.join('')] || [];
	return CerbUI.editorCore.filterItems(items, ctx.prefix); // shared fuzzy match/score (default 'subsequence')
};

new CerbUI.KataEditor(el, { onAutocomplete: localSource, minLines: 4, maxLines: 10 });{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// KataEditor #1 — the kataFieldSource adapter against a real cerbAutocompleteSuggestions schema
	(function() {
		const el = document.getElementById('uiref-kataeditor-schema');
		const out = document.getElementById('uiref-kataeditor-schema-path');
		if(el && window.CerbUI && CerbUI.KataEditor && window.cerbAutocompleteSuggestions) {
			const ed = new CerbUI.KataEditor(el, {
				minLines: 6,
				maxLines: 16,
				onAutocomplete: CerbUI.KataEditor.kataFieldSource(cerbAutocompleteSuggestions.kataSchemaMetricsExplorerSeries),
				// Click the left gutter margin to toggle a breakpoint pip (markers render left of the numbers).
				onGutterClick: function(row) {
					const mk = ed.getMarkers().get(row);
					if(mk && mk.type === 'breakpoint') ed.clearMarker(row);
					else ed.setMarker(row, { type:'breakpoint', title:'Breakpoint' });
				},
			});
			// A seeded marker so the gutter column shows an icon example (errors/warnings/info live left of #s).
			ed.setMarker(2, { type:'warning', title:'Example warning' });
			// Show the editor state at the caret as you move around: inside a script tag, the KataScript
			// context (open delimiter / sub-context / partial word, or the enclosing call for args); else the path.
			const ta = el.querySelector('.cerb-ui-kataeditor--input');
			const showPath = function() {
				if(!out) return;
				const t = CerbUI.editorCore.kataScript.contextAt(ta.value, ta.selectionStart);
				if(t && t.sub === 'args' && t.name) {
					out.textContent = 'katascript · ' + t.open + ' · args · ' + t.name + (t.kind === 'filter' ? '|()' : '()');
				} else if(t) {
					out.textContent = 'katascript · ' + t.open + ' · ' + t.sub + (t.prefix ? ' · "' + t.prefix + '"' : '');
				} else {
					out.textContent = 'kata · ' + (ed.getTokenPath().join('') || '(root)');
				}
			};
			ta.addEventListener('keyup', showPath);
			ta.addEventListener('click', showPath);
			showPath();
		}
	})();

	// KataEditor #2 — a custom onAutocomplete (local static key-path map, no backend)
	(function() {
		const el = document.getElementById('uiref-kataeditor-custom');
		if(el && window.CerbUI && CerbUI.KataEditor) {
			const SCHEMA = {
				'':         [
					{ caption: 'name:',    value: 'name: $0' },
					{ caption: 'color:',   value: 'color@text: $0' },
					{ caption: 'options:', value: 'options:\n  $0' },
				],
				'options:': [
					{ caption: 'multiple:', value: 'multiple@bool: $0' },
					{ caption: 'icon:',     value: 'icon: $0' },
				],
			};
			const localSource = function(ctx) {
				const items = SCHEMA[ctx.path.join('')] || [];
				return CerbUI.editorCore.filterItems(items, ctx.prefix);
			};
			new CerbUI.KataEditor(el, { onAutocomplete: localSource, minLines: 4, maxLines: 10 });
		}
	})();
})();
</script>
