<?php
namespace Cerb\Automation\Builder\Trigger\UiInteraction\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationExecution;

class TextareaAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationExecution $execution) {
		return false;
	}
	
	function validate(_DevblocksValidationService $validation) {
		@$prompt_label = $this->_data['label'];
		
		$field = $validation->addField($this->_key, $prompt_label);
		
		$field_type = $field
			->string()
			->setMaxLength('24 bits')
		;
		
		if(array_key_exists('required', $this->_data) && $this->_data['required'])
			$field_type->setRequired(true);
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function render(Model_AutomationExecution $execution) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$label = $this->_data['label'];
		@$placeholder = $this->_data['placeholder'];
		@$default = $this->_data['default'];
		@$max_length = $this->_data['max_length'];
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		
		$tpl->assign('label', $label);
		$tpl->assign('placeholder', $placeholder);
		$tpl->assign('default', $default);
		$tpl->assign('var', $this->_key);
		$tpl->assign('value', $this->_value);
		$tpl->assign('is_required', $is_required);
		$tpl->assign('max_length', $max_length);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/ui.interaction/await/textarea.tpl');
	}
}