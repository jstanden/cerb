<?php
namespace Cerb\Automation\Builder\Trigger\UiInteraction\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationExecution;

class SayAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationExecution $execution) {
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation) {
	}
	
	function render(Model_AutomationExecution $execution) {
		$tpl = DevblocksPlatform::services()->template();
		
		$msg = '';
		$format = 'text';
		
		if(is_string($this->_data)) {
			$msg = $this->_data;
		} else if(array_key_exists('content', $this->_data)) {
			$msg = DevblocksPlatform::parseMarkdown($this->_data['content']);
			$format = 'markdown';
		} else if(array_key_exists('message', $this->_data)) {
			$msg = @$this->_data['message'];
		}
		
		$tpl->assign('message', $msg);
		$tpl->assign('format', $format);
		$tpl->assign('style', @$this->_data['style']);
		$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_text.tpl');
	}
}