<?php
namespace Cerb\LLM\Providers;

use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmProvider;

/**
 * OpenRouter -- one endpoint, one key, and one bill in front of nearly every frontier model.
 *
 * It speaks the OpenAI chat-completions wire format, so it subclasses OpenAI the same way Groq, z.ai, and
 * Qwen Cloud do. What earns it an extension rather than a hand-typed `provider: openai` endpoint is the
 * MODEL CATALOG: `/v1/models` reports `context_length` and `architecture.input_modalities` per model,
 * so the agent model editor's fill-on-select gets a real context window and a correct vision flag for
 * every model on the service. That's richer than what the hosted vendors publish about themselves --
 * AWS Bedrock's catalog, for one, reports no context window at all.
 *
 * Fronting many vendors is not a reason to stay generic: Bedrock does too, and it's first class. The line
 * that matters is whether the endpoint and the brand can be KNOWN. OpenRouter has exactly one of each.
 * The `display:` override remains the answer for endpoints that have neither -- llama.cpp, LM Studio, vLLM
 * -- where the address is wherever you started the server and the brand says nothing about the model.
 *
 * Model ids are namespaced `vendor/model` (`anthropic/claude-sonnet-5`, `openai/gpt-5.6-terra`). That
 * prefix is load-bearing here: it's what routes the reasoning guardrail below, and it's why a model
 * reached through this provider still paints the OpenRouter mark unless a `display:` block overrides it
 * -- getIcon() has no model argument. Showing the route the tokens (and the bill) actually took is the
 * more useful default; `display:` is there when the underlying vendor is what you'd rather see.
 */
class OpenRouter extends OpenAI {
	const ID = 'openrouter';

	// Live capabilities from the catalog, keyed by model id. Filled as a side effect of
	// _parseChatModelsResponse() and read back by getModelDefaults() for the editor.
	private array $_model_meta = [];

	function getIcon() : string {
		return 'logo-openrouter';
	}

	function getIconColor() : string {
		return '#6467F2';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	function __construct(array $params, bool $validate=true) {
		// Skip OpenAI's constructor: it would default the endpoint to api.openai.com.
		Extension_DevblocksLlmProvider::__construct($params, false);

		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://openrouter.ai/api');

		if($validate && !$this->getParam('authentication'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:openrouter:authentication: is required.');

		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:openrouter:model: is required.');
	}

	// OpenAI's `developer` role is an OpenAI-ism; the compatible endpoints validate against a fixed enum.
	function getSystemPromptRole() : string {
		return 'system';
	}

	// No endpoint overrides: `api_endpoint_url` is the base BELOW the spec version, and the inherited
	// methods append `/v1/chat/completions` and `/v1/models` themselves -- the same arrangement as Groq's
	// `https://api.groq.com/openai`. (Gemini, z.ai, and Qwen Cloud override instead because their base url
	// already ends in a vendor-specific compatibility prefix that the `/v1/...` paths would double.)

	/**
	 * No embeddings endpoint here, so the capability inherited from OpenAI has to be declared away.
	 * Structural `instanceof Embedding` is the wrong answer for a subclass that shares a dialect but not a
	 * product: without this, OpenRouter would be offered in `llm.embed:` autocomplete and then 404 at run
	 * time, which is a worse failure than not being offered at all. Same reasoning as Groq.
	 */
	function supportsEmbeddings() : bool {
		return false;
	}

	/**
	 * `effort:` -> `reasoning_effort` is inherited from OpenAI ON PURPOSE, unlike Groq and Together which
	 * blank it. OpenRouter accepts the OpenAI-compatible key and normalizes it onto its own unified
	 * reasoning parameter for whichever upstream ends up serving the request.
	 *
	 * The inherited guardrail is what needs narrowing. `_applyToolReasoningGuardrail()` exists because
	 * OpenAI's own /v1/chat/completions refuses function tools on a reasoning turn for the gpt-5.x family;
	 * that limitation belongs to OpenAI's endpoint, not to Anthropic's or Google's. Left alone it would
	 * also misfire twice over: its `^gpt-(\d+)` probe can't match a NAMESPACED id, so every model here --
	 * Claude and Gemini included -- would take the else branch and have the author's `effort:` silently
	 * stripped on any tool-using turn.
	 *
	 * So: apply it only to `openai/` models, and hand the parent the BARE id so its version probe works.
	 */
	protected function _applyToolReasoningGuardrail(array $params, array $tools, string $model) : array {
		if(!str_starts_with(DevblocksPlatform::strLower(trim($model)), 'openai/'))
			return $params;

		return parent::_applyToolReasoningGuardrail($params, $tools, $this->_getBareModelId($model));
	}

	// `anthropic/claude-sonnet-5` -> `claude-sonnet-5`. Only the LAST segment is dropped-to, since a few
	// ids carry a routing suffix (`:free`, `:nitro`) that the version probes don't care about either.
	private function _getBareModelId(string $model) : string {
		$model = trim($model);

		if(false !== ($pos = strpos($model, '/')))
			$model = substr($model, $pos + 1);

		return $model;
	}

	/**
	 * The catalog, and the reason this provider exists.
	 *
	 * Side effect by design (the Bedrock pattern): this fills `_model_meta` for every chat model it sees,
	 * and getModelDefaults() reads the capabilities back out for the editor's fill-on-select.
	 *
	 * `context_length` is the ROUTER's ceiling for the model. `top_provider.context_length` can differ when
	 * a specific upstream is pinned, but the router is what we're actually talking to, so that's the honest
	 * number to pre-fill.
	 */
	protected function _parseChatModelsResponse(array $response_json) : array {
		$this->_model_meta = [];
		$models = [];

		foreach($response_json['data'] ?? [] as $row) {
			if(!is_array($row))
				continue;

			if('' === ($model_id = strval($row['id'] ?? '')))
				continue;

			$architecture = is_array($row['architecture'] ?? null) ? $row['architecture'] : [];

			$lower = fn($values) => array_map(
				fn($v) => DevblocksPlatform::strLower(strval($v)),
				is_array($values) ? $values : []
			);

			$input_modalities = $lower($architecture['input_modalities'] ?? []);
			$output_modalities = $lower($architecture['output_modalities'] ?? []);

			// Chat is text in, text out. This drops the image-generation and transcription models that
			// share the catalog. An entry that declares NO modalities is kept -- absent metadata is not
			// evidence against it, and a free-text model id has to keep working regardless.
			if($input_modalities && !in_array('text', $input_modalities, true))
				continue;

			if($output_modalities && !in_array('text', $output_modalities, true))
				continue;

			$meta = [
				'vision' => in_array('image', $input_modalities, true),
			];

			// Absent rather than zero when unknown: the editor keeps whatever's typed for a missing key,
			// but a 0 would overwrite a hand-entered context window with nonsense.
			if(($context_length = intval($row['context_length'] ?? 0)) > 0)
				$meta['context_window'] = $context_length;

			// `name` is the vendor-qualified label ("Anthropic: Claude Sonnet 5"), which is exactly what
			// the picker wants next to a namespaced id. `description` is prose paragraphs -- too long.
			if('' !== ($name = trim(strval($row['name'] ?? ''))))
				$meta['description'] = $name;

			$this->_model_meta[$model_id] = $meta;
			$models[] = $model_id;
		}

		return $models;
	}

	function getModelDefaults(string $model) : array {
		return $this->_model_meta[$model] ?? [];
	}

	/**
	 * A deliberately SHORT list of hints, not an inventory -- the authoritative list is the live one from
	 * fetchChatModels() (the editor's Refresh button), which is the whole point of this provider. This is
	 * only what shows before anyone has connected. Free text either way: a new id works the day it ships.
	 */
	function getChatModels() : array {
		return [
			'anthropic/claude-sonnet-5',
			'anthropic/claude-opus-4-8',
			'openai/gpt-5.6-terra',
			'google/gemini-3-pro-preview',
			'deepseek/deepseek-v4-pro',
		];
	}

	/**
	 * Caching is per UPSTREAM here -- an Anthropic model routed through OpenRouter caches on Anthropic's
	 * terms, an OpenAI one on OpenAI's. With no single wall-clock TTL that holds across the catalog, a
	 * countdown ring would be a guess dressed as a fact. Same answer as Groq and Together.
	 */
	function getCacheHintSeconds(array $params) : ?int {
		return null;
	}

	// OpenRouter fronts every vendor at once, so it advertises the union and normalizes per upstream. Spelled
	// out rather than inherited even though it currently matches OpenAI's: this list tracks what the ROUTER
	// accepts across all its upstreams, which has no reason to follow OpenAI's own model roadmap.
	function getEffortLevels() : array {
		return ['none', 'minimal', 'low', 'medium', 'high', 'xhigh', 'max'];
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
					'docHTML' => '<b>effort:</b> Reasoning effort, passed through as the OpenAI-compatible <code>reasoning_effort</code> and normalized by OpenRouter for the upstream model. Which levels are accepted varies by model; validated by the provider, not here.',
				],
				['caption' => 'stream@bool:', 'snippet' => 'stream@bool: no', 'docHTML' => '<b>stream@bool:</b>Stream the response (default <code>yes</code>). Streaming replaces the request timeout with an inactivity cutoff, so a long turn is not killed partway through, and it lets a running turn be stopped.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://openrouter.ai/api'],
				'effort:' => $this->getEffortLevels(),
			],
		];
	}
}
