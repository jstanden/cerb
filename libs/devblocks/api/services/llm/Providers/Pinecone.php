<?php
namespace Cerb\LLM\Providers;

use Cerb\LLM\Providers\Interfaces\Embedding;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

class Pinecone extends Extension_DevblocksLlmProvider implements Embedding {
	const ID = 'pinecone';
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		parent::__construct($params);
		
		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api.pinecone.io');
		
		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:pinecone:model: is required.');
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function embed(array $texts) : array {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url', 'https://api.pinecone.io'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		
		$verb = 'POST';
		$url = $base_url . '/embed';
		$headers = [
			'Content-Type' => 'application/json',
			'X-Pinecone-API-Version' => '2025-01',
		];
		$body_payload = [
			'model' => $this->getParam('model', ''),
			'parameters' => [
				'input_type' => 'passage',
				'truncate' => 'END',
			],
			'inputs' => array_map(fn($text) => ['text' => $text], $texts)
		];
		
		$body = json_encode($body_payload);
		
		$request = new Request($verb, $url, $headers, $body);
		$request_options = [
			'http_errors' => false,
		];
		$error = null;
		
		// Authenticate the request if required
		if ($authentication_uri) {
			if (!$this->_authenticateRequest($authentication_uri, $request, $request_options, $error))
				throw new Exception_DevblocksAutomationError($error);
		}
		
		if (false === ($response = $http->sendRequest($request, $request_options, $error)))
			throw new Exception_DevblocksAutomationError($error);
		
		if (false === ($response_json = $http->getResponseAsJson($response, $error)))
			throw new Exception_DevblocksAutomationError($error);
		
		if (200 != $response->getStatusCode()) {
			if ($response_json['error']['message'] ?? null)
				throw new Exception_DevblocksAutomationError($response_json['error']['message']);
			
			throw new Exception_DevblocksAutomationError('HTTP status code: ' . $response->getStatusCode());
		}
		
		return [
			'embeddings' => array_map(
				fn($data) => $data['values'] ?? [],
				$response_json['data'] ?? []
			),
		];
	}
}