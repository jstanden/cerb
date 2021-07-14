<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class SubmitAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function formatValue() {
		return $this->_value;
	}
	
	function validate(_DevblocksValidationService $validation) {
		$prompt_label = $this->_data['label'] ?? null;
		
		$validation
			->addField($this->_key, $prompt_label)
			->string()
		;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$buttons_data = $this->_data['buttons'] ?? null;
		
		// Synthesize buttons
		if(!$buttons_data) {
			@$show_continue = $this->_data['continue'] ?? true;
			@$show_reset = $this->_data['reset'] ?? false;
			
			if($show_continue) {
				$buttons_data['continue'] = [
					'label' => 'Continue',
					'icon' => 'right-arrow',
					'icon_at' => 'end',
				];
			}
			
			if($show_reset) {
				$buttons_data['reset'] = [
					'label' => 'Start over',
					'style' => 'secondary',
					'icon' => 'repeat',
					'icon_at' => 'start',
				];
			}
		}
		
		if(is_array($buttons_data)) {
			$buttons = $this->_parseButtons($buttons_data);
			$tpl->assign('buttons', $buttons);
		}
		
		$tpl->assign('var', $this->_key);
		$tpl->assign('value', $this->_value);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/submit.tpl');
	}
	
	private function _parseButtons(array $buttons_data) : array {
		$buttons = [];
		
		foreach($buttons_data as $button_key => $button_data) {
			list($button_type, $button_name) = array_pad(explode('/', $button_key), 2, null);
			
			if(!in_array($button_type, ['continue', 'reset']))
				continue;
			
			if(!$button_name)
				$button_name = $button_type;
			
			// [TODO] Sanitize style + icons
			
			$buttons[$button_name] = [
				'_key' => $button_key,
				'_name' => $button_name,
				'_type' => $button_type,
				'label' => $button_data['label'] ?? DevblocksPlatform::strTitleCase($button_type),
				'icon' => $button_data['icon'] ?? '',
				'icon_at' => $button_data['icon_at'] ?? 'start',
				'style' => $button_data['style'] ?? '',
				'value' => $button_data['value'] ?? $button_type,
			];
		}
		
		return $buttons;
	}
}