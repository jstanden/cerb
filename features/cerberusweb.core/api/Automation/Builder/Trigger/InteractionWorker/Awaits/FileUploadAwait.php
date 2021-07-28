<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class FileUploadAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function validate(_DevblocksValidationService $validation) {
		@$prompt_label = $this->_data['label'];
		
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		
		$input_field = $validation->addField($this->_key, $prompt_label);
		
		$input_field_type = $input_field->id()
			->addValidator($validation->validators()->contextId(\CerberusContexts::CONTEXT_ATTACHMENT, !$is_required))
			;
		
		if($is_required)
			$input_field_type->setRequired(true);
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function setValue($key, $value, $dict) {
		if(DevblocksPlatform::strEndsWith($key, '_id')) {
			$id_key_prefix = substr($key, 0, -3);
		} else {
			$id_key_prefix = $key;
		}
		
		if(is_array($dict)) {
			$dict[$key] = $value;
			$dict[$id_key_prefix . '__context'] = \CerberusContexts::CONTEXT_ATTACHMENT;
			$dict[$id_key_prefix . '_id'] = $value;
			
		} elseif ($dict instanceof \DevblocksDictionaryDelegate) {
			$dict->set($key, $value);
			$dict->set($id_key_prefix . '__context', \CerberusContexts::CONTEXT_ATTACHMENT);
			$dict->set($id_key_prefix . '_id', $value);
		}
		
		return $dict;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$label = $this->_data['label'];
		@$placeholder = $this->_data['placeholder'];
		@$default = $this->_data['default'];
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
	
		$tpl->assign('label', $label);
		$tpl->assign('placeholder', $placeholder);
		$tpl->assign('default', $default);
		$tpl->assign('var', $this->_key);
		$tpl->assign('value', $this->_value);
		$tpl->assign('is_required', $is_required);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/file_upload.tpl');
	}
}