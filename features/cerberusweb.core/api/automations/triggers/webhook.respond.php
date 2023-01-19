<?php
class AutomationTrigger_WebhookRespond extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.webhook.respond';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	public function getInputsMeta() {
		return [
			[
				'key' => 'request_body',
				'notes' => 'The request body as text.',
			],
			[
				'key' => 'request_client_ip',
				'notes' => 'The client IP making the request (e.g. `1.2.3.4`).',
			],
			[
				'key' => 'request_headers',
				'notes' => 'The request headers. Keys are lowercase with dashes as underscores (e.g. `content_type`).',
			],
			[
				'key' => 'request_method',
				'notes' => 'Method name in uppercase (e.g. `POST`).',
			],
			[
				'key' => 'request_params',
				'notes' => 'The query string parameters as a key/value object. Keys are lowercase with dashes as underscores (e.g. `query_string`).',
			],
			[
				'key' => 'request_path',
				'notes' => 'The request path (e.g. `some/folder/file.ext`).',
			],
		];
	}

	public function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'body',
					'notes' => 'The body content to return. Use `body@base64:` for binary.',
				],
				[
					'key' => 'headers',
					'notes' => 'A set of `key: value` pairs (e.g. `Content-Type: application/json`)',
				],
				[
					'key' => 'status_code',
					'notes' => 'HTTP status code (e.g. `200`=OK, `403`=Forbidden, `404`=Not Found, `500`=Error)',
				]
			]
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):return:' => [
					[
						'caption' => 'body@base64:',
						'snippet' => "body@base64:\n\t\${1:base64-encoded content}",
						'docHTML' => "<b>body@base64:</b><br>Set binary HTTP response body",
					],
					[
						'caption' => 'body@text:',
						'snippet' => "body@text:\n\t\${1:example response}",
						'docHTML' => "<b>body@base64:</b><br>Set text HTTP response body",
					],
					[
						'caption' => 'headers:',
						'snippet' => "headers:\n\t\${1:Content-Type}: \${2:text/plain}",
						'docHTML' => "<b>headers:</b><br>Set HTTP response headers",
					],
					[
						'caption' => 'status_code:',
						'snippet' => "status_code: \${1:200}",
						'docHTML' => "<b>status_code:</b><br>Return an HTTP status code",
					],
				],
				'(.*):return:headers:' => [
					[
						'caption' => 'Content-Type:',
						'snippet' => "Content-Type: \${1:text/plain}",
						'docHTML' => "Return a content-type header",
					],
					[
						'caption' => 'X-Header-Name:',
						'snippet' => "\${1:X-Header-Name}: \${2:value}",
						'docHTML' => "An example header/value pair",
					],
				],
				'(.*):return:body:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation_resource' => null,
						'resource' => null,
					]
				]
			]
		];
	}
}