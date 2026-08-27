<?php
namespace Cerb\Agent\Pane;

// NOT a bare `Config` -- inside this namespace that resolves to `Cerb\Agent\Pane\Config`, which doesn't exist.
use Cerb\Agent\Config;

/**
 * The launcher tiles an agent pane offers -- one per AGENT enabled on that surface.
 *
 * This replaces the `agent.pane` TOOLBAR. That toolbar's items were hand-authored rows naming an automation
 * and gated per host with `hidden@bool: {{ component != 'icon' }}`, which meant configuring an agent happened
 * in two unrelated places: the agent record said what it could do, and a toolbar section decided where it
 * showed up. Now the record's `components:` block is the only answer to "where does this agent appear", and
 * the tiles are derived from it.
 *
 * What that buys beyond one place to look:
 *
 *   - Every tile carries a real agent -- id, name, title, avatar -- so a launcher reads as `@cerb` rather than
 *     as the name of an automation, and the same identity paints the transcript and History.
 *   - `inputs: agent:` means ONE interaction serves every agent. Nothing in the script names an id or an
 *     `@mention`; it reads `agent_id` out of scope (see `_startBotInteractionAsAutomation()`).
 *   - Nothing arbitrary can appear here, which is what makes the list explainable.
 *
 * An environment that wants its own chat in a pane points an agent's `automation:` at its own script.
 *
 * ⚠ `CALLER_NAME` moved here from `Toolbar_AgentPane` and MUST NOT change: it's the first segment of every
 * stored `resume_scope` (`DAO_AutomationContinuation::resumeScopeFor()`), so renaming it orphans every parked
 * conversation.
 */
class Launchers {
	/** The `caller.name` the pane posts when it launches an interaction. Baked into stored `resume_scope`s. */
	const CALLER_NAME = 'agent.pane';

	/** The chat an agent runs when its record names none of its own. */
	const DEFAULT_AUTOMATION_URI = 'cerb:automation:cerb.ai.agent.chat';

	/**
	 * The launcher input naming the agent -- RESERVED, and consumed by
	 * `PageSection_ProfilesAutomation::_startBotInteractionAsAutomation()` before the script's `inputs:` are
	 * validated. An automation must NOT declare it: which agent is running belongs to the launcher, not to a
	 * script's configuration, and an undeclared input otherwise fails the run ("Unknown inputs: agent").
	 */
	const INPUT_AGENT = 'agent';

	/**
	 * The launcher KATA for one surface.
	 *
	 * Filtered in PHP rather than emitted with a `hidden@bool: {{ component != … }}` gate. Every caller knows
	 * its own surface, so there is nothing for a gate to decide -- and it sidesteps the trap that Twig's
	 * `is not` runs a TEST rather than an inequality, which is a silent wrong answer rather than an error.
	 *
	 * Built through `kata()->emit()` so a name carrying a `#`, a newline, or a leading sigil can't produce
	 * broken KATA -- an agent's name is a person's free text.
	 */
	static function getKata(string $surface) : string {
		if('' === trim($surface))
			return '';

		$tree = [];

		foreach(Config::getEnabledAgents($surface) as $worker_id => $agent) {
			$worker = $agent['worker'];
			$config = Config::resolve($agent['config'], $surface);

			$item = [
				'label' => $worker->getName(),
				'uri' => trim(strval($config['automation'] ?? '')) ?: self::DEFAULT_AUTOMATION_URI,
				'icon' => 'bot',
				// The agent's own picture, so the tile is the agent rather than a generic glyph. The command bar
				// already renders `image:`; the pane's own renderer passes it through as `data-image`.
				'image' => $worker->getImageUrl(),
				// Read as the tile's tooltip -- an agent's title is the one-line "what is this one for".
				'tooltip' => trim(strval($worker->title ?? '')),
				// What makes ONE interaction serve every agent: the script reads `agent_id` from scope instead
				// of naming an id or an `@mention`.
				'inputs' => [
					self::INPUT_AGENT => sprintf('cerb:worker:%d', $worker_id),
				],
			];

			$tree[sprintf('interaction/agent_%d', $worker_id)] = array_filter(
				$item,
				fn($v) => is_array($v) ? (bool) $v : ('' !== trim(strval($v)))
			);
		}

		return $tree ? \DevblocksPlatform::services()->kata()->emit($tree) : '';
	}

	/**
	 * The launcher KATA for one surface, parsed into the toolbar array every host renders.
	 *
	 * Through the same `ui()->toolbar()->parse()` the toolbar record used, so `cerb:` URI reduction and
	 * `enforceCallerPolicy()` behave exactly as before -- an agent whose automation refuses the `agent.pane`
	 * caller is still flagged hidden.
	 */
	static function parse(string $surface, \DevblocksDictionaryDelegate $dict) : array {
		if('' === ($kata = self::getKata($surface)))
			return [];

		$toolbar = \DevblocksPlatform::services()->ui()->toolbar()->parse($kata, $dict);

		return is_array($toolbar) ? $toolbar : [];
	}

	/**
	 * The rendered `<ul class="cerb-ui-toolbar">` a `CerbUI.AgentPane` host hands to its `toolbarHtml` option.
	 *
	 * Empty markup is a real answer -- the pane hides its own toggle when no tile resolves, which is what an
	 * install with no agents configured for this surface should see.
	 */
	static function fetch(string $surface, \DevblocksDictionaryDelegate $dict) : string {
		if(!($toolbar = self::parse($surface, $dict)))
			return '';

		return strval(\DevblocksPlatform::services()->ui()->toolbar()->fetch($toolbar));
	}

	/**
	 * The state dict a host builds to render its launchers: the surface plus the active worker, which is what
	 * `enforceCallerPolicy()` and any `{{worker_*}}` in an agent's automation policy read.
	 *
	 * `caller_name` must be the caller NAME the pane posts, not an extension id, or policy enforcement hides
	 * every item.
	 */
	static function newDict(string $surface, array $extra = []) : \DevblocksDictionaryDelegate {
		$active_worker = \CerberusApplication::getActiveWorker();

		$dict = \DevblocksDictionaryDelegate::instance(array_merge([
			'caller_name' => self::CALLER_NAME,
			'component' => $surface,
		], $extra));

		if($active_worker) {
			$dict->mergeKeys('worker_', \DevblocksDictionaryDelegate::getDictionaryFromModel($active_worker, \CerberusContexts::CONTEXT_WORKER));
		} else {
			$dict->set('worker__context', \CerberusContexts::CONTEXT_WORKER);
			$dict->set('worker_id', 0);
		}

		return $dict;
	}
}
