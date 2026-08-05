<?php
class AutomationTrigger_InteractionWebsite extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.interaction.website';
	
	public static function getFormComponentMeta(): array {
		return [
			'end' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\EndAwait', 'icon' => 'stop'],
			'fileUpload' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\FileUploadAwait', 'icon' => 'upload'],
			'llmTranscript' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\LlmTranscriptAwait', 'icon' => 'conversation'],
			'say' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SayAwait', 'icon' => 'comments'],
			'sheet' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SheetAwait', 'icon' => 'table'],
			'submit' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\SubmitAwait', 'icon' => 'send'],
			'text' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\TextAwait', 'icon' => 'text'],
			'textarea' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits\TextareaAwait', 'icon' => 'text'],
		];
	}

	// The public-safe sheet column types allowed when designing an `await:form` sheet in the Sheet Builder —
	// mirrors the restricted whitelist in InteractionWebsite/Awaits/SheetAwait.php.
	public static function getSheetColumnTypes() : array {
		return ['date', 'link', 'markdown', 'selection', 'slider', 'text', 'time_elapsed'];
	}

	// The website Awaits take a 4th portal-schema arg (worker Awaits are 3-arg). For the design-time form builder
	// and the simulator preview a default-styled schema is sufficient (custom per-portal styling is applied at
	// runtime); an empty array yields all-default accessors.
	public static function newFormComponent(string $type, $var, $value, $data) : ?object {
		if(!($class = static::getFormComponentClass($type)))
			return null;
		return new $class($var, $value, $data, new CerbPortalWebsiteInteractions_Model([]));
	}

	// Render the preview as the customer-facing portal popup (portal chrome) with the portal's own stylesheet, so
	// components (sheet grid, buttons, inputs, transcript) look like they do on the public site.
	public static function getFormPreviewPresentation() : array {
		return [
			'chrome' => 'portal',
			'stylesheets' => [
				['p' => 'cerb.website.interactions', 'f' => 'css/cerb.css'],
			],
		];
	}

	// Inspector descriptors for the form builder — the public-safe subset of the worker schema (no chooser/query/
	// chart/map/agentPrompt/audio/editor/fileDownload). Same shape: title, has_var, fields[], `new` starter config.
	public static function getFormComponentSchema() : array {
		return [
			'text' => [
				'title' => 'Text',
				'has_var' => true,
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'type', 'label' => 'Type', 'input' => 'select', 'default' => 'freeform', 'options' => ['freeform','bool','date','decimal','email','geopoint','ip','ipv4','ipv6','number','password','timestamp','uri','url']],
					['key' => 'placeholder', 'label' => 'Placeholder', 'input' => 'text'],
					['key' => 'default', 'label' => 'Default', 'input' => 'text'],
					['key' => 'required', 'label' => 'Required', 'input' => 'bool'],
					['key' => 'min_length', 'label' => 'Min length', 'input' => 'number'],
					['key' => 'max_length', 'label' => 'Max length', 'input' => 'number'],
					['key' => 'hidden', 'label' => 'Hidden', 'input' => 'bool'],
				],
				'new' => ['label' => 'Text:'],
			],
			'textarea' => [
				'title' => 'Text area',
				'has_var' => true,
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'placeholder', 'label' => 'Placeholder', 'input' => 'text'],
					['key' => 'default', 'label' => 'Default', 'input' => 'multiline'],
					['key' => 'required', 'label' => 'Required', 'input' => 'bool'],
					['key' => 'min_length', 'label' => 'Min length', 'input' => 'number'],
					['key' => 'max_length', 'label' => 'Max length', 'input' => 'number'],
					['key' => 'hidden', 'label' => 'Hidden', 'input' => 'bool'],
				],
				'new' => ['label' => 'Text:'],
			],
			'say' => [
				'title' => 'Say',
				'has_var' => false,
				'fields' => [
					['key' => 'content', 'label' => 'Content (Markdown)', 'input' => 'multiline'],
					['key' => 'hidden', 'label' => 'Hidden', 'input' => 'bool'],
				],
				'new' => ['content' => 'Sample message text.'],
			],
			'sheet' => [
				'title' => 'Sheet',
				'has_var' => true,
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'default', 'label' => 'Default', 'input' => 'text'],
					['key' => 'limit', 'label' => 'Limit', 'input' => 'number'],
					['key' => 'required', 'label' => 'Required', 'input' => 'bool'],
					['key' => 'hidden', 'label' => 'Hidden', 'input' => 'bool'],
					['key' => 'data', 'label' => 'Data (KATA)', 'input' => 'kata'],
					['key' => 'schema', 'label' => 'Schema (KATA)', 'input' => 'kata'],
				],
				// No placeholder data/schema — a blank element opens the Sheet Builder in its default state
				// (Records→Ticket, a card/_label column, table layout), matching the standalone tool.
				'new' => [
					'label' => 'Select:',
				],
			],
			'fileUpload' => [
				'title' => 'File upload',
				'has_var' => true,
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'placeholder', 'label' => 'Placeholder', 'input' => 'text'],
					['key' => 'as', 'label' => 'Store as', 'input' => 'select', 'default' => 'attachment', 'options' => ['attachment','automation_resource']],
					['key' => 'required', 'label' => 'Required', 'input' => 'bool'],
					['key' => 'hidden', 'label' => 'Hidden', 'input' => 'bool'],
				],
				'new' => ['label' => 'Upload:'],
			],
			'llmTranscript' => [
				'title' => 'Transcript',
				'has_var' => true,
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'session_id', 'label' => 'Session ID', 'input' => 'text'],
				],
				'new' => [],
			],
			'submit' => [
				'title' => 'Submit',
				'has_var' => false,
				'fields' => [
					['key' => 'continue', 'label' => 'Continue button', 'input' => 'bool', 'default' => true],
					['key' => 'reset', 'label' => 'Reset button', 'input' => 'bool', 'default' => true],
					['key' => 'is_automatic', 'label' => 'Automatic submit', 'input' => 'bool'],
					['key' => 'hidden', 'label' => 'Hidden', 'input' => 'bool'],
				],
				'new' => ['continue' => true, 'reset' => true],
			],
			// `end` has no curated inspector — like the worker family it falls back to the raw KATA editor.
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
				'type' => 'text',
				'notes' => 'The name of the interaction.',
			],
			[
				'key' => 'interaction_params',
				'type' => 'text',
				'notes' => 'Arbitrary interaction parameters.',
			],
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'portal_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'portal',
				],
				'notes' => 'The portal record.',
			],
			[
				'key' => 'client_ip',
				'type' => 'text',
				'notes' => 'The client IP address.',
			],
			[
				'key' => 'client_browser_name',
				'type' => 'text',
				'notes' => 'The client browser name (e.g. Safari).',
			],
			[
				'key' => 'client_browser_platform',
				'type' => 'text',
				'notes' => 'The client browser platform (e.g. Macintosh).',
			],
			[
				'key' => 'client_browser_version',
				'type' => 'text',
				'notes' => 'The client browser version.',
			],
		];
	}

	// Pre-fill the client_* fields from the current worker's own request (the same signals App.php reads at runtime).
	function getSimulationInputs() : array {
		return $this->_mockClientSimulationDefaults(parent::getSimulationInputs());
	}

	function getOutputsMeta() : array {
		return [
			'return' => [
				[
					'key' => 'redirect_url',
					'notes' => 'Redirect the visitor to a URL'
				]
			],
		];
	}
	
	function getUsageMeta(string $automation_name): array {
		$db = DevblocksPlatform::services()->database();
		
		$results = [];
		
		// Automations
		if(($linked_automations = DAO_Automation::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_Automation::SCRIPT),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_automations = array_filter($linked_automations, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w->script, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_automations)
				$results['automation'] = array_column($linked_automations, 'id');
		}
		
		// Portals
		if(($linked_portals = $db->GetArrayReader(sprintf("select id, property_value from community_tool inner join community_tool_property on (community_tool_property.tool_code=community_tool.code) where extension_id in ('cerb.website.interactions') and property_key in ('automations_kata') and property_value like %s",
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_portals = array_filter($linked_portals, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w['property_value'], false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_portals)
				$results['portal'] = array_column($linked_portals, 'id');
		}
		
		return $results;
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
						'snippet' => "form:\n\ttitle: \${1:Form Title}\n\telements:\n\t\t",
						'score' => 2000,
						'description' => "Display a form and wait for valid user input",
					],
					[
						'caption' => 'interaction:',
						'snippet' => "interaction:\n\t",
						'score' => 1999,
						'description' => "Run an interaction and wait for completion",
					],
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
						'caption' => 'fileUpload:',
						'snippet' => "fileUpload/\${1:prompt_file}:\n\t\${2:}",
						'description' => "Prompt for one or more file uploads",
					],
					[
						'caption' => 'llmTranscript:',
						'snippet' => "llmTranscript/\${1:prompt_transcript}:\n\t\${2:}",
						'description' => "Display an LLM transcript",
					],
					[
						'caption' => 'say:',
						'snippet' => "say/\${1:prompt_say}:\n\t\${2:}",
						'description' => "Display arbitrary plaintext or Markdown",
					],
					[
						'caption' => 'sheet:',
						'snippet' => "sheet/\${1:prompt_sheet}:\n\t\${2:}",
						'description' => "Prompt using a table with single/multiple selection, filtering, and paging",
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
					],
					[
						'caption' => 'textarea:',
						'snippet' => "textarea/\${1:prompt_textarea}:\n\t\${2:}",
						'description' => "Prompt for multiple lines of text",
					]
				],
				
				'(.*):await:form:elements:fileUpload:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'accept: .png,image/png,.jpg,image/jpeg',
					'hidden@bool: yes',
					'multiple@bool: yes',
					'required@bool: yes',
					'validation@raw:',
					[
						'caption' => 'validation: (image/png)',
						'snippet' => "validation@raw:\n\t{% if prompt_file_mime_type != 'image/png' %}\n\tThe file must be a PNG image ({{prompt_file_mime_type}})\n\t{% elseif prompt_file_size > 1024000 %}\n\tThe file ({{prompt_file_size|bytes_pretty}}) must be smaller than 1MB.\n\t{% endif %}",
					]
				],
				
				'(.*):await:form:elements:llmTranscript:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'session_id: a1b2c3d4-a1b2-c3d4-e5f6-a1b2c3d4e5f6',
					'hidden@bool: yes',
				],
				
				'(.*):await:form:elements:say:' => [
					[
						'caption' => 'content:',
						'snippet' => "content@text:\n\t\${1:}",
						'score' => 2000,
						'description' => "Display Markdown formatted text",
					],
					'hidden@bool: yes',
					[
						'caption' => 'message:',
						'snippet' => "message@text:\n\t\${1:}",
						'score' => 1999,
						'description' => "Display plaintext without formatting",
					],
					[
						'caption' => 'references:',
						'snippet' => "references:\n\tresource/\${1:example}:\n\t\turi:",
						'description' => "Add image resources as references",
					],
					'styles@csv:',
				],
				'(.*):await:form:elements:say:references:' => [
					[
						'caption' => 'resource:',
						'snippet' => "resource:\n\tresource/\${1:example}:\n\t\turi:",
						'description' => "Load an image resources",
					],
				],
				'(.*):await:form:elements:say:references:resource:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'resource' => [
							'types' => [
								ResourceType_PortalImage::ID,
							]
						]
					]
				],
				'(.*):await:form:elements:say:styles:' => [
					'text-center',
					'text-large',
					'text-left',
					'text-right',
					'text-small',
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
					'hidden@bool: yes',
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
					'date/key:',
					'link/key:',
					'markdown/key:',
					'selection/key:',
					'slider/key:',
					'text/key:',
					'time_elapsed/key:',
				],
				'(.*):await:form:elements:sheet:schema:columns:date:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:date:params:' => [
					'format: d-M-Y H:i:s T',
					'value: 1577836800',
					'value_key: updated',
				],
				'(.*):await:form:elements:sheet:schema:columns:link:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:link:params:' => [
					'href: https://example.com/',
					'href_key: record_url',
					'href_template@raw:',
					'href_new_tab@bool: yes',
					'text: Link title',
					'text_key: _label',
					'text_template@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:markdown:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:markdown:params:' => [
					'value: **Markdown**',
					'value_key: key',
					'value_template@raw: {{key}}',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:params:' => [
					'mode: single',
					'mode: multiple',
					'label: Description',
					'label_key: description',
					'label_template@raw: {{description}}',
					'value: 123',
					'value_key: key',
					'value_template@raw: {{key}}',
				],
				'(.*):await:form:elements:sheet:schema:columns:slider:' => [
					'label:',
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
					'label:',
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
					'label:',
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
				'(.*):await:form:elements:sheet:schema:layout:style:' => [
					'buttons',
					'fieldsets',
					'scale',
					'table',
				],
				
				'(.*):await:form:elements:submit:' => [
					'buttons:',
					'continue@bool: no',
					'reset@bool: no',
					'is_automatic@bool: yes',
				],
				
				'(.*):await:form:elements:submit:buttons:' => [
					[
						'caption' => 'continue:',
						'snippet' => "continue/\${1:yes}:\n\tlabel: Continue\n\ticon: circle-ok\n\ticon_at: start\n\tvalue: yes\n",
					],
					[
						'caption' => 'reset:',
						'snippet' => "reset:\n\tlabel: Reset\n\ticon: refresh\n\ticon_at: start",
					],
				],
				
				'(.*):await:form:elements:submit:buttons:continue:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'hidden@bool: yes',
					'icon:',
					'icon_at:',
					'size:',
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
				
				'(.*):await:form:elements:submit:buttons:continue:size:' => [
					'whole',
					'half',
					'third',
					'quarter',
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
					'hidden@bool: yes',
					'icon:',
					'icon_at:',
					'size:',
					'style:',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:icon' => [
					'type' => 'icon',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:icon_at:' => [
					'start',
					'end',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:size:' => [
					'whole',
					'half',
					'third',
					'quarter',
				],
				
				'(.*):await:form:elements:submit:buttons:reset:style:' => [
					'outline',
					'secondary',
				],
				
				'(.*):await:form:elements:text:' => [
					'default:',
					'hidden@bool: yes',
					'label:',
					'max_length@int:',
					'min_length@int:',
					'placeholder:',
					'required@bool:',
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
					'hidden@bool: yes',
					'label:',
					'max_length@int:',
					'min_length@int:',
					'placeholder:',
					'required@bool:',
					'truncate@bool: yes',
					'validation@raw:',
				],
				
				'(.*):await:interaction:' => [
					'inputs:',
					[
						'caption' => 'output:',
						'snippet' => "output: \${1:results}",
					],
					'uri:',
				],
				'(.*):await:interaction:inputs:' => [
					'type' => 'automation-inputs',
				],
				'(.*):await:interaction:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.interaction.website',
							]
						]
					]
				],
			],
		];
	}
}