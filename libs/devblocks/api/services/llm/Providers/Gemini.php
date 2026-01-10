<?php
namespace Cerb\LLM\Providers;

use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmProvider;

class Gemini extends OpenAI {
	const ID = 'gemini';
	
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
}