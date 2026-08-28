<?php
namespace Cerb\AutomationBuilder\Action;

use Cerb\LLM\MemoryStore\NoHistory;
use Cerb\LLM\Providers\Interfaces\Chat;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Exception_DevblocksLlmApiError;
use Model_Automation;

class LlmChatAction extends AbstractAction {
	const ID = 'llm.chat';
	
	private array $_inputs = [];
	private string $_output = '';
	private DevblocksDictionaryDelegate $_dict;
	private array $_node_memory = [];
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, ?string &$error=null) : string|false {
		$validation = DevblocksPlatform::services()->validation();
		
		$policy = $automation->getPolicy();
		
		$this->_node_memory =& $node_memory;
		$this->_dict = $dict;
		
		$params = $automation->getParams($this->node, $this->_dict);
		$this->_inputs = $params['inputs'] ?? [];
		$this->_output = $params['output'] ?? '';
		
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
			
			$validation->addField('llm', 'llm:')
				->array();

			// `model: <agent_model name>: <overrides>` — reference a first-class model record instead of
			// hand-authoring `llm:`. Multiple entries are a fallback list; the first enabled record wins.
			$validation->addField('model', 'model:')
				->array();

			$validation->addField('messages', 'messages:')
				->array()
				->setRequired(true);
			
			$validation->addField('system_prompt', 'system_prompt:')
				->string()
				->setMaxLength(200_000)
			;
			
			if(false === ($validation->validateAll($this->_inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Policy
			
			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $this->_inputs,
				'output' => $this->_output,
			]);
			
			if (!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = "The automation policy does not allow the `llm.chat:` command.";
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$llm_provider = $this->_getLlmProvider();
			
			if(!($llm_provider instanceof Chat)) {
				list($llm_id, ) = $this->_resolveLlmBlock();
				$error = sprintf('LLM provider does not support chat completions: %s', $llm_id);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			if(!$this->_activateLLM($error))
				return false;
			
		} catch (Exception_DevblocksAutomationError $e) {
			$message = $e->getMessage();

			// A provider failure carries the provider's -- or Guzzle's -- own text, which routinely names the
			// endpoint URL, an api key riding a query string, request ids, and echoed payload fragments. This
			// lands in `<output>.error`, which an author is free to print anywhere -- including a website
			// interaction an anonymous visitor is looking at. Log it for an admin; hand back only the class.
			if($e instanceof Exception_DevblocksLlmApiError) {
				DevblocksPlatform::logError(sprintf(
					'[llm.chat] node=%s provider request failed (status=%d): %s',
					$this->node->getId(),
					$e->statusCode,
					$message
				));

				$message = \_DevblocksLlmService::formatTurnFailure($e->statusCode, $e->retryAfter);
			}

			$error = sprintf("[%s] %s", $this->node->getId(), $message);
			
			if (null != ($event_error = $this->node->getChild($this->node->getId() . ':on_error'))) {
				if ($this->_output) {
					$dict->set($this->_output, [
						'error' => $error,
					]);
				}
				
				return $event_error->getId();
			}
			
			return false;
		}
		
		if (null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
	
	/**
	 * The effective `[provider_id, params]` to run: `llm:` (manual) wins and is used as-is; a `model:`
	 * reference to an `agent_model` record is consulted only when `llm:` is omitted. Throws when neither
	 * resolves (e.g. a `model:` list with no enabled record).
	 */
	private function _resolveLlmBlock() : array {
		$llm = DevblocksPlatform::services()->llm();

		if(is_array($this->_inputs['llm'] ?? null) && $this->_inputs['llm']) {
			$provider_id = strval(array_key_first($this->_inputs['llm']));
			$params = is_array($this->_inputs['llm'][$provider_id] ?? null) ? $this->_inputs['llm'][$provider_id] : [];
			return [$provider_id, $params];
		}

		$error = null;

		if(($resolved = $llm->resolveModelInput($this->_inputs['model'] ?? null, $error)))
			return $resolved;

		// An explicit `model:` that matched nothing is a MISTAKE, not an invitation to substitute something
		// else -- running a model the author didn't ask for is worse than not running.
		if($error)
			throw new Exception_DevblocksAutomationError($error);

		// `llm.chat` has no session, so this resolves per CALL rather than being stamped once. To narrow the pool,
		// resolve it with `llm.router:` and pass the result in as `model:`.
		$pool = \DAO_AgentModel::mapNamesToModels(\DAO_AgentModel::resolveQueryModelNames(''));

		if(($resolved = $llm->resolveModelInput($pool, $error)))
			return $resolved;

		throw new Exception_DevblocksAutomationError(
			"`llm.chat` has no models. Name an `llm:` block or a `model:` reference, or make at least one agent model available."
		);
	}

	private function _getLlmProvider() : ?\Extension_DevblocksLlmProvider {
		list($provider_id, $params) = $this->_resolveLlmBlock();
		return DevblocksPlatform::services()->llm()->getProvider($provider_id, $params);
	}
	
	/**
	 * @param string|null $error
	 * @return bool
	 */
	private function _activateLLM(?string &$error=null) : bool {
		$llm_provider = $this->_getLlmProvider(); /* @var $llm_provider Chat */
		$llm = DevblocksPlatform::services()->llm();

		// Resolve any per-message `images:` (resource uris → base64). Throws if the model lacks vision; the
		// caller's catch turns that into the command's `on_error`.
		$messages = [];
		foreach($this->_inputs['messages'] ?? [] as $message) {
			$messages[] = is_array($message) ? $llm->normalizeMessageImages($message, $llm_provider) : $message;
		}

		// LLM
		$llm_response = $llm_provider->chatCompletion(
			$messages,
			$this->_inputs['system_prompt'] ?? '',
			[],
			new NoHistory()
		);
		
		// Return an abstract list of messages
		$this->_dict->set($this->_output, [
			'messages' => $llm_response->getMessages(),
		]);
		
		return true;
	}
}