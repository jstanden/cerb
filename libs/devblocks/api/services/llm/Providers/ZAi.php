<?php
namespace Cerb\LLM\Providers;

use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmProvider;

/**
 * z.ai (Zhipu / GLM) — an OpenAI-compatible destination, so it subclasses OpenAI the same way Docker and
 * Gemini do. It earns its own extension by knowing three things the operator would otherwise type and get
 * wrong: the endpoint, the model namespace, and the brand.
 *
 * Without it, a GLM model has to be configured as `provider: openai` with a hand-typed endpoint, and every
 * icon in the product paints the OpenAI logo — which is the exact problem the `display:` override exists to
 * paper over. A first-class provider fixes it at the source; keep `display:` for endpoints that genuinely
 * can't be known (llama.cpp, LM Studio, vLLM, and gateways like OpenRouter that front many vendors).
 */
class ZAi extends OpenAI {
	const ID = 'zai';

	function getIcon() : string {
		return 'logo-z-ai';
	}

	function getIconColor() : string {
		return '#3B6FF2';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	function __construct(array $params, bool $validate=true) {
		// Skip OpenAI's constructor: it would default the endpoint to api.openai.com.
		Extension_DevblocksLlmProvider::__construct($params, false);

		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api.z.ai/api/paas/v4');

		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:zai:model: is required.');
	}

	// OpenAI's `developer` role is an OpenAI-ism; compatible endpoints only know `system`.
	function getSystemPromptRole() : string {
		return 'system';
	}

	// The base url already ends in the OpenAI-compatible prefix (/api/paas/v4), so the standard `/v1/...`
	// paths don't apply — same situation as Gemini's /v1beta/openai.
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
	 * A deliberately SHORT list of hints, not an inventory. z.ai ships models faster than we release, so the
	 * authoritative list is the live one from `fetchChatModels()` (the editor's Refresh button) — this is only
	 * what shows before anyone has connected. Free text either way: a new model id works the day it ships.
	 */
	function getChatModels() : array {
		return [
			'glm-4.6',
		];
	}

	function getModelDefaults(string $model) : array {
		if(!str_starts_with($model, 'glm-'))
			return [];

		// GLM-4.6 is a 200K text model. A TRAILING `v` is the vision line (glm-4.5v, glm-4.1v) — match the
		// suffix, not a substring, or a name that merely contains a `v` reads as vision. Everything else is
		// text-only, so vision is opt-in rather than assumed: a wrong `true` silently enables image paste in
		// the composer for a model that then 400s.
		return [
			'vision' => str_ends_with($model, 'v'),
			'context_window' => 200000,
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
					'docHTML' => '<b>effort:</b> Reasoning effort, passed through as the OpenAI-compatible <code>reasoning_effort</code>. Validated by the provider, not here.',
				],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://api.z.ai/api/paas/v4'],
				'effort:' => ['none', 'minimal', 'low', 'medium', 'high'],
			],
		];
	}
}
