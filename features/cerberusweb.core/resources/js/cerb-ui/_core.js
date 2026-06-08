/*
 * cerb-ui — design-system component behaviors (plain JS, no jQuery).
 *
 * In DEVELOPMENT_MODE these per-component source files load individually for live editing.
 * For release, `composer build-js` concatenates + minifies them into resources/js/cerb-ui.js.
 * Components attach to the shared CerbUI global, e.g. CerbUI.Toggle.
 */
window.CerbUI = window.CerbUI || {};

// Metric data lives under two parallel namespaces, keyed the same way (a key suffix, or none = default):
//   numeric  -> data-value  / data-value-{key}   (e.g. 'size' -> data-value-size)   [used for math]
//   display  -> data-text   / data-text-{key}    (e.g. 'size' -> data-text-size)    [optional formatted text]
// Separate namespaces avoid the data-value-text vs. key="text" collision.
CerbUI._suffix = function(base, key) {
	return key ? (base + key.charAt(0).toUpperCase() + key.slice(1)) : base;
};
CerbUI.valueAttr = function(key) { return CerbUI._suffix('value', key); };
CerbUI.textAttr = function(key) { return CerbUI._suffix('text', key); };
