<?php
namespace Cerb\LLM\Providers;

use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmProvider;

class Gemini extends OpenAI {
	const ID = 'gemini';

	function getIcon() : string {
		return 'logo-gemini';
	}

	function getIconColor() : string {
		return '#1A73E8';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	function __construct(array $params, bool $validate = true) {
		Extension_DevblocksLlmProvider::__construct($params, false);
		
		if (!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://generativelanguage.googleapis.com/v1beta/openai');
		
		if ($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:gemini:model: is required.');
	}
	
	function getChatCompletionEndpointUrl(string $base_url) : string {
		return $base_url . '/chat/completions';
	}
	
	function getChatCompletionsParams() : array {
		$params = [];
		
		if($this->getParam('thinking_include')) {
			$params['extra_body']['google']['thinking_config']['include_thoughts'] = true;
		}
		
		// minimal, low, medium, high
		if(($thinking_level = $this->getParam('thinking_level'))) {
			$params['reasoning_effort'] = $thinking_level;
		}
		
		return $params;
	}
	
	function getEmbeddingsEndpointUrl(string $base_url) : string {
		return $base_url . '/embeddings';
	}

	function getChatModels() : array {
		return [
			'gemini-3-pro-preview',
			'gemini-3-flash-preview',
			'gemini-2.5-pro',
			'gemini-2.5-flash',
			'gemini-2.5-flash-lite',
			'gemini-2.0-flash',
			'gemini-2.0-flash-lite',
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
				['caption' => 'thinking_include:', 'snippet' => "thinking_include@bool: yes", 'docHTML' => '<b>thinking_include:</b>Include thoughts in the chat completion output.'],
				['caption' => 'thinking_level:', 'snippet' => "thinking_level: low", 'docHTML' => '<b>thinking_level:</b>Adjust the reasoning effort based on the complexity of a request.<br><code>low</code> or <code>high</code> for Gemini 3 Pro, any setting for Gemini 3 Flash. Not supported for Gemini 2.5.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://generativelanguage.googleapis.com/v1beta/openai'],
				'thinking_level:' => ['minimal', 'low', 'medium', 'high'],
			],
		];
	}

	function getEmbeddingModels() : array {
		return [
			'text-embedding-004',
		];
	}

	function getEmbeddingKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
			],
			'values' => [
				'model:' => $this->getEmbeddingModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://generativelanguage.googleapis.com/v1beta/openai'],
			],
		];
	}
}