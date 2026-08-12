<?php
namespace Cerb\LLM\Providers;

use Cerb\LLM\Providers\Interfaces\Chat;
use Cerb\LLM\Providers\Interfaces\Embedding;
use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

class AwsBedrock extends Extension_DevblocksLlmProvider implements Chat, Embedding {
	const ID = 'aws_bedrock';

	// Per-model capabilities harvested from the live catalog during a fetchChatModels() pass, keyed by the id
	// it's invoked as (a foundation model id AND, for a profile, the profile id). Read back by
	// getModelDefaults() -- the agent model editor calls both on the same provider instance, which is the
	// only place this is populated. Empty at runtime, exactly like the base class.
	private array $_model_meta = [];

	function getIcon() : string {
		return 'logo-bedrock';
	}

	function getIconColor() : string {
		return '#EC7211';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		parent::__construct($params);
		
		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://bedrock-runtime.us-east-1.amazonaws.com');
		
		if($validate && !$this->getParam('authentication'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:aws_bedrock:authentication: is required.');
		
		if(!$this->getParam('max_tokens'))
			$this->setParam('max_tokens', 2048);
		
		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:aws_bedrock:model: is required.');
	}
	
	public function convertToGenericMessage(array $message, ?string $message_uuid=null): DevblocksLlmChatResponse {
		$chat_response = new DevblocksLlmChatResponse('', $message_uuid);
		
		if(array_key_exists('role', $message))
			$chat_response->setRole($message['role']);
		
		if(
			array_key_exists('content', $message)
			&& is_string($message['content'])
		) {
			$message['content'] = [
				[
					'type' => 'text',
					'text' => $message['content']
				]
			];
		}
		
		foreach($message['content'] ?? [] as $message_content) {
			if ('text' == ($message_content['type'] ?? null))
				$chat_response->pushMessage($message_content['text']);
			
			if ('tool_use' == $message_content['type'] ?? null) {
				if (!($message_content['id'] ?? null) || !($message_content['name'] ?? null))
					continue;
				
				$tool = new DevblocksLlmChatResponse_Tool(
					$message_content['name'] ?? '',
					$message_content['input'] ?? [],
					$message_content['id'],
				);
				
				$chat_response->pushTool($tool);
			}
			
			if('tool_result' == $message_content['type'] ?? null) {
				$chat_response->setRole('tool');
				$chat_response->pushToolResult($message_content['tool_use_id'] ?? '', $message_content['content']);
			}
		}
		
		// Surface any neutral `images:` (resource uris) for the transcript viewer.
		$this->_pushMessageImages($message, $chat_response);

		return $chat_response;
	}
	
	function toNativeMessage(DevblocksLlmChatResponse $message) : array {
		$tool_results = $message->getToolResults();

		// Bedrock (Anthropic-on-Bedrock) tool results are user-role tool_result blocks.
		if($tool_results) {
			$blocks = [];

			foreach($tool_results as $tool_id => $content) {
				$blocks[] = [
					'type' => 'tool_result',
					'tool_use_id' => $tool_id,
					'content' => is_array($content) ? json_encode($content) : strval($content),
				];
			}

			return [[
				'role' => 'user',
				'content' => $blocks,
			]];
		}

		$blocks = [];

		foreach($message->getMessages() as $block) {
			if('' !== ($block['content'] ?? ''))
				$blocks[] = ['type' => 'text', 'text' => $block['content']];
		}

		foreach($message->getToolCalls() as $tool) {
			$blocks[] = [
				'type' => 'tool_use',
				'id' => $tool->getId(),
				'name' => $tool->getName(),
				'input' => $tool->getParameters() ?: (object)[],
			];
		}

		$role = $message->getRole();
		$role = ('' === $role || 'tool' === $role) ? 'assistant' : $role;

		return [[
			'role' => $role,
			'content' => $blocks,
		]];
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function embed(array $texts) : array {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		$model = $this->getParam('model', 'amazon.titan-embed-text-v2:0');
		$dimensions = $this->getParam('dimensions', 512);
		
		$embeddings = [];
		
		foreach($texts as $text) {
			$body_payload = [
				'inputText' => $text,
				'dimensions' => intval($dimensions),
				'normalize' => true,
			];
			
			$verb = 'POST';
			$url = $base_url . '/model/' . $model . '/invoke';
			$headers = [
				'Content-Type' => 'application/json',
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
			
			$embeddings[] = $response_json['embedding'];
		}
		
		return $embeddings;
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		$max_tokens = intval($this->getParam('max_tokens', 2048));
		$anthropic_version = $this->getParam('anthropic_version', 'bedrock-2023-05-31');
		$model = $this->getParam('model', 'us.anthropic.claude-haiku-4-5-20251001-v1:0');
		
		$body_payload = [
			'anthropic_version' => $anthropic_version,
			'max_tokens' => $max_tokens,
			'messages' => $this->sanitizeMessages($messages),
		];
		
		if($system_prompt)
			$body_payload['system'] = $system_prompt;
		
		// Convert OpenAI format tools to Anthropic format
		if($tools) {
			$tools = array_map(function($tool){
				$tool = $tool['function'];
				
				if($tool['parameters'] ?? null) {
					$tool['input_schema'] = $tool['parameters'];
					unset($tool['parameters']);
				}
				
				return $tool;
			}, $tools);
			
			$body_payload['tools'] = $tools;
		}
		
		$verb = 'POST';
		$url = $base_url . '/model/' . $model . '/invoke';
		$headers = [
			'Content-Type' => 'application/json',
			'anthropic-version' => $anthropic_version,
		];
		$body = json_encode($body_payload);
		
		$request = new Request($verb, $url, $headers, $body);
		$request_options = [
			'http_errors' => false,
		];
		$error = null;
		
		// Authenticate the request if required
		if($authentication_uri) {
			if(!$this->_authenticateRequest($authentication_uri, $request, $request_options, $error))
				throw new Exception_DevblocksAutomationError($error);
		}
		
		if(false === ($response = $http->sendRequest($request, $request_options, $error)))
			throw new Exception_DevblocksAutomationError($error);
		
		if(false === ($response_json = $http->getResponseAsJson($response, $error)))
			throw new Exception_DevblocksAutomationError($error);
		
		if(200 != $response->getStatusCode()) {
			if($response_json['error']['message'] ?? null)
				throw new Exception_DevblocksAutomationError($response_json['error']['message']);
			
			throw new Exception_DevblocksAutomationError('HTTP status code: ' . $response->getStatusCode());
		}
		
		// Why generation stopped: `max_tokens` here normalizes to `length`.
		$finish_reason = self::normalizeFinishReason($response_json['stop_reason'] ?? null);

		// Add to the memory
		if($response_json['content'] ?? null) {
			$memory->appendMessage([
				'role' => $response_json['role'],
				'content' => $response_json['content'],
			], finish_reason: $finish_reason);
		}

		$response = $this->convertToGenericMessage($response_json);
		$response->setFinishReason($finish_reason);

		return $response;
	}
	
	function sanitizeMessages(array $messages) : array {
		while(!empty($messages)) {
			$key = array_key_first($messages);
			
			if(
				($messages[$key]['role'] ?? '') == 'user'
				&& 'tool_result' != ($messages[$key]['content'][0]['type'] ?? '')
			) break;
			
			// Prune non-user messages
			unset($messages[$key]);
		}
		
		// Fix tool calls with no inputs
		foreach($messages as $message_index => $message) {
			if(!is_array($message['content'] ?? null))
				continue;
			
			$messages[$message_index]['content'] = array_map(
				function($content) {
					// Fix tool use for empty inputs [] -> {}
					if(
						($content['type'] ?? null) == 'tool_use'
						&& is_array($content['input'])
						&& empty($content['input'])
					) $content['input'] = (object)[];
					
					return $content;
				},
				$message['content']
			);
		}
		
		// Expand any neutral `images:` into native content parts (images before text).
		return array_map(fn($m) => $this->expandMessageImages($m), array_values($messages));
	}

	// Bedrock runs Anthropic models — native `image` source blocks (base64), like Anthropic.
	protected function _nativeImagePart(string $mime_type, string $data) : ?array {
		if('' === $mime_type || '' === $data)
			return null;

		return [
			'type' => 'image',
			'source' => [
				'type' => 'base64',
				'media_type' => $mime_type,
				'data' => $data,
			],
		];
	}
	
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$tool_message = [
			'role' => 'user',
			'content' => [
				[
					'type' => 'tool_result',
					'tool_use_id' => $tool->getId(),
					'content' => $content,
				],
			],
		];
		
		$memory->appendMessage($tool_message);
	}

	/**
	 * Bedrock has TWO hosts. `api_endpoint_url` points at the RUNTIME plane
	 * (`bedrock-runtime.<region>.amazonaws.com`), where every `invoke` goes -- but the model catalog lives on
	 * the CONTROL plane (`bedrock.<region>.amazonaws.com`). Listing against the runtime host is a 404, which
	 * is exactly what the inherited OpenAI-shaped `/v1/models` did before this override existed.
	 *
	 * SigV4 needs nothing: `deriveServiceRegionFromHost()` already scopes `bedrock-runtime` to the `bedrock`
	 * service, and the control-plane host parses to that same service and region.
	 *
	 * A host that isn't `bedrock-runtime` (a VPC endpoint, a proxy) is left alone -- the operator pointed us
	 * somewhere deliberate and we'd only be guessing at its layout.
	 */
	private function _getControlPlaneBaseUrl(string $base_url) : string {
		return preg_replace('#^(https?://)bedrock-runtime(-fips)?\.#i', '$1bedrock$2.', $base_url);
	}

	// ListFoundationModels. No query string on purpose: the SigV4 canonicalizer round-trips the query through
	// DevblocksPlatform::strParseQueryString(), which rewrites `.` and `[...]` keys -- so we take the whole
	// (~100 row) catalog and filter it below rather than risk a signature mismatch on a `by*` filter.
	function getChatModelsEndpointUrl(string $base_url) : string {
		return $this->_getControlPlaneBaseUrl($base_url) . '/foundation-models';
	}

	/**
	 * `{modelSummaries: [{modelId, modelName, providerName, inputModalities, outputModalities,
	 * inferenceTypesSupported, modelLifecycle:{status}}]}` -- nothing like OpenAI's `{data:[{id}]}`.
	 *
	 * Returns only the ON_DEMAND ids, which are the ones invocable as they stand. Everything else on Bedrock
	 * is reached through an inference profile, and those ids are merged in by fetchChatModels().
	 *
	 * Side effect by design: this fills `_model_meta` for EVERY text model it sees, not just the ones it
	 * returns. The inference-profile pass resolves its ARNs against that map, and getModelDefaults() reads
	 * the capabilities back out for the editor.
	 */
	protected function _parseChatModelsResponse(array $response_json) : array {
		$this->_model_meta = [];
		$models = [];

		$upper = fn($values) => array_map(fn($v) => DevblocksPlatform::strUpper(strval($v)), is_array($values) ? $values : []);

		foreach($response_json['modelSummaries'] ?? [] as $row) {
			if(!is_array($row))
				continue;

			if('' === ($model_id = strval($row['modelId'] ?? '')))
				continue;

			// A model that's been deprecated or withdrawn can't be invoked; absent means ACTIVE.
			if('ACTIVE' !== DevblocksPlatform::strUpper(strval($row['modelLifecycle']['status'] ?? 'ACTIVE')))
				continue;

			$input_modalities = $upper($row['inputModalities'] ?? []);
			$output_modalities = $upper($row['outputModalities'] ?? []);

			// Chat is text in, text out. This is what drops the embedding (EMBEDDING out), image, and video
			// models that share the catalog.
			if(!in_array('TEXT', $input_modalities, true) || !in_array('TEXT', $output_modalities, true))
				continue;

			// We speak the SYNCHRONOUS Converse call, so `converse.sync` is the predicate -- not the mere
			// presence of a `converse` block. They differ in practice: twelvelabs.pegasus advertises
			// `converse: {streaming:true, sync:false}` and answers `/converse` with "This action doesn't
			// support the model that you provided". Without this the picker offers models that can't run.
			if(!($row['inferenceAPIsSupported']['converse']['sync'] ?? true))
				continue;

			$this->_model_meta[$model_id] = [
				'vision' => in_array('IMAGE', $input_modalities, true),
				'description' => trim(sprintf('%s %s',
					strval($row['providerName'] ?? ''),
					strval($row['modelName'] ?? '')
				)),
			];

			$inference_types = $upper($row['inferenceTypesSupported'] ?? []);

			if(in_array('ON_DEMAND', $inference_types, true))
				$models[] = $model_id;
		}

		return $models;
	}

	/**
	 * ListInferenceProfiles -- the OTHER half of the catalog, and the half people actually want. Modern
	 * Claude and Nova models on Bedrock are INFERENCE_PROFILE-only: the bare `anthropic.claude-...` id from
	 * the catalog above 400s at invoke, and the id that works is the cross-region profile
	 * (`us.anthropic.claude-...`) that only this endpoint knows.
	 *
	 * `maxResults` is safe to sign (alphanumeric key and value). `nextToken` deliberately is NOT followed:
	 * it's a base64 blob whose `+/=` are exactly what the canonicalizer noted above would mangle, and no
	 * region is anywhere near 1000 profiles.
	 *
	 * @return string[] Invocable profile ids ([] on any failure -- see fetchChatModels)
	 */
	private function _fetchInferenceProfiles(?string &$error=null) : array {
		if(!($base_url = rtrim(strval($this->getParam('api_endpoint_url')), '/')))
			return [];

		$url = $this->_getControlPlaneBaseUrl($base_url) . '/inference-profiles?maxResults=1000';

		if(null === ($response_json = $this->_fetchModelsJson($url, [], $error)))
			return [];

		$profiles = [];

		foreach($response_json['inferenceProfileSummaries'] ?? [] as $row) {
			if(!is_array($row))
				continue;

			if('' === ($profile_id = strval($row['inferenceProfileId'] ?? '')))
				continue;

			if('ACTIVE' !== DevblocksPlatform::strUpper(strval($row['status'] ?? 'ACTIVE')))
				continue;

			// Resolve the profile to the foundation model it fronts: the ARN basename
			// (`arn:aws:bedrock:us-east-1::foundation-model/<modelId>`), else the geo-stripped profile id
			// (`us.anthropic.foo` -> `anthropic.foo`) for a profile that fronts another profile.
			$model_arn = strval($row['models'][0]['modelArn'] ?? '');
			$model_id = str_contains($model_arn, '/') ? substr($model_arn, strrpos($model_arn, '/') + 1) : '';

			if(!array_key_exists($model_id, $this->_model_meta))
				$model_id = preg_replace('#^[a-z0-9-]+\.(?=[a-z0-9-]+\.)#i', '', $profile_id);

			// Unknown to the catalog means it isn't a text model we kept (or isn't a model at all), so it has
			// no business in a chat picker.
			if(!array_key_exists($model_id, $this->_model_meta))
				continue;

			$this->_model_meta[$profile_id] = $this->_model_meta[$model_id];
			$profiles[] = $profile_id;
		}

		return $profiles;
	}

	/**
	 * The catalog is two calls, and only the first is load-bearing.
	 *
	 * The profile pass is ADDITIVE and its failure is swallowed on purpose: an IAM policy that grants
	 * `bedrock:ListFoundationModels` but not `bedrock:ListInferenceProfiles` should still get a usable list
	 * of on-demand models rather than an error where a list used to be.
	 */
	function fetchChatModels(?string &$error=null) : ?array {
		// The vendor catalog, via the base class (getChatModelsEndpointUrl + _parseChatModelsResponse above).
		// Fatal: without it we know nothing, including how to filter the profiles.
		if(null === ($models = parent::fetchChatModels($error)))
			return null;

		$profiles_error = null;

		if(($profiles = $this->_fetchInferenceProfiles($profiles_error)))
			$models = array_merge($models, $profiles);

		$models = array_values(array_unique($models));

		sort($models, SORT_NATURAL | SORT_FLAG_CASE);

		return $models;
	}

	// Live capabilities from the catalog, for the editor's fill-on-select. Bedrock reports no context window,
	// so that key is simply absent and the field keeps whatever's typed.
	function getModelDefaults(string $model) : array {
		return $this->_model_meta[$model] ?? [];
	}

	/**
	 * The regional runtime endpoints, us-east-1 first. Bedrock is the one provider where the endpoint is a
	 * routine per-install choice rather than an override, so the whole list is worth offering -- both here in
	 * the KATA editor and in the agent model editor's Endpoint URL field, which reads this same list out of
	 * the provider catalog.
	 */
	private function _getEndpointUrls() : array {
		$regions = [
			'us-east-1', 'us-east-2', 'us-west-2',
			'ca-central-1',
			'eu-central-1', 'eu-west-1', 'eu-west-2', 'eu-west-3', 'eu-north-1',
			'ap-northeast-1', 'ap-northeast-2', 'ap-northeast-3', 'ap-south-1', 'ap-southeast-1', 'ap-southeast-2',
			'sa-east-1',
		];

		return array_map(fn($region) => sprintf('https://bedrock-runtime.%s.amazonaws.com', $region), $regions);
	}

	function getChatModels() : array {
		return [
			'us.anthropic.claude-opus-4-8',
			'us.anthropic.claude-sonnet-5',
			'us.anthropic.claude-haiku-4-5-20251001-v1:0',
			'us.anthropic.claude-fable-5',
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				'anthropic_version: bedrock-2023-05-31',
				'api_endpoint_url:',
				'authentication:',
				'max_tokens@int: 2048',
				['caption' => 'model:', 'snippet' => "# See: https://docs.aws.amazon.com/bedrock/latest/userguide/inference-profiles-support.html\nmodel:", 'score' => 2000],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => $this->_getEndpointUrls(),
			],
		];
	}

	function getEmbeddingModels() : array {
		return [
			'amazon.titan-embed-text-v2:0',
		];
	}

	function getEmbeddingKataAutocomplete() : array {
		return [
			'keys' => [
				'api_endpoint_url:',
				'authentication:',
				'dimensions:',
				'model:',
			],
			'values' => [
				'model:' => $this->getEmbeddingModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => $this->_getEndpointUrls(),
				'dimensions:' => ['256', '512', '1024'],
			],
		];
	}
}