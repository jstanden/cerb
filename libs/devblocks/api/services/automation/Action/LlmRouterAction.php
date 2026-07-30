<?php
namespace Cerb\AutomationBuilder\Action;

use DAO_AgentModelRouter;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

/**
 * `llm.router:` — resolve an agent model router to the `models:` map that `llm.agent:`, `llm.chat:`, and
 * `agentPrompt` already consume.
 *
 * This command exists for when the list is needed **as data** — to filter it, round-robin it, feed two
 * commands from one resolution, or inspect what came back. To simply USE the models, name an `agent:` on the
 * command (or say nothing and get the default router); you don't need this.
 *
 *     llm.router:
 *       inputs:
 *         router: fast          # optional -- omit for the default router
 *       output: routed
 *
 *     llm.agent:
 *       inputs:
 *         model@key: routed:models
 *
 * Bare `llm.router:` is the usual form; `llm.router/<name>:` disambiguates two calls in the same block (KATA
 * keys are unique among siblings), the same way `record.search/ticket:` does.
 *
 * ⚠ Don't hardcode a `router:` in code that ships to other environments -- the name is yours, not theirs.
 * That's the same portability problem as hardcoding `models:`, one level up. Portable automations name an
 * AGENT and let it carry the router.
 */
class LlmRouterAction extends AbstractAction {
	const ID = 'llm.router';

	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, ?string &$error=null) : string|false {
		$validation = DevblocksPlatform::services()->validation();

		$policy = $automation->getPolicy();

		$params = $automation->getParams($this->node, $dict);
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? '';

		try {
			// Params validation

			$validation->addField('inputs', 'inputs:')
				->array()
			;

			$validation->addField('output', 'output:')
				->string()
				->setRequired(true)
			;

			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);

			$validation->reset();

			// Inputs validation

			// Optional on purpose: omitting it is the common case and resolves the DEFAULT router, so a portable
			// automation never has to name one.
			$validation->addField('router', 'router:')
				->string()
				->setMaxLength(255)
			;

			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);

			// Policy

			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $inputs,
				'output' => $output,
			]);

			if(!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = "The automation policy does not allow the `llm.router:` command.";
				throw new Exception_DevblocksAutomationError($error);
			}

			$router = $this->_resolveRouter(strval($inputs['router'] ?? ''));

			// The dict is passed through so a per-model `disabled@bool: {{...}}` in the router's document is
			// evaluated against the calling automation's state, not left as a literal.
			$models = $router->getModels($dict, $models_error);

			if(!$models) {
				throw new Exception_DevblocksAutomationError(sprintf(
					"The `%s` model router resolved no usable models.%s",
					$router->name,
					$models_error ? ' ' . $models_error : ' Every entry is missing or its model record is disabled.'
				));
			}

			$dict->set($output, [
				'router' => $router->name,
				'models' => $models,
			]);

		} catch (Exception_DevblocksAutomationError $e) {
			$error = sprintf("[%s] %s", $this->node->getId(), $e->getMessage());

			if(null != ($event_error = $this->node->getChild($this->node->getId() . ':on_error'))) {
				if($output) {
					$dict->set($output, [
						'error' => $error,
					]);
				}

				return $event_error->getId();
			}

			return false;
		}

		if(null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
			return $event_success->getId();
		}

		return $this->node->getParent()->getId();
	}

	/**
	 * `cerb:agent_model_router:<name>` | `<name>` | '' (the default).
	 *
	 * Parsed as a string rather than through parseURI(), for the same reason `LlmAgentNode::_resolveAgentWorker()`
	 * does: no context-registry dependency, so it stays pure and headless-testable.
	 *
	 * @throws Exception_DevblocksAutomationError when nothing resolves -- never silently falls back to the
	 *         default, because running the wrong models is worse than not running.
	 */
	private function _resolveRouter(string $ref) : \Model_AgentModelRouter {
		$ref = trim($ref);

		if('' === $ref) {
			if(!($router = DAO_AgentModelRouter::getDefault()))
				throw new Exception_DevblocksAutomationError(
					"No default agent model router is configured. Create one, or name a `router:`."
				);

			return $router;
		}

		if(str_starts_with($ref, 'cerb:')) {
			$parts = explode(':', $ref);

			if(3 !== count($parts) || 'agent_model_router' !== ($parts[1] ?? ''))
				throw new Exception_DevblocksAutomationError(sprintf("`router: %s` isn't an agent model router URI.", $ref));

			$ref = strval($parts[2]);
		}

		if(!($router = DAO_AgentModelRouter::getByName($ref)))
			throw new Exception_DevblocksAutomationError(sprintf("`router: %s` doesn't match an agent model router.", $ref));

		// A disabled router is an explicit "don't use this" -- resolving it anyway would make the flag meaningless.
		if($router->is_disabled)
			throw new Exception_DevblocksAutomationError(sprintf("The `%s` agent model router is disabled.", $router->name));

		return $router;
	}
}
