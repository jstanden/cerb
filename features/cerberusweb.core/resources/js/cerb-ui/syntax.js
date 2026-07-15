// CerbUI.SyntaxHighlight: read-only code highlighting that reuses the editors' own tokenizers.
// The server (e.g. sheet `code` columns) emits raw code in an element carrying `data-cerb-syntax="kata|json"`.
// enhance() finds those, tokenizes their text with the matching editor tokenizer, and swaps in colored spans
// using the editors' token classes. Keeping the raw code on the server keeps the output portable — an API
// consumer or a non-Cerb-UI portal gets the plain text + marker and can highlight it with its own system.
window.CerbUI = window.CerbUI || {};

CerbUI.SyntaxHighlight = {
	// Return colored mirror HTML for `text` in the given language, or escaped plain text for anything else.
	render: function(text, lang) {
		if('kata' === lang && CerbUI.KataEditor) return CerbUI.KataEditor.highlight(text);
		if('json' === lang && CerbUI.JsonEditor) return CerbUI.JsonEditor.highlight(text);
		return CerbUI.editorCore.escapeHtml(text);
	},

	// Highlight every not-yet-enhanced [data-cerb-syntax] element under `root` (a DOM node or jQuery-ish object).
	// Idempotent via the data-cerb-syntax-done flag; safe to re-run after an AJAX refresh replaces the DOM.
	enhance: function(root) {
		const el = (root && root.jquery) ? root[0] : (root || document);
		if(!el || !el.querySelectorAll) return;

		el.querySelectorAll('[data-cerb-syntax]:not([data-cerb-syntax-done])').forEach(function(node) {
			const lang = node.getAttribute('data-cerb-syntax');
			node.innerHTML = CerbUI.SyntaxHighlight.render(node.textContent, lang);
			node.setAttribute('data-cerb-syntax-done', '1');
		});
	},
};
