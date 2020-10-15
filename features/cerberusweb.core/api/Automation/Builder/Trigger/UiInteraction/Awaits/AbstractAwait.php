<?php
namespace Cerb\Automation\Builder\Trigger\UiInteraction\Awaits;

use _DevblocksValidationService;
use Model_AutomationExecution;

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
	abstract function render(Model_AutomationExecution $execution);
	abstract function invoke(string $prompt_key, string $action, Model_AutomationExecution $execution);
}