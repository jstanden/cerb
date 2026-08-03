<?php
namespace Cerb\LLM\Providers;

use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmProvider;

/**
 * Qwen Cloud (Alibaba DashScope) — an OpenAI-compatible destination, so it subclasses OpenAI the same way
 * Docker, Gemini, and ZAi do.
 *
 * The extension id is `qwen`, not the vendor's own `qwen_cloud`: the id names the PROVIDER (where the call
 * goes), and Qwen *models* are also served by other routers. Keeping the id short and the label explicit
 * ("Qwen Cloud") keeps that distinction legible — a `qwen3.7-plus` reached through OpenRouter is
 * `provider: openai` + a `display:` override, not this.
 *
 * Note it also serves DeepSeek models, which is why getModelDefaults() matches both namespaces.
 */
class Qwen extends OpenAI {
	const ID = 'qwen';

	function getIcon() : string {
		return 'logo-qwen';
	}

	function getIconColor() : string {
		return '#615CED';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	function __construct(array $params, bool $validate=true) {
		// Skip OpenAI's constructor: it would default the endpoint to api.openai.com.
		Extension_DevblocksLlmProvider::__construct($params, false);

		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://dashscope-us.aliyuncs.com/compatible-mode/v1');

		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:qwen:model: is required.');
	}

	// DashScope validates the role against a fixed enum and 400s on OpenAI's `developer`:
	// "developer is not one of ['system', 'assistant', 'user', 'tool', 'function']".
	function getSystemPromptRole() : string {
		return 'system';
	}

	// The base url already ends in the OpenAI-compatible prefix (/compatible-mode/v1), so OpenAI's `/v1/...`
	// paths would double it. Same situation as Gemini's /v1beta/openai and ZAi's /api/paas/v4.
	function getChatCompletionEndpointUrl(string $base_url) : string {
		return $base_url . '/chat/completions';
	}

	function getEmbeddingsEndpointUrl(string $base_url) : string {
		return $base_url . '/embeddings';
	}

	function getChatModelsEndpointUrl(string $base_url) : string {
		return $base_url . '/models';
	}

	/**
	 * Hints, not an inventory — `qwen3.7-plus` first because it's the balanced default. `max` is the frontier
	 * tier, `plus`/`pro` balanced, `flash` the budget tier. The live list from `fetchChatModels()` (the
	 * editor's Refresh button) is authoritative; free text means a new id works the day it ships.
	 */
	function getChatModels() : array {
		return [
			'qwen3.7-plus',
			'qwen3.7-max',
			'qwen3.7-flash',
			'deepseek-v4-pro',
			'deepseek-v4-flash',
		];
	}

	function getModelDefaults(string $model) : array {
		// Both namespaces are served here; anything else came from elsewhere and gets no defaults from us.
		if(!str_starts_with($model, 'qwen') && !str_starts_with($model, 'deepseek-'))
			return [];

		// Every model on this platform is 1M-context, thinking-capable, and tool-capable — and NONE of them
		// take image input. `vision => false` is asserted, not guessed: a wrong `true` silently enables image
		// paste in the composer for a model that then rejects the request.
		return [
			'vision' => false,
			'context_window' => 1000000,
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
				[
					'caption' => 'effort:',
					'snippet' => "effort: \${1:medium}",
					'docHTML' => '<b>effort:</b> Reasoning effort, passed through as the OpenAI-compatible <code>reasoning_effort</code>. Every model here is thinking-capable. Validated by the provider, not here.',
				],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://dashscope-us.aliyuncs.com/compatible-mode/v1'],
				'effort:' => ['none', 'minimal', 'low', 'medium', 'high'],
			],
		];
	}
}
