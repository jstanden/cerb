	<div class="cerb-uiref-component" id="scriptingeditor">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-function"></span>ScriptingEditor</div>

		{* Example 1: editable — KataScript/Twig highlighting + language autocomplete *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A multi-line editor for Cerb's <b>Twig / KataScript</b> template documents (the eventual Ace <code>mode/twig</code> replacement) &mdash; the whole document is one value (free text with embedded <code>{literal}{{ output }}{/literal}</code> and <code>{literal}{% command %}{/literal}</code> tags), no KATA hierarchy and no folding. Live highlighting of tags/strings/numbers/functions, a line-number gutter, Tab = 2 spaces. <b>Autocomplete</b> fires inside a tag (Ctrl/&#8984;+Space to force, &darr; to pick): <code>{literal}{%{/literal} f</code> &rarr; <code>for</code> (command), <code>{literal}{{{/literal} </code> &rarr; functions, after a <code>|</code> pipe &rarr; filters, inside a call's <code>(&hellip;</code> &rarr; that function/filter's arguments. <b>Ctrl/&#8984;+F</b> opens find &amp; replace (live count, prev/next with wrap, case &amp; regex toggles). (Scope-aware <b>variable</b> autocomplete is a follow-up.)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-scriptingeditor" id="uiref-scriptingeditor-edit">
					<div class="cerb-ui-scriptingeditor--gutter" aria-hidden="true"></div>
					<div class="cerb-ui-scriptingeditor--field">
						<div class="cerb-ui-scriptingeditor--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-scriptingeditor--input" data-editor-lines="14" spellcheck="false">{literal}Hi {{worker_first_name|default('there')}},

{% if ticket_status == 'open' %}
Your ticket #{{ticket_mask}} is still open.
{% endif %}

Thanks,
{{worker_signature}}{/literal}</textarea>
						<span class="cerb-ui-scriptingeditor--caret-anchor"></span>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-scriptingeditor" id="ed"&gt;
	&lt;div class="cerb-ui-scriptingeditor--gutter" aria-hidden="true"&gt;&lt;/div&gt;
	&lt;div class="cerb-ui-scriptingeditor--field"&gt;
		&lt;div class="cerb-ui-scriptingeditor--highlight" aria-hidden="true"&gt;&lt;/div&gt;
		&lt;textarea class="cerb-ui-scriptingeditor--input" name="body" data-editor-lines="14" spellcheck="false"&gt;&lt;/textarea&gt;
		&lt;span class="cerb-ui-scriptingeditor--caret-anchor"&gt;&lt;/span&gt;
	&lt;/div&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const ed = new CerbUI.ScriptingEditor(document.getElementById('ed'), {
	minLines: 6,
	maxLines: 14,                // or set data-editor-lines on the textarea
	gutter: true,                // left line-number gutter (default true)
	// readOnly: true,           // or data-editor-readonly on the textarea
});

ed.getValue();                 // the full document text
ed.onChange((value) => { /* … */ });{/literal}</pre>
			</div>
		</div>

		{* Example 2: read-only viewer *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Read-only viewer &mdash; pass <code>readOnly: true</code> (or <code>data-editor-readonly</code>): highlighting still works, the text can't be edited</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-scriptingeditor" id="uiref-scriptingeditor-readonly">
					<div class="cerb-ui-scriptingeditor--gutter" aria-hidden="true"></div>
					<div class="cerb-ui-scriptingeditor--field">
						<div class="cerb-ui-scriptingeditor--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-scriptingeditor--input" data-editor-lines="8" data-editor-readonly spellcheck="false">{literal}{% for line in lines %}
  {{line.product}} x{{line.qty}} = {{line.total|currency}}
{% endfor %}
Total: {{order_total|currency}}{/literal}</textarea>
						<span class="cerb-ui-scriptingeditor--caret-anchor"></span>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.ScriptingEditor(el, { readOnly: true });{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	(function() {
		const el = document.getElementById('uiref-scriptingeditor-edit');
		if(el && window.CerbUI && CerbUI.ScriptingEditor) {
			new CerbUI.ScriptingEditor(el, { minLines: 6, maxLines: 14 });
		}
	})();
	(function() {
		const el = document.getElementById('uiref-scriptingeditor-readonly');
		if(el && window.CerbUI && CerbUI.ScriptingEditor) {
			new CerbUI.ScriptingEditor(el, { readOnly: true, minLines: 4, maxLines: 8 });
		}
	})();
})();
</script>
