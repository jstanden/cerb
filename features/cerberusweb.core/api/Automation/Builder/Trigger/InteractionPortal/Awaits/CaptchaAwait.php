<?php
namespace Cerb\Automation\Builder\Trigger\InteractionPortal\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class CaptchaAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $prompt_action, array $prompt_params, Model_AutomationContinuation $continuation) {
		return false;
	}

	function validate(_DevblocksValidationService $validation, Model_AutomationContinuation $continuation) {
		$prompt_label = $this->_data['label'] ?? '';
		
		$otp_code = $continuation->state_data['dict'][$this->_key . '__otp'];
		
		if(0 === strcasecmp($otp_code, $this->_value)) {
			$input_field = $validation->addField($this->_key, $prompt_label);
			$input_field->string();
			
		} else {
			$input_field = $validation->addField($this->_key, $prompt_label);
			
			$input_field->error()
				->setError("Your text did not match the image.")
				->setRequired(true);
		}
	}
	
	function formatValue() {
		return $this->_value;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$captcha = DevblocksPlatform::services()->captcha();
		
		$code = \CerberusApplication::generatePassword(4);
		
		// Save the CAPTCHA code in the dictionary
		$continuation->state_data['dict'][$this->_key . '__otp'] = $code;
		
		$image_bytes = $captcha->createImage($code);
		$tpl->assign('image_bytes', base64_encode($image_bytes));
		
		$label = $this->_data['label'] ?? null;
		$tpl->assign('label', $label);
		
		$tpl->assign('var', $this->_key);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.portal/await/captcha.tpl');
	}
}