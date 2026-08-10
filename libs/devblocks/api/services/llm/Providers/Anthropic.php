<?php
namespace Cerb\LLM\Providers;

use Cerb\LLM\Providers\Interfaces\Chat;
use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

class Anthropic extends Extension_DevblocksLlmProvider implements Chat {
	const ID = 'anthropic';

	function getIcon() : string {
		return 'logo-claude';
	}

	function getIconColor() : string {
		return '#D97757';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		parent::__construct($params);
		
		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api.anthropic.com');
		
		if($validate && !$this->getParam('authentication'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:anthropic:authentication: is required.');
		
		if(!$this->getParam('max_tokens'))
			$this->setParam('max_tokens', 2048);
		
		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:anthropic:model: is required.');
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

			// Extended-thinking summary blocks (empty text when display:omitted or redacted_thinking).
			if ('thinking' == ($message_content['type'] ?? null))
				$chat_response->pushThinking(strval($message_content['thinking'] ?? ''));

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

		// Anthropic tool results are user-role messages of tool_result content blocks.
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
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		$max_tokens = intval($this->getParam('max_tokens', 2048));
		
		$body_payload = [
			'model' => $this->getParam('model', ''),
			'max_tokens' => $max_tokens,
			'stream' => false,
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

		// Prompt caching: two ephemeral breakpoints — a STABLE prefix (tools+system) at 1h and a ROLLING tail at
		// the author's `cache_ttl` (5m default). Enabled by Cerb-primitive intent, which the provider API can't
		// infer — `llm.agent` defaults ON (prefix re-sent + read next turn), `llm.chat` OFF (one-shot).
		if($this->_getCacheIntent()['enabled'])
			$this->_applyPromptCache($body_payload);

		// Extended thinking: translate the grouped `thinking:` block → native `thinking` + `output_config.effort`.
		$this->_applyThinking($body_payload);

		// Neutral top-level `effort` (the agentPrompt's per-model selection / a fixed catalog `effort:`) →
		// output_config.effort. Applied independently of the `thinking:` block (which _applyThinking skips when
		// absent), and takes precedence over any `thinking.effort`.
		$this->_applyEffort($body_payload);

		$verb = 'POST';
		$url = $base_url . '/v1/messages';
		$headers = [
			'Content-Type' => 'application/json',
			'anthropic-version' => '2023-06-01', // [TODO] Configurable
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
		
		// Add to the memory

		// Neutral token usage for the turn. Anthropic maps directly: input_tokens (fresh),
		// cache_read/cache_creation_input_tokens (read/write), output_tokens.
		$native_usage = $response_json['usage'] ?? [];
		$usage = [
			'input' => intval($native_usage['input_tokens'] ?? 0),
			'output' => intval($native_usage['output_tokens'] ?? 0),
			'cache_read' => intval($native_usage['cache_read_input_tokens'] ?? 0),
			'cache_write' => intval($native_usage['cache_creation_input_tokens'] ?? 0),
		];

		// Why generation stopped: `max_tokens` here normalizes to `length`.
		$finish_reason = self::normalizeFinishReason($response_json['stop_reason'] ?? null);

		// Add to the memory (usage rides the assistant turn — usage_json column, not the replayed data_json)
		if($response_json['content'] ?? null) {
			$memory->appendMessage([
				'role' => $response_json['role'],
				'content' => $response_json['content'],
			], usage: $usage, finish_reason: $finish_reason);
		}
		
		return $this->convertToGenericMessage($response_json);
		$response->setUsage($usage);
		$response->setFinishReason($finish_reason);
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

		// Expand any neutral `images:` into native `image` source blocks (images before text).
		return array_map(fn($m) => $this->expandMessageImages($m), array_values($messages));
	}

	// Two ephemeral breakpoints (of the 4 allowed), ordered longest-TTL-first per the render order
	// tools -> system -> messages:
	//   1. PREFIX — the last `system` block, ALWAYS 1h. Caches tools+system together (they render before it),
	//      written once with byte-stable content, so 1h keeps the expensive prefix (tool schemas, agent_fs
	//      overview, skill summaries) warm across a coding-agent's think/test pauses. This anchor also survives
	//      the 20-block lookback that a rolling tail alone can lose on a >20-block turn. 1h is GA (no beta header)
	//      and supported on Anthropic + Bedrock + Vertex, so it's unconditional here.
	//   2. TAIL — the last content block of the last message, at the author's `cache_ttl` (5m default, 1h opt-in).
	//      Advances each turn; a lapsed 5m tail just re-parses the last turns. Its TTL is <= the 1h prefix, so
	//      system(1h)->messages(<=1h) keeps the longest TTL first.
	// A scalar `system`/`content` is wrapped into a single text block so the marker has somewhere to live.
	private function _applyPromptCache(array &$body_payload) : void {
		$intent = $this->_getCacheIntent();
		$prefix_cc = $this->_cacheControl('1h');
		$tail_cc = $this->_cacheControl($intent['ttl']);

		if(is_string($body_payload['system'] ?? null) && '' !== $body_payload['system']) {
			$body_payload['system'] = [
				['type' => 'text', 'text' => $body_payload['system'], 'cache_control' => $prefix_cc],
			];
		} elseif(is_array($body_payload['system'] ?? null) && $body_payload['system']) {
			$sys_key = array_key_last($body_payload['system']);

			if(is_array($body_payload['system'][$sys_key] ?? null))
				$body_payload['system'][$sys_key]['cache_control'] = $prefix_cc;
		}

		// `cache_tail_skip` moves the rolling breakpoint back N messages so a trailing turn stays OUTSIDE the
		// cached region (a summarize sidecar's instruction). See _getCacheIntent(); N=0 is the normal turn.
		if($intent['tail'] && is_array($body_payload['messages'] ?? null) && $body_payload['messages']) {
			$keys = array_keys($body_payload['messages']);
			$last = $keys[count($keys) - 1 - $intent['tail_skip']] ?? null;

			if(is_null($last))
				return;

			$content = $body_payload['messages'][$last]['content'] ?? null;

			if(is_string($content)) {
				$body_payload['messages'][$last]['content'] = [
					['type' => 'text', 'text' => $content, 'cache_control' => $tail_cc],
				];
			} elseif(is_array($content) && $content) {
				$block_key = array_key_last($content);

				if(is_array($body_payload['messages'][$last]['content'][$block_key] ?? null))
					$body_payload['messages'][$last]['content'][$block_key]['cache_control'] = $tail_cc;
			}
		}
	}

	// The rolling-tail cache lifetime this model uses — the window that lapses first (the tools+system prefix is
	// always cached at 1h). `cache_ttl` is 5m (default) or 1h; that's the real "getting close" signal for the
	// composer's cache ring. (Caller gates on cache being on.)
	function getCacheHintSeconds(array $params) : ?int {
		$ttl = strtolower(trim(strval($params['cache_ttl'] ?? '5m')));
		return ('1h' === $ttl) ? 3600 : 300;
	}

	// Build a cache_control marker; 5m is the API default (no `ttl` field), 1h is opt-in.
	private function _cacheControl(string $ttl) : array {
		$cc = ['type' => 'ephemeral'];

		if('1h' === $ttl)
			$cc['ttl'] = '1h';

		return $cc;
	}

	// Anthropic image content block (base64 source; `media_type` is Anthropic's native key for the mime type).
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

	// Translate the grouped `thinking:` param block into Anthropic's native request shape (`type`/`display`
	// only). Author-declared: we form valid JSON for the chosen `type` and DON'T classify the model (a wrong
	// pairing surfaces as the API's own 400 — model ids are free-text). Effort is NOT authored here anymore —
	// it's the canonical top-level `effort:` key (see _applyEffort); the legacy `enabled` budget derives from
	// it too. No `thinking:` block → no change.
	private function _applyThinking(array &$body_payload) : void {
		$thinking = $this->getParam('thinking');

		if(!is_array($thinking) || !$thinking)
			return;

		$type = DevblocksPlatform::strLower(trim(strval($thinking['type'] ?? '')));
		$display = DevblocksPlatform::strLower(trim(strval($thinking['display'] ?? '')));

		if('enabled' === $type) {
			// Legacy models: `budget_tokens` is required and `output_config`/`effort` is rejected. Derive the
			// budget from the canonical top-level `effort:` (falls back to the default when unset).
			$budget = $this->_effortToBudget($this->getEffort() ?? '', intval($this->getParam('max_tokens', 2048)));

			if(!is_null($budget))
				$body_payload['thinking'] = ['type' => 'enabled', 'budget_tokens' => $budget];

			return;
		}

		if('disabled' === $type) {
			$body_payload['thinking'] = ['type' => 'disabled'];
		} elseif('adaptive' === $type) {
			$body_payload['thinking'] = ['type' => 'adaptive'];

			if('' !== $display)
				$body_payload['thinking']['display'] = $display;
		}
	}

	// Route the canonical top-level `effort:` (provider_params['effort']) to `output_config.effort`. Verbatim —
	// the API validates the level for the model (low|medium|high|xhigh|max on current models); we don't clamp or
	// whitelist. Skipped for legacy `thinking: {type: enabled}`, which rejects output_config and instead maps
	// effort → budget_tokens in _applyThinking. No effort → no change.
	private function _applyEffort(array &$body_payload) : void {
		if(null === ($effort = $this->getEffort()))
			return;

		$thinking = $this->getParam('thinking');
		$type = is_array($thinking) ? DevblocksPlatform::strLower(trim(strval($thinking['type'] ?? ''))) : '';
		if('enabled' === $type)
			return;

		$body_payload['output_config'] = array_merge($body_payload['output_config'] ?? [], ['effort' => $effort]);
	}

	// Map a grouped effort level → a legacy `budget_tokens` value, clamped so it's ≥1024 and < max_tokens.
	// Returns null when max_tokens can't fit a valid budget (skip legacy thinking rather than send a 400).
	private function _effortToBudget(string $effort, int $max_tokens) : ?int {
		$budget = [
			'low' => 4096,
			'medium' => 8192,
			'high' => 16384,
			'xhigh' => 24576,
			'max' => 32768,
		][$effort] ?? 8192;

		$ceiling = $max_tokens - 1;

		if($ceiling < 1024)
			return null;

		return max(1024, min($budget, $ceiling));
	}

	// Anthropic's /v1/models returns OpenAI's `{data:[{id}]}` shape, so only the version header differs.
	protected function _getChatModelsRequestHeaders() : array {
		return ['anthropic-version' => '2023-06-01'];
	}

	function getChatModels() : array {
		return [
			'claude-opus-4-8',
			'claude-sonnet-5',
			'claude-haiku-4-5-20251001',
			'claude-fable-5',
		];
	}

	// Every current Claude model is multimodal; only the context window varies by tier. Powers the agent
	// model editor's "default on select" (vision + context window) when a model is picked from the live list.
	function getModelDefaults(string $model) : array {
		if(!str_starts_with($model, 'claude-'))
			return [];

		$windows = [
			'claude-fable-5' => 1000000,
			'claude-opus-5' => 1000000,
			'claude-sonnet-5' => 200000,
			'claude-haiku-4-5-20251001' => 200000,
		];

		return [
			'vision' => true,
			'context_window' => $windows[$model] ?? 200000,
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'authentication:',
				'max_tokens@int: 2048',
				['caption' => 'thinking:', 'snippet' => "thinking:\n\ttype: adaptive", 'docHTML' => '<b>thinking:</b>Extended thinking. <code>type</code>: adaptive|enabled|disabled &middot; <code>display</code>: summarized|omitted. Modern models use <code>adaptive</code>; older models use <code>enabled</code>. Reasoning depth is the top-level <code>effort:</code> key.'],
				['caption' => 'effort:', 'snippet' => "effort: high", 'docHTML' => '<b>effort:</b>Reasoning effort (empty = provider default). Values: <code>low|medium|high|xhigh|max</code>. On legacy <code>thinking: {type: enabled}</code> models it maps to a thinking budget instead.'],
				['caption' => 'cache@bool:', 'snippet' => 'cache@bool: yes', 'docHTML' => '<b>cache@bool:</b>Prompt caching. Defaults ON for <code>llm.agent</code> (multi-turn), OFF for <code>llm.chat</code> (one-shot). The stable prefix (tools+system) is always cached at 1h; the rolling tail uses <code>cache_ttl</code>.'],
				['caption' => 'cache_ttl:', 'snippet' => 'cache_ttl: 1h', 'docHTML' => '<b>cache_ttl:</b>Rolling-tail cache lifetime. <code>5m</code> (default — a lapsed tail just re-parses the last turns) or <code>1h</code> (editor/coding-agent sessions with long pauses between turns). The prefix is always 1h regardless.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'thinking:' => ['type:', 'display:'],
				'thinking:type:' => ['adaptive', 'enabled', 'disabled'],
				'thinking:display:' => ['summarized', 'omitted'],
				'effort:' => ['low', 'medium', 'high', 'xhigh', 'max'],
				'cache_ttl:' => ['5m', '1h'],
			],
		];
	}
}