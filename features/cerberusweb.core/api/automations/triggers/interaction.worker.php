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
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):await:' => [
					[
						'caption' => 'form:',
						'snippet' => "form:\n\ttitle: Form Title\n\telements:\n\t\t\${1:}",
						'score' => 2000,
						'description' => "Display a form and wait for valid user input",
					],
					[
						'caption' => 'interaction:',
						'snippet' => "interaction:\n\t",
						'score' => 1999,
						'description' => "Run an interaction and wait for completion",
					],
					[
						'caption' => 'draft:',
						'snippet' => "draft:\n\t",
						'description' => "Open the email editor popup and wait for completion",
					],
					[
						'caption' => 'duration:',
						'snippet' => "duration:\n\t",
						'description' => "Wait for an amount of time",
					],
					[
						'caption' => 'record:',
						'snippet' => "record:\n\t",
						'description' => "Open a record editor popup and wait for completion",
					],
				],
				
				'(.*):await:draft:' => [
					[
						'caption' => 'uri:',
						'snippet' => "uri:",
						'score' => 2000,
						'description' => "The draft record to open in the editor",
					],
					[
						'caption' => 'output:',
						'snippet' => "output: \${1:results}",
					],
				],
				'(.*):await:draft:uri' => [
					'type' => 'cerb-uri',
					'params' => [
						'draft' => null,	
					]			
				],
				
				'(.*):await:duration:' => [
					'message: Waiting...',
					'until: 5 seconds',
				],
				
				'(.*):await:form:' => [
					[
						'caption' => 'title:',
						'snippet' => "title:",
						'score' => 2000,
					],
					[
						'caption' => 'elements:',
						'snippet' => "elements:",
						'score' => 1999,
					],
				],
				'(.*):await:form:elements:' => [
					[
						'caption' => 'editor:',
						'snippet' => "editor/\${1:prompt_editor}:\n\t\${2:}",
						'description' => "Prompt with a code editor",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.promptEditor',
					],
					[
						'caption' => 'fileUpload:',
						'snippet' => "fileUpload/\${1:prompt_file}:\n\t\${2:}",
						'description' => "Prompt for a file upload",
					],
					[
						'caption' => 'map:',
						'snippet' => "map/\${1:prompt_map}:\n\t\${2:}",
						'description' => "Display an interactive map",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.map',
					],
					[
						'caption' => 'say:',
						'snippet' => "say/\${1:prompt_say}:\n\t\${2:}",
						'description' => "Display arbitrary plaintext or Markdown",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.say',
					],
					[
						'caption' => 'sheet:',
						'snippet' => "sheet/\${1:prompt_sheet}:\n\t\${2:}",
						'description' => "Prompt using a table with single/multiple selection, filtering, and paging",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.promptSheet',
					],
					[
						'caption' => 'submit:',
						'snippet' => "submit:\n\t\${1:}",
						'description' => "Prompt for one or more submit actions",
					],
					[
						'caption' => 'text:',
						'snippet' => "text/\${1:prompt_text}:\n\t\${2:}",
						'description' => "Prompt for a line of text",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.promptText',
					],
					[
						'caption' => 'textarea:',
						'snippet' => "textarea/\${1:prompt_textarea}:\n\t\${2:}",
						'description' => "Prompt for multiple lines of text",
					],
				],
				
				'(.*):await:form:elements:editor:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'syntax:',
					'default:',
					'validation@raw:',
					'required@bool:',
				],
				'(.*):await:form:elements:editor:syntax:' => [
					'cerb_query_data',
					'cerb_query_search',
					'html',
					'json',
					'kata',
					'markdown',
					'text',
					'yaml',
				],
				
				'(.*):await:form:elements:fileUpload:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
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
					[
						'caption' => 'content:',
						'snippet' => "content@text:\n\t\${1:}",
						'score' => 2000,
						'description' => "Display Markdown formatted text",
					],
					[
						'caption' => 'message:',
						'snippet' => "message@text:\n\t\${1:}",
						'score' => 1999,
						'description' => "Display plaintext without formatting",
					],
				],
				
				'(.*):await:form:elements:sheet:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					[
						'caption' => 'data:',
						'snippet' => "data:\n\t\${1:}",
						'score' => 1999,
					],
					[
						'caption' => 'schema:',
						'snippet' => "schema:\n\tlayout:\n\t\t\${1:}\n\tcolumns:\n\t\t\${2:}",
						'score' => 1998,
					],
					'default:',
					'limit:',
					'page:',
					'required@bool: yes',
					'validation@raw:',
				],
				'(.*):await:form:elements:sheet:data:' => [
					[
						'caption' => 'automation:',
						'snippet' => "automation:\n  uri: cerb:automation:\${1:cerb.data.records}\n  inputs:\n    record_type: ticket\n    query_required: status:o\n",
					],
					[
						'caption' => '(manual)',
						'snippet' => "0:\n  key: key1\n  value: value1\n1:\n  key: key2\n  value: value2\n",
					]
				],
				'(.*):await:form:elements:sheet:data:automation:' => [
					[
						'caption' => 'uri:',
						'snippet' => "uri:",
						'score' => 2000,
					],
					[
						'caption' => 'inputs:',
						'snippet' => "inputs:\n\t\${1:}",
						'score' => 1999,
					],
				],
				'(.*):await:form:elements:sheet:data:automation:inputs:' => [
					'type' => 'automation-inputs',
				],
				'(.*):await:form:elements:sheet:data:automation:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.ui.sheet.data',
							]
						]
					]
				],
				'(.*):await:form:elements:sheet:schema:' => [
					'columns:',
					'layout:',
				],
				'(.*):await:form:elements:sheet:schema:columns:' => [
					[
						'caption' => 'card:',
						'snippet' => "card/\${1:_label}:",
					],
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
					'context:',
					'context_key:',
					'context_template@raw:',
					'icon:',
					'id:',
					'id_key:',
					'id_template@raw:',
					'image@bool: yes',
					'label:',
					'label_key:',
					'label_template@raw:',
					'underline@bool: yes',
				],
				'(.*):await:form:elements:sheet:schema:columns:card:params:icon:' => [
					[
						'caption' => 'image: circle-ok',
						'snippet' => "image: \${1:circle-ok}",
					],
					'image_key: icon_key',
					'image_template@raw:',
					'record_uri@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:card:params:image:' => [
					'type' => 'icon'
				],
				'(.*):await:form:elements:sheet:schema:columns:card:params:record_uri:' => [
					'type' => 'cerb-uri',
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
					[
						'caption' => 'image: circle-ok',
						'snippet' => "image: \${1:circle-ok}",
					],
					'image_key: icon_key',
					'image_template@raw:',
					'record_uri@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:icon:params:image:' => [
					'type' => 'icon',
				],
				'(.*):await:form:elements:sheet:schema:columns:icon:params:record_uri:' => [
					'type' => 'cerb-uri',
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
					[
						'caption' => 'context: ticket',
						'snippet' => "context: \${1:ticket}",
					],
					'query_key: query',
					'query_template@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:search:params:context:' => [
					'type' => 'record-type',
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:params:' => [
					[
						'caption' => 'context: ticket',
						'snippet' => "context: \${1:ticket}",
					],
					'query_key: query',
					'query_template@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:params:context:' => [
					'type' => 'record-type',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:params:' => [
					[
						'caption' => 'mode:',
						'snippet' => "mode: \${1:single}",
						'description' => "`single` or `multiple` row selection",
					],
					'label: Description',
					'label_key: description',
					'label_template@raw: {{description}}',
					'value: 123',
					'value_key: key',
					'value_template@raw: {{key}}',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:params:mode:' => [
					'single',
					'multiple',
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
					[
						'caption' => 'image: circle-ok',
						'snippet' => "image: \${1:circle-ok}",
					],
					'image_key: icon_key',
					'image_template@raw:',
					'record_uri@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:params:icon:image:' => [
					'type' => 'icon',
				],
				'(.*):await:form:elements:sheet:schema:columns:text:params:icon:record_uri:' => [
					'type' => 'cerb-uri',
				],
				'(.*):await:form:elements:sheet:schema:columns:time_elapsed:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:time_elapsed:params:' => [
					'precision: 2',
					'value: 123',
					'value_key: key',
					'value_template@raw: {{key}}',
				],
				'(.*):await:form:elements:sheet:schema:layout:' => [
					'filtering@bool: yes',
					'headings@bool: yes',
					'paging@bool: yes',
					[
						'caption' => 'params:',
						'snippet' => "params:\n  \${1:}",
					],
					[
						'caption' => 'style:',
						'snippet' => "style: \${1:table}",
					],
					[
						'caption' => 'title_column:',
						'snippet' => "title_column: \${1:_label}",
						'description' => "The column to emphasize as the row title",
					],
				],
				'(.*):await:form:elements:sheet:schema:layout:style:' => [
					[
						'caption' => 'table',
						'snippet' => 'table',
						'description' => "Display the rows as a table",
						'score' => 2000,
					],
					[
						'caption' => 'columns',
						'snippet' => 'columns',
						'description' => "Display items as columns",
					],
					[
						'caption' => 'fieldsets',
						'snippet' => 'fieldsets',
						'description' => "Display the rows as fieldsets",
					],
					[
						'caption' => 'grid',
						'snippet' => 'grid',
						'description' => "Display the rows as a grid",
					],
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
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'icon:',
					'icon_at:',
					'style:',
					'value:',
				],
				
				'(.*):await:form:elements:submit:buttons:continue:icon:' => [
					'type' => 'icon',
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
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'icon:',
					'icon_at:',
					'style:',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:icon' => [
					'type' => 'icon',
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
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'default:',
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
					[
						'caption' => 'output:',
						'snippet' => "output: \${1:results}",
					],
					'uri:',
				],
				'(.*):await:interaction:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.interaction.worker',
							]
						]
					]
				],
				
				'(.*):await:record:' => [
					'uri:',
					[
						'caption' => 'output:',
						'snippet' => "output: \${1:results}",
					],
				],
				'(.*):await:record:uri:' => [
					'type' => 'cerb-uri',
				],
			]
		];
	}
}
