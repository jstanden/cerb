<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits;

use _DevblocksValidationService;
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
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$msg = '';
		$format = 'text';
		$style = is_array($this->_data) && array_key_exists('style', $this->_data) ? $this->_data['style'] : null;
		
		if(is_string($this->_data)) {
			$msg = $this->_data;
			
		} else if(array_key_exists('content', $this->_data)) {
			$msg = \Portal_WebsiteInteractions::parseMarkdown($this->_data['content']);
			$format = 'markdown';
			
			$msg = DevblocksPlatform::purifyHTML($msg, true, true);
			
		} else if(array_key_exists('message', $this->_data)) {
			$msg = @$this->_data['message'];
		}
		
		$tpl->assign('message', $msg);
		$tpl->assign('format', $format);
		$tpl->assign('style', $style);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/say.tpl');
	}
}