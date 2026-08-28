<?php
namespace Cerb\Agent;

/**
 * What an AI worker contributes to a turn that runs as it -- read from `agent.config_kata`.
 *
 * An agent declares a default system prompt, model query, tools, filesystems, terminal and commands, plus a
 * per-SURFACE override of each. A surface is where the agent is running: today an agent-pane component
 * (`automation`, `mail_reply`, `icon`, ...), later an event (`mention`, `assigned`). The caller names it; this
 * class has no opinion about the vocabulary, which is what keeps the pane's `component` string out of
 * `LlmAgentNode`.
 *
 * The blocks are DELIBERATELY the same grammar as `llm.agent: inputs:`, so `LlmAgentNode` merges them without
 * translating and an author who has written one has written the other. A `tools:` entry is keyed
 * by the `agent_tool` record's own name; what the tool TAKES is declared in the automation that answers it. See
 * `_CerbApplication_KataSchemas::agent()`.
 *
 * The merge is: scalars REPLACE, collections MERGE, prose APPENDS. The root scope is GLOBAL rather than a set
 * of defaults -- for the collections a surface UNIONS with it, so there is nothing there to override.
 *
 *   system_prompt                 global, then the surface's addendum -- a persona plus what's different here
 *   models_query, automation      the surface's value wins when it isn't blank
 *   tools, mounts, commands       deep union; the surface overrides only the leaves it names, so it can turn
 *                                 one tool off or re-point one mount without restating the entry
 *   terminal                      union, and ADDITIVE ONLY: a surface can grant a `cerb` namespace the agent
 *                                 doesn't have, and can never revoke one it does. Enforced in resolve(), which
 *                                 prunes each scope BEFORE merging -- see _pruneTerminal().
 *   disabled, description         surface only -- there is no global value to fall back to
 *
 * AUTHORING A COMPONENT BLOCK IS THE OPT-IN. A surface the agent has no block for is not enabled, so adding a
 * new pane component never turns every existing agent loose on it. Turning one OFF is `disabled@bool: yes`,
 * not deleting the block: the UI editor can't comment a block out the way a hand author would, so switching a
 * surface off would otherwise discard its prompt, tools, and model query. Same polarity as `is_disabled`
 * everywhere else, and as `disabled@bool:` on a tool.
 *
 * The pure half (parse/merge) takes arrays and returns arrays so it can be exercised with no database; only
 * forWorker() and getEnabledAgents() touch DAOs.
 */
class Config {
	/** Surface-independent keys, in the order LlmAgentNode consumes them. */
	const KEY_AUTOMATION = 'automation';
	const KEY_COMMANDS = 'commands';
	const KEY_COMPONENTS = 'components';
	const KEY_DISABLED = 'disabled';
	const KEY_MODELS_QUERY = 'models_query';
	const KEY_MOUNTS = 'mounts';
	const KEY_SYSTEM_PROMPT = 'system_prompt';
	const KEY_TERMINAL = 'terminal';
	const KEY_TOOLS = 'tools';

	/**
	 * The keys a scope may carry, in the order they're WRITTEN -- the agent's defaults, or one surface's block.
	 *
	 * The AI tab serializes against this instead of re-emitting every key it happens to find. Carrying an
	 * unknown key through sounds conservative and isn't: `agent()` rejects it on save, and the editor renders
	 * no field for it, so the record becomes unsavable with no way to remove the thing blocking it. A key that
	 * is no longer part of the grammar has to be DROPPED for the tab to stay usable.
	 *
	 * "Valid", not "rendered" -- a key the editor draws no control for still belongs here and still rides
	 * through untouched (`commands:` is the standing example, as is a mount's `at:` or a tool's `labels:`
	 * nested under a key that IS listed).
	 *
	 * ⚠ Must match `_CerbApplication_KataSchemas::agent()`. A key added there and not here is silently dropped
	 * the next time someone opens the AI tab and saves.
	 */
	static function getScopeKeys(bool $is_surface = false) : array {
		$keys = [
			self::KEY_SYSTEM_PROMPT,
			self::KEY_MODELS_QUERY,
			self::KEY_AUTOMATION,
			self::KEY_MOUNTS,
			self::KEY_TERMINAL,
			self::KEY_TOOLS,
			self::KEY_COMMANDS,
		];

		// Surface-only, and they LEAD: both say what the block is before what the agent brings to it.
		// `components:` is on neither list -- the nesting is the serializer's own structure, not a key.
		if($is_surface)
			array_unshift($keys, self::KEY_DISABLED, self::KEY_DESCRIPTION);

		return $keys;
	}

	/**
	 * `config_kata` text -> the annotated tree, or `[]` when it's blank or unparseable.
	 *
	 * formatTree() rather than parse() alone: `@bool`/`@int` annotations are applied there, so without it a
	 * `disabled@bool: yes` arrives as the string "yes" under the key `disabled@bool` and every gate reads the
	 * wrong answer -- here, the dangerous direction, since an unrecognized key means "not disabled".
	 * The dict is null on purpose -- an agent's config is static configuration, not a per-turn template.
	 */
	static function parse(?string $config_kata, ?string &$error = null) : array {
		if('' === trim(strval($config_kata)))
			return [];

		$kata = \DevblocksPlatform::services()->kata();

		if(false === ($parsed = $kata->parse($config_kata, $error)))
			return [];

		if(false === ($parsed = $kata->formatTree($parsed, null, $error)))
			return [];

		return is_array($parsed) ? $parsed : [];
	}

	/**
	 * The AI tab's POST -> the config tree to emit, merged onto what is already stored.
	 *
	 * The counterpart to `getScopeKeys()`, and the one place the tab's fields become KATA. It runs here rather
	 * than in the browser so the emitter, the schema, and the merge rules all stay on one side of the wire.
	 *
	 * Pure -- arrays in, arrays out, no DAOs. `$refs` carries the lookups the caller already resolved
	 * (`filesystems`/`tools`/`automations`, each `id => name`), because a chooser posts record IDS while KATA
	 * keys on NAMES. `$surfaces` and `$namespaces` are the legal keys; nothing is ever taken from a POST key
	 * NAME, so a crafted form can't author a key the schema has never heard of.
	 *
	 * Two properties the form depends on, both easy to lose:
	 *
	 * - Only keys `getScopeKeys()` still lists are written. What the form renders no control for rides through
	 *   untouched (`commands:`, a mount's `at:`, a tool's `labels:`), so visiting this tab can't lose config
	 *   written through the API. But a key the schema no longer HAS is dropped: `agent()` rejects it on save
	 *   and there is no field to remove it through, so carrying it makes the record permanently unsavable.
	 * - An enabled surface writes NO `disabled` key, and a childless block is the shortest way to say "yes,
	 *   here, with the defaults". Which is why a surface that is off AND empty is omitted entirely -- writing
	 *   an empty block for every catalog entry would turn the agent loose on all of them.
	 */
	static function fromForm(array $existing, array $post, array $refs, array $surfaces, array $namespaces) : array {
		$strings = \DevblocksPlatform::services()->string();

		$config = self::_scopeFromForm($existing, $post, $refs, $namespaces, false, []);

		$existing_components = is_array($existing[self::KEY_COMPONENTS] ?? null) ? $existing[self::KEY_COMPONENTS] : [];
		$posted_components = is_array($post[self::KEY_COMPONENTS] ?? null) ? $post[self::KEY_COMPONENTS] : [];

		$components = [];

		// A block for a surface this install no longer has -- a disabled plugin, a component since renamed --
		// is carried through. The tab renders no panel for it, so dropping it would lose an agent's config for
		// a reason that has nothing to do with the agent.
		foreach($existing_components as $key => $block) {
			if(is_array($block) && !in_array(strval($key), $surfaces, true))
				$components[$key] = $block;
		}

		foreach($surfaces as $surface) {
			// A surface the form didn't carry is not a surface someone cleared -- it's one that wasn't on the
			// page. Building a block for it would author config nobody asked for, which for a component the
			// form has never rendered is indistinguishable from switching the agent loose on it.
			if(!array_key_exists($surface, $posted_components) && !array_key_exists($surface, $existing_components))
				continue;

			$was = is_array($existing_components[$surface] ?? null) ? $existing_components[$surface] : [];
			$posted = is_array($posted_components[$surface] ?? null) ? $posted_components[$surface] : [];

			$enabled = $strings->toBool($posted['enabled'] ?? false);
			$block = self::_scopeFromForm($was, $posted, $refs, $namespaces, true, $config);

			// Nothing authored and not running here: say nothing rather than write a block that means "off".
			if(!$block && !$enabled && !array_key_exists($surface, $existing_components))
				continue;

			// LEADS, because it changes what the whole block means.
			$components[$surface] = $enabled ? $block : array_merge([self::KEY_DISABLED => true], $block);
		}

		if($components)
			$config[self::KEY_COMPONENTS] = $components;

		return $config;
	}

	/**
	 * One scope -- the agent's defaults, or one surface's block -- in `getScopeKeys()` order.
	 *
	 * `$global` is the ALREADY-BUILT agent-wide scope, not the stored one: granting a terminal namespace
	 * globally and picking it on a surface in the same save must not write it twice, and the stored config
	 * would still think it was ungranted.
	 */
	private static function _scopeFromForm(array $existing, array $post, array $refs, array $namespaces, bool $is_surface, array $global) : array {
		$out = [];

		foreach(self::getScopeKeys($is_surface) as $key) {
			switch($key) {
				// Derived from the surface's Enabled toggle and written by the caller, where it can lead.
				case self::KEY_DISABLED:
					continue 2;

				case self::KEY_DESCRIPTION:
				case self::KEY_MODELS_QUERY:
				case self::KEY_SYSTEM_PROMPT:
					$value = trim(strval($post[$key] ?? ''));
					break;

				case self::KEY_AUTOMATION:
					$name = strval(($refs['automations'] ?? [])[intval($post['automation_id'] ?? 0)] ?? '');
					$value = ('' !== $name) ? ('cerb:automation:' . $name) : '';
					break;

				case self::KEY_MOUNTS:
					$value = self::_referencesFromForm(
						$existing[$key] ?? [],
						$post['mount_ids'] ?? [],
						$post['mount_keys'] ?? [],
						$refs['filesystems'] ?? []
					);
					break;

				case self::KEY_TOOLS:
					$value = self::_referencesFromForm(
						$existing[$key] ?? [],
						$post['tool_ids'] ?? [],
						$post['tool_keys'] ?? [],
						$refs['tools'] ?? []
					);
					break;

				case self::KEY_TERMINAL:
					$value = self::_terminalFromForm(
						$existing[$key] ?? [],
						$post[$key] ?? [],
						$namespaces,
						$is_surface ? (($global[self::KEY_TERMINAL] ?? [])['cerb'] ?? []) : []
					);
					break;

				// A key in the grammar the form draws no control for (`commands:`). Untouched, not dropped.
				default:
					$value = $existing[$key] ?? null;
					break;
			}

			if(is_array($value) ? !!$value : ('' !== strval($value)))
				$out[$key] = $value;
		}

		return $out;
	}

	/**
	 * A chooser's posted record ids -> a `{name => options}` map, keyed the way KATA is.
	 *
	 * Keyed by NAME because that is what survives an export. The stored KEY is reused when one exists, since it
	 * may carry an annotation the chip has no way to show (`docs@optional:`), and so is the stored VALUE, since
	 * it may carry options this form renders nothing for (a mount's `at:`, a tool's `labels:`).
	 *
	 * `$raw_keys` is what no chooser can represent: an entry under a reserved prefix (`automation/`, `tool/`),
	 * or a name whose record is gone. They ride through only while their hidden input is posted -- that is what
	 * makes the remove button work at all, and it is why an unknown key is never invented from one.
	 */
	private static function _referencesFromForm(mixed $existing, mixed $ids, mixed $raw_keys, array $names_by_id) : array {
		$existing = is_array($existing) ? $existing : [];
		$out = [];

		$key_by_name = [];

		foreach(array_keys($existing) as $key)
			$key_by_name[explode('@', strval($key), 2)[0]] = $key;

		foreach((is_array($ids) ? $ids : []) as $id) {
			if(!($name = strval($names_by_id[intval($id)] ?? '')))
				continue;

			$key = $key_by_name[$name] ?? $name;
			$out[$key] = is_array($existing[$key] ?? null) ? $existing[$key] : [];
		}

		foreach((is_array($raw_keys) ? $raw_keys : []) as $raw) {
			$raw = strval($raw);

			if('' === $raw || array_key_exists($raw, $out) || !array_key_exists($raw, $existing))
				continue;

			$out[$raw] = is_array($existing[$raw]) ? $existing[$raw] : [];
		}

		return $out;
	}

	/**
	 * The `terminal:` block from the picked namespace names.
	 *
	 * Grants only. A namespace the agent already has is skipped rather than restated -- and, more to the point,
	 * a surface has no way to say "not this one": the picker never offers an inherited namespace and this never
	 * writes a false. That is the same rule `resolve()` enforces on the merge; expressing it in both places is
	 * what keeps the form honest about what it can do.
	 *
	 * The legal set comes from the registered namespace list, never from the POST, so a crafted form can't
	 * grant something this install doesn't have. An UNREGISTERED namespace already in the config is a different
	 * thing -- it isn't "unpicked", it's unknown -- so it rides through.
	 */
	private static function _terminalFromForm(mixed $existing, mixed $picked, array $namespaces, array $inherited) : array {
		$out = is_array($existing) ? $existing : [];
		$was = is_array($out['cerb'] ?? null) ? $out['cerb'] : [];
		$picked = is_array($picked) ? array_map('strval', $picked) : [];

		$cerb = [];

		foreach($namespaces as $name) {
			if(array_key_exists($name, $inherited) || !in_array($name, $picked, true))
				continue;

			// Any per-namespace options someone authored survive being re-picked.
			$cerb[$name] = is_array($was[$name] ?? null) ? $was[$name] : [];
		}

		foreach($was as $name => $value) {
			if(!in_array(strval($name), $namespaces, true) && !array_key_exists($name, $cerb))
				$cerb[$name] = $value;
		}

		if($cerb)
			$out['cerb'] = $cerb;
		else
			unset($out['cerb']);

		return $out;
	}

	/**
	 * Is this agent enabled on `$surface`?
	 *
	 * The KEY being authored is the opt-in, so `array_key_exists()` rather than a truthiness test -- a childless
	 * `mail_reply:` parses to an empty array, which is the shortest way to say "yes, here, with the defaults"
	 * and must not read as absence.
	 *
	 * The flag itself goes through `DevblocksUiEventHandler::isBlockEnabled()` -- the same predicate event
	 * handlers, toolbar items, and outcomes use -- rather than an `empty()` test here. It reads `enabled:` as
	 * well as `disabled:`, tolerates a named flag (`disabled/over_budget:`), and runs the value through
	 * `toBool()`, which is the part a local test always gets wrong: an unannotated `disabled: off` stays the
	 * raw string "off" and PHP calls that truthy, switching the surface off with the word for on.
	 */
	static function isEnabledOn(array $config, string $surface) : bool {
		if('' === $surface)
			return false;

		$components = $config[self::KEY_COMPONENTS] ?? [];

		if(!is_array($components) || !array_key_exists($surface, $components))
			return false;

		$component = $components[$surface];

		if(!is_array($component))
			return true;

		return \DevblocksUiEventHandler::isBlockEnabled($component);
	}

	/**
	 * Which surfaces this agent is enabled on -- authored and not disabled.
	 *
	 * @return string[]
	 */
	static function getEnabledSurfaces(array $config) : array {
		$components = $config[self::KEY_COMPONENTS] ?? [];

		if(!is_array($components))
			return [];

		$surfaces = [];

		foreach(array_keys($components) as $surface) {
			if(self::isEnabledOn($config, strval($surface)))
				$surfaces[] = strval($surface);
		}

		sort($surfaces);

		return $surfaces;
	}

	/**
	 * The agent's defaults merged with one surface's overrides.
	 *
	 * `$surface` may be '' (no surface, or one this agent has no block for) -- the defaults are still returned,
	 * because `agent:` on an `llm.agent:` outside any pane is a legitimate way to run as an agent.
	 *
	 * @return array `{system_prompt, models_query, automation, commands, terminal, mounts, tools}`
	 */
	static function resolve(array $config, string $surface = '') : array {
		$override = [];

		if('' !== $surface && is_array($config[self::KEY_COMPONENTS][$surface] ?? null))
			$override = $config[self::KEY_COMPONENTS][$surface];

		$resolved = [
			self::KEY_SYSTEM_PROMPT => self::_appendProse(
				$config[self::KEY_SYSTEM_PROMPT] ?? '',
				$override[self::KEY_SYSTEM_PROMPT] ?? ''
			),
			self::KEY_MODELS_QUERY => self::_replaceScalar(
				$config[self::KEY_MODELS_QUERY] ?? '',
				$override[self::KEY_MODELS_QUERY] ?? ''
			),
			self::KEY_AUTOMATION => self::_replaceScalar(
				$config[self::KEY_AUTOMATION] ?? '',
				$override[self::KEY_AUTOMATION] ?? ''
			),
		];

		// DEEP, so a surface overrides only what it names. `tools: automation/web-search: disabled@bool: yes`
		// has to turn an inherited tool off WITHOUT dropping the `uri:` that identifies it, and
		// `mounts: acme-docs: mode: rw` has to keep the default's `at:`. A shallow replace makes both of those
		// silently discard the rest of the entry, which is the whole point of naming a default at the agent
		// level and overriding it per surface.
		//
		// Safe here because no leaf in this grammar is a LIST -- array_replace_recursive merges lists
		// element-wise by index, so `['x']` over `['a','b','c']` yields `['x','b','c']`. If a list-valued key is
		// ever added to agent(), it needs its own shallow branch.
		foreach([self::KEY_COMMANDS, self::KEY_MOUNTS, self::KEY_TOOLS] as $key) {
			$resolved[$key] = array_replace_recursive(
				is_array($config[$key] ?? null) ? $config[$key] : [],
				is_array($override[$key] ?? null) ? $override[$key] : []
			);
		}

		// Terminal is ADDITIVE, and this is the line that makes that true: each scope is pruned BEFORE the
		// merge, so a false under a surface can only ever mean "I didn't grant this here" -- never "revoke what
		// the agent already has". Pruning AFTER would let a surface delete an inherited namespace, which is a
		// power a per-surface block shouldn't have and which no UI can show honestly.
		$resolved[self::KEY_TERMINAL] = array_replace_recursive(
			self::_pruneTerminal($config[self::KEY_TERMINAL] ?? []),
			self::_pruneTerminal($override[self::KEY_TERMINAL] ?? [])
		);

		return $resolved;
	}

	/**
	 * Drop `cerb` namespaces written with a false value, and any block left empty.
	 *
	 * `Cli::_enabled()` gates on a namespace KEY being present (`array_intersect_key`), not on its value -- so
	 * without this, `records@bool: no` would ENABLE records. That is the dangerous direction, and it is the
	 * whole reason this runs. Read through `toBool()` for the same reason the enablement flag is: an
	 * unannotated `records: no` is the raw string "no", which PHP calls truthy.
	 *
	 * Called PER SCOPE, before the merge (see resolve()), so a false says "not granted in this block" and
	 * nothing more. A surface cannot revoke a namespace the agent already has; it can only add.
	 *
	 * An emptied `cerb:` is removed rather than left as an empty block, because an empty block would still
	 * advertise the CLI with nothing under it.
	 */
	private static function _pruneTerminal(mixed $terminal) : array {
		if(!is_array($terminal))
			return [];

		$strings = \DevblocksPlatform::services()->string();

		foreach($terminal as $key => $namespaces) {
			if(!is_array($namespaces))
				continue;

			foreach($namespaces as $name => $enabled) {
				// An object (including the empty one a childless key parses to) is the ON shape.
				if(!is_array($enabled) && !$strings->toBool($enabled))
					unset($terminal[$key][$name]);
			}

			if(!$terminal[$key])
				unset($terminal[$key]);
		}

		return $terminal;
	}

	/**
	 * `agent.config_kata` for one worker, resolved for `$surface`. An agent with no row (or no config) resolves
	 * to empty defaults rather than null, so callers never branch on existence -- the same contract
	 * `DAO_Agent::get()` already offers.
	 */
	static function forWorker(int $worker_id, string $surface = '') : array {
		return self::resolve(\DAO_Agent::getConfig($worker_id), $surface);
	}

	/**
	 * The models this agent may use, as the `{name => overrides}` map `llm.agent:`, `llm.chat:`, and
	 * `agentPrompt:` already consume.
	 *
	 * Takes a RESOLVED config (defaults ⊕ surface), so a per-surface `models_query` narrows the pool wherever
	 * the surface is known. A blank query is the zero-config pool -- every available model in the admin's
	 * `priority` order -- which is what an agent with no model policy should get.
	 *
	 * This is the whole reason `llm.router:` doesn't need an `agent:` input: an agent's model policy reaches a
	 * turn through the same two places its identity already does, so a script that names an agent gets its
	 * pool without wiring one up. `llm.router:` stays for the case it was built for -- needing the list AS
	 * DATA, to round-robin or budget against.
	 *
	 * `status:available` is forced ahead of the query by `resolveQueryModelNames()`, so a query here can only
	 * ever narrow -- an agent record cannot widen its own pool.
	 */
	static function resolveModelPool(array $resolved_config, ?string &$error = null) : array {
		$query = trim(strval($resolved_config[self::KEY_MODELS_QUERY] ?? ''));

		return \DAO_AgentModel::mapNamesToModels(\DAO_AgentModel::resolveQueryModelNames($query, $error));
	}

	/**
	 * Resolve every record a config REFERENCES, for an editor that has to show them as chips.
	 *
	 * The config stores portable references -- a filesystem by name, an automation by `cerb:automation:` URI --
	 * because that's what survives an export and reads correctly in KATA. A record chooser needs `{id, label}`.
	 * Rather than make the browser resolve them one request at a time, every reference in every scope is
	 * collected here and looked up in two queries.
	 *
	 * A reference that resolves to nothing is simply absent from the map: the editor shows the raw name, which
	 * is the honest rendering of a filesystem someone deleted.
	 *
	 * `tools` carries each referenced tool's label and icon, which is all a card needs -- what a tool TAKES is
	 * declared in the automation that answers it and is not configured here.
	 *
	 * @return array `{filesystems: {name => {id, label}}, automations: {uri => {id, label}}, tools: {name => {id, label, icon, parameters}}}`
	 */
	static function describeReferences(array $config) : array {
		$scopes = [$config];

		foreach(($config[self::KEY_COMPONENTS] ?? []) as $block) {
			if(is_array($block))
				$scopes[] = $block;
		}

		$mount_names = [];
		$automation_uris = [];
		$tool_names = [];

		foreach($scopes as $scope) {
			foreach(array_keys(is_array($scope[self::KEY_MOUNTS] ?? null) ? $scope[self::KEY_MOUNTS] : []) as $name) {
				// A mount key may carry an annotation (`docs@optional:`); the name is what precedes it.
				$mount_names[\DevblocksPlatform::services()->string()->strBefore(strval($name), '@')] = true;
			}

			foreach((is_array($scope[self::KEY_TOOLS] ?? null) ? $scope[self::KEY_TOOLS] : []) as $tool_key => $tool) {
				if(!is_array($tool))
					continue;

				// A legacy `automation/<name>:` entry points at an automation; everything that isn't one of the
				// reserved families names an `agent_tool` record in its key BASE.
				if(($uri = trim(strval($tool['uri'] ?? ''))))
					$automation_uris[$uri] = true;

				$base = strval(explode('/', explode('@', strval($tool_key), 2)[0], 2)[0]);

				if('' !== $base && !in_array($base, \Cerb\AutomationBuilder\Node\LlmAgentNode::TOOL_RESERVED_PREFIXES, true))
					$tool_names[$base] = true;
			}

			if(($uri = trim(strval($scope[self::KEY_AUTOMATION] ?? ''))))
				$automation_uris[$uri] = true;
		}

		$filesystems = [];

		if($mount_names) {
			foreach(\DAO_AgentFilesystem::getAll() as $fs) {
				// Case-insensitively, because that's how a mount resolves at runtime
				// (`Filesystem::describeSpecs()` lowercases the name).
				if(array_key_exists(\DevblocksPlatform::strLower($fs->name), array_change_key_case($mount_names)))
					$filesystems[$fs->name] = ['id' => $fs->id, 'label' => $fs->name];
			}
		}

		$automations = [];

		if($automation_uris) {
			$names = array_map(
				fn($uri) => \DevblocksPlatform::services()->string()->strAfter($uri, 'cerb:automation:') ?: $uri,
				array_keys($automation_uris)
			);

			foreach(\DAO_Automation::getByUris($names) as $automation)
				$automations['cerb:automation:' . $automation->name] = ['id' => $automation->id, 'label' => $automation->name];
		}

		$tools = [];

		if($tool_names) {
			foreach(\DAO_AgentTool::getAll() as $tool) {
				if(!array_key_exists($tool->name, $tool_names))
					continue;

				// The NAME, not `getDisplayName()`: this labels a chip in the AI tab's tool chooser, and that
				// chooser rebuilds the `tools:` map from its chips -- so a display label here would become the
				// KATA key the next time anyone touched the field. It also matches what the autocomplete lists.
				$tools[$tool->name] = [
					'id' => $tool->id,
					'label' => $tool->name,
					'icon' => $tool->getDisplayIcon(),
				];
			}
		}

		return [
			'filesystems' => $filesystems,
			'automations' => $automations,
			'tools' => $tools,
		];
	}

	/**
	 * One scope shaped for the form that edits it -- the read side of `fromForm()`.
	 *
	 * Everything the template would otherwise have to work out in Smarty: which references resolved to a record
	 * (a chooser chip) and which didn't (a row with a remove button), what a `docs@optional:` key's bare name
	 * is, and whether a terminal namespace is on because this scope says so or because the agent does.
	 *
	 * `$global` is the agent-wide scope, empty when describing that scope itself. It decides which terminal
	 * namespaces are still grantable, and it produces the `global` summaries a surface panel uses as each
	 * field's PLACEHOLDER -- what you get here by leaving the field alone. Computed here because it is the one
	 * place that can read both scopes.
	 *
	 * "Global", not "defaults": for mounts, tools, and terminal a surface UNIONS with this scope rather than
	 * overriding it, so calling it a default would promise a replace that never happens.
	 */
	static function describeScopeForForm(array $scope, array $refs, array $namespaces, array $global = []) : array {
		$strings = \DevblocksPlatform::services()->string();

		$out = [
			self::KEY_DESCRIPTION => trim(strval($scope[self::KEY_DESCRIPTION] ?? '')),
			self::KEY_SYSTEM_PROMPT => strval($scope[self::KEY_SYSTEM_PROMPT] ?? ''),
			self::KEY_MODELS_QUERY => strval($scope[self::KEY_MODELS_QUERY] ?? ''),
			self::KEY_AUTOMATION => ['id' => 0, 'label' => ''],
			self::KEY_MOUNTS => self::_describeEntriesForForm($scope[self::KEY_MOUNTS] ?? [], $refs['filesystems'] ?? [], false),
			self::KEY_TOOLS => self::_describeEntriesForForm($scope[self::KEY_TOOLS] ?? [], $refs['tools'] ?? [], true),
			self::KEY_TERMINAL => [],
		];

		if(($uri = trim(strval($scope[self::KEY_AUTOMATION] ?? '')))) {
			$ref = ($refs['automations'] ?? [])[$uri] ?? null;

			// An automation that no longer exists still shows, as its URI -- the honest rendering, and it keeps
			// the reference until someone clears the field.
			$out[self::KEY_AUTOMATION] = [
				'id' => intval($ref['id'] ?? 0),
				'label' => strval($ref['label'] ?? $strings->strAfter($uri, 'cerb:automation:') ?: $uri),
			];
		}

		$own = is_array(($scope[self::KEY_TERMINAL] ?? [])['cerb'] ?? null) ? $scope[self::KEY_TERMINAL]['cerb'] : [];
		$up = is_array(($global[self::KEY_TERMINAL] ?? [])['cerb'] ?? null) ? $global[self::KEY_TERMINAL]['cerb'] : [];

		// Every namespace, with what THIS scope grants. Which of them are already granted above is a separate
		// answer (`global.terminal`) because the editor shows them rather than hiding them -- as ghosts, so you
		// can see what a surface is adding TO without being offered a control that can't do anything.
		foreach($namespaces as $name => $summary) {
			$out[self::KEY_TERMINAL][$name] = [
				'on' => array_key_exists($name, $own) && (is_array($own[$name]) || $strings->toBool($own[$name])),
				'summary' => strval($summary),
			];
		}

		if($global) {
			$mounts = self::_describeEntriesForForm($global[self::KEY_MOUNTS] ?? [], $refs['filesystems'] ?? [], false);
			$tools = self::_describeEntriesForForm($global[self::KEY_TOOLS] ?? [], $refs['tools'] ?? [], true);

			$uri = trim(strval($global[self::KEY_AUTOMATION] ?? ''));

			$out['global'] = [
				self::KEY_SYSTEM_PROMPT => trim(strval($global[self::KEY_SYSTEM_PROMPT] ?? '')),
				self::KEY_MODELS_QUERY => trim(strval($global[self::KEY_MODELS_QUERY] ?? '')),
				self::KEY_MOUNTS => implode(', ', array_merge(array_column($mounts['records'], 'label'), array_column($mounts['raw'], 'key'))),
				self::KEY_TOOLS => implode(', ', array_merge(array_column($tools['records'], 'label'), array_column($tools['raw'], 'key'))),
				self::KEY_AUTOMATION => $uri ? strval($strings->strAfter($uri, 'cerb:automation:') ?: $uri) : '',
				self::KEY_TERMINAL => implode(', ', array_keys($up)),
			];
		}

		return $out;
	}

	/**
	 * A `mounts:`/`tools:` block split into what a chooser can show and what it can't.
	 *
	 * `raw` is the second half of the pass-through contract: an entry under a reserved prefix, or a name whose
	 * record is gone, is listed as itself with a remove button. Without that it would ride through every save
	 * with no way to delete it -- "untouched" quietly meaning "permanent". The two are told apart by `reason`,
	 * because they read completely differently to whoever opens the tab: one was authored deliberately
	 * somewhere else, the other is a reference that broke.
	 *
	 * @return array `{records: [{id, label, icon}], raw: [{key, reason}]}`
	 */
	private static function _describeEntriesForForm(mixed $entries, array $refs, bool $has_prefixes) : array {
		$out = ['records' => [], 'raw' => []];

		foreach(array_keys(is_array($entries) ? $entries : []) as $key) {
			$key = strval($key);
			$name = explode('@', $key, 2)[0];

			if($has_prefixes && in_array(explode('/', $name, 2)[0], \Cerb\AutomationBuilder\Node\LlmAgentNode::TOOL_RESERVED_PREFIXES, true)) {
				$out['raw'][] = ['key' => $key, 'reason' => 'reserved'];
				continue;
			}

			if(!($ref = $refs[$name] ?? null)) {
				$out['raw'][] = ['key' => $key, 'reason' => 'missing'];
				continue;
			}

			$out['records'][] = [
				'id' => intval($ref['id'] ?? 0),
				'label' => strval($ref['label'] ?? $name),
				'icon' => strval($ref['icon'] ?? ''),
			];
		}

		return $out;
	}

	/**
	 * Every agent that can be launched, optionally narrowed to one surface.
	 *
	 * An agent is a worker, so "available" is the worker's own state: `is_ai` and not deactivated. Ordered by
	 * display name, which is the only stable order there is until agents earn a priority of their own.
	 *
	 * @return array `[{worker: Model_Worker, config: array}]` keyed by worker id
	 */
	static function getEnabledAgents(?string $surface = null) : array {
		$agents = [];

		$workers = \DAO_Worker::getAllActive();

		foreach(\DAO_Agent::getAll() as $worker_id => $row) {
			if(!($worker = $workers[$worker_id] ?? null))
				continue;

			if(!$worker->is_ai)
				continue;

			$config = $row['config'] ?? [];

			if(!is_null($surface) && !self::isEnabledOn($config, $surface))
				continue;

			$agents[$worker_id] = [
				'worker' => $worker,
				'config' => $config,
			];
		}

		uasort($agents, fn($a, $b) => strcasecmp($a['worker']->getName(), $b['worker']->getName()));

		return $agents;
	}

	/** Blank on either side contributes nothing; both sides present are joined as two paragraphs. */
	private static function _appendProse(mixed $base, mixed $addendum) : string {
		$parts = array_filter([trim(strval($base)), trim(strval($addendum))], fn($v) => '' !== $v);

		return implode("\n\n", $parts);
	}

	/** A blank override is "not set", not "set to nothing" -- clearing a default is done by editing the default. */
	private static function _replaceScalar(mixed $base, mixed $override) : string {
		$override = trim(strval($override));

		return ('' !== $override) ? $override : trim(strval($base));
	}
}
