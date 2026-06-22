/*
 * CerbUI.nodeTypes — the data-type system behind the node editor (CerbUI.NodeEditor & friends).
 *
 * Like CerbUI.editorCore / CerbUI.num it's a plain CerbUI.<name> utility (camelCase, not a component): a single
 * stateless-ish registry of named data types with single inheritance, plus the compatibility check the editor uses
 * to decide whether an outlet may connect to an inlet. Zero DOM — pure logic, so it's unit-testable headless.
 *
 * A "type" is a name with an optional base type, forming a chain that always terminates at the root `any`:
 *   any → dictionary → credentials → api_key          (an api_key IS-A credentials IS-A dictionary)
 *   any → dictionary → record → ticket                (a ticket IS-A record IS-A dictionary)
 * An inlet that requires `credentials` therefore accepts a `credentials`, `api_key`, or `oauth2_token` source, but
 * not a bare `dictionary` (too general). `any` is the universal wildcard in both directions.
 *
 * Generics use angle-bracket notation parsed structurally: `list<ticket>` is compatible with `list<record>` (the
 * inner type is covariant), and `list<text>` satisfies a bare `list`.
 *
 * Usage:
 *   CerbUI.nodeTypes.register('credentials', 'dictionary', { color: '#ed8936', label: 'Credentials' });
 *   CerbUI.nodeTypes.register('api_key', 'credentials');
 *   CerbUI.nodeTypes.isCompatible('credentials', 'api_key');   // true  (source api_key fits target credentials)
 *   CerbUI.nodeTypes.isCompatible('api_key', 'credentials');   // false (a bare credentials isn't an api_key)
 *   CerbUI.nodeTypes.isCompatible('any', 'ticket');            // true  (any accepts everything)
 *
 * Ported from js-flow's TypeRegistry + isTypeCompatible (src/core/type-registry.js).
 */
CerbUI.nodeTypes = (function() {
	// name -> { name, base, color, label, schema, sample }. `any` is the root; everything chains up to it.
	const types = new Map();

	// The built-in primitives every dialect starts from. Custom types extend these (or each other) via register().
	const BASE = [
		{ name: 'any',        base: null, color: '#718096', label: 'Any' },
		{ name: 'text',       base: 'any', color: '#3182ce', label: 'Text' },
		{ name: 'int',        base: 'any', color: '#38a169', label: 'Integer' },
		{ name: 'float',      base: 'any', color: '#d69e2e', label: 'Float' },
		{ name: 'bool',       base: 'any', color: '#e53e3e', label: 'Boolean' },
		{ name: 'dictionary', base: 'any', color: '#ed8936', label: 'Dictionary' },
		{ name: 'list',       base: 'any', color: '#0987a0', label: 'List' },
		{ name: 'picklist',   base: 'any', color: '#805ad5', label: 'Picklist' },
	];

	function seed() {
		types.clear();
		BASE.forEach(t => types.set(t.name, { name: t.name, base: t.base, color: t.color, label: t.label, schema: null, sample: null }));
	}
	seed();

	// Parse `list<ticket>` → { container:'list', inner:'ticket', isGeneric:true }. A plain name → isGeneric:false.
	function parseType(typeString) {
		const m = String(typeString == null ? '' : typeString).match(/^(\w+)<(.+)>$/);
		if(m)
			return { container: m[1], inner: m[2], isGeneric: true, full: typeString };
		return { container: null, inner: null, isGeneric: false, full: typeString };
	}

	// Walk the base chain: does `childType` resolve to `parentType` at some ancestor? Handles generics structurally.
	function inheritsFrom(childType, parentType) {
		const child = parseType(childType);
		const parent = parseType(parentType);

		// Both generic (list<ticket> vs list<record>): containers AND inners must each inherit.
		if(child.isGeneric && parent.isGeneric)
			return inheritsFrom(child.container, parent.container) && inheritsFrom(child.inner, parent.inner);

		// Parent is generic but child isn't — a scalar can't satisfy a generic.
		if(parent.isGeneric && !child.isGeneric)
			return false;

		// Child is generic, parent isn't — compare the container (list<text> inherits from list).
		if(child.isGeneric && !parent.isGeneric)
			return inheritsFrom(child.container, parentType);

		// Plain chain walk.
		const name = child.isGeneric ? child.container : childType;
		if(name === parentType) return true;
		const t = types.get(name);
		if(!t || !t.base) return false;
		return inheritsFrom(t.base, parentType);
	}

	const nodeTypes = {
		// Register (or redefine) a type extending `baseType`. opts: { color, label, schema, sample }.
		register: function(name, baseType, opts) {
			baseType = baseType || 'any';
			if(!types.has(baseType))
				throw new Error("CerbUI.nodeTypes: base type '" + baseType + "' not found");
			opts = opts || {};
			types.set(name, {
				name: name,
				base: baseType,
				color: opts.color != null ? opts.color : null,
				label: opts.label != null ? opts.label : name,
				schema: opts.schema != null ? opts.schema : null,
				sample: opts.sample != null ? opts.sample : null,
			});
			return this;
		},

		has: function(name) { return types.has(parseType(name).isGeneric ? parseType(name).container : name); },
		get: function(name) { return types.get(name) || null; },
		all: function() { return Array.from(types.values()); },

		parseType: parseType,
		inheritsFrom: inheritsFrom,

		// The editor's connection gate: can a `source`-typed outlet feed a `target`-typed inlet?
		// `any` accepts/satisfies everything; otherwise source must inherit from (be-a) target.
		isCompatible: function(targetType, sourceType) {
			if(targetType === sourceType) return true;
			if(targetType === 'any') return true;   // target accepts anything
			if(sourceType === 'any') return true;   // source fits anywhere

			const target = parseType(targetType);
			const source = parseType(sourceType);

			// Both generic: container inherits + inner is covariant (list<ticket> → list<record>).
			if(target.isGeneric && source.isGeneric) {
				if(target.container !== source.container && !inheritsFrom(source.container, target.container))
					return false;
				return this.isCompatible(target.inner, source.inner);
			}
			// Target generic, source scalar — never.
			if(target.isGeneric && !source.isGeneric) return false;
			// Source generic, target scalar — list<text> satisfies list.
			if(!target.isGeneric && source.isGeneric) return inheritsFrom(source.container, targetType);

			// Plain inheritance: source IS-A target.
			return inheritsFrom(sourceType, targetType);
		},

		// Every type that (transitively) inherits from baseType, excluding baseType itself.
		getSubtypes: function(baseType) {
			const out = [];
			for(const name of types.keys())
				if(name !== baseType && inheritsFrom(name, baseType)) out.push(name);
			return out;
		},

		// The chain from `typeName` up to (and including) the root, e.g. ['ticket','record','dictionary','any'].
		getInheritanceChain: function(typeName) {
			const p = parseType(typeName);
			const name = p.isGeneric ? p.container : typeName;
			const chain = [typeName];
			const t = types.get(name);
			if(t && t.base) chain.push.apply(chain, this.getInheritanceChain(t.base));
			return chain;
		},

		// The display color for a type — its own, else inherited from its base, else neutral gray.
		getColor: function(typeName) {
			const p = parseType(typeName);
			if(p.isGeneric) return this.getColor(p.container);   // list<ticket> uses list's color
			const t = types.get(typeName);
			if(!t) return '#718096';
			if(t.color) return t.color;
			if(t.base) return this.getColor(t.base);
			return '#718096';
		},

		// Wipe back to just the base primitives (tests / re-seeding a fresh dialect).
		reset: function() { seed(); return this; },
	};

	return nodeTypes;
})();
