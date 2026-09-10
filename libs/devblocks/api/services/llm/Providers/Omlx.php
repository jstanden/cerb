<?php
namespace Cerb\LLM\Providers;

use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmProvider;

class Omlx extends OpenAI {
	const ID = 'omlx';

	function getIcon() : string {
		return 'logo-omlx';
	}

	function getIconColor() : string {
		return '#000000';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	function __construct(array $params, bool $validate=true) {
		Extension_DevblocksLlmProvider::__construct($params, false);

		// oMLX runs on the Mac, and Cerb usually runs in a container next to it -- where `127.0.0.1` is the
		// container itself and reaches nothing. The host alias is the default so the common deployment works
		// unconfigured; `127.0.0.1:8000` is offered alongside it for a Cerb running on the Mac directly.
		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'http://host.docker.internal:8000');

		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:omlx:model: is required.');
	}

	// OpenAI's `developer` role is an OpenAI-ism; a local chat template only knows `system`.
	function getSystemPromptRole() : string {
		return 'system';
	}

	// OpenAI's tool-vs-reasoning refusal is its own endpoint's rule; a local server never had it.
	protected function _appliesToolReasoningGuardrail(string $model) : bool {
		return false;
	}

	/**
	 * oMLX forwards `chat_template_kwargs` to the model's Jinja template but does NOT map the standard
	 * top-level `reasoning_effort` into it. Sent OpenAI's way an effort reaches the server, is dropped on
	 * the floor, and nothing says so -- measured: an invalid level returned 200 and no level was
	 * distinguishable from sending none. Putting it where the template reads it is the reason this is its
	 * own provider rather than an `openai` endpoint override.
	 *
	 * The template then validates: Qwen3 takes low|medium|xhigh and FAILS the request on anything else.
	 * A level that errors beats one that vanishes.
	 *
	 * `extra_body:` still merges last, so an author whose server wants a different key keeps the override.
	 */
	protected function _getReasoningParams() : array {
		if(!($effort = $this->getEffort()))
			return [];

		return ['chat_template_kwargs' => ['reasoning_effort' => $effort]];
	}

	// The transcript's Thinking chip reports what SHIPPED. The parent reads a top-level `reasoning_effort`,
	// which is never where this provider puts it -- so without this every reasoning turn reports none.
	function getEffectiveEffort(bool $has_tools) : ?string {
		$params = array_replace_recursive($this->_getReasoningParams(), $this->_getExtraBodyParams());

		return $params['chat_template_kwargs']['reasoning_effort']
			?? $params['reasoning_effort']
			?? null;
	}

	/**
	 * A local runner has no catalog Cerb can know, so nothing is asserted about what it serves.
	 *
	 * Without this the parent's table applies: it keys on a `gpt-` prefix and never reads the endpoint, so
	 * a locally served `gpt-oss-*` would fill OpenAI's hosted vision flag and 400K window -- figures about
	 * somebody else's hosting, arriving as if they had been measured here. A wrong vision flag is the one
	 * that shows: it offers workers an image attachment the model then rejects.
	 */
	function getModelDefaults(string $model) : array {
		return [];
	}

	// The union across the reasoning models commonly served here; which of them a given model honors is its
	// chat template's call. Qwen3 is the reason `xhigh` is in the list and `high` is not enough on its own.
	function getEffortLevels() : array {
		return ['low', 'medium', 'high', 'xhigh'];
	}

	// A deliberately short list of hints, not an inventory -- what a given machine has converted is whatever
	// its operator pulled, which is what the editor's Refresh button is for.
	function getChatModels() : array {
		return [
			'mlx-community/Qwen3.8-27B-8bit',
			'mlx-community/Qwen3-30B-A3B-8bit',
			'mlx-community/gpt-oss-20b-MXFP4-Q8',
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
				['caption' => 'stream@bool:', 'snippet' => 'stream@bool: no', 'docHTML' => '<b>stream@bool:</b>Stream the response (default <code>yes</code>). Streaming replaces the request timeout with an inactivity cutoff, so a long turn is not killed partway through, and it lets a running turn be stopped.'],
				['caption' => 'effort:', 'snippet' => 'effort: ${1:medium}', 'docHTML' => '<b>effort:</b> Reasoning effort. Sent as <code>chat_template_kwargs.reasoning_effort</code>, which is where oMLX reads it -- the standard top-level <code>reasoning_effort</code> is ignored here. Your model\'s chat template validates the level: Qwen3 takes <code>low|medium|xhigh</code> and errors on anything else.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['http://host.docker.internal:8000', 'http://127.0.0.1:8000'],
				'effort:' => $this->getEffortLevels(),
			],
		];
	}

	function getEmbeddingKataAutocomplete() : array {
		return [
			'keys' => [
				'api_endpoint_url:',
				'authentication:',
				'model:',
			],
			'values' => [
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['http://host.docker.internal:8000', 'http://127.0.0.1:8000'],
			],
		];
	}
}
