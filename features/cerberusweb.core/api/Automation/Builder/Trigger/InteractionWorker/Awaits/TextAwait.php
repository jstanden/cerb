<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use DevblocksPlatform;
use Model_AutomationContinuation;

class TextAwait extends AbstractAwait {
	function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation) {
		return false;
	}

	function validate(_DevblocksValidationService $validation) {
		$prompt_label = $this->_data['label'] ?? null;
		$prompt_type = $this->_data['type'] ?? 'freeform';
		
		$input_field = $validation->addField($this->_key, $prompt_label);
		
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
		
		switch ($prompt_type) {
			case 'freeform':
				$input_field_type = $input_field->string($validation::STRING_UTF8MB4)
					->setMaxLength(1024)
				;
				break;
				
			case 'bool':
				$input_field_type = $input_field->string()
					->setNotEmpty(false)
					->setPossibleValues(['true','false'])
				;
				break;
			
			case 'date':
				$input_field_type = $input_field->string()
					->addValidator($validation->validators()->date());
				break;
			
			case 'decimal':
				// [TODO] Precision
				// [TODO] Min/max
				$input_field_type = $input_field->float()
					->setNotEmpty(false)
					//->setMin(0)
					//->setMax(255)
				;
				break;
			
			case 'email':
				$input_field_type = $input_field->string()
					->addValidator($validation->validators()->email(!$is_required));
				break;
			
			case 'geopoint':
				//$dict->setKeyPath('inputs.' . $input_key, DevblocksPlatform::parseGeoPointString($input_value));
				$input_field_type = $input_field->geopoint();
				break;
			
			case 'ip':
				$input_field_type = $input_field->string()
					->addValidator($validation->validators()->ip());
				break;
			
			case 'ipv4':
				$input_field_type = $input_field->string()
					->addValidator($validation->validators()->ipv4());
				break;
			
			case 'ipv6':
				$input_field_type = $input_field->string()
					->addValidator($validation->validators()->ipv6());
				break;
			
			case 'number':
				$input_field_type = $input_field->number()
					->setNotEmpty(false)
					//->setMin(0)
					//->setMax(255)
				;
				break;
			
			case 'password':
				$input_field_type = $input_field->string();
				break;
			
			case 'record_type':
				$input_field_type = $input_field->string()
					->addValidator($validation->validators()->context(true))
				;
				break;
				
			case 'timestamp':
				$input_field_type = $input_field->timestamp();
				break;
			
			case 'uri':
				$input_field_type = $input_field->string()
					->addValidator($validation->validators()->uri())
				;
				break;
			
			case 'url':
				$input_field_type = $input_field->url()
					->setMaxLength(2048)
				;
				break;
			
			default:
				// [TODO] Error on unknown
				$input_field_type = $input_field->string($validation::STRING_UTF8MB4)
					->setMaxLength(1024)
				;
				break;
		}
		
		if($is_required)
			$input_field_type->setRequired(true);
		
		if(array_key_exists('min_length', $this->_data) && is_numeric($this->_data['min_length']) && $this->_data['min_length'])
			$input_field_type->setMinLength($this->_data['min_length']);
		
		if(array_key_exists('max_length', $this->_data) && is_numeric($this->_data['max_length']) && $this->_data['max_length'])
			$input_field_type->setMaxLength($this->_data['max_length']);
		
		if(method_exists($input_field_type, 'setTruncation')) {
			$is_truncated = DevblocksPlatform::services()->string()->toBool($this->_data['truncate'] ?? 'yes');
			$input_field_type->setTruncation($is_truncated);
		}
	}
	
	function formatValue() {
		$prompt_type = $this->_data['type'] ?? 'freeform';
		
		switch ($prompt_type) {
			case 'bool':
				return DevblocksPlatform::services()->string()->toBool($this->_value);
			
			case 'decimal':
				return floatval($this->_value);
			
			case 'geopoint':
				return DevblocksPlatform::parseGeoPointString($this->_value);
			
			case 'number':
			case 'timestamp':
				return intval($this->_value);
		}
		
		return $this->_value;
	}
	
	function render(Model_AutomationContinuation $continuation) {
		$tpl = DevblocksPlatform::services()->template();
		
		$type = $this->_data['type'] ?? null;
		$label = $this->_data['label'] ?? null;
		$placeholder = $this->_data['placeholder'] ?? null;
		$default = $this->_data['default'] ?? null;
		$max_length = $this->_data['max_length'] ?? null;
		$is_required = array_key_exists('required', $this->_data) && $this->_data['required'];
	
		// Defaults by type
		switch($type) {
			case 'date':
				$placeholder = $placeholder ?? '(tomorrow 8am, next Friday, 31 Dec 2025 noon)';
				break;
				
			case 'decimal':
				$placeholder = $placeholder ?? '(3.1415)';
				break;
				
			case 'email':
				$placeholder = $placeholder ?? '(mailbox@host)';
				break;
				
			case 'geopoint':
				$placeholder = $placeholder ?? '(latitude, longitude)';
				break;
				
			case 'ip':
			case 'ipv4':
				$placeholder = $placeholder ?? '(1.2.3.4)';
				break;
				
			case 'ipv6':
				$placeholder = $placeholder ?? '(1234:5678:90ab:cdef:1234:5678:90ab:cdef)';
				break;
				
			case 'number':
				$placeholder = $placeholder ?? '(12345)';
				break;
				
			case 'uri':
				$placeholder = $placeholder ?? '(letters, numbers, dot, underscore)';
				break;
				
			case 'url':
				$placeholder = $placeholder ?? '(https://example.com/)';
				break;
		}
		
		$tpl->assign('label', $label);
		$tpl->assign('type', $type);
		$tpl->assign('placeholder', $placeholder);
		$tpl->assign('default', $default);
		$tpl->assign('var', $this->_key);
		$tpl->assign('value', $this->_value);
		$tpl->assign('is_required', $is_required);
		$tpl->assign('max_length', $max_length);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/text.tpl');
	}
}