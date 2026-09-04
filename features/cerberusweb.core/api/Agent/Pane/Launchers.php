<?php
namespace Cerb\Agent\Pane;

use Cerb\Agent\Config;

/**
 * The launcher tiles an agent pane offers -- one per agent enabled on that surface, derived from the
 * agent record's `components:` block.
 */
class Launchers {
	/** Posted as `caller.name`. First segment of every stored `resume_scope` -- renaming it orphans every parked conversation. */
	const CALLER_NAME = 'agent.pane';

	/** The chat an agent runs when its record names none of its own. */
	const DEFAULT_AUTOMATION_URI = 'cerb:automation:cerb.ai.agent.chat';

	/** The global command bar surface. */
	const SURFACE_COMMANDBAR = 'commandbar';

	/**
	 * RESERVED. Consumed by `PageSection_ProfilesAutomation::_startBotInteractionAsAutomation()` before a
	 * script's `inputs:` are validated -- an automation that declares it fails with "Unknown inputs: agent".
	 */
	const INPUT_AGENT = 'agent';

	/**
	 * The launcher KATA for one surface.
	 *
	 * Emitted through `kata()->emit()`: an agent's name is a person's free text, and a `#`, newline or
	 * leading sigil would otherwise produce broken KATA.
	 */
	static function getKata(string $surface) : string {
		if('' === trim($surface))
			return '';

		$surface_tagline = trim(strval((Components::get($surface) ?? [])['tagline'] ?? ''));

		$tree = [];

		foreach(Config::getEnabledAgents($surface) as $worker_id => $agent) {
			$worker = $agent['worker'];
			$config = Config::resolve($agent['config'], $surface);

			$name = $worker->getName();

			// NOT the worker's `title`: that column is a job title ("System Administrator"), which names a
			// role rather than what the agent is for.
			$description = trim(strval($config[Config::KEY_DESCRIPTION] ?? '')) ?: $surface_tagline;

			$item = [
				'label' => $name,
				'uri' => trim(strval($config['automation'] ?? '')) ?: self::DEFAULT_AUTOMATION_URI,
				'icon' => 'bot',
				'image' => $worker->getImageUrl(),
				// Both: hosts render `description` inline, but a raw toolbar `<li>` only reads `tooltip`.
				'description' => $description,
				'tooltip' => $description,
				'inputs' => [
					self::INPUT_AGENT => sprintf('cerb:worker:%d', $worker_id),
				],
			];

			// The command bar sits among every other shortcut in Cerb, not among other agents, so a row is
			// labeled with the `@handle` a person would type to reach this agent anywhere else.
			if(self::SURFACE_COMMANDBAR === $surface) {
				if('' !== ($mention = trim(strval($worker->at_mention_name ?? ''))))
					$item['label'] = '@' . $mention;
			}

			$tree[sprintf('interaction/agent_%d', $worker_id)] = array_filter(
				$item,
				fn($v) => is_array($v) ? (bool) $v : ('' !== trim(strval($v)))
			);
		}

		return $tree ? \DevblocksPlatform::services()->kata()->emit($tree) : '';
	}

	/**
	 * The launcher KATA for one surface, parsed into the toolbar array every host renders. `parse()` applies
	 * `cerb:` URI reduction and `enforceCallerPolicy()`, so an agent whose automation refuses the
	 * `agent.pane` caller comes back flagged hidden.
	 */
	static function parse(string $surface, \DevblocksDictionaryDelegate $dict) : array {
		if('' === ($kata = self::getKata($surface)))
			return [];

		$toolbar = \DevblocksPlatform::services()->ui()->toolbar()->parse($kata, $dict);

		return is_array($toolbar) ? $toolbar : [];
	}

	/**
	 * The rendered `<ul class="cerb-ui-toolbar">` a `CerbUI.AgentPane` host hands to its `toolbarHtml` option.
	 * Empty markup is a valid answer, not a failure: the pane hides its own toggle when no tile resolves.
	 */
	static function fetch(string $surface, \DevblocksDictionaryDelegate $dict) : string {
		if(!($toolbar = self::parse($surface, $dict)))
			return '';

		return strval(\DevblocksPlatform::services()->ui()->toolbar()->fetch($toolbar));
	}

	/**
	 * The state dict a host builds to render its launchers, read by `enforceCallerPolicy()` and any
	 * `{{worker_*}}` in an agent's automation policy.
	 *
	 * `caller_name` must be the caller NAME, not an extension id, or policy enforcement hides every item.
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
