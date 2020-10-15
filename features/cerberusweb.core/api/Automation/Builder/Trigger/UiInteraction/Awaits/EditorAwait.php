<?php
namespace Cerb\Automation\Builder\Trigger\UiInteraction\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationExecution;

class EditorAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationExecution $execution) {
		return false;
	}
	
	function validate(_DevblocksValidationService $validation) {
		@$prompt_label = $this->_data['label'];
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		
		$validation->addField($this->_key, $prompt_label)
			->string()
			->setRequired($is_required)
		;
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function render(Model_AutomationExecution $execution) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$label = $this->_data['label'];
		@$default = $this->_data['default'];
		@$mode = $this->_data['mode'];
		
		$tpl->assign('var', $this->_key);
		$tpl->assign('label', $label);
		$tpl->assign('default', $default);
		$tpl->assign('editor_mode', $mode);
		
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/ui.interaction/await/editor.tpl');
	}
}