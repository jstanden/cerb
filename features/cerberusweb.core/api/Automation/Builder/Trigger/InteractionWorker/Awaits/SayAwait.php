<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use Cerb_HTMLPurifier_URIFilter_Email;
use DevblocksPlatform;
use Model_AutomationContinuation;

class SayAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation) {
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		$msg = '';
		$format = 'text';
		
		if(is_string($this->_data)) {
			$msg = $this->_data;
			
		} else if(array_key_exists('content', $this->_data)) {
			$msg = DevblocksPlatform::parseMarkdown($this->_data['content'], true);
			$format = 'markdown';
			
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			$msg = DevblocksPlatform::purifyHTML($msg, true, true, [$filter]);
			
		} else if(array_key_exists('message', $this->_data)) {
			$msg = @$this->_data['message'];
		}
		
		$tpl->assign('message', $msg);
		$tpl->assign('format', $format);
		$tpl->assign('style', $this->_data['style'] ?? null);
		$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_text.tpl');
	}
}