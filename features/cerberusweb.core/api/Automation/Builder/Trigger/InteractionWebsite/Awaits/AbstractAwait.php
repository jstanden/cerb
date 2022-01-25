<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits;

use _DevblocksValidationService;
use CerbPortalWebsiteInteractions_Model;
use Model_AutomationContinuation;

abstract class AbstractAwait {
	protected $_key;
	protected $_data;
	protected $_value;
	protected CerbPortalWebsiteInteractions_Model $_schema;
	
	function __construct($key, $value, $data, CerbPortalWebsiteInteractions_Model $schema) {
		$this->_key = $key;
		$this->_data = $data;
		$this->_value = $value;
		$this->_schema = $schema;
	}
	
	abstract function validate(_DevblocksValidationService $validation);
	abstract function formatValue();
	abstract function render(Model_AutomationContinuation $continuation);
	abstract function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation);
}