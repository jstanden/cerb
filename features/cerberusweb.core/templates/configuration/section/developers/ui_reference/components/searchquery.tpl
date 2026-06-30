	<div class="cerb-uiref-component" id="searchquery">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-search"></span>SearchQuery</div>

		{* Example 1 (the common case): the ready-made adapter against a real record context — fully documented *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">A textarea editor for Cerb search syntax &mdash; live highlighting (filter names, strings, AND/OR). <b>The usual setup:</b> <code>queryFieldSource(context)</code> wires autocomplete to Cerb's real endpoints (lazy-loaded fields + values; a nested context inserts <code>field:()</code> and keeps suggesting). Enter searches, Shift+Enter / &#8984;+Enter = newline; type or Ctrl/&#8984;+Space to suggest, &darr; to pick. Try <code>group:</code>, <code>bucket:</code>, or <code>sender:</code> &rarr; <code>org:</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<textarea id="uiref-searchquery-adapter" rows="1" placeholder="Search tickets…"></textarea>
				<div class="cerb-uiref-result">Search: <b id="uiref-searchquery-adapter-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Simple: author just the textarea — the editor adds the search icon + a Suggestions button. --&gt;
&lt;textarea id="q" rows="1" placeholder="Search…"&gt;&lt;/textarea&gt;

&lt;!-- Custom right-side actions? Author the full shell and put your own markup in --right (see examples below):
&lt;div class="cerb-ui-searchquery" id="q"&gt;
	&lt;span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"&gt;&lt;/span&gt;
	&lt;div class="cerb-ui-searchquery--field"&gt;
		&lt;div class="cerb-ui-searchquery--highlight" aria-hidden="true"&gt;&lt;/div&gt;
		&lt;textarea class="cerb-ui-searchquery--input" rows="1" placeholder="Search…"&gt;&lt;/textarea&gt;
		&lt;span class="cerb-ui-searchquery--caret-anchor"&gt;&lt;/span&gt;
	&lt;/div&gt;
	&lt;div class="cerb-ui-searchquery--right"&gt;&lt;a data-action="autocomplete"&gt;&lt;span class="cerb-icons cerb-icon-sparkles"&gt;&lt;/span&gt;&lt;/a&gt;&lt;/div&gt;
&lt;/div&gt; --&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}const el = document.getElementById('q');

const sq = new CerbUI.SearchQuery(el, {
	// Enter ALWAYS submits; Shift+Enter / ⌘·Ctrl+Enter = newline. Autocomplete is opt-in: type (debounced) or
	// Ctrl/⌘+Space to open the menu, ↓ to step into it (or click); Enter selects only once you're in the menu.
	onSearch:          (query) => { console.log('search:', query); },

	// queryFieldSource(context, opts?) — ready-made source: lazy-loads fields + values from Cerb's endpoints,
	// caches per scope, inserts a nested context as `field:()` then keeps suggesting.
	// opts.filterMode: 'subsequence' (default) | 'substring' | 'prefix'   (see the toolbar example below)
	onAutocomplete:    CerbUI.SearchQuery.queryFieldSource('cerberusweb.contexts.ticket'),
	context:           'cerberusweb.contexts.ticket', // root context alias, passed through to onAutocomplete

	autocompleteDelay: 200,                // ms debounce for typing-triggered suggestions
	minChars:          0,                  // min total query length before typing fires suggestions
	maxHeight:         160,                // px the field grows to before it scrolls (auto-grow)
	// placeholder:    null,               // overrides the textarea's own placeholder when set
});

// Optional: a --right toolbar button to force the menu open.
el.querySelector('[data-action=autocomplete]').addEventListener('click', () => sq.openAutocomplete());

// setContext(context) re-roots autocomplete live (e.g. a record-type dropdown changing what you search) — it
// updates the context handed to onAutocomplete AND drops a queryFieldSource's per-scope cache, then closes any menu.
sq.setContext('cerberusweb.contexts.org');

// CerbUI.SearchQuery.from(el) -> the instance; sq.getValue() / setValue(str) / focus()
// Editor-family parity (used by shared editor toolbars): getCursorPosition() -> {row,column}, getSelectedText(),
// insertSnippet('a:($0)') ($0 = caret), insertAtCursor(text, {replace}){/literal}</pre>
			</div>
		</div>

		{* Example 2: a custom onAutocomplete — return your own items (local / Ajax / hybrid) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Custom autocomplete &mdash; return your own items from <code>onAutocomplete(ctx)</code> for the current path + prefix (local, Ajax, or hybrid). A nested-context item uses a <code>snippet</code> with <code>$0</code> to insert <code>field:()</code> and chain. Walk <code>sender:</code> &rarr; <code>org:</code> &rarr; <code>name:</code>; or open <code>created:(…)</code> &mdash; inside the parens the path's final segment is tagged <code>created:()</code>, so the source serves the date sub-keys (<code>since:</code>/<code>until:</code>/<code>days:</code>/<code>time:</code>)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-searchquery" id="uiref-searchquery-custom">
					<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
					<div class="cerb-ui-searchquery--field">
						<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-searchquery--input" rows="1" placeholder="Search…">status:[open,waiting] sender:(org:(name:"Fiaflux Games"))</textarea>
						<span class="cerb-ui-searchquery--caret-anchor"></span>
					</div>
					<div class="cerb-ui-searchquery--right">
						<a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
					</div>
				</div>
				<div class="cerb-uiref-result">Search: <b id="uiref-searchquery-custom-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// onAutocomplete(ctx) returns items (or a Promise of items) — only the source changes from the first example.
// ctx  = { path:['sender:','org:'], prefix, context, query, caret }
// item = { caption, value, snippet?, hint?, icon?, suppressAutocomplete? }
//   snippet overrides value; a single `$0` marks the caret — a nested context inserts `sender:($0)` and chains
//   suppressAutocomplete = don't re-open suggestions after this pick (a terminal value like `open`)
// In GROUP-KEY position (caret inside `field:(…)`) the path's final segment is tagged with a trailing `()`
// (e.g. 'created:()') — serve that group's sub-keys there, and fall back to the plain key otherwise.
const FIELDS = [
	{ caption: 'status:',        value: 'status:',        hint: 'list' },
	{ caption: 'subject:',       value: 'subject:',       hint: 'text' },
	{ caption: 'sender:',        value: 'sender:',        snippet: 'sender:($0)', hint: 'contact' },
	{ caption: 'links.address:', value: 'links.address:', hint: 'text' },
	{ caption: 'created:',       value: 'created:',       snippet: 'created:($0)', hint: 'date' },
];
const NESTED = {
	'status:':     [{ caption:'open', value:'open', suppressAutocomplete:true } /* , waiting, closed, deleted */],
	'sender:':     [{ caption:'org:', value:'org:', snippet:'org:($0)', hint:'org' }, { caption:'email:', value:'email:' }],
	'sender:org:': [{ caption:'name:', value:'name:' }],
	'created:()':  [{ caption:'since:', snippet:'since:"$0"' }, { caption:'until:', snippet:'until:"$0"' }, { caption:'days:', snippet:'days:[$0]' }, { caption:'time:', snippet:'time:$0' }],
};
function localSource(ctx) {
	let key = ctx.path.join('');
	if(key.endsWith('()') && !NESTED[key]) key = key.slice(0, -2); // group position falls back to the plain key
	const items = (key === '') ? FIELDS : (NESTED[key] || []);
	return CerbUI.SearchQuery.filterItems(items, ctx.prefix); // default 'subsequence'; or pass 'substring' | 'prefix'
}

new CerbUI.SearchQuery(el, {
	onAutocomplete: localSource,
	onSearch:       (query) => { /* … */ },
	// […same options as the first example…]
});{/literal}</pre>
			</div>
		</div>

		{* Example 3: a custom --right action — a filter-mode config menu persisted to localStorage *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Custom toolbar action &mdash; the <code>--right</code> slot holds arbitrary per-instance controls. Here a <span class="cerb-icons cerb-icon-gear"></span> opens a <code>CerbUI.Menu</code> to choose the filter mode (the source reads it via <code>CerbUI.SearchQuery.filterItems</code>), persisted in <code>localStorage</code>. Clear the field and type <code>linadd</code> with <em>Subsequence</em> on</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-ui-searchquery" id="uiref-searchquery-toolbar">
					<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
					<div class="cerb-ui-searchquery--field">
						<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
						<textarea class="cerb-ui-searchquery--input" rows="1" placeholder="Search…"></textarea>
						<span class="cerb-ui-searchquery--caret-anchor"></span>
					</div>
					<div class="cerb-ui-searchquery--right">
						<a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
						<a style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Saved queries"><span class="cerb-icons cerb-icon-bookmark"></span></a>
						<a data-action="config" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Filter mode"><span class="cerb-icons cerb-icon-gear"></span></a>
					</div>
				</div>
				<ul id="uiref-searchquery-modemenu" hidden>
					<li data-value="substring">Substring</li>
					<li data-value="prefix">Prefix</li>
					<li data-value="subsequence">Subsequence</li>
				</ul>
				<div class="cerb-uiref-result">Search: <b id="uiref-searchquery-toolbar-out">&mdash;</b></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Add a gear to the --right toolbar… --&gt;
&lt;a data-action="config" title="Filter mode"&gt;&lt;span class="cerb-icons cerb-icon-gear"&gt;&lt;/span&gt;&lt;/a&gt;
&lt;!-- …and a hidden menu of the modes it picks from --&gt;
&lt;ul id="mode-menu" hidden&gt;
	&lt;li data-value="substring"&gt;Substring&lt;/li&gt;
	&lt;li data-value="prefix"&gt;Prefix&lt;/li&gt;
	&lt;li data-value="subsequence"&gt;Subsequence&lt;/li&gt;
&lt;/ul&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// The mode lives in localStorage; your onAutocomplete reads it via CerbUI.SearchQuery.filterItems(items, prefix, mode).
const STORE_KEY = 'myapp.searchquery.filterMode';
let mode = CerbUI.SearchQuery.MATCH_MODES.indexOf(localStorage.getItem(STORE_KEY)) !== -1
	? localStorage.getItem(STORE_KEY) : 'subsequence';

// A gear in the --right toolbar opens a CerbUI.Menu of the modes, with a check on the active one.
const cfgMenu = new CerbUI.Menu(document.getElementById('mode-menu'), {
	onRenderItem: (li, src) => {
		const ico = document.createElement('span');
		ico.className = src.dataset.value === mode ? 'cerb-icons cerb-icon-check' : '';
		ico.style.cssText = 'width:1.2em;display:inline-block;margin-right:0.3em;';
		li.insertBefore(ico, li.firstChild);
	},
	onSelect: (li, src) => {
		mode = src.dataset.value;
		localStorage.setItem(STORE_KEY, mode);
		sq.focus(); sq.openAutocomplete();
	},
});
const cfgBtn = el.querySelector('[data-action=config]');
cfgBtn.addEventListener('click', () => cfgMenu.isOpen() ? cfgMenu.close() : cfgMenu.open(cfgBtn));{/literal}</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	// SearchQuery #1 — the adapter against a real record context (lazy-loads from the worklist endpoints)
	(function() {
		const el = document.getElementById('uiref-searchquery-adapter');
		const out = document.getElementById('uiref-searchquery-adapter-out');
		if(el && window.CerbUI && CerbUI.SearchQuery) {
			const sq = new CerbUI.SearchQuery(el, {
				onSearch: function(query) { if(out) out.textContent = query; },
				onAutocomplete: CerbUI.SearchQuery.queryFieldSource('cerberusweb.contexts.ticket'),
				context: 'cerberusweb.contexts.ticket',
			});
			const acBtn = el.querySelector('[data-action=autocomplete]');
			if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());
		}
	})();

	// SearchQuery #2 — a custom onAutocomplete (local static source with a nested sender:org:name: path)
	(function() {
		const el = document.getElementById('uiref-searchquery-custom');
		const out = document.getElementById('uiref-searchquery-custom-out');
		if(el && window.CerbUI && CerbUI.SearchQuery) {
			const FIELDS = [
				{ caption: 'status:',        value: 'status:',        hint: 'list' },
				{ caption: 'subject:',       value: 'subject:',       hint: 'text' },
				{ caption: 'sender:',        value: 'sender:',        snippet: 'sender:($0)', hint: 'contact' },
				{ caption: 'links.address:', value: 'links.address:', hint: 'text' },
				{ caption: 'created:',       value: 'created:',       snippet: 'created:($0)', hint: 'date' },
			];
			const NESTED = {
				'status:': [
					{ caption: 'open',    value: 'open',    suppressAutocomplete: true },
					{ caption: 'waiting', value: 'waiting', suppressAutocomplete: true },
					{ caption: 'closed',  value: 'closed',  suppressAutocomplete: true },
					{ caption: 'deleted', value: 'deleted', suppressAutocomplete: true },
				],
				'sender:': [
					{ caption: 'org:',   value: 'org:', snippet: 'org:($0)', hint: 'org' },
					{ caption: 'email:', value: 'email:', hint: 'text' },
				],
				'sender:org:': [{ caption: 'name:', value: 'name:', hint: 'text' }],
				// Parameterized-group sub-keys, keyed by the group-position scope (a trailing '()'):
				'created:()': [
					{ caption: 'since:', value: 'since:', snippet: 'since:"$0"' },
					{ caption: 'until:', value: 'until:', snippet: 'until:"$0"' },
					{ caption: 'days:',  value: 'days:',  snippet: 'days:[$0]' },
					{ caption: 'time:',  value: 'time:',  snippet: 'time:$0' },
				],
			};
			const localSource = function(ctx) {
				let key = ctx.path.join('');
				// In group-key position the final segment is tagged with '()' (e.g. 'created:()', 'sender:()').
				// Use the group's sub-keys if defined; otherwise fall back to the plain sibling key.
				if(key.endsWith('()') && !NESTED[key]) key = key.slice(0, -2);
				const items = (key === '') ? FIELDS : (NESTED[key] || []);
				return CerbUI.SearchQuery.filterItems(items, ctx.prefix); // default 'subsequence'
			};
			const sq = new CerbUI.SearchQuery(el, {
				onSearch: function(query) { if(out) out.textContent = query; },
				onAutocomplete: localSource,
			});
			const acBtn = el.querySelector('[data-action=autocomplete]');
			if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());
		}
	})();

	// SearchQuery #3 — a custom --right action: gear opens a filter-mode menu, persisted in localStorage
	(function() {
		const el = document.getElementById('uiref-searchquery-toolbar');
		const out = document.getElementById('uiref-searchquery-toolbar-out');
		if(el && window.CerbUI && CerbUI.SearchQuery && CerbUI.Menu) {
			// A focused flat field list — enough to feel the filter modes (try `linadd` with Subsequence).
			const FIELDS = [
				{ caption: 'status:',        value: 'status:' },
				{ caption: 'subject:',       value: 'subject:' },
				{ caption: 'sender:',        value: 'sender:' },
				{ caption: 'links.address:', value: 'links.address:' },
				{ caption: 'created:',       value: 'created:' },
			];
			const STORE_KEY = 'cerb.uiref.searchquery.filterMode';
			let mode = localStorage.getItem(STORE_KEY);
			if(CerbUI.SearchQuery.MATCH_MODES.indexOf(mode) === -1) mode = 'subsequence';

			const sq = new CerbUI.SearchQuery(el, {
				onSearch: function(query) { if(out) out.textContent = query; },
				onAutocomplete: function(ctx) {
					if(ctx.path.length) return []; // this demo only suggests top-level fields
					return CerbUI.SearchQuery.filterItems(FIELDS, ctx.prefix, mode);
				},
			});
			const acBtn = el.querySelector('[data-action=autocomplete]');
			if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());

			const cfgBtn = el.querySelector('[data-action=config]');
			const modeUl = document.getElementById('uiref-searchquery-modemenu');
			if(cfgBtn && modeUl) {
				const cfgMenu = new CerbUI.Menu(modeUl, {
					onRenderItem: function(li, src) {
						const ico = document.createElement('span');
						ico.className = src.dataset.value === mode ? 'cerb-icons cerb-icon-check' : '';
						ico.style.cssText = 'width:1.2em;display:inline-block;margin-right:0.3em;';
						li.insertBefore(ico, li.firstChild);
					},
					onSelect: function(li, src) {
						mode = src.dataset.value;
						localStorage.setItem(STORE_KEY, mode);
						sq.focus();
						sq.openAutocomplete();
					},
				});
				cfgBtn.addEventListener('click', () => cfgMenu.isOpen() ? cfgMenu.close() : cfgMenu.open(cfgBtn));
			}
		}
	})();
})();
</script>
