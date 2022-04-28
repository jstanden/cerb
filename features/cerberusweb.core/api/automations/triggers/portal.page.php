<?php
class AutomationTrigger_PortalPage extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.portal.page';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) : bool {
		return true;
	}
	
	// [TODO] Request params
	function getInputsMeta() : array {
		return [
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'identity_*',
				'notes' => 'The identity of the current visitor (if any).',
			],
			[
				'key' => 'portal_*',
				'notes' => 'The portal record.',
			],
			[
				'key' => 'request_body',
				'notes' => 'The request body as text.',
			],
			[
				'key' => 'request_client_ip',
				'notes' => 'The client IP making the request (e.g. `1.2.3.4`).',
			],
			[
				'key' => 'request_client_browser_name',
				'notes' => 'The client browser name (e.g. Safari).',
			],
			[
				'key' => 'request_client_browser_platform',
				'notes' => 'The client browser platform (e.g. Macintosh).',
			],
			[
				'key' => 'request_client_browser_version',
				'notes' => 'The client browser version.',
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
	
	function getOutputsMeta() : array {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):return:' => [
					'dashboard:',
					'text:',
				],
				
				'(.*):return:dashboard:' => [
					'label:',
					[
						'caption' => 'layout: content',
						'snippet' => "layout:\n  content:\n",
					],
					[
						'caption' => 'layout: content|sidebar',
						'snippet' => "layout:\n  content:\n  sidebar:\n",
					],
					[
						'caption' => 'layout: left|right',
						'snippet' => "layout:\n  left:\n  right:\n",
					],
					[
						'caption' => 'layout: left|center|right',
						'snippet' => "layout:\n  left:\n  center:\n  right:\n",
					],
					[
						'caption' => 'layout: sidebar|content',
						'snippet' => "layout:\n  sidebar:\n  content:\n",
					],
				],
				
				'(.*):return:dashboard:layout:(center|content|left|right|sidebar):' => [
					[
						'caption' => 'interaction:',
						'snippet' => "interaction/\${1:name}:\n  ",
					],
					[
						'caption' => 'text:',
						'snippet' => "text/\${1:name}:\n  ",
					],
				],
				
				'(.*):return:dashboard:layout:(center|content|left|right|sidebar):interaction:' => [
					'label:',
					[
						'caption' => 'uri:',
						'snippet' => "uri: cerb:automation:\${1:automation.name}",
					],
				],
				
				'(.*):return:dashboard:layout:(center|content|left|right|sidebar):text:' => [
					'content:',
					'label:',
				],
				
				'(.*):return:text:' => [
					'label:',
					'content@text:',
				],
			
			]
		];
	}
}