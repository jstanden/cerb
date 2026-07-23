	<div class="cerb-uiref-component" id="kataeditor">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-placeholders"></span>KataEditor</div>

		{* Example 1: schema-driven — the kataFieldSource adapter against a real CerbUI.editorCore.autocompleteSchemas schema *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A multi-line textarea editor for Cerb's <b>KATA</b> syntax (the eventual Ace replacement) &mdash; live highlighting (keys, <code>/identifiers</code>, <code>@annotations</code>, values, <code>{literal}{{ scripting }}{/literal}</code>, <code>#&nbsp;comments</code>), a line-number gutter, indentation guides (<code>indentGuides</code>, on by default), and Tab = 2 spaces / Shift+Tab dedent. <b>Ctrl/&#8984;+F</b> opens find &amp; replace (live match count, prev/next with wrap, case &amp; regex toggles &mdash; folded matches reveal on navigate). <b>The usual setup:</b> <code>kataFieldSource(schema)</code> wires KATA autocomplete to a path-keyed suggestion map (static keys + dynamic types that hit Cerb's endpoints). Ctrl/&#8984;+Space to suggest, &darr; to pick. Try a new <code>series:</code> child, or <code>metric:</code> (dynamic names) and <code>filters:</code> (dynamic dimensions). <b>KataScript</b> autocomplete is built in wherever a tag is open (each suggestion shows a colored type icon): <code>{literal}{%{/literal} f</code> &rarr; <code>for</code> (command), <code>{literal}{{{/literal} </code> &rarr; functions, after a <code>|</code> pipe &rarr; filters, and inside a call's <code>(&hellip;</code> &rarr; that function/filter's <b>arguments</b> (try <code>{literal}{{ array_column({/literal}</code>). <b>Gutter markers</b> render left of the numbers &mdash; this demo seeds a warning on line&nbsp;3 and toggles a breakpoint pip when you click the left margin</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<textarea id="uiref-kataeditor-schema" data-editor-lines="16" spellcheck="false"># Metrics Explorer series — Ctrl/⌘+Space to suggest
&default-format:
  function: count
  label: Opened tickets
series/opened:
  metric:
  format@ref: default-format
</textarea>
				<div class="cerb-uiref-result">State &middot; path at caret: <b id="uiref-kataeditor-schema-path">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Author just the textarea — the editor builds its shell (gutter + highlight mirror + caret anchor)
     around it. (The full markup still works if you need to hand-author it.) --&gt;
&lt;textarea id="ed" name="kata" data-editor-lines="16" spellcheck="false"&gt;&lt;/textarea&gt;</pre>
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
	onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataSchemaMetricsExplorerSeries),
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
ed.clearMarkers();                              // remove every marker

// Active "matched" line — full-width body band + tinted gutter cell (e.g. a tester's matched rule):
ed.highlightLine(2);                            // default accent (red)
ed.highlightLine(2, {color:'green'});           // any Cerb tag color: red|green|blue|orange|purple|gray
ed.clearHighlight();                            // remove it{/literal}</pre>
			</div>
		</div>

		{* Reference (no live demo): the built-in keyboard shortcuts — also enumerable at runtime via getShortcuts() *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Keyboard shortcuts &mdash; <kbd class="cerb-ui-kbd">Mod</kbd> is <kbd class="cerb-ui-kbd">&#8984;</kbd> on macOS, <kbd class="cerb-ui-kbd">Ctrl</kbd> on Windows &amp; Linux. The same set is enumerable at runtime via <code>ed.getShortcuts()</code> (with OS-appropriate labels) to build a hint popup</div>
		</div>
		<div class="cerb-uiref-example">
			<ul class="cerb-uiref-keys">
				<li><kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">Space</kbd> &mdash; show autocomplete suggestions (<kbd class="cerb-ui-kbd">&darr;</kbd> to pick, <kbd class="cerb-ui-kbd">Esc</kbd> to dismiss)</li>
				<li><kbd class="cerb-ui-kbd">Mod</kbd> + <kbd class="cerb-ui-kbd">F</kbd> &mdash; find &amp; replace (<kbd class="cerb-ui-kbd">Enter</kbd> / <kbd class="cerb-ui-kbd">Shift</kbd> + <kbd class="cerb-ui-kbd">Enter</kbd> next / prev with wrap-around, <kbd class="cerb-ui-kbd">Aa</kbd> case &amp; <kbd class="cerb-ui-kbd">.*</kbd> regex toggles, <kbd class="cerb-ui-kbd">Esc</kbd> to close). Matches inside a collapsed fold are revealed on navigate</li>
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
				<textarea id="uiref-kataeditor-custom" data-editor-lines="10" spellcheck="false">name: Status
color@text: green
options:
  multiple@bool: no
</textarea>
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

		{* Example 3: sections-only toolbar — KataEditor ships no built-in buttons, the caller supplies its own *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><b>Toolbar sections</b> &mdash; every editor can show a built-in <code>cerb-ui-toolbar</code> strip above the field. KataEditor publishes no formatting buttons (no <code>TOOLBAR_BUILTINS</code>), so it's <b>sections-only</b>: the caller passes its own <code>cerb-ui-toolbar</code> <code>&lt;ul&gt;</code>(s) via <code>toolbar.sections</code> and the editor merges + wires them. One <code>onAction(value, editor, item, sourceLi)</code> handles every click &mdash; here it delegates to the editor's own <code>foldAll()</code>/<code>unfoldAll()</code> or runs a custom snippet insert</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<ul class="cerb-ui-toolbar" id="uiref-kataeditor-sections-toolbar" hidden>
					<li data-value="foldAll" data-icon="chevron-right" title="Fold all"></li>
					<li data-value="unfoldAll" data-icon="chevron-down" title="Unfold all"></li>
					<li></li>
					<li data-value="snippet" data-icon="clipboard" title="Insert label snippet"></li>
				</ul>
				<textarea id="uiref-kataeditor-sections" data-editor-lines="10" spellcheck="false">series/opened:
  metric: ticket.created
  function: count
series/closed:
  metric: ticket.closed
  function: count
</textarea>
				<div class="cerb-uiref-result">Last action &middot; <b id="uiref-kataeditor-sections-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- The host section: a cerb-ui-toolbar &lt;ul&gt; the editor merges into its strip (it builds the strip itself). --&gt;
&lt;ul class="cerb-ui-toolbar" id="more" hidden&gt;
	&lt;li data-value="foldAll" data-icon="chevron-right" title="Fold all"&gt;&lt;/li&gt;
	&lt;li data-value="unfoldAll" data-icon="chevron-down" title="Unfold all"&gt;&lt;/li&gt;
	&lt;li&gt;&lt;/li&gt;
	&lt;li data-value="snippet" data-icon="clipboard" title="Insert label snippet"&gt;&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// KataEditor publishes no TOOLBAR_BUILTINS, so it has no formatting buttons — a caller adds its own via `sections`.
// One onAction routes every click by value: delegate to the editor's own methods (foldAll/unfoldAll) or run a
// custom action. (Returning falsy would fall through to a built-in, of which KataEditor has none.)
new CerbUI.KataEditor(el, {
	toolbar: {
		sections: [document.getElementById('more')],
		onAction: (value, ed, item, sourceLi) => {
			if(value === 'snippet') { ed.insertSnippet('label: $0'); ed.focus(); return true; }
			if(typeof ed[value] === 'function') { ed[value](); return true; } // foldAll / unfoldAll
			return false;
		},
	},
});{/literal}</pre>
			</div>
		</div>

		{* Example 4: scroll-alignment regression — few maxLines (vertical scroll) + a very long line (horizontal scroll) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><b>Scroll-alignment regression</b> &mdash; a low <code>maxLines</code> forces a <b>vertical</b> scrollbar while the very long first line forces a <b>horizontal</b> one. With both present the textarea's client height shrinks by the scrollbar; <code>editorCore.syncOverlayHeight()</code> matches the mirror/gutter to it so the colored text stays locked to the caret at every scroll position, and <code>editorCore.observeWidth()</code> re-runs autosize on resize. <b>Drag the pane narrow, then scroll to the very bottom</b> &mdash; the highlight must not drift from the textarea text. The fix is shared by every editorCore editor (KataEditor / JsonEditor / MarkdownEditor / ScriptingEditor / DataQuery / SearchQuery)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<textarea id="uiref-kataeditor-scroll" spellcheck="false"># Regression check: this first line is deliberately far wider than the editor to force a horizontal scrollbar — drag the pane narrow, scroll to the bottom, and confirm the colored mirror stays exactly on top of the caret text with no vertical drift.
series/opened:
  metric: ticket.created
  function: count
series/closed:
  metric: ticket.closed
  function: count
series/waiting:
  metric: ticket.waiting
  function: count
</textarea>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Low maxLines → vertical scroll; a long line → horizontal scroll. editorCore.syncOverlayHeight (called by every
// editor's _autosize) sizes the mirror/gutter to the textarea's CLIENT height so a horizontal scrollbar can't
// desync them; editorCore.observeWidth re-runs _autosize when the editor is resized narrow/wide.
new CerbUI.KataEditor(el, { minLines: 4, maxLines: 6 });{/literal}</pre>
			</div>
		</div>

		{* Example 5: dragKeys — drag a key row out of one editor into another as a dot-notation placeholder *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><b>dragKeys</b> &mdash; reading nesting by eye and hand-typing <code>a.b.c</code> is the wall most authors hit (this is what the automation simulator's Input/Output panes use). Hover a <b>key</b> token and a drag handle floats in just left of it: <b>drag</b> it onto any <code>CerbUI.Droppable</code>, or <b>click</b> it to fire <code>onKeyClick</code>. The floating <code>cerb-ui-pill</code> is the literal placeholder the drop will insert, so the drag itself shows what you'll get. The handle is a separate element stacked <b>above</b> the textarea, so the drag starts on <em>it</em> &mdash; the caret, selection and typing are untouched and this works in an editable editor (it hides while you type). Only the key token itself arms it (the indent, the value, blanks, comments and <code>- list</code> items have no path), but once armed it stays up anywhere on that row so you can travel to it. It parks in the row's own indent; a top-level key has none, so there it reaches back over the gutter rather than indenting every line to make room. The zone below tracks the pointer with <code>positionFromPoint()</code> + <code>setCursorPosition(&hellip;, {literal}{ scroll: false }{/literal})</code>, so the <b>native caret is the drop-point preview</b></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-uiref-result" style="margin-bottom:0.4em;">Drag a key from here&hellip; (this pane is editable &mdash; type in it, the handle stays out of the way)</div>
				<textarea id="uiref-kataeditor-dragsrc" data-editor-lines="14" spellcheck="false"># Hover a key token — a drag handle floats in to its left
ticket:
  id: 12345
  subject: Re: your order
  group:
    name: Support
    is_private: false
worker:
  email: alice@example.com
http_response:
  headers:
    set-cookie: session=abc
attempts: 2
</textarea>
				<div class="cerb-uiref-result" style="margin:0.6em 0 0.4em;">&hellip;and drop it in here (or click a grip to insert at the caret)</div>
				<textarea id="uiref-kataeditor-dragdst" data-editor-lines="6" spellcheck="false">say:
  content:
</textarea>
				<div class="cerb-uiref-result">Last insert &middot; <b id="uiref-kataeditor-drag-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// The SOURCE: hovering a key token floats a drag handle. The drag payload is
//   { editor, modelRow, path, expr, key, line }   — expr is a Twig accessor: ticket.group.name
const src = new CerbUI.KataEditor(srcEl, {
	dragKeys:   true,
	onKeyClick: (payload) => insert(payload),   // the handle CLICKED, not dragged (the zone may be off-screen)
});

// The TARGET: any CerbUI.Droppable. Tracking the pointer with positionFromPoint() means the editor's own caret
// is the insertion preview — pass { scroll: false } or the editor fights the pointer on every move.
const insert = (payload) => dst.insertSnippet('{{' + payload.expr + '}}');

new CerbUI.Droppable(dst.el, {
	accept:     (item, payload) => !!(payload && payload.expr),
	hoverClass: 'cerb-ui-kataeditor--drop-target',
	overlay:    false,
	onMove:     (info) => {
		const p = dst.positionFromPoint(info.clientX, info.clientY);
		if(p) dst.setCursorPosition(p.row, p.column, { scroll: false });
	},
	onDrop:     (info) => insert(info.payload),
});{/literal}</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// The path APIs behind it — both are usable on their own.
ed.getPathForRow(4);                        // ['ticket:', 'group:', 'name:']  (the row-keyed getTokenPath)
CerbUI.KataEditor.pathToAccessor(path);     // 'ticket.group.name'  (strips /identifiers + @annotations)
                                            // a key Twig can't dot becomes a subscript, since `a.set-cookie`
                                            // would parse as SUBTRACTION:  headers['set-cookie']
ed.positionFromPoint(clientX, clientY);     // { row, column } in MODEL space, or null if outside the field{/literal}</pre>
			</div>
		</div>

		{* Example: diffGutter — mark unsaved edits vs a save-checkpoint baseline *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label"><b>diffGutter</b> &mdash; mark your unsaved edits in the gutter against a <b>baseline</b> captured when the editor opened (the same idea git-gutter / VS Code give you). <b>Edit</b> the text below: <b>added</b> lines get a green right-edge bar, <b>modified</b> lines a blue bar, and a <b>deleted</b> run leaves a small red wedge at the boundary. Contiguous rows in a hunk stack into one continuous span. Click <b>Save (reset baseline)</b> to re-checkpoint &mdash; the marks clear until the next edit (this is what the automation editor calls on save-and-continue). <code>getDiffState()</code> returns the hunks as plain data (status, current/baseline row spans, added/removed line text) so an editor <b>agent</b> can read the unsaved diff without opening the Change History popup</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<textarea id="uiref-kataeditor-diff" data-editor-lines="12" spellcheck="false">name: Order status
color@text: green
options:
  multiple@bool: false
  icon: circle-check
worker:
  email: alice@example.com
attempts: 2
</textarea>
				<div class="cerb-uiref-result" style="margin-top:0.4em;">
					<button type="button" class="cerb-ui-button cerb-ui-button--subtle" id="uiref-kataeditor-diff-save"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> Save (reset baseline)</button>
					&nbsp; Hunks &middot; <b id="uiref-kataeditor-diff-out">0</b>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const ed = new CerbUI.KataEditor(el, { diffGutter: true });

// …the user edits… the gutter now shows added/modified/deleted marks vs the baseline.

ed.resetDiffBaseline();   // re-checkpoint on save (marks clear until the next edit)
ed.getDiffState();        // { baseline, current, hunks:[ { status, rowStart, rowEnd,
                          //   baseStart, baseEnd, added:[…], removed:[…] } ] }{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// KataEditor #1 — the kataFieldSource adapter against a real CerbUI.editorCore.autocompleteSchemas schema
	(function() {
		const el = document.getElementById('uiref-kataeditor-schema');
		const out = document.getElementById('uiref-kataeditor-schema-path');
		if(el && window.CerbUI && CerbUI.KataEditor && CerbUI.editorCore && CerbUI.editorCore.autocompleteSchemas) {
			const ed = new CerbUI.KataEditor(el, {
				minLines: 6,
				maxLines: 16,
				onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataSchemaMetricsExplorerSeries),
				// Click the left gutter margin to toggle a breakpoint pip (markers render left of the numbers).
				onGutterClick: function(row) {
					const mk = ed.getMarkers().get(row);
					if(mk && mk.type === 'breakpoint') ed.clearMarker(row);
					else ed.setMarker(row, { type:'breakpoint', title:'Breakpoint' });
				},
			});
			// A seeded marker so the gutter column shows an icon example (errors/warnings/info live left of #s).
			ed.setMarker(2, { type:'warning', title:'Example warning' });
			// A seeded active "matched" line: full-width body band + tinted gutter cell (default accent is red).
			ed.highlightLine(4, { color:'green' });
			// Show the editor state at the caret as you move around: inside a script tag, the KataScript
			// context (open delimiter / sub-context / partial word, or the enclosing call for args); else the path.
			const ta = ed.textarea;
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

	// KataEditor #3 — a caller-provided sections-only toolbar (no built-in formatting buttons)
	(function() {
		const el = document.getElementById('uiref-kataeditor-sections');
		const more = document.getElementById('uiref-kataeditor-sections-toolbar');
		const out = document.getElementById('uiref-kataeditor-sections-out');
		if(el && more && window.CerbUI && CerbUI.KataEditor) {
			new CerbUI.KataEditor(el, {
				minLines: 6,
				maxLines: 12,
				toolbar: {
					sections: [more],
					onAction: function(value, ed) {
						if(value === 'snippet') { ed.insertSnippet('label: $0'); ed.focus(); if(out) out.textContent = 'insertSnippet'; return true; }
						if(typeof ed[value] === 'function') { ed[value](); if(out) out.textContent = value; return true; }
						return false;
					},
				},
			});
		}
	})();

	// KataEditor #4 — scroll-alignment regression (vertical + horizontal scrollbars); see syncOverlayHeight/observeWidth
	(function() {
		const el = document.getElementById('uiref-kataeditor-scroll');
		if(el && window.CerbUI && CerbUI.KataEditor)
			new CerbUI.KataEditor(el, { minLines: 4, maxLines: 6 });
	})();

	// KataEditor #5 — dragKeys: drag a key row out as a dot-notation placeholder (the automation simulator's pattern)
	(function() {
		const srcEl = document.getElementById('uiref-kataeditor-dragsrc');
		const dstEl = document.getElementById('uiref-kataeditor-dragdst');
		const out = document.getElementById('uiref-kataeditor-drag-out');
		if(!srcEl || !dstEl || !(window.CerbUI && CerbUI.KataEditor && CerbUI.Droppable)) return;

		const dst = new CerbUI.KataEditor(dstEl, { minLines: 6, maxLines: 6 });

		const insert = function(payload) {
			{literal}dst.insertSnippet('{{' + payload.expr + '}}');
			if(out) out.textContent = '{{' + payload.expr + '}}';{/literal}
		};

		new CerbUI.KataEditor(srcEl, { minLines: 14, maxLines: 14, dragKeys: true, onKeyClick: insert });

		new CerbUI.Droppable(dst.el, {
			accept: function(item, payload) { return !!(payload && payload.expr); },
			hoverClass: 'cerb-ui-kataeditor--drop-target',
			overlay: false,
			onMove: function(info) {
				const p = dst.positionFromPoint(info.clientX, info.clientY);
				if(p) dst.setCursorPosition(p.row, p.column, { scroll: false });
			},
			onDrop: function(info) { insert(info.payload); }
		});
	})();

	// KataEditor #6 — diffGutter: mark unsaved edits vs a save-checkpoint baseline
	(function() {
		const el = document.getElementById('uiref-kataeditor-diff');
		const out = document.getElementById('uiref-kataeditor-diff-out');
		const save = document.getElementById('uiref-kataeditor-diff-save');
		if(!el || !(window.CerbUI && CerbUI.KataEditor)) return;

		const ed = new CerbUI.KataEditor(el, { diffGutter: true, minLines: 6, maxLines: 12 });
		const refresh = function() { if(out) out.textContent = String(ed.getDiffState().hunks.length); };
		ed.onChange(refresh);
		if(save) save.addEventListener('click', function() { ed.resetDiffBaseline(); refresh(); });
		refresh();
	})();
})();
</script>
