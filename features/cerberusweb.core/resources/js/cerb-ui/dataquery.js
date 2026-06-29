/*
 * CerbUI.DataQuery — a multi-line editor for Cerb's data-query language (the `type:… of:… by:… format:…
 * series.*: query:(…)` syntax that drives charts, sheets, datasources, and visualizations). It SUBCLASSES
 * CerbUI.ScriptingEditor to reuse the whole multi-line shell (line-number gutter, autosize, Tab/dedent,
 * move/delete-line, gutter markers, readOnly, Enter=newline) and swaps three behaviors:
 *
 *   - _tokenize: threads editorCore.kataScript OVER the shared search-query tokenizer, so the query syntax
 *     colors normally AND embedded template tags ({{ … }} / {% … %}) color on top — the `c=ui&a=dataQuery`
 *     endpoint runs the value through the template builder before executing, so a data query is query syntax
 *     with Twig/KataScript interpolation.
 *   - _scopeAt: inside a tag → the partial script word; else the nested data-query field path
 *     (editorCore.searchQuery.scopePathAt — the same grammar SearchQuery uses).
 *   - _autocompleteItems: inside a tag → kataScript suggestions; else the data-query field source (Part C).
 *
 * Markup mirrors ScriptingEditor under the `cerb-ui-dataquery` class prefix (set via the inherited static _NS).
 * The --input <textarea name="…"> holds the value directly and is the real POST field (native submit, no
 * folding, no hidden input):
 *
 *   <div class="cerb-ui-dataquery" id="dq">
 *     <span class="cerb-ui-dataquery--icon cerb-icons cerb-icon-database"></span>   (optional chrome)
 *     <div class="cerb-ui-dataquery--gutter" aria-hidden="true"></div>             (optional)
 *     <div class="cerb-ui-dataquery--field">
 *       <div class="cerb-ui-dataquery--highlight" aria-hidden="true"></div>
 *       <textarea class="cerb-ui-dataquery--input" name="…"></textarea>
 *       <span class="cerb-ui-dataquery--caret-anchor"></span>
 *     </div>
 *     <div class="cerb-ui-dataquery--right"><!-- icon buttons --></div>            (optional)
 *   </div>
 *
 * Usage (the field source is wired to the existing endpoints, so it's a drop-in for real widget configs):
 *   new CerbUI.DataQuery(el, { onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(type, of) });
 *
 * Public API (getValue/setValue/onChange/insertAtCursor/appendText/markers/…) is inherited from ScriptingEditor.
 * CSS lives in cerb.css (.cerb-ui-dataquery--*, mostly aliasing the scriptingeditor/searchquery token classes).
 */
CerbUI.DataQuery = class extends CerbUI.ScriptingEditor {
	static _instances = new WeakMap();   // own registry so CerbUI.DataQuery.from(el) is isolated from ScriptingEditor
	static _NS = 'dataquery';            // element-class namespace -> `.cerb-ui-dataquery--input`, etc.

	// Thread the query tokenizer UNDER kataScript line-by-line, carrying the open-tag state across lines so a
	// multi-line {% … %} keeps highlighting. Outside tags, each run goes through the shared query tokenizer.
	_tokenize(text) {
		const lines = text.split('\n');
		const toks = [];
		const queryPlain = (t, s /*, baseType */) => CerbUI.editorCore.searchQuery.tokenizeInto(t, s);
		let scriptOpen = null;
		for(let li = 0; li < lines.length; li++) {
			if(li > 0) toks.push({ type: 'text', value: '\n' });
			scriptOpen = CerbUI.editorCore.kataScript.tokenize(toks, lines[li], 'text', scriptOpen, queryPlain);
		}
		return toks;
	}

	// Autocomplete scope at the caret: inside a {{ }} / {% %} tag, the partial script word (so an accepted
	// suggestion replaces it); else the nested data-query field path (type:/of:/by:/… and the fields inside a
	// descending query:(…) clause).
	_scopeAt(text, caret) {
		const tctx = CerbUI.editorCore.kataScript.contextAt(text, caret);
		if(tctx) return { path: [], prefix: tctx.prefix, prefixRaw: tctx.prefixRaw, caret };
		return CerbUI.editorCore.searchQuery.scopePathAt(text, caret);
	}

	// Inside a tag → script language suggestions (command/function/filter or the call's params). Outside a tag →
	// the data-query field source (this.opts.onAutocomplete).
	_autocompleteItems(ctx) {
		const v = this.textarea.value, c = this.textarea.selectionStart;
		const t = CerbUI.editorCore.kataScript.contextAt(v, c);
		if(t) return (t.sub === 'args') ? CerbUI.editorCore.kataScript.suggestArgs(t) : CerbUI.editorCore.kataScript.suggest(t);
		return (typeof this.opts.onAutocomplete === 'function') ? this.opts.onAutocomplete(ctx) : [];
	}
};

// Query tokens use the searchquery palette; {{ }} / {% %} tags use the shared kscript palette. No new token CSS
// — DataQuery's SCSS just aliases these existing classes.
CerbUI.DataQuery._TOK_CLASS = Object.assign({},
	CerbUI.editorCore.searchQuery.TOK_CLASS,
	CerbUI.editorCore.kataScript.TOK_CLASS
);

// Built-in toolbar action — DataQuery is multi-line (gutter + tags), so it uses the shared editor toolbar (a top
// strip, like KataEditor/MarkdownEditor) rather than a SearchQuery-style --right corner. The one universal action
// is "show suggestions"; a host merges its own buttons (Run / Help / …) via `toolbar.sections`. Opt-in via
// `toolbar:true` (or `toolbar:{…}`); the inherited ScriptingEditor constructor calls editorCore.attachToolbar.
CerbUI.DataQuery.TOOLBAR_BUILTINS = {
	suggest: { icon: 'autocomplete', title: 'Suggestions (Ctrl/⌘+Space)', fn: (ed) => ed.openAutocomplete() },
};

/*
 * dataQueryFieldSource(seedType, seedOf, opts) — a ready-made onAutocomplete wired to the existing endpoints,
 * a CerbUI port of the Ace completer `$.fn.cerbCodeEditorAutocompleteDataQueries` (cerberus.js). Scope is the
 * flat `{type, of}` derived from the buffer (the widget's computed type/of are accepted as fallbacks when the
 * buffer hasn't named them yet). Three endpoints, all GET JSON:
 *   - structural keys  -> c=ui&a=dataQuerySuggestions&type=&of=   (the type's scope map: of:/by:/format:/…,
 *     plus `_contexts` and dynamic `_type:'autocomplete'` value descriptors). Empty/invalid type -> just `type:`.
 *   - dynamic values   -> c=ui&a=dataQuery&q=…   (substituting {{term}} with the typed prefix)
 *   - nested query:(…) -> delegated to CerbUI.SearchQuery.queryFieldSource(of) for the governing context, so the
 *     fields inside a `query:(…)` clause descend exactly like a worklist search (group:…, sender:org:…, etc.).
 * The type meta is refetched only when type/of changes; querySuggestionMeta (valid contexts + types) is cached
 * in localStorage by schema version, mirroring the Ace completer.
 */
CerbUI.DataQuery.dataQueryFieldSource = function(seedType, seedOf, opts) {
	opts = opts || {};
	const mode = opts.filterMode || 'subsequence';
	const aceSnippetToCerb = CerbUI.editorCore.aceSnippetToCerb;
	const SQ = CerbUI.editorCore.searchQuery;

	const meta = { loaded: false, recordTypes: [], dataQueryTypes: [] };
	let cache = {};                 // current type's scope map (scopeKey -> array | {_type} | _contexts)
	let curType = null, curOf = null;
	const qsByContext = {};         // memoized CerbUI.SearchQuery.queryFieldSource per `of:` context

	function get(urlargs) {
		return new Promise((resolve) => { genericAjaxGet('', urlargs, (json) => resolve(json)); });
	}

	function baseMap() { return { '': ['type:'], 'type:': meta.dataQueryTypes }; }

	// querySuggestionMeta: valid record contexts + data-query types. localStorage-cached by schema version.
	function loadMeta() {
		if(meta.loaded) return Promise.resolve();
		try {
			if(window.localStorage && localStorage.cerbQuerySuggestionMeta) {
				const cached = JSON.parse(localStorage.cerbQuerySuggestionMeta);
				if(cached && cached.schemaVersion
					&& typeof CerbSchemaRecordsVersion !== 'undefined' && cached.schemaVersion == CerbSchemaRecordsVersion) {
					meta.recordTypes = cached.recordTypes || [];
					meta.dataQueryTypes = cached.dataQueryTypes || [];
					meta.loaded = true;
					return Promise.resolve();
				}
			}
		} catch(e) { /* fall through to fetch */ }
		return get('c=ui&a=querySuggestionMeta').then(json => {
			if(json && typeof json === 'object') {
				meta.recordTypes = json.recordTypes || [];
				meta.dataQueryTypes = json.dataQueryTypes || [];
				meta.loaded = true;
				try { if(window.localStorage) localStorage.cerbQuerySuggestionMeta = JSON.stringify(json); } catch(e) {}
			}
		});
	}

	// The value of a top-level `field:` (e.g. type:/of:) — the first non-ws token after it at paren depth 0.
	function readTopLevelValue(query, fieldName) {
		const toks = SQ.tokenize(query);
		let depth = 0;
		for(let i = 0; i < toks.length; i++) {
			const t = toks[i];
			if(t.type === 'lparen' || t.type === 'lparenNeg' || t.type === 'lbrack') { depth++; continue; }
			if(t.type === 'rparen' || t.type === 'rbrack') { if(depth > 0) depth--; continue; }
			if(depth === 0 && t.type === 'field' && t.value === fieldName)
				return readValueAfter(toks, i);
		}
		return '';
	}

	// The `of:` declared INSIDE a `series.X:(…)` / `values.X:(…)` group (for nested series query descent).
	function readGroupOf(query, ownerField) {
		const toks = SQ.tokenize(query);
		for(let i = 0; i < toks.length; i++) {
			if(toks[i].type === 'field' && toks[i].value === ownerField) {
				let depth = 0, started = false;
				for(let j = i + 1; j < toks.length; j++) {
					const t = toks[j];
					if(t.type === 'lparen' || t.type === 'lparenNeg' || t.type === 'lbrack') { depth++; started = true; continue; }
					if(t.type === 'rparen' || t.type === 'rbrack') { if(depth > 0) depth--; if(started && depth === 0) break; continue; }
					if(started && depth === 1 && t.type === 'field' && t.value === 'of:')
						return readValueAfter(toks, j);
				}
				return '';
			}
		}
		return '';
	}

	function readValueAfter(toks, i) {
		for(let j = i + 1; j < toks.length; j++) {
			const v = toks[j];
			if(v.type === 'ws') continue;
			if(v.type === 'quoted') return v.inner || '';
			if(v.type === 'text' || v.type === 'number') return v.value;
			return '';
		}
		return '';
	}

	// Refetch the type's structural scope map when type/of changes (skip a mid-typed, not-yet-valid of:).
	function ensureScope(type, of) {
		if(type === curType && of === curOf) return Promise.resolve();
		curType = type; curOf = of;
		if(!type || meta.dataQueryTypes.indexOf(type) === -1) { cache = baseMap(); return Promise.resolve(); }
		return get('c=ui&a=dataQuerySuggestions&type=' + encodeURIComponent(type) + '&of=' + encodeURIComponent(of))
			.then(json => { cache = (json && typeof json === 'object') ? json : baseMap(); });
	}

	function toItem(s) {
		if(typeof s === 'string') return { caption: s, value: s };
		const value = (s.snippet != null) ? aceSnippetToCerb(s.snippet) : (s.value != null ? s.value : s.caption);
		const item = {
			caption: (s.caption != null) ? s.caption : value,
			value: value,
			hint: s.hint || s.meta || null,
			suppressAutocomplete: !!s.suppress_autocomplete,
		};
		if(s.snippet != null) item.snippet = aceSnippetToCerb(s.snippet);
		if(typeof s.score === 'number') item.score = s.score;
		if(s.icon) { item.icon = s.icon; if(s.color) item.iconColor = s.color; }
		return item;
	}

	function shape(arr, prefix) {
		return CerbUI.editorCore.filterItems(arr.map(toItem), prefix, mode);
	}

	// Resolve a {_type:'autocomplete', query, key, min_length} descriptor against c=ui&a=dataQuery.
	function resolveDynamic(d, prefix) {
		const minLen = d.min_length || 0;
		if(minLen && prefix.length < minLen) return Promise.resolve([]);
		const q = String(d.query).replace('{{term}}', prefix);
		return get('c=ui&a=dataQuery&q=' + encodeURIComponent(q)).then(json => {
			const out = [];
			if(json && json.data) {
				for(const row of json.data) {
					const v = row[d.key];
					if(v == null || String(v).length === 0) continue;
					out.push({
						caption: v,
						value: (String(v).indexOf(' ') !== -1) ? ('"' + v + '"') : v,
						suppressAutocomplete: true, // a concrete value is terminal
					});
				}
			}
			return out;
		});
	}

	function queryFieldSourceFor(of) {
		if(!qsByContext[of]) qsByContext[of] = CerbUI.SearchQuery.queryFieldSource(of, opts);
		return qsByContext[of];
	}

	// A structural scope key (type:/of:/by:/format:/series.*:/…). Best-effort clones the series.*: / values.*:
	// template for a concrete `series.NAME:` alias, mirroring the Ace completer.
	function structuralItems(scopeKey, prefix) {
		let val = cache[scopeKey];
		if(val === undefined && scopeKey.endsWith('()')) { scopeKey = scopeKey.slice(0, -2); val = cache[scopeKey]; }
		if(val === undefined) {
			const m = scopeKey.match(/^((series|values)\.)[^:]+:(.*)$/);
			if(m) {
				const tmpl = cache[m[1] + '*:'];           // 'series.*:' / 'values.*:' -> { suffix: suggestions }
				if(tmpl && typeof tmpl === 'object') {
					const suffix = m[3];                   // '' = group-key position inside series.NAME:(
					if(suffix === '') return shape(Array.isArray(tmpl['']) ? tmpl[''] : Object.keys(tmpl), prefix);
					val = tmpl[suffix];
				}
			}
		}
		if(Array.isArray(val)) return shape(val, prefix);
		if(val && typeof val === 'object' && val._type === 'autocomplete') return resolveDynamic(val, prefix);
		return [];
	}

	function resolveItems(ctx, of) {
		const path = ctx.path.slice();
		const prefix = ctx.prefix || '';

		// Inside a query:(…) clause? Delegate the field descent to a search-query source for the governing `of:`.
		const isQuerySeg = (seg) => /^query(\.required)?:(\(\))?$/.test(seg);
		const qIdx = path.findIndex(isQuerySeg);
		if(qIdx !== -1) {
			let ctxOf = of;
			const ownerRaw = (qIdx >= 1) ? path[qIdx - 1].replace(/\(\)$/, '') : '';
			if(/^(series|values)\.[^:]+:$/.test(ownerRaw))
				ctxOf = readGroupOf(ctx.query || '', ownerRaw) || of;
			if(!ctxOf) return [];
			return queryFieldSourceFor(ctxOf)({
				path: path.slice(qIdx + 1),
				prefix: prefix,
				prefixRaw: ctx.prefixRaw,
				context: ctxOf,
				query: ctx.query,
				caret: ctx.caret,
			});
		}

		return structuralItems(path.join(''), prefix);
	}

	return function(ctx) {
		const query = ctx.query || '';
		return loadMeta().then(() => {
			let type = readTopLevelValue(query, 'type:');
			if(!type && seedType) type = seedType;
			let of = readTopLevelValue(query, 'of:');
			if(of && meta.recordTypes.indexOf(of) === -1) of = ''; // ignore a half-typed of:
			if(!of && seedOf && meta.recordTypes.indexOf(seedOf) !== -1) of = seedOf;
			if(!of) of = curOf || '';
			return ensureScope(type, of).then(() => resolveItems(ctx, of));
		});
	};
};
