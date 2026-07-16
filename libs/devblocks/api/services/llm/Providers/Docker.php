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

	function getChatModels() : array {
		return [
			'ai/llama3.2',
		];
	}

	function getEmbeddingModels() : array {
		return [
			'ai/mxbai-embed-large',
		];
	}
}