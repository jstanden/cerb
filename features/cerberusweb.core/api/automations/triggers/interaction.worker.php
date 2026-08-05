<?php
class AutomationTrigger_InteractionWorker extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.interaction.worker';
	
	public static function getFormComponentMeta() {
		return [
			'agentPrompt' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\AgentPromptAwait', 'icon' => 'bot-message'],
			'audio' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\AudioAwait', 'icon' => 'speaker'],
			'chart' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\ChartAwait', 'icon' => 'chart-line'],
			'chooser' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\ChooserAwait', 'icon' => 'search'],
			'editor' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\EditorAwait', 'icon' => 'editor'],
			'end' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\EndAwait', 'icon' => 'stop'],
			'fileDownload' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\FileDownloadAwait', 'icon' => 'download'],
			'fileUpload' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\FileUploadAwait', 'icon' => 'upload'],
			'llmTranscript' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\LlmTranscriptAwait', 'icon' => 'conversation'],
			'map' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\MapAwait', 'icon' => 'map'],
			'query' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\QueryAwait', 'icon' => 'search'],
			'say' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\SayAwait', 'icon' => 'comments'],
			'sheet' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\SheetAwait', 'icon' => 'table'],
			'submit' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\SubmitAwait', 'icon' => 'send'],
			'text' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\TextAwait', 'icon' => 'text'],
			'textarea' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\TextareaAwait', 'icon' => 'text'],
		];
	}

	// The sheet column types allowed when designing an `await:form` sheet element in the Sheet Builder — the full
	// set for the worker context (mirrors SheetAwait's withDefaultTypes()).
	public static function getSheetColumnTypes() : array {
		return \Cerb\Sheets\SheetBuilder::allowedColumnTypes();
	}

	/**
	 * Inspector descriptors for the form builder: per component type, the config fields to edit + a `new`
	 * starter config used when the component is dropped onto the canvas. Types with no curated field set
	 * fall back to `raw` (edit the element's KATA body directly). A distilled, inspector-friendly view of
	 * the `getAutocompleteSuggestions()` config maps — kept in sync by hand.
	 */
	public static function getFormComponentSchema() : array {
		return [
			'text' => [
				'title' => 'Text',
				'has_var' => true,
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'type', 'label' => 'Type', 'input' => 'select', 'default' => 'freeform', 'options' => ['freeform','bool','date','decimal','email','geopoint','ip','ipv4','ipv6','number','password','record_type','timestamp','uri','url']],
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
			'chooser' => [
				'title' => 'Chooser',
				'has_var' => true,
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'record_type', 'label' => 'Record type', 'input' => 'text'],
					['key' => 'query', 'label' => 'Query', 'input' => 'multiline'],
					['key' => 'default', 'label' => 'Default', 'input' => 'text'],
					['key' => 'multiple', 'label' => 'Allow multiple', 'input' => 'bool'],
					['key' => 'autocomplete', 'label' => 'Autocomplete', 'input' => 'bool'],
					['key' => 'required', 'label' => 'Required', 'input' => 'bool'],
					['key' => 'hidden', 'label' => 'Hidden', 'input' => 'bool'],
				],
				'new' => ['label' => 'Choose:', 'record_type' => 'ticket'],
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
			'submit' => [
				'title' => 'Submit',
				'has_var' => false,
				// Rendered as a mutually-exclusive Visible/Hidden/Automatic mode in the builder inspector.
				'fields' => [
					['key' => 'continue', 'label' => 'Continue button', 'input' => 'bool', 'default' => true],
					['key' => 'reset', 'label' => 'Reset button', 'input' => 'bool', 'default' => true],
					['key' => 'is_automatic', 'label' => 'Automatic submit', 'input' => 'bool'],
					['key' => 'hidden', 'label' => 'Hidden', 'input' => 'bool'],
				],
				'new' => ['continue' => true, 'reset' => true],
			],
			'query' => [
				'title' => 'Query',
				'has_var' => true,
				// record_type is rendered as a record-type SelectMenu by the builder inspector.
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'record_type', 'label' => 'Record type', 'input' => 'text'],
					['key' => 'default', 'label' => 'Default', 'input' => 'multiline'],
					['key' => 'required', 'label' => 'Required', 'input' => 'bool'],
					['key' => 'hidden', 'label' => 'Hidden', 'input' => 'bool'],
				],
				'new' => ['label' => 'Search:', 'record_type' => 'ticket'],
			],
			'chart' => [
				'title' => 'Chart',
				'has_var' => true,
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'datasets', 'label' => 'Datasets (KATA)', 'input' => 'kata'],
					['key' => 'schema', 'label' => 'Schema (KATA)', 'input' => 'kata'],
				],
				'new' => [
					'label' => 'Chart:',
					'datasets' => "manual/series0:\n  data:\n    series_name@csv: 1,2,3",
					'schema' => "data:\n  series:\n    series0:",
				],
			],
			'map' => [
				'title' => 'Map',
				'has_var' => true,
				// resource is rendered as a SelectMenu of map resources by the builder inspector.
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
				],
				'new' => ['resource_uri' => 'cerb:resource:map.world.countries'],
			],
			'llmTranscript' => [
				'title' => 'Transcript',
				'has_var' => true,
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'session_id', 'label' => 'Session ID', 'input' => 'text'],
					['key' => 'view', 'label' => 'View', 'input' => 'select', 'default' => 'toggle', 'options' => ['toggle','markdown','text']],
					['key' => 'layout', 'label' => 'Layout', 'input' => 'select', 'default' => 'interleaved', 'options' => ['conversation','interleaved']],
					['key' => 'thinking', 'label' => 'Thinking', 'input' => 'select', 'default' => 'summary', 'options' => ['summary','raw','hide']],
					['key' => 'tools', 'label' => 'Tool calls', 'input' => 'select', 'default' => 'summary', 'options' => ['summary','raw','hide']],
					['key' => 'expand', 'label' => 'Expand raw', 'input' => 'select', 'default' => 'latest', 'options' => ['latest','all','none']],
					['key' => 'tokens', 'label' => 'Token counts', 'input' => 'bool'],
				],
				'new' => [],   // no default label (transcripts rarely show one); session_id seeded with a sample UUID client-side
			],
			'agentPrompt' => [
				'title' => 'Agent prompt',
				'has_var' => true,
				// models are rendered as a guided list (presets + per-model props) by the builder inspector.
				// `references:` (what `@` completes — `workers:` / `filesystems:`) is authored as raw KATA for
				// now; it's an open map keyed by volume handle, so it needs the same guided-list treatment as
				// `models:` rather than a flat field.
				'fields' => [
					['key' => 'label', 'label' => 'Label', 'input' => 'text'],
					['key' => 'placeholder', 'label' => 'Placeholder', 'input' => 'text'],
				],
				'new' => [],   // no default label (agent prompts rarely show one); session_id + a default model seeded client-side
			],
		];
	}

	// Map resources offered in the builder's map inspector (uri + label).
	public static function getMapResources() : array {
		return [
			['uri' => 'cerb:resource:map.world.countries', 'label' => 'World — countries'],
			['uri' => 'cerb:resource:map.country.usa.states', 'label' => 'USA — states'],
			['uri' => 'cerb:resource:map.country.usa.counties', 'label' => 'USA — counties'],
		];
	}

	// Model presets for the agentPrompt builder — working defaults per model.
	public static function getAgentModelPresets() : array {
		return [
			['id' => 'claude_fable', 'label' => 'Claude Fable 5', 'provider' => 'anthropic', 'model' => 'claude-fable-5', 'vision' => true, 'context_window' => 1000000],
			['id' => 'claude_opus', 'label' => 'Claude Opus 4.8', 'provider' => 'anthropic', 'model' => 'claude-opus-4-8', 'vision' => true, 'context_window' => 1000000],
			['id' => 'claude_sonnet', 'label' => 'Claude Sonnet 5', 'provider' => 'anthropic', 'model' => 'claude-sonnet-5', 'vision' => true, 'context_window' => 200000],
			['id' => 'claude_haiku', 'label' => 'Claude Haiku 4.5', 'provider' => 'anthropic', 'model' => 'claude-haiku-4-5-20251001', 'vision' => true, 'context_window' => 200000],
			['id' => 'gpt', 'label' => 'GPT-5.6 Sol', 'provider' => 'openai', 'model' => 'gpt-5.6-sol', 'vision' => true, 'context_window' => 400000],
			['id' => 'gemini_flash', 'label' => 'Gemini Flash', 'provider' => 'gemini', 'model' => 'gemini-2.5-flash', 'vision' => true, 'context_window' => 1000000],
		];
	}

	// Every chat-capable LLM provider, for the agentPrompt "+ Model" menu + per-model config: icon, friendly
	// label, known model ids (TextChooser suggestions), and the default API endpoint (placeholder for overrides).
	// Delegates to the LLM service, which is the shared source for every model-card host (this trigger's
	// `agentPrompt` inspector and the worker profile's AI tab).
	public static function getAgentProviders() : array {
		return DevblocksPlatform::services()->llm()->getAgentProviders();
	}

	// The enabled `agent_model` records the agentPrompt design-time pickers offer (Automation Builder wizard +
	// Form Builder inspector): a model row REFERENCES one by name, and the record supplies provider/model/auth/
	// vision/context window. Disabled (retired) records are excluded — they can't be referenced.
	public static function getAgentModelChoices() : array {
		$out = [];

		foreach(\DAO_AgentModel::getAll() as $model) {
			if($model->is_disabled)
				continue;

			$out[] = [
				'name' => $model->name,
				'provider' => $model->provider,
				'model' => $model->model,
				'icon' => $model->getDisplayIcon(),
				'vision' => (bool) $model->has_vision,
				'context_window' => intval($model->context_window),
				'description' => $model->description,
			];
		}

		return $out;
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
				'type' => 'text',
				'notes' => 'The caller which started the interaction.',
			],
			[
				'key' => 'caller_params',
				'notes' => 'Built-in parameters based on the caller type.',
			],
			[
				'key' => 'client_ip',
				'type' => 'text',
				'notes' => 'The client IP address.',
			],
			[
				'key' => 'client_url',
				'type' => 'text',
				'notes' => 'The client current URL.',
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
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'worker_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'worker',
				],
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}

	// Pre-fill the client_* fields from the current worker's own request (worker_* already defaults to you).
	function getSimulationInputs() : array {
		return $this->_mockClientSimulationDefaults(parent::getSimulationInputs());
	}

	function getOutputsMeta() : array {
		return [
			'return' => [
				[
					'key' => 'alert',
					'notes' => 'Display the given message at the top of the browser',
				],
				[
					'key' => 'callout',
					'notes' => 'Show a floating tooltip relative to a DOM element selector',
				],
				[
					'key' => 'clipboard',
					'notes' => 'Copy the given text to the browser clipboard',
				],
				[
					'key' => 'explore_page',
					'notes' => 'The next page in an explore interaction (if applicable)',
				],
				[
					'key' => 'open_link',
					'notes' => 'Open a new browser tab with the given URL',
				],
				[
					'key' => 'open_url',
					'notes' => 'Open the given URL in the current browser tab',
				],
				[
					'key' => 'search',
					'notes' => 'Open a search popup with a `record_type:` and `query:`',
				],
				[
					'key' => 'snippet',
					'notes' => 'Insert the given text at the cursor in the current editor (if applicable)',
				],
				[
					'key' => 'timer',
					'notes' => 'Start time tracking with the given time entry record ID',
				]
			]
		];
	}
	
	function getUsageMeta(string $automation_name): array {
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
		
		// Toolbars
		if(($linked_toolbar_sections = DAO_ToolbarSection::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_ToolbarSection::TOOLBAR_KATA),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_toolbar_sections = array_filter($linked_toolbar_sections, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w->toolbar_kata, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_toolbar_sections)
				$results['toolbar_section'] = array_column($linked_toolbar_sections, 'id');
		}
		
		// Card widgets
		if(($linked_card_widgets = DAO_CardWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_CardWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'cerb.card.widget.automation',
				'cerb.card.widget.chart.timeblocks',
				'cerb.card.widget.fields',
				'cerb.card.widget.form_interaction',
				'cerb.card.widget.sheet',
			])),
			Cerb_ORMHelper::escape(DAO_CardWidget::EXTENSION_PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_card_widgets = array_filter($linked_card_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'cerb.card.widget.automation' => $w->extension_params['automation_kata'] ?? '',
					'cerb.card.widget.chart.timeblocks' => implode(' ', [$w->extension_params['datasets_kata'] ?? '', $w->extension_params['timeblocks_kata'] ?? '']),
					'cerb.card.widget.fields' => $w->extension_params['toolbar_kata'] ?? '',
					'cerb.card.widget.form_interaction' => $w->extension_params['interactions_kata'] ?? '',
					'cerb.card.widget.sheet' => implode(' ', [$w->extension_params['data_query'] ?? '', $w->extension_params['sheet_kata'] ?? '', $w->extension_params['toolbar_kata'] ?? '']),
				};
				
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				
				return in_array($automation_name, $tokens);
			});
			
			if($linked_card_widgets)
				$results['card_widget'] = array_column($linked_card_widgets, 'id');
		}
		
		// Profile widgets
		if(($linked_profile_widgets = DAO_ProfileWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_ProfileWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'cerb.profile.tab.widget.automation',
				'cerb.profile.tab.widget.chart.timeblocks',
				'cerb.profile.tab.widget.fields',
				'cerb.profile.tab.widget.form_interaction',
				'cerb.profile.tab.widget.map.geopoints',
				'cerb.profile.tab.widget.sheet',
			])),
			Cerb_ORMHelper::escape(DAO_ProfileWidget::EXTENSION_PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_profile_widgets = array_filter($linked_profile_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'cerb.profile.tab.widget.automation' => $w->extension_params['automations_kata'] ?? '',
					'cerb.profile.tab.widget.chart.timeblocks' => implode(' ', [$w->extension_params['datasets_kata'] ?? '', $w->extension_params['timeblocks_kata'] ?? '']),
					'cerb.profile.tab.widget.fields' => $w->extension_params['toolbar_kata'] ?? '',
					'cerb.profile.tab.widget.form_interaction' => $w->extension_params['interactions_kata'] ?? '',
					'cerb.profile.tab.widget.geopoints' => $w->extension_params['map_kata'] ?? '',
					'cerb.profile.tab.widget.sheet' => implode(' ', [$w->extension_params['data_query'] ?? '', $w->extension_params['sheet_kata'] ?? '', $w->extension_params['toolbar_kata'] ?? '']),
					default => '',
				};
				
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				
				return in_array($automation_name, $tokens);
			});
			
			if($linked_profile_widgets)
				$results['profile_widget'] = array_column($linked_profile_widgets, 'id');
		}
		
		// Project board column
		if(($linked_project_board_columns = DAO_ProjectBoardColumn::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_ProjectBoardColumn::TOOLBAR_KATA),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_project_board_columns = array_filter($linked_project_board_columns, function($w) use ($automation_name) {
				$content = implode(' ', [$w->toolbar_kata, $w->functions_kata]);
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_project_board_columns)
				$results['project_board_column'] = array_column($linked_project_board_columns, 'id');
		}
		
		// Workspace widgets
		if(($linked_workspace_widgets = DAO_WorkspaceWidget::getWhere(sprintf("%s IN (%s) AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_WorkspaceWidget::EXTENSION_ID),
			implode(',', Cerb_ORMHelper::qstrArray([
				'core.workspace.widget.automation',
				'cerb.workspace.widget.chart.kata',
				'cerb.workspace.widget.chart.timeblocks',
				'core.workspace.widget.form_interaction',
				'cerb.workspace.widget.map.geopoints',
				'core.workspace.widget.record.fields',
				'core.workspace.widget.sheet',
			])),
			Cerb_ORMHelper::escape(DAO_WorkspaceWidget::PARAMS_JSON),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_workspace_widgets = array_filter($linked_workspace_widgets, function($w) use ($automation_name) {
				$content = match($w->extension_id) {
					'core.workspace.widget.automation' => $w->params['automations_kata'] ?? '',
					'cerb.workspace.widget.chart.kata' => implode(' ', [$w->params['datasets_kata'] ?? '', $w->params['chart_kata'] ?? '']),
					'cerb.workspace.widget.chart.timeblocks' => implode(' ', [$w->params['datasets_kata'] ?? '', $w->params['timeblocks_kata'] ?? '']),
					'core.workspace.widget.form_interaction' => $w->params['interactions_kata'] ?? '',
					'cerb.workspace.widget.map.geopoints' => implode(' ', [$w->params['map_kata'] ?? '', $w->params['automation']['map_clicked'] ?? '']),
					'core.workspace.widget.record.fields' => $w->params['toolbar_kata'] ?? '',
					'core.workspace.widget.sheet' => implode(' ', [$w->params['data_query'] ?? '', $w->params['sheet_kata'] ?? '', $w->params['toolbar_kata'] ?? '']),
					default => '',
				};
				
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				
				return in_array($automation_name, $tokens);
			});
			
			if($linked_workspace_widgets)
				$results['workspace_widget'] = array_column($linked_workspace_widgets, 'id');
		}
		
		return $results;
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		$toolbar_keyprefix = '(.*):await:form:elements:(.*):(.*):?toolbar:';
		
		$suggestions = [
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
				
				$toolbar_keyprefix => [
					[
						'caption' => 'interaction:',
						'snippet' => 'interaction/${1:name}:'
					],
					[
						'caption' => 'menu:',
						'snippet' => 'menu/${1:name}:'
					]
				],
				$toolbar_keyprefix . '(.*):?interaction:' => [
					[
						'caption' => 'uri:',
						'snippet' => 'uri: cerb:automation:${1:}'
					],
					'label:',
					'icon:',
					'tooltip:',
					[
						'caption' => 'hidden:',
						'snippet' => 'hidden@bool: ${1:yes}'
					],
					[
						'caption' => 'badge:',
						'snippet' => 'badge: 123'
					],
					'inputs:'
				],
				$toolbar_keyprefix . '(.*):?interaction:hidden:' => [
					'yes',
					'no',
				],
				$toolbar_keyprefix . '(.*):?interaction:icon:' => [
					'type' => 'icon'
				],
				$toolbar_keyprefix . '(.*):?interaction:inputs:' => [
					'type' => 'automation-inputs'
				],
				$toolbar_keyprefix . '(.*):?interaction:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.interaction.worker'
							]
						]
					]
				],
				$toolbar_keyprefix . '(.*):?menu:' => [
					'label:',
					[
						'caption' => 'hidden:',
						'snippet' => 'hidden@bool: ${1:yes}'
					],
					'icon:',
					'tooltip:',
					'items:'
				],
				$toolbar_keyprefix . '(.*):?menu:icon:' => [
					'type' => 'icon'
				],
				$toolbar_keyprefix . '(.*):?menu:items:' => [
					[
						'caption' => 'interaction:',
						'snippet' => 'interaction/${1:name}:'
					],
					[
						'caption' => 'menu:',
						'snippet' => 'menu/${1:name}:'
					]
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
						'caption' => 'agentPrompt:',
						'snippet' => "agentPrompt/\${1:prompt_agent}:\n\t\${2:}",
						'description' => "An agentic chat input (model picker, @-mentions, /-commands, image paste)",
					],
					[
						'caption' => 'audio:',
						'snippet' => "audio/\${1:prompt_audio}:\n\t\${2:}",
						'description' => "Play an audio file",
					],
					[
						'caption' => 'chart:',
						'snippet' => "chart/\${1:prompt_chart}:\n\t\${2:}",
						'description' => "Display a chart visualization",
					],
					[
						'caption' => 'chooser:',
						'snippet' => "chooser/\${1:prompt_chooser}:\n\t\${2:}",
						'description' => "Open a search popup for selecting records of a given type",
					],
					[
						'caption' => 'editor:',
						'snippet' => "editor/\${1:prompt_editor}:\n\t\${2:}",
						'description' => "Prompt with a code editor",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.promptEditor',
					],
					[
						'caption' => 'fileDownload:',
						'snippet' => "fileDownload/\${1:prompt_file}:\n\t\${2:}",
						'description' => "Prompt for a file download",
					],
					[
						'caption' => 'fileUpload:',
						'snippet' => "fileUpload/\${1:prompt_file}:\n\t\${2:}",
						'description' => "Prompt for a file upload",
					],
					[
						'caption' => 'llmTranscript:',
						'snippet' => "llmTranscript/\${1:prompt_transcript}:\n\t\${2:}",
						'description' => "Display an LLM transcript",
					],
					[
						'caption' => 'map:',
						'snippet' => "map/\${1:prompt_map}:\n\t\${2:}",
						'description' => "Display an interactive map",
						'interaction' => 'ai.cerb.automationBuilder.interaction.worker.await.map',
					],
					[
						'caption' => 'query:',
						'snippet' => "query/\${1:prompt_query}:\n\t\${2:}",
						'description' => "Prompt for a search query with autocompletion",
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
				
				'(.*):await:form:elements:agentPrompt:' => [
						[
							'caption' => 'label:',
							'snippet' => "label: \${1:Label:}",
							'score' => 2000,
						],
						'placeholder: Message the agent…',
						'session_id: a1b2c3d4-a1b2-c3d4-e5f6-a1b2c3d4e5f6',
						'default:',
						'required@bool: no',
						'hidden@bool: no',
						[
							'caption' => 'agent:',
							'snippet' => "agent: \${1:@cerb}",
							'score' => 1990,
							'docHTML' => 'The AI worker this prompt is for &mdash; an <code>@mention</code>, a bare handle, an id, or <code>cerb:worker:&lt;id|mention&gt;</code> (the same shapes <code>llm.agent:</code> accepts).<br><br>Supplies the model catalog via the agent\'s <b>model router</b>, so a portable interaction can name an agent instead of naming models. <b>Omit both this and <code>models:</code></b> and the system default router is used.',
						],
						[
							'caption' => 'models:',
							'snippet' => "models:",
							'description' => "The model catalog offered in the chat picker: each key is an agent_model record NAME (the same reference grammar as llm.agent:/llm.chat: model:); the first enabled one is the default. Overrides ride under each name in that model's provider grammar. Omit it to use the agent's router, or the system default.",
						],
						[
							'caption' => 'commands:',
							'snippet' => "commands:\n\trewrite/\${1:flatten}:\n\t\tdescription: \${2:Aggressively compact the thread}\n\t\ttext@text: \${3:/compact hard}",
							'docHTML' => 'The <code>/slash</code> commands this composer OFFERS. A <b>bare key</b> opts into a built-in by naming it (<code>compact:</code>); <code>rewrite/&lt;name&gt;:</code> defines a NEW command that <b>expands to text in the browser before submit</b>, so <code>llm.agent</code> only ever sees the expansion. Independent of the <code>llm.agent</code> node\'s own <code>commands:</code>, which decides what the agent ACTS on &mdash; an alias can shadow a built-in, and expanding to one the node didn\'t opt into just reaches the model as prose.',
						],
						[
							'caption' => 'references:',
							'snippet' => "references:",
							'docHTML' => 'What <code>@</code> autocompletes, <b>opt-in</b> — with no <code>references:</code> block <code>@</code> completes nothing. Add <code>workers:</code> for <code>@handle</code> mentions and <code>filesystems:</code> (keyed by volume name) for <code>@&lt;volume&gt;/&lt;path&gt;</code> file references. Independent of what <code>llm.agent</code> actually mounts: declaring a volume here only offers its paths for completion, and a reference that doesn\'t resolve is just text the agent says it can\'t find.',
						],
						'validation@raw:',
					],
					// The AI workers, by @mention -- same list `llm.agent:inputs:agent:` offers.
					'(.*):await:form:elements:agentPrompt:agent:' =>
						DevblocksPlatform::services()->llm()->getKataAgentWorkerAutocomplete(),

					'(.*):await:form:elements:agentPrompt:references:' => [
						[
							'caption' => 'workers:',
							'snippet' => "workers:",
							'docHTML' => 'Complete worker <code>@handle</code> mentions — what every agentPrompt did implicitly before <code>references:</code> existed.',
						],
						[
							'caption' => 'filesystems:',
							'snippet' => "filesystems:",
							'docHTML' => 'Complete <code>@&lt;volume&gt;/&lt;path&gt;</code> file references. Each key is an agent filesystem name; typing matches the volume and path together as a subsequence, so <code>mountpatexa</code> finds <code>mount/path/example.md</code>.',
						],
					],
					// The real volume names, same static list `llm.agent:inputs:mounts:` uses — the set is small
					// and cached, so an AJAX suggestion type would be overkill.
					'(.*):await:form:elements:agentPrompt:references:filesystems:' => array_values(
						array_map(
							function($filesystem) { /* @var $filesystem Model_AgentFilesystem */
								$doc = array_filter([
									$filesystem->is_disabled ? '(disabled)' : '',
									$filesystem->description,
									sprintf('%d file%s, %s',
										$filesystem->file_count,
										(1 == $filesystem->file_count) ? '' : 's',
										DevblocksPlatform::strPrettyBytes($filesystem->total_bytes)
									),
								]);

								return [
									'caption' => $filesystem->name . ':',
									'snippet' => $filesystem->name . ":\n",
									'docHTML' => '<b>' . DevblocksPlatform::strEscapeHtml($filesystem->name) . '</b><br>'
										. DevblocksPlatform::strEscapeHtml(implode(' — ', $doc)),
								];
							},
							DAO_AgentFilesystem::getAll()
						)
					),
					// The models catalog autocompletes agent_model record NAMES, and under each name that record's
					// OWN provider knobs — the same helper `llm.agent:`/`llm.chat: model:` use — plus the
					// agentPrompt-only per-model knobs (effort_choices / disabled / compaction).
					...DevblocksPlatform::services()->llm()->getKataAgentModelAutocomplete(
						'(.*):await:form:elements:agentPrompt:models:',
						[ // extra block keys (appended to every model — overrides of record capabilities + agentPrompt-only knobs)
							'context_window: 200000',
							'vision@bool: yes',
							[
								'caption' => 'effort_choices:',
								'snippet' => "effort_choices: \${1:medium,high,xhigh,max}",
								'description' => "Reasoning-effort levels to offer for this model as a submenu in the chat picker (comma-separated or a @list). The fixed `effort:` is the default (pre-selected / used when the model is picked without a submenu choice). agentPrompt-only.",
							],
							'disabled@bool: no',
							[
								'caption' => 'disabled@bool: {{…}}',
								'snippet' => 'disabled@bool: {{${1:worker_over_budget}}}',
								'description' => "Conditionally hide this model from the picker (resolved per turn). A disabled model drops out and the first remaining model (definition order) becomes the default — the rate-limit/budget fallback.",
							],
							[
								'caption' => 'compaction:',
								'snippet' => "compaction:\n\t\tsummarize@bool: yes\n\t\tcontext_ratio: \${1:0.9}\n\t\ttail_ratio: \${2:0.05}",
								'description' => "Per-model compaction: at context_ratio of the model's context_window, summarize the window into a new root node and keep a tail_ratio verbatim tail. summarize@bool:no truncates instead. Rides the session on resume.",
							],
						],
						[ // extra value sub-paths (per provider)
							'vision:' => ['yes', 'no'],
							'context_window:' => ['128000', '200000', '1000000'],
							'effort_choices:' => ['low,medium,high', 'medium,high,xhigh,max', 'minimal,low,medium,high'],
							'disabled:' => [
								'yes',
								'no',
								['caption' => '{{…}}', 'snippet' => '{{${1:worker_over_budget}}}'],
							],
							'compaction:' => [
								'summarize@bool: yes',
								'context_ratio: 0.9',
								'tail_ratio: 0.05',
							],
						]
					),
					'(.*):await:form:elements:agentPrompt:commands:' => [
						[
							'caption' => 'rewrite/',
							'snippet' => "rewrite/\${1:flatten}:\n\tdescription: \${2:Aggressively compact the thread}\n\ttext@text: \${3:/compact hard}",
							'score' => 2000,
							'docHTML' => 'A command this element DEFINES: typing <code>/&lt;name&gt;</code> is replaced by its <code>text:</code> before the message is sent, with anything typed after the command left intact (<code>/flatten now</code> &rarr; <code>/compact hard now</code>). A bare key instead (<code>compact:</code>) opts into a built-in by naming it.',
						],
					],
					// A `rewrite/flatten:` key normalizes to the path segment `rewrite:` (CerbUI.KataEditor
					// truncates each segment at `/`), so the typed form gets its own sub-key set.
					'(.*):await:form:elements:agentPrompt:commands:rewrite:' => [
						[
							'caption' => 'text@text:',
							'snippet' => "text@text: \${1:/compact hard}",
							'score' => 2000,
							'docHTML' => 'What <code>/&lt;name&gt;</code> expands to. A one-liner splices in place; a multi-line block is followed by a blank line before whatever the worker typed after the command.',
						],
						'description:',
					],
					'(.*):await:form:elements:agentPrompt:commands:(.*):' => [
						[
							'caption' => 'label:',
							'snippet' => "label: /\${1:summarize}",
							'score' => 2000,
						],
						'description:',
					],

					'(.*):await:form:elements:audio:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'autoplay@bool: yes',
					'controls@bool: yes',
					'hidden@bool: no',
					'loop@bool: no',
					'source:',
				],
				'(.*):await:form:elements:audio:source:' => [
					'blob: data:audio/mpeg;base64,...',
					'uri:'
				],
				'(.*):await:form:elements:audio:source:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'resource' => null,
						'automation_resource' => null,
					],
				],
				
				'(.*):await:form:elements:chart:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'hidden@bool: no',
					'datasets:',
					'schema:',
				],
				
				'(.*):await:form:elements:chooser:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'default:',
					'hidden@bool: no',
					'record_type:',
					'query@text:',
					'multiple@bool: yes',
					'required@bool: yes',
					'autocomplete@bool: no',
				],
				'(.*):await:form:elements:chooser:record_type:' => [
					'type' => 'record-type',
				],
				'(.*):await:form:elements:chooser:is_multiple:' => [
					'yes',
					'no',
				],
				
				'(.*):await:form:elements:editor:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'syntax:',
					'default:',
					[
						'caption' => 'record_type:',
						'snippet' => "record_type: \${1::}",
						'description' => "The record type whose fields autocomplete in a `cerb_query_search` editor",
					],
					'hidden@bool: no',
					'validation@raw:',
					'readonly@bool: yes',
					'line_numbers@bool: no',
					'toolbar:',
				],
				'(.*):await:form:elements:editor:record_type:' => [
					'type' => 'record-type',
				],
				'(.*):await:form:elements:editor:line_numbers:' => [
					'yes',
					'no',
				],
				'(.*):await:form:elements:editor:readonly:' => [
					'yes',
					'no',
				],
				'(.*):await:form:elements:editor:options:' => [
					'markdown:',
				],
				'(.*):await:form:elements:editor:options:markdown:' => [
					'paste_images@bool: yes',
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
				
				'(.*):await:form:elements:fileDownload:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'hidden@bool: no',
					[
						'caption' => 'uri:',
						'snippet' => "uri: cerb:\${1:}",
						'score' => 1999,
					],
					[
						'caption' => 'filename:',
						'snippet' => "filename: \${1:example.zip}",
						'score' => 1998,
					],
				],
				'(.*):await:form:elements:fileDownload:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'attachment' => null,
						'automation_resource' => null,
						'resource' => null,
					],
				],
				
				'(.*):await:form:elements:fileUpload:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'as:',
					'required@bool: yes',
					'validation@raw:',
				],
				'(.*):await:form:elements:fileUpload:as:' => [
					'attachment',
					'automation_resource',
				],
				
				'(.*):await:form:elements:llmTranscript:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					'session_id: a1b2c3d4-a1b2-c3d4-e5f6-a1b2c3d4e5f6',
					'hidden@bool: yes',
					[
						'caption' => 'view:',
						'snippet' => "view: \${1:toggle}",
						'docHTML' => '<b>view:</b> <code>toggle</code> (offer Markdown/Text), <code>markdown</code>, or <code>text</code>.',
					],
					[
						'caption' => 'layout:',
						'snippet' => "layout: \${1:conversation}",
						'docHTML' => '<b>layout:</b> <code>interleaved</code> (default &mdash; a step log; each preamble sits directly above the tool it prompted) or <code>conversation</code> (the agent\'s text pools as one flowing answer with its work in a sub-thread below).',
					],
					[
						'caption' => 'thinking:',
						'snippet' => "thinking: \${1:summary}",
						'docHTML' => '<b>thinking:</b> <code>summary</code> (default), <code>raw</code> (the full reasoning), or <code>hide</code>.',
					],
					[
						'caption' => 'expand:',
						'snippet' => "expand: \${1:latest}",
						'docHTML' => '<b>expand:</b> Which <code>raw</code> tool/thinking bubbles start open: <code>latest</code> (the newest agent turn only), <code>all</code>, or <code>none</code>.',
					],
					[
						'caption' => 'tools:',
						'snippet' => "tools: \${1:summary}",
						'docHTML' => '<b>tools:</b> <code>summary</code> (default), <code>raw</code> (the ACTUAL request/response payloads &mdash; often data the reader isn\'t cleared for), or <code>hide</code>. Summaries come from the session\'s <code>llm.agent:inputs:tools:…:labels:</code>.',
					],
					[
						'caption' => 'tokens@bool:',
						'snippet' => "tokens@bool: \${1:yes}",
						'docHTML' => '<b>tokens@bool:</b> Show a per-turn In/Out/Cached token chip on agent turns. Off by default.',
					],
				],
				'(.*):await:form:elements:llmTranscript:view:' => [
					'toggle',
					'markdown',
					'text',
				],
				'(.*):await:form:elements:llmTranscript:layout:' => [
					'conversation',
					'interleaved',
				],
				'(.*):await:form:elements:llmTranscript:thinking:' => [
					'summary',
					'raw',
					'hide',
				],
				'(.*):await:form:elements:llmTranscript:tools:' => [
					'summary',
					'raw',
					'hide',
				],
				'(.*):await:form:elements:llmTranscript:expand:' => [
					'latest',
					'all',
					'none',
				],
				
				'(.*):await:form:elements:map:' => [
					[
						'caption' => 'resource:',
						'snippet' => "resource:\n\turi: cerb:resource:\${1:}",
						'score' => 2000,
					],
					'hidden@bool: no',
					'projection:',
					[
						'caption' => 'regions:',
						'snippet' => "regions:\n\tproperties:\n\t#label:\n\t#filter:\n\t#fill:",
						'description' => "Define the shapes used in the base map (countries, states, etc.)",
						'score' => 1999,
					],
					'points:',
				],

				'(.*):await:form:elements:map:resource:' => [
					[
						'caption' => 'uri:',
						'snippet' => "uri: cerb:resource:\${1:}",
						'score' => 2000,
					],
				],

				'(.*):await:form:elements:map:resource:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'resource' => [
							'types' => [
								ResourceType_Map::ID,
							]
						]
					],
				],

				'(.*):await:form:elements:map:projection:' => [
					'type:',
					'scale:',
					'center:',
					'zoom:',
				],
				
				'(.*):await:form:elements:map:projection:type:' => [
					'mercator',
					'albersUsa',
				],
				
				'(.*):await:form:elements:map:projection:center:' => [
					'latitude:',
					'longitude:',
				],
				
				'(.*):await:form:elements:map:projection:zoom:' => [
					'latitude:',
					'longitude:',
					'scale:',
				],
				
				'(.*):await:form:elements:map:regions:' => [
					'properties:',
					'label:',
					'filter:',
					'fill:',
				],
				
				'(.*):await:form:elements:map:regions:properties:' => [
					'resource:',
					[
						'caption' => 'resource:',
						'snippet' => "resource:\n  uri: cerb:resource:\${1:}",
						'score' => 2000,
					],
					'data:',
					'join:',
				],
				
				'(.*):await:form:elements:map:regions:properties:resource:' => [
					[
						'caption' => 'uri:',
						'snippet' => "uri: cerb:resource:\${1:}",
						'score' => 2000,
					],
				],

				'(.*):await:form:elements:map:regions:properties:resource:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'resource' => [
							'types' => [
								ResourceType_MapProperties::ID,
							]
						]
					]
				],
				
				'(.*):await:form:elements:map:regions:properties:join:' => [
					[
						'caption' => 'property:',
						'snippet' => "property: \${1:name}",
						'score' => 2000,
					],
					'case:',
				],
				
				'(.*):await:form:elements:map:regions:properties:join:case:' => [
					[
						'caption' => 'upper',
						'snippet' => "upper",
						'description' => "Normalize values of keys to upper case",
						'score' => 2000,
					],
					[
						'caption' => 'lower',
						'snippet' => 'lower',
						'description' => "Normalize values of keys to lower case",
						'score' => 1999,
					],
				],
				
				'(.*):await:form:elements:map:regions:label:' => [
					[
						'caption' => 'title:',
						'snippet' => "title: \${1:key}",
						'description' => 'Define the property use as title',
						'score' => 2000,
					],
					[
						'caption' => 'properties:',
						'snippet' => "properties:\n  key:\n    label: Value\n    format: number\n  #key2:\n    #label: Value 2\n    #format: number",
						'description' => 'Define the properties which should be displayed',
						'score' => 1999,
					],
				],
				
				'(.*):await:form:elements:map:regions:filter:' => [
					[
						'caption' => 'property:',
						'snippet' => "property: \${1:key}",
						'score' => 2001,
					],
					[
						'caption' => 'is:',
						'snippet' => "is: Value",
						'score' => 2000,
					],
					[
						'caption' => 'is@list:',
						'snippet' => "is@list:\n  Value 1\n  Value 2\n  Value 3",
						'score' => 1999,
					],
					[
						'caption' => 'is@csv:',
						'snippet' => "is@csv: Value 1, Value 2, Value 3",
						'score' => 1998,
					],
					[
						'caption' => 'not:',
						'snippet' => "not: Value",
						'score' => 1997,
					],
					[
						'caption' => 'not@list:',
						'snippet' => "not@list:\n  Value 1\n  Value 2\n  Value 3",
						'score' => 1996,
					],
					[
						'caption' => 'not@csv:',
						'snippet' => "not@csv: Value 1, Value 2, Value 3",
						'score' => 1996,
					],
				],
				
				'(.*):await:form:elements:map:regions:fill:' => [
					[
						'caption' => 'color_key:',
						'snippet' => "color_key:\n\tproperty: key",
						'description' => "Select colors directly from a property.",
						'score' => 2000,
					],
					[
						'caption' => 'color_map:',
						'snippet' => "color_map:\n\tproperty: key\n\tcolors:\n\t\t1: gray\n\t\t2: blue\n\t\t3: green\n\t\t4: orange\n\t\t5: red",
						'description' => "Associate colors with specific property values",
						'score' => 1999,
					],
					[
						'caption' => 'choropleth:',
						'snippet' => "choropleth:\n\tproperty: key\n\tclasses: value",
						'description' => "Interpolate color intensity on a scale based on a numeric property",
						'score' => 1998,
					],
				],
				
				'(.*):await:form:elements:map:points:' => [
					[
						'caption' => 'resource:',
						'snippet' => "resource:\n  uri: cerb:resource:\${1:}",
						'score' => 2000,
					],
					[
						'caption' => 'data:',
						'snippet' => "data:\n\tpoint/berlin:\n\t\tlatitude: 52.549636074382285\n\t\tlongitude: 13.403320312499998\n\t\tproperties:\n\t\t\tname: Berlin\n\t\t\tcountry: Germany\n\t\t\tcontinent: Europe\n\tpoint/los_angeles:\n\t\tlatitude: 34.08906131584994\n\t\tlongitude: 241.69921874999997\n\t\tproperties:\n\t\t\tname: Los Angeles\n\t\t\tcountry: United States of America\n\t\t\tcontinent: North America",
						'description' => "Select colors directly from a property",
						'score' => 1999,
					],
					[
						'caption' => 'label:',
						'snippet' => "label:\n\ttitle: key\n\tproperties:\n\t\tkey:\n\t\t\tlabel: Value\n\t\tkey2:\n\t\t\tlabel: Value 2\n\t\t\tformat: number",
						'description' => "Define the properties which should be displayed",
						'score' => 1998,
					],
					[
						'caption' => 'filter:',
						'snippet' => "filter:\n\tproperty: \${1:key}\n\t#is@list:\n\t\t#Value 1\n\t\t#Value 2",
						'score' => 1997,
					],
					[
						'caption' => 'size:',
						'snippet' => "size:\n\tdefault: \${1:2.5}\n\t#value_map:",
						'score' => 1996,
					],
					[
						'caption' => 'fill:',
						'snippet' => "fill:\n\tdefault: \${1:red}\n\t#color_map:\n\t\t#property: key\n\t\t#colors: \n\t\t\t#1: red\n\t\t\t#2: blue\n\t\t\t#3: green",
						'score' => 1995,
					],
				],
				
				'(.*):await:form:elements:map:points:resource:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'resource' => [
							'types' => [
								ResourceType_MapPoints::ID,
							]
						]
					],
				],
				
				'(.*):await:form:elements:map:points:filter:' => [
					[
						'caption' => 'is:',
						'snippet' => "is: Value",
						'score' => 2000,
					],
					[
						'caption' => 'is@list:',
						'snippet' => "is@list:\n  Value 1\n  Value 2\n  Value 3",
						'score' => 1999,
					],
					[
						'caption' => 'is@csv:',
						'snippet' => "is@csv: Value 1, Value 2, Value 3",
						'score' => 1998,
					],
					[
						'caption' => 'not:',
						'snippet' => "not: Value",
						'score' => 1997,
					],
					[
						'caption' => 'not@list:',
						'snippet' => "not@list:\n  Value 1\n  Value 2\n  Value 3",
						'score' => 1996,
					],
					[
						'caption' => 'not@csv:',
						'snippet' => "not@csv: Value 1, Value 2, Value 3",
						'score' => 1996,
					],
					[
						'caption' => 'property:',
						'snippet' => "property: \${1:key}",
						'score' => 1995,
					],
				],
				
				'(.*):await:form:elements:map:points:size:' => [
					[
						'caption' => 'default:',
						'snippet' => "default: \${1:2.5}",
						'score' => 2000,
					],
					[
						'caption' => 'value_map:',
						'snippet' => "value_map:\n\tproperty: \${1:key}\n\tvalues:\n\t\t1: 5.0\n\t\t2: 7.5",
						'score' => 1999,
					],
				],
				
				'(.*):await:form:elements:map:points:fill:' => [
					[
						'caption' => 'default:',
						'snippet' => "default: \${1:red}",
						'score' => 2000,
					],
					[
						'caption' => 'color_map:',
						'snippet' => "color_map:\n\tproperty: \${1:key}\n\tcolors: \n\t\t1: red\n\t\t2: blue\n\t\t3: green",
						'score' => 1999,
					],
				],
				
				'(.*):await:form:elements:query:' => [
					[
						'caption' => 'label:',
						'snippet' => "label: \${1:Label:}",
						'score' => 2000,
					],
					[
						'caption' => 'record_type:',
						'snippet' => "record_type: \${1::}",
						'score' => 1999,
					],
					'hidden@bool: no',
					'default:',
				],
				'(.*):await:form:elements:query:record_type:' => [
					'type' => 'record-type',
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
					'hidden@bool: no',
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
					'hidden@bool: no',
					'limit:',
					'page:',
					'required@bool: yes',
					'toolbar:',
					'validation@raw:',
				],
				'(.*):await:form:elements:sheet:data:' => [
					[
						'caption' => 'automation:',
						'snippet' => "automation:\n\turi: cerb:automation:\${1:cerb.data.records}\n\tinputs:\n\t\trecord_type: ticket\n\t\tquery_required: status:o\n",
					],
					[
						'caption' => '(manual)',
						'snippet' => "0:\n\tkey: key1\n\tvalue: value1\n1:\n\tkey: key2\n\tvalue: value2\n",
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
					'interaction/key:',
					'link/key:',
					'selection/key:',
					'slider/key:',
					'text/key:',
					'time_elapsed/key:',
					'toolbar/key:',
				],
				'(.*):await:form:elements:sheet:schema:columns:card:' => [
					'label:',
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
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
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
					'label:',
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
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
				],
				'(.*):await:form:elements:sheet:schema:columns:icon:params:image:' => [
					'type' => 'icon',
				],
				'(.*):await:form:elements:sheet:schema:columns:icon:params:record_uri:' => [
					'type' => 'cerb-uri',
				],
				'(.*):await:form:elements:sheet:schema:columns:interaction:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:interaction:params:' => [
					'inputs:',
					'text: Link title',
					'text_key: _label',
					'text_template@raw:',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
					'uri:',
					'uri_key:',
					'uri_template@raw:',
				],
				'(.*):await:form:elements:sheet:schema:columns:interaction:params:inputs:' => [
					'type' => 'automation-inputs',
				],
				'(.*):await:form:elements:sheet:schema:columns:interaction:params:uri:' => [
					'type' => 'cerb-uri',
					'params' => [
						'automation' => [
							'triggers' => [
								'cerb.trigger.interaction.worker',
							]
						]
					]
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
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%'
				],
				'(.*):await:form:elements:sheet:schema:columns:markdown:' => [
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:markdown:params:' => [
					'value: **Markdown**',
					'value_key: key',
					'value_template@raw: {{key}}',
				],
				'(.*):await:form:elements:sheet:schema:columns:search:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:search:params:' => [
					[
						'caption' => 'context: ticket',
						'snippet' => "context: \${1:ticket}",
					],
					'query_key: query',
					'query_template@raw:',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%'
				],
				'(.*):await:form:elements:sheet:schema:columns:search:params:context:' => [
					'type' => 'record-type',
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:params:' => [
					[
						'caption' => 'context: ticket',
						'snippet' => "context: \${1:ticket}",
					],
					'query_key: query',
					'query_template@raw:',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%'
				],
				'(.*):await:form:elements:sheet:schema:columns:search_button:params:context:' => [
					'type' => 'record-type',
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:' => [
					'label:',
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
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%'
				],
				'(.*):await:form:elements:sheet:schema:columns:selection:params:mode:' => [
					'single',
					'multiple',
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
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
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
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
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
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:time_elapsed:params:' => [
					'precision: 2',
					'value: 123',
					'value_key: key',
					'value_template@raw: {{key}}',
					'color@raw:',
					'text_color@raw:',
					'text_size@raw: 150%',
				],
				'(.*):await:form:elements:sheet:schema:columns:toolbar:' => [
					'label:',
					'params:',
				],
				'(.*):await:form:elements:sheet:schema:columns:toolbar:params:' => [
					'kata:',
				],
				'(.*):await:form:elements:sheet:schema:layout:' => [
					'filtering@bool: yes',
					'headings@bool: yes',
					'paging@bool: yes',
					[
						'caption' => 'params:',
						'snippet' => "params:\n\t\${1:}",
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
				'(.*):await:form:elements:sheet:toolbar:' => [
				],
				
				'(.*):await:form:elements:submit:' => [
					'buttons:',
					'continue@bool: yes',
					'hidden@bool: no',
 					'is_automatic@bool: yes',
					'reset@bool: no',
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
					'hidden@bool: yes',
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
					'hidden@bool: no',
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
					'password',
					'uri',
					'url',
				],
				
				'(.*):await:form:elements:textarea:' => [
					'default:',
					'hidden@bool: no',
					'label:',
					'max_length@int:',
					'min_length@int:',
					'placeholder:',
					'required@bool: yes',
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
		
		// Chart schema
		$suggestions['*'] += CerberusApplication::kataAutocompletions()->chart(
			'(.*?):await:form:elements:chart:schema:',
			true
		);
		
		// Dataset
		$suggestions['*'] += CerberusApplication::kataAutocompletions()->dataset(
			'(.*?):await:form:elements:chart:datasets:',
		);
		
		$suggestions['*']['(.*):return:callout:'] = [
			'selector: #someElement',
			'message: This is the callout text',
			'my: center bottom',
			'at: center top'
		];
		
		$suggestions['*']['(.*):return:search:'] = [
			'record_type: ticket',
			'query: status:o',
		];
		
		$suggestions['*']['(.*):return:'] = [
			'alert:',
			'callout:',
			'clipboard:',
			'open_link:',
			'open_url:',
			'search:',
			'snippet:',
			'timer:',
		];
		
		return $suggestions;
	}
}
