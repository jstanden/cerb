	<div class="cerb-uiref-component" id="dataquery">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-database"></span>DataQuery</div>

		{* Example 1: editable — the ready-made adapter against a real data-query type, with a live "Run" *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A multi-line editor for Cerb's <b>data-query</b> language (the <code>type:&hellip; of:&hellip; by:&hellip; format:&hellip; query:(&hellip;)</code> syntax behind charts, sheets, and datasources). Same query grammar as <b>SearchQuery</b> &mdash; but these are config textareas that also embed <code>{literal}{{ }}{/literal}</code> / <code>{literal}{% %}{/literal}</code> template tags (colored on top). <b>Enter = newline</b>; Ctrl/&#8984;+Space to suggest, &darr; to pick. The ready-made <code>dataQueryFieldSource(type, of)</code> wires autocomplete to Cerb's endpoints: try <code>type:</code> &rarr; <code>of:</code> &rarr; <code>by:</code>, and inside <code>query:(&hellip;)</code> the fields of the chosen <code>of:</code>. Being multi-line, it uses the <b>shared editor toolbar</b> (a top strip, like KataEditor/MarkdownEditor) &mdash; <b>Suggestions</b> is its built-in action; <b>Run</b> comes from a host <code>toolbar.sections</code> <code>&lt;ul&gt;</code>.</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				{* DataQuery is multi-line, so it uses the shared editor toolbar (a top strip) — Suggestions is its
				   built-in action; this host section adds Run. (Merged in via toolbar.sections.) *}
				<ul class="cerb-ui-toolbar" id="uiref-dataquery-tools" hidden>
					<li data-value="run" data-icon="play-button" title="Run query"></li>
				</ul>
				<div class="cerb-ui-dataquery" id="uiref-dataquery-edit">
					<div class="cerb-ui-dataquery--gutter" aria-hidden="true"></div>
					<div class="cerb-ui-dataquery--field">
						<div class="cerb-ui-dataquery--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-dataquery--input" data-editor-lines="10" spellcheck="false">{literal}type:worklist.subtotals
of:ticket
by:[status]
format:table
query:(status:[open,waiting,closed]){/literal}</textarea>
						<span class="cerb-ui-dataquery--caret-anchor"></span>
					</div>
				</div>
				<div class="cerb-uiref-result">Result: <b id="uiref-dataquery-edit-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Gutter + field. The editor builds its own toolbar above this; your buttons go in a host section. --&gt;
&lt;div class="cerb-ui-dataquery" id="dq"&gt;
	&lt;div class="cerb-ui-dataquery--gutter" aria-hidden="true"&gt;&lt;/div&gt;
	&lt;div class="cerb-ui-dataquery--field"&gt;
		&lt;div class="cerb-ui-dataquery--highlight" aria-hidden="true"&gt;&lt;/div&gt;
		&lt;textarea class="cerb-ui-dataquery--input" name="data_query" data-editor-lines="10" spellcheck="false"&gt;&lt;/textarea&gt;
		&lt;span class="cerb-ui-dataquery--caret-anchor"&gt;&lt;/span&gt;
	&lt;/div&gt;
&lt;/div&gt;
&lt;!-- A host toolbar section (merged after the built-in Suggestions button) --&gt;
&lt;ul class="cerb-ui-toolbar" id="dq-tools" hidden&gt;
	&lt;li data-value="run" data-icon="play-button" title="Run query"&gt;&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const dq = new CerbUI.DataQuery(document.getElementById('dq'), {
	// dataQueryFieldSource(type, of) — ready-made source: derives type:/of: from the buffer (the args are
	// fallbacks the widget config can pass), suggests structural keys + dynamic value lists from Cerb's
	// endpoints, and delegates the fields inside a query:(…) clause to the search-query source for that of:.
	onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(),

	gutter:   true,    // left line-number gutter (default true)
	maxLines: 10,      // or set data-editor-lines on the textarea
	// readOnly: true, // or data-editor-readonly on the textarea

	// Multi-line, so it uses the shared editor toolbar (a top strip, like KataEditor/MarkdownEditor) instead of a
	// --right corner. The built-in `suggest` button is always there; merge your own buttons via `sections`, and
	// route every click through one onAction (truthy = handled, falsy = built-in runs).
	toolbar: {
		sections: [document.getElementById('dq-tools')],
		onAction: (value, ed) => {
			if(value === 'run') { runQuery(ed.getValue()); return true; }
			return false; // 'suggest' opens autocomplete via the built-in
		},
	},
});

// The --input textarea (with a name=) holds the value directly — native form submit, no hidden field.
dq.getValue();                       // the full data-query text
dq.onChange((value) => { /* … */ }); // CerbUI.DataQuery.from(el) returns the instance{/literal}</pre>
			</div>
		</div>

		{* Example 2: read-only viewer demonstrating embedded template tags colored on top of query syntax *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Embedded template tags &mdash; a data query can interpolate <code>{literal}{{ }}{/literal}</code> / <code>{literal}{% %}{/literal}</code> (the endpoint runs it through the template builder first). The query syntax colors normally and the tags color on top. Shown here read-only (<code>readOnly: true</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-dataquery" id="uiref-dataquery-readonly">
					<div class="cerb-ui-dataquery--gutter" aria-hidden="true"></div>
					<div class="cerb-ui-dataquery--field">
						<div class="cerb-ui-dataquery--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-dataquery--input" data-editor-lines="8" data-editor-readonly spellcheck="false">{literal}type:worklist.subtotals
of:ticket
by:[group_id]
format:table
query:(
  created:after:{{ date.diff('now', '-30 days') }}
  status:[open,waiting]
){/literal}</textarea>
						<span class="cerb-ui-dataquery--caret-anchor"></span>
					</div>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.DataQuery(el, { readOnly: true });{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// DataQuery #1 — the adapter against a real data-query type, with a live "Run" through c=ui&a=dataQuery
	(function() {
		const el = document.getElementById('uiref-dataquery-edit');
		const out = document.getElementById('uiref-dataquery-edit-out');
		if(el && window.CerbUI && CerbUI.DataQuery) {
			const runQuery = function(q) {
				if(out) out.textContent = 'Running…';
				genericAjaxGet('', 'c=ui&a=dataQuery&q=' + encodeURIComponent(q), function(json) {
					if(!out) return;
					if(json && json.error) out.textContent = 'Error: ' + json.error;
					else if(json && Array.isArray(json.data)) out.textContent = json.data.length + ' row(s)';
					else out.textContent = '—';
				});
			};
			new CerbUI.DataQuery(el, {
				onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(),
				maxLines: 12,
				toolbar: {
					// 'suggest' is DataQuery's built-in toolbar button; 'run' comes from the host section <ul>.
					sections: [document.getElementById('uiref-dataquery-tools')],
					onAction: function(value, ed) {
						if(value === 'run') { runQuery(ed.getValue()); return true; }
						return false;
					},
				},
			});
		}
	})();

	// DataQuery #2 — read-only viewer (query syntax + embedded template tags colored on top)
	(function() {
		const el = document.getElementById('uiref-dataquery-readonly');
		if(el && window.CerbUI && CerbUI.DataQuery) {
			new CerbUI.DataQuery(el, { readOnly: true, minLines: 4, maxLines: 8 });
		}
	})();
})();
</script>
