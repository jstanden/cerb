<?php
namespace Cerb\LLM\Providers;

use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmProvider;

class Docker extends OpenAI {
	const ID = 'docker';

	function getIcon() : string {
		return 'logo-docker';
	}

	function getIconColor() : string {
		return '#2496ED';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	function __construct(array $params, bool $validate=true) {
		Extension_DevblocksLlmProvider::__construct($params, false);
		
		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'http://model-runner.docker.internal/engines');
		
		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:docker:model: is required.');
	}

	// OpenAI's `developer` role is an OpenAI-ism; a local runner's chat template only knows `system`.
	function getSystemPromptRole() : string {
		return 'system';
	}

	// OpenAI's tool-vs-reasoning refusal is its own endpoint's rule; a local runner never had it, and no
	// `ai/...` model id matches the `^gpt-(\d+)` probe it keys on anyway.
	protected function _appliesToolReasoningGuardrail(string $model) : bool {
		return false;
	}

	// Whatever the locally-pulled model was trained to read. The runner forwards the param; the graded scale
	// is the common vocabulary among models that use one at all.
	function getEffortLevels() : array {
		return ['low', 'medium', 'high'];
	}

	function getChatModels() : array {
		return [
			'ai/llama3.2',
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
				['caption' => 'effort:', 'snippet' => 'effort: ${1:medium}', 'docHTML' => '<b>effort:</b> Reasoning effort, passed through as the OpenAI-compatible <code>reasoning_effort</code>. Whether the locally-run model reads it depends on the model. Validated by the runner, not here.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['http://model-runner.docker.internal/engines'],
				'effort:' => $this->getEffortLevels(),
			],
		];
	}

	function getEmbeddingModels() : array {
		return [
			'ai/mxbai-embed-large',
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
				'model:' => $this->getEmbeddingModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['http://model-runner.docker.internal/engines'],
			],
		];
	}
}