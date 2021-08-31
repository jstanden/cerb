<?php
class AutomationTrigger_InteractionWorker extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.interaction.worker';
	
	public static function getFormComponentMeta() {
		return [
			'editor' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\EditorAwait',
			'end' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\EndAwait',
			'fileUpload' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\FileUploadAwait',
			'map' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\MapAwait',
			'say' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\SayAwait',
			'sheet' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\SheetAwait',
			'submit' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\SubmitAwait',
			'text' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\TextAwait',
			'textarea' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\TextareaAwait',
		];
	}
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() {
		return [
			[
				'key' => 'caller_name',
				'notes' => 'The caller which started the interaction.',
			],
			[
				'key' => 'caller_params',
				'notes' => 'Built-in parameters based on the caller type.',
			],
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		$toolbar['menu/insert']['items']['menu/await'] = [
			'label' => 'Await',
			'items' => [
				'interaction/prompt_editor' => [
					'label' => 'Editor',
					'uri' => 'ai.cerb.automationBuilder.interaction.worker.await.promptEditor',
				],
				'interaction/respond_map' => [
					'label' => 'Map',
					'uri' => 'ai.cerb.automationBuilder.interaction.worker.await.map',
				],
				'interaction/respond_say' => [
					'label' => 'Say',
					'uri' => 'ai.cerb.automationBuilder.interaction.worker.await.say',
				],
				'interaction/prompt_sheet' => [
					'label' => 'Sheet',
					'uri' => 'ai.cerb.automationBuilder.interaction.worker.await.promptSheet',
				],
				'interaction/prompt_text' => [
					'label' => 'Text',
					'uri' => 'ai.cerb.automationBuilder.interaction.worker.await.promptText',
				]
			], // items
		];
		
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):await:' => [
					'draft:',
					'duration:',
					'form:',
					'interaction:',
					'record:',
				],
				
				'(.*):await:draft:' => [
					'uri:',
					'output:',
				],
				
				'(.*):await:duration:' => [
					'message: Waiting...',
					'until: 5 seconds',
				],
				
				'(.*):await:form:' => [
					'title:',
					'elements:',
				],
				'(.*):await:form:elements:' => [
					[
						'caption' => 'editor:',
						'snippet' => 'editor/${1:prompt_name}:',
					],
					[
						'caption' => 'fileUpload:',
						'snippet' => 'fileUpload/${1:prompt_name}:',
					],
					[
						'caption' => 'map:',
						'snippet' => 'map/${1:prompt_name}:',
					],
					[
						'caption' => 'say:',
						'snippet' => 'say/${1:prompt_name}:',
					],
					[
						'caption' => 'sheet:',
						'snippet' => 'sheet/${1:prompt_name}:',
					],
					[
						'caption' => 'submit:',
						'snippet' => 'submit:',
					],
					[
						'caption' => 'text:',
						'snippet' => 'text/${1:prompt_name}:',
					],
					[
						'caption' => 'textarea:',
						'snippet' => 'textarea/${1:prompt_name}:',
					],
				],
				
				'(.*):await:form:elements:editor:' => [
					'label:',
					'syntax:',
					'default:',
					'validation@raw:',
				],
				'(.*):await:form:elements:editor:syntax:' => [
					'cerb_query',
					'html',
					'json',
					'markdown',
					'text',
					'yaml',
				],
				
				'(.*):await:form:elements:fileUpload:' => [
					'label:',
					'validation@raw:',
				],
			
				// [TODO] Maps KATA	
				'(.*):await:form:elements:map:' => [
					'resource:',
					'projection:',
					'regions:',
					'points:',
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
					'validation@raw:',
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
					'card/key:',
					'date/key:',
					'icon/key:',
					'link/key:',
					'selection/key:',
					'slider/key:',
					'text/key:',
					'time_elapsed/key:',
				],
				'(.*):await:form:elements:sheet:schema:columns:card:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:card:params:' => [
					'bold@bool: yes',
					'context_key:',
					'icon:',
					'id_key:',
					'image@bool: yes',
					'label_key:',
					'underline@bool: yes',
				],
				'(.*):await:form:elements:sheet:schema:columns:card:params:icon:' => [
					'image:',
				],
				'(.*):await:form:elements:sheet:schema:columns:date:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:date:params:' => [
					'format: d-M-Y H:i:s T',
					'value: 1577836800',
					'value_key: updated',
				],
				'(.*):await:form:elements:sheet:schema:columns:icon:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:icon:params:' => [
					'image: circle-ok',
					'image_key: icon_key',
					'image_template@raw:',
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
				'(.*):await:form:elements:sheet:schema:columns:search:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:search:params:' => [
					'context: ticket',
					'query_key: query',
					'query_template@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:params:' => [
					'context: ticket',
					'query_key: query',
					'query_template@raw:',
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
					'fieldsets',
					'grid',
					'table',
				],
				
				'(.*):await:form:elements:submit:' => [
					'buttons:',
					'continue@bool: yes',
					'reset@bool: no',
				],
				
				'(.*):await:form:elements:submit:buttons:' => [
					[
						'caption' => 'continue:',
						'snippet' => "continue/\${1:yes}:\n  label: Continue\n  icon: circle-ok\n  icon_at: start\n  value: yes\n",
					],
					[
						'caption' => 'reset:',
						'snippet' => "reset:\n  label: Reset\n  icon: refresh\n  icon_at: start",
					],
				],
				
				'(.*):await:form:elements:submit:buttons:continue:' => [
					'label:',
					'icon:',
					'icon_at:',
					'style:',
					'value:',
				],
				
				'(.*):await:form:elements:submit:buttons:continue:icon_at:' => [
					'start',
					'end',
				],
				
				'(.*):await:form:elements:submit:buttons:continue:style:' => [
					'outline',
					'secondary',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:' => [
					'label:',
					'icon:',
					'icon_at:',
					'style:',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:icon_at:' => [
					'start',
					'end',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:style:' => [
					'outline',
					'secondary',
				],
				
				'(.*):await:form:elements:text:' => [
					'default:',
					'label:',
					'max_length@int:',
					'min_length@int:',
					'placeholder:',
					'required@bool: yes',
					'truncate@bool: yes',
					'type:',
					'validation@raw:',
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
					'required@bool: yes',
					'truncate@bool: yes',
					'validation@raw:',
				],
				
				'(.*):await:interaction:' => [
					'uri:',
					'output:',
				],
				
				'(.*):await:record:' => [
					'uri:',
					'output:',
				],
			]
		];
	}
}