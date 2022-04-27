<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class QueryAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function validate(_DevblocksValidationService $validation) {
		$prompt_label = $this->_data['label'] ?? null;
		
		$input_field = $validation->addField($this->_key, $prompt_label);
		
		$input_field_type = $input_field->string($validation::STRING_UTF8MB4)
			->setMaxLength(1024)
		;
		
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		
		if($is_required)
			$input_field_type->setRequired(true);
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		$label = $this->_data['label'] ?? null;
		$default = $this->_data['default'] ?? null;
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		$record_type = $this->_data['record_type'] ?? null;
	
		$tpl->assign('label', $label);
		$tpl->assign('record_type', $record_type);
		$tpl->assign('default', $default);
		$tpl->assign('var', $this->_key);
		$tpl->assign('value', $this->_value);
		$tpl->assign('is_required', $is_required);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/query.tpl');
	}
}