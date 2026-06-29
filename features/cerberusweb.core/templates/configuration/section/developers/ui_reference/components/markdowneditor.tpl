	<div class="cerb-uiref-component" id="markdowneditor">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-quote"></span>MarkdownEditor</div>

		{* Example: the live editor + a cerb-ui-toolbar strip driving its formatting methods *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A textarea-based <b>markdown</b> composer (the replacement for the legacy jQuery <code>cerbTextEditor</code> stack on the mail reply / comment editors) &mdash; live <b>syntax highlighting</b> (headings, <code>**bold**</code>, <code>_italic_</code>, <code>`code`</code>, <code>[links](…)</code>, <code>&gt; quotes</code>, lists), a plaintext&harr;markdown <b>mode toggle</b>, formatting actions, caret-anchored <code>@mention</code> autocomplete, and inline-image paste. <b>Ctrl/&#8984;+F</b> opens find &amp; replace (live count, prev/next with wrap, case &amp; regex toggles). Highlighting is <b>color-only</b> (the overlay's glyph metrics must match the textarea) &mdash; a syntax highlighter, not WYSIWYG. <b>The component owns its toolbar</b>: set <code>toolbar:&nbsp;true</code> (or an options object) and it builds + wires the formatting strip + mode toggle itself (CerbUI.Toolbar does the rendering). <b>Merge a host section</b> &mdash; a hand-authored or record-rendered <code>cerb-ui-toolbar</code> <code>&lt;ul&gt;</code> &mdash; via <code>sections</code> (here a Mention + Preview group, divider between). One <code>onAction(value, editor, item, sourceLi)</code> handles every click: return truthy to handle it (this demo claims <b>Mention</b>, <b>Preview</b>, and overrides <b>Image</b>), falsy to let the editor run its built-in (bold/italic/&hellip;)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				{* The toolbar is built BY the component (opts.toolbar) and inserted above this element — no hand-authored strip.
				   This host SECTION (e.g. a worker-configured toolbar record) is merged in after the built-in formatting. *}
				<ul class="cerb-ui-toolbar" id="uiref-markdowneditor-extra" hidden>
					<li data-value="mention" data-icon="mention" title="Mention (@)"></li>
					<li data-value="preview" data-icon="eye-open" title="Preview"></li>
				</ul>
				<div class="cerb-ui-markdowneditor" id="uiref-markdowneditor">
					<div class="cerb-ui-markdowneditor--field">
						<div class="cerb-ui-markdowneditor--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-markdowneditor--input" spellcheck="true"># Heading

Some **bold** and _italic_ text with a [link](https://cerb.ai) and `inline code`.

> A blockquote line.

* first item
* second item

Type @ to try mention autocomplete.</textarea>
						<span class="cerb-ui-markdowneditor--caret-anchor"></span>
					</div>
				</div>
				<div class="cerb-uiref-result">Mode &middot; <b id="uiref-markdowneditor-mode">markdown</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- A colored --highlight mirror under the transparent --input. --&gt;
&lt;div class="cerb-ui-markdowneditor" id="ed"&gt;
	&lt;div class="cerb-ui-markdowneditor--field"&gt;
		&lt;div class="cerb-ui-markdowneditor--highlight" aria-hidden="true"&gt;&lt;/div&gt;
		&lt;textarea class="cerb-ui-markdowneditor--input" name="comment"&gt;&lt;/textarea&gt;
		&lt;span class="cerb-ui-markdowneditor--caret-anchor"&gt;&lt;/span&gt;
	&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- A host toolbar SECTION — hand-authored, or a toolbar record rendered via ui/toolbar/render.tpl. --&gt;
&lt;ul class="cerb-ui-toolbar" id="more" hidden&gt;
	&lt;li data-value="mention" data-icon="mention" title="Mention (@)"&gt;&lt;/li&gt;
	&lt;li data-value="preview" data-icon="eye-open" title="Preview"&gt;&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const ed = new CerbUI.MarkdownEditor(document.getElementById('ed'), {
	mode: 'markdown',                                 // 'markdown' colors syntax · 'plaintext' renders plain
	readOnly: false,                                  // view-only (also via data-editor-readonly on the textarea)
	onAutocomplete: CerbUI.MarkdownEditor.mentionSource(),   // @mention completion (c=ui&a=getMentionsJson)
	onChange: (value) => { /* … */ },
	onImage: ({url, file_id, file_name}) => { /* host adds the attachment */ },
	images: true,                                     // false = no paste-upload / image() / Image button (hosts w/o /files URLs)

	// The component builds + wires its own formatting toolbar above the field (CerbUI.Toolbar does the rendering).
	toolbar: {
		// buttons: ['bold','italic','link','image','list','quote','code','table'],  // restrict/reorder (default: all)
		// mode: false,                            // suppress the markdown↔plaintext switcher (on by default)
		onMode: (mode) => { /* host maps the mode to its submit flag, e.g. comment_is_markdown */ },

		// MERGE host toolbar section &lt;ul&gt;(s) in after the formatting buttons (divider between) — no per-item JSON:
		sections: [document.getElementById('more')],

		// One callback gets first crack at EVERY click — return truthy to handle it, falsy to fall through to the
		// built-in. `item` is the CerbUI.Toolbar item; `sourceLi` is the original &lt;li&gt; (read its data-attrs/classes).
		onAction: (value, ed, item, sourceLi) => {
			if(value === 'mention') { ed.insertText('@'); ed.openAutocomplete(); return true; }
			if(value === 'preview') { /* sourceLi.classList.contains('cerb-bot-trigger') … */ return true; }
			if(value === 'image')   { ed.insertText('![alt](https://cerb.ai/logo.png)'); return true; } // override built-in
			return false;                                                                                  // bold/italic/… run their built-in
		},
	},
});{/literal}</pre>
			</div>
		</div>

		{* Reference (no live demo): the public API *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">The public API &mdash; formatting actions, the textarea command API (ported from the legacy <code>cerbTextEditor</code>), and the mode toggle</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Formatting actions (transform the selection / current line):
ed.bold(); ed.italic(); ed.heading();
ed.link(); ed.list(); ed.quote(); ed.code(); ed.table();
ed.image();                                     // opens the file chooser, inserts ![…](url), fires onImage

// Mode (host maps this to its submit flag, e.g. comment_is_markdown):
ed.getMode();                                   // 'markdown' | 'plaintext'
ed.isMarkdown();
ed.setMode('plaintext'); ed.toggleMode();

// Value + selection + insertion (textarea command API):
ed.getValue(); ed.setValue('…'); ed.focus();
ed.getSelection(); ed.getSelectedText();        // getSelectedText is the editor-family alias of getSelection
ed.getSelectionBounds(); ed.setSelection(start, end);
ed.getCursorPosition();                          // {row, column} — editor-family shape (was a raw index)
ed.setCursorPosition(row, column);               // editor-family shape (was a raw index)
ed.getCurrentWord(); ed.getCurrentLine();
ed.insertText('…'); ed.replaceSelection('…');
ed.insertSnippet('[$0](url)');                   // family parity: insert at caret, $0 marks the final caret
ed.insertAtCursor('{{token}}', { replace:false });      // literal insert (placeholder-menu hook); replace clears first
ed.wrapSelection('**'); ed.prefixSelection('# '); ed.prefixCurrentLine('&gt; ');

// Autocomplete:
ed.openAutocomplete();                          // Ctrl/⌘+Space also forces it
ed.onChange((value) => {});
ed.destroy();
CerbUI.MarkdownEditor.from(el);{/literal}</pre>
			</div>
		</div>

		{* Scripting: optional Twig/KataScript highlighting + autocomplete anywhere in the markdown *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Scripting (<code>scripting: true</code>) &mdash; optionally highlight and autocomplete Twig/KataScript tags (<code>{literal}{{ output }}{/literal}</code> / <code>{literal}{% command %}{/literal}</code>) <b>anywhere</b> in the markdown, reusing the same <code>kataScript</code> brain as KataEditor/ScriptingEditor. Type inside a tag to autocomplete; <code>@mentions</code> still work outside tags</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-markdowneditor" id="uiref-markdowneditor-scripting">
					<div class="cerb-ui-markdowneditor--field">
						<div class="cerb-ui-markdowneditor--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-markdowneditor--input" spellcheck="false">{literal}# Hi {{worker_first_name}}

{% if ticket_status == 'open' %}
Your ticket **#{{ticket_mask}}** is still _open_.
{% endif %}{/literal}</textarea>
						<span class="cerb-ui-markdowneditor--caret-anchor"></span>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// markdown + Twig/KataScript tags, highlighted &amp; autocompleted anywhere:
new CerbUI.MarkdownEditor(el, { mode: 'markdown', scripting: true });{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const el = document.getElementById('uiref-markdowneditor');
	const modeOut = document.getElementById('uiref-markdowneditor-mode');
	if(!el || !(window.CerbUI && CerbUI.MarkdownEditor))
		return;

	{literal}
	// A local stub mention source for the gallery (no backend dependency) — shows the rich row layout:
	// avatar (monogram) + three stacked lines: @handle (eyebrow) / name / title.
	const stubMentions = function(ctx) {
		if(!ctx || ctx.path[0] !== '@')
			return [];
		const people = [
			{ name: 'Jeff Standen', handle: '@jstanden', title: 'Founder', type: 'Worker' },
			{ name: 'Kerry Bahl', handle: '@kbahl', title: 'Support Lead', type: 'Worker' },
			{ name: 'Dan Pisarra', handle: '@dpisarra', title: 'Engineer', type: 'Worker' },
			{ name: 'Support', handle: '@support', title: 'Saved search', type: 'Group' },
			{ name: 'Billing', handle: '@billing', title: 'Saved search', type: 'Group' },
		];
		const term = (ctx.prefix || '').toLowerCase();
		return people
			.filter(p => p.name.toLowerCase().startsWith(term) || p.handle.toLowerCase().startsWith('@' + term))
			.map(p => ({
				caption: p.name,
				value: p.handle + ' ',
				handle: p.handle,
				subtitle: p.title,
				avatar: { label: p.name, seed: p.type + ':' + p.handle },
			}));
	};

	const ed = new CerbUI.MarkdownEditor(el, {
		mode: 'markdown',
		minHeight: 160,
		onAutocomplete: stubMentions,
		// The component builds + wires the formatting toolbar + mode toggle itself.
		toolbar: {
			onMode: function(v) { if(modeOut) modeOut.textContent = v; },
			// Merge the host section <ul> (mention + preview) in after the built-in formatting — a hybrid strip.
			sections: [document.getElementById('uiref-markdowneditor-extra')],
			// ONE callback by value (item + original <li> available); truthy = handled, falsy = built-in runs.
			onAction: function(value, ed, item, sourceLi) {
				if(value === 'mention') { ed.insertText('@'); ed.openAutocomplete(); return true; }
				if(value === 'preview') { if(modeOut) modeOut.textContent = 'preview clicked'; return true; }
				if(value === 'image')   { ed.insertText('![alt](https://cerb.ai/logo.png)'); return true; } // override built-in
				return false;                                                                                 // bold/italic/… fall through
			},
		},
	});

	// Register the demo handles so the seeded @mentions in the editor highlight right away (the real comment
	// editor's mentionSource does this from getMentionsJson when the @ autocomplete first runs).
	ed.registerMentions(['@jstanden', '@kbahl', '@dpisarra', '@support', '@billing']);
	{/literal}
})();

(function() {
	const el = document.getElementById('uiref-markdowneditor-scripting');
	if(el && window.CerbUI && CerbUI.MarkdownEditor)
		new CerbUI.MarkdownEditor(el, { mode: 'markdown', scripting: true });
})();
</script>
