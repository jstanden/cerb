<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits;

use _DevblocksValidationService;
use Model_AutomationContinuation;

abstract class AbstractAwait {
	protected $_key;
	protected $_data;
	protected $_value;
	
	function __construct($key, $value, $data) {
		$this->_key = $key;
		$this->_data = $data;
		$this->_value = $value;
	}
	
	abstract function validate(_DevblocksValidationService $validation);
	abstract function formatValue();
	abstract function render(Model_AutomationContinuation $continuation);
	abstract function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation);
	
	function setValue($key, $value, $dict) {
		if($dict instanceof \DevblocksDictionaryDelegate) {
			$dict->set($key, $value);
		} elseif (is_array($dict)) {
			$dict[$key] = $value;
		}
		
		return $dict;
	}
}