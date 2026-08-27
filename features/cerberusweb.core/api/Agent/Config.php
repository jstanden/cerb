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
 * translating and an author who has written one has written the other. `tools:` is the one narrowing: it takes
 * `automation/` references only, because a `tool/` is answered by an `on_tool:` branch and an agent record has
 * no script to put one in. See `_CerbApplication_KataSchemas::agent()`.
 *
 * The merge is: scalars REPLACE, collections MERGE, prose APPENDS.
 *
 *   system_prompt                 default, then the surface's addendum -- a persona plus what's different here
 *   models_query, automation      the surface's value wins when it isn't blank
 *   tools, mounts, commands,      deep union; the surface overrides only the leaves it names, so it can turn
 *   terminal                      one inherited tool off or re-point one mount without restating the entry
 *   disabled                      surface only -- never inherited from the defaults
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
		foreach([self::KEY_COMMANDS, self::KEY_MOUNTS, self::KEY_TERMINAL, self::KEY_TOOLS] as $key) {
			$resolved[$key] = array_replace_recursive(
				is_array($config[$key] ?? null) ? $config[$key] : [],
				is_array($override[$key] ?? null) ? $override[$key] : []
			);
		}

		return $resolved;
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
