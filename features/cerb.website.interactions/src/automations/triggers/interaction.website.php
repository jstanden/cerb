<?php
class AutomationTrigger_InteractionWebsite extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.interaction.website';
	
	public static function getFormComponentMeta(): array {
		return [
			'end' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\EndAwait',
			'say' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SayAwait',
			'sheet' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SheetAwait',
			'submit' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SubmitAwait',
			'text' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\TextAwait',
			'textarea' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\TextareaAwait',
		];
	}
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) : bool {
		return true;
	}
	
	function getInputsMeta() : array {
		return [
			[
				'key' => 'interaction',
				'notes' => 'The name of the interaction.',
			],
			[
				'key' => 'interaction_params',
				'notes' => 'Arbitrary interaction parameters.',
			],
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'portal_*',
				'notes' => 'The portal record.',
			],
			[
				'key' => 'client_ip',
				'notes' => 'The client IP address.',
			],
			[
				'key' => 'client_browser_name',
				'notes' => 'The client browser name (e.g. Safari).',
			],
			[
				'key' => 'client_browser_platform',
				'notes' => 'The client browser platform (e.g. Macintosh).',
			],
			[
				'key' => 'client_browser_version',
				'notes' => 'The client browser version.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		$toolbar['menu/insert']['items']['menu/await'] = [
			'label' => 'Await',
			'items' => [
//				'interaction/respond_say' => [
//					'label' => 'Say',
//					'uri' => 'ai.cerb.automationBuilder.interaction.worker.await.say',
//				],
//				'interaction/prompt_sheet' => [
//					'label' => 'Sheet',
//					'uri' => 'ai.cerb.automationBuilder.interaction.worker.await.promptSheet',
//				],
//				'interaction/prompt_text' => [
//					'label' => 'Text',
//					'uri' => 'ai.cerb.automationBuilder.interaction.worker.await.promptText',
//				]
			], // items
		];
		
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):await:' => [
					'form:',
					'interaction:',
				],
				
				'(.*):await:form:' => [
					'title:',
					'elements:',
				],
				
				'(.*):await:form:elements:' => [
					'say:',
					'sheet:',
					'submit:',
					'text:',
					'textarea:',
				],
				
				'(.*):await:form:elements:say:' => [
					'content@text:',
					'message@text:',
				],
				
				'(.*):await:form:elements:sheet:' => [
					'data:',
					'default:',
					'label:',
					'limit:',
					'page:',
					'required@bool: yes',
					'schema:',
				],
				'(.*):await:form:elements:sheet:data:' => [
					[
						'caption' => 'automation:',
						'snippet' => "automation:\n  uri: cerb:automation:\${1:automation.name}\n  inputs:\n",
					],
					[
						'caption' => '(manual)',
						'snippet' => "0:\n  key: key1\n  value: value1\n1:\n  key: key2\n  value: value2\n",
					]
				],
				'(.*):await:form:elements:sheet:data:automation:' => [
					'uri:',
					'inputs:',
				],
				'(.*):await:form:elements:sheet:schema:' => [
					'columns:',
					'layout:',
				],
				'(.*):await:form:elements:sheet:schema:columns:' => [
					'date/key:',
					'link/key:',
					'selection/key:',
					'slider/key:',
					'text/key:',
					'time_elapsed/key:',
				],
				'(.*):await:form:elements:sheet:schema:columns:date:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:date:params:' => [
					'format: d-M-Y H:i:s T',
					'value: 1577836800',
					'value_key: updated',
				],
				'(.*):await:form:elements:sheet:schema:columns:link:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:link:params:' => [
					'href: https://example.com/',
					'href_key: record_url',
					'href_template@raw:',
					'text: Link title',
					'text_key: _label',
					'text_template@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:params:' => [
					'mode: single',
					'mode: multiple',
					'value: 123',
					'value_key: key',
					'value_template@raw: {{key}}',
				],
				'(.*):await:form:elements:sheet:schema:columns:slider:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:slider:params:' => [
					'min: 0',
					'max: 100',
					'value: 50',
					'value_key: importance',
					'value_template@raw: {{importance}}',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:params:' => [
					'bold@bool: yes',
					'value: Text',
					'value_key: key',
					'value_template@raw: {{key}}',
					'value_map:',
					'icon:',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:params:icon:' => [
					'image:',
				],
				'(.*):await:form:elements:sheet:schema:columns:time_elapsed:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:time_elapsed:params:' => [
					'precision: 2',
				],
				'(.*):await:form:elements:sheet:schema:layout:' => [
					'filtering@bool: yes',
					'headings@bool: yes',
					'paging@bool: yes',
					'style: table',
					'title_column:',
				],
				'(.*):await:form:elements:sheet:schema:layout:style' => [
					'buttons',
					'scale',
					'table',
				],
				
				'(.*):await:form:elements:text:' => [
					'default:',
					'label:',
					'max_length@int:',
					'min_length@int:',
					'placeholder:',
					'required@bool:',
					'truncate@bool: yes',
					'type:',
				],
				
				'(.*):await:form:elements:text:type:' => [
					'date',
					'decimal',
					'email',
					'freeform',
					'geopoint',
					'ip',
					'ipv4',
					'ipv6',
					'number',
					'uri',
					'url',
				],
				
				'(.*):await:form:elements:textarea:' => [
					'default:',
					'label:',
					'max_length@int:',
					'min_length@int:',
					'placeholder:',
					'required@bool:',
					'truncate@bool: yes',
				],
				
				'(.*):await:interaction:' => [
					'uri:',
					'output:',
				],
			]
		];
	}
}