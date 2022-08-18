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
		$syntax = $this->_data['syntax'] ?? null;
		$editor_readonly = boolval($this->_data['readonly'] ?? null);
		
		if(array_key_exists('line_numbers', $this->_data)) {
			$editor_show_line_numbers = boolval($this->_data['line_numbers'] ?? false);
		} else {
			$editor_show_line_numbers = true;
		}
		
		$editor_mode = '';
		$editor_autocompletion = '';
		
		switch($syntax) {
			case 'cerb_query_data':
				$editor_mode = 'ace/mode/cerb_query';
				$editor_autocompletion = 'data_query';
				break;
			
			case 'cerb_query':
			case 'cerb_query_search':
				$editor_mode = 'ace/mode/cerb_query';
				$editor_autocompletion = 'search_query';
				break;
			
			case 'kata':
				$editor_mode = 'ace/mode/cerb_kata';
				$schema = $this->_data['schema'] ?? [];
				$editor_autocompletion = \CerberusApplication::kataAutocompletions()->fromSchema(['schema' => $schema]);
				break;
		}
		
		$tpl->assign('var', $this->_key);
		$tpl->assign('label', $label);
		$tpl->assign('default', $default);
		$tpl->assign('editor_mode', $editor_mode);
		$tpl->assign('editor_autocompletion', $editor_autocompletion);
		$tpl->assign('editor_readonly', $editor_readonly);
		$tpl->assign('editor_show_line_numbers', $editor_show_line_numbers);
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/editor.tpl');
	}
}