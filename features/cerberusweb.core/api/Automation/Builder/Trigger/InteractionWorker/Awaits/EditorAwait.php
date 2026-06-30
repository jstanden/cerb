<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class EditorAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}
	
	function validate(_DevblocksValidationService $validation) {
		$prompt_label = $this->_data['label'] ?? null;
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		
		$validation->addField($this->_key, $prompt_label)
			->string()
			->setRequired($is_required)
		;
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		$label = $this->_data['label'] ?? null;
		$default = $this->_data['default'] ?? null;
		$options = $this->_data['options'] ?? [];
		$syntax = $this->_data['syntax'] ?? null;
		$record_type = $this->_data['record_type'] ?? null;
		$editor_readonly = boolval($this->_data['readonly'] ?? null);
		
		if(!is_array($options))
			$options = [];
		
		if(array_key_exists('line_numbers', $this->_data)) {
			$editor_show_line_numbers = boolval($this->_data['line_numbers'] ?? false);
		} else {
			$editor_show_line_numbers = true;
		}
		
		// The template picks the CerbUI editor straight off $syntax; only the autocomplete kind + markdown options
		// need deriving here. ('cerb_query*' → DataQuery/SearchQuery via $editor_autocompletion; 'kata' → KataEditor;
		// 'json' → JsonEditor; 'markdown' → MarkdownEditor; everything else → ScriptingEditor.)
		$editor_autocompletion = '';
		$editor_options = [];

		switch($syntax) {
			case 'cerb_query_data':
				$editor_autocompletion = 'data_query';
				break;

			case 'cerb_query':
			case 'cerb_query_search':
				$editor_autocompletion = 'search_query';
				break;

			case 'markdown':
				$editor_options = $options['markdown'] ?? [];
				break;

			case 'kata':
				$schema = $this->_data['schema'] ?? [];
				$editor_autocompletion = \CerberusApplication::kataAutocompletions()->fromSchema(['schema' => $schema]);
				break;
		}
		
		$toolbar_schema = $this->_data['toolbar'] ?? [];
		
		if($toolbar_schema) {
			$toolbar_dict = \DevblocksDictionaryDelegate::instance([
				'caller_name' => 'cerb.toolbar.interaction.worker.await.editor',
				
				'worker__context' => \CerberusContexts::CONTEXT_WORKER,
				'worker_id' => \CerberusApplication::getActiveWorker()->id ?? 0,
			]);
			
			if(is_array($toolbar_schema))
				$toolbar_schema = DevblocksPlatform::services()->kata()->emit($toolbar_schema);
			
			$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_schema, $toolbar_dict);
			$tpl->assign('editor_toolbar', $toolbar);
			$tpl->assign('editor_has_toolbar', true);
		} else {
			$tpl->assign('editor_toolbar', '');
			$tpl->assign('editor_has_toolbar', false);
		}
		
		$tpl->assign('var', $this->_key);
		$tpl->assign('label', $label);
		$tpl->assign('default', $default);
		$tpl->assign('syntax', $syntax);
		$tpl->assign('editor_autocompletion', $editor_autocompletion);
		$tpl->assign('record_type', $record_type);
		$tpl->assign('editor_options', $editor_options);
		$tpl->assign('editor_readonly', $editor_readonly);
		$tpl->assign('editor_show_line_numbers', $editor_show_line_numbers);
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/editor.tpl');
	}
}