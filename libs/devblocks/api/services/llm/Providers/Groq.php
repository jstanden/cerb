<?php
namespace Cerb\LLM\Providers;

use DevblocksLlmChatResponse_Tool;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;

/**
 * Groq speaks the OpenAI chat-completions wire format, so it inherits the whole request/response path
 * — including SSE streaming, `request_timeout`, `Exception_DevblocksLlmApiError` (which is what lets
 * the queue tell a retryable failure from a permanent one) and neutral `images:` expansion, none of
 * which its hand-copied chatCompletion() had.
 */
class Groq extends OpenAI {
	const ID = 'groq';

	function getIcon() : string {
		return 'logo-groq';
	}

	function getIconColor() : string {
		return '#F55036';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		// Skip OpenAI's constructor, which would default the endpoint to api.openai.com.
		Extension_DevblocksLlmProvider::__construct($params, false);

		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api.groq.com/openai');

		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:groq:model: is required.');
	}

	/**
	 * Groq has no embeddings endpoint, so the capability it would otherwise inherit from OpenAI has to
	 * be declared away. Structural `instanceof Embedding` is the wrong answer for a subclass that shares
	 * a dialect but not a product: without this Groq would be offered in `llm.embed:` autocomplete and
	 * then 404 at run time, which is a worse failure than not being offered at all.
	 */
	function supportsEmbeddings() : bool {
		return false;
	}

	/**
	 * OpenAI renamed `system` to `developer`, but that rename is OpenAI's alone — the compatible
	 * endpoints validate against a fixed role enum and hard-reject `developer` with a 400.
	 */
	function getSystemPromptRole() : string {
		return 'system';
	}

	/**
	 * Deliberately empty, which preserves exactly what Groq was sent before it was reparented.
	 *
	 * OpenAI's version emits `reasoning_effort` from the canonical `effort:` key. Groq accepts that only
	 * on some model families, so forwarding it here would be a behavior change smuggled in on a
	 * streaming refactor. Worth revisiting on its own.
	 *
	 * Keeping it empty also neutralizes the inherited _applyToolReasoningGuardrail(): that helper
	 * force-sets or unsets `reasoning_effort` for the gpt-5.x family, and since no Groq model id matches
	 * its `^gpt-(\d+)` probe it would otherwise strip an author's effort on any tool-using turn.
	 */
	function getChatCompletionsParams() : array {
		return [];
	}

	// Groq wants the tool NAME alongside the id on a tool result; OpenAI's base message omits it.
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$tool_message = [
			'role' => 'tool',
			'name' => $tool->getName(),
			'tool_call_id' => $tool->getId(),
			'content' => $content,
		];

		$memory->appendMessage($tool_message);
	}

	function getChatModels() : array {
		return [
			'deepseek-r1-distill-llama-70b',
			'deepseek-r1-distill-qwen-32b',
			'gemma2-9b-it',
			'llama-3.1-8b-instant',
			'llama-3.3-70b-versatile',
			'mixtral-8x7b-32768',
			'qwen-2.5-32b',
		];
	}

	// A hosted inference API with no documented wall-clock cache TTL: no ring, same as a local
	// OpenAI-compatible server. (OpenAI's own 5m hint would be a guess here.)
	function getCacheHintSeconds(array $params) : ?int {
		return null;
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
				['caption' => 'stream@bool:', 'snippet' => 'stream@bool: no', 'docHTML' => '<b>stream@bool:</b>Stream the response (default <code>yes</code>). Streaming replaces the request timeout with an inactivity cutoff, so a long turn is not killed partway through, and it lets a running turn be stopped.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://api.groq.com/openai'],
			],
		];
	}
}