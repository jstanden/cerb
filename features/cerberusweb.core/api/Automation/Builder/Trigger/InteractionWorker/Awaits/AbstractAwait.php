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

	// True when rendering inert (no live scripting) — the design-time form-builder preview OR the simulator's
	// form-fill (set by _renderFormElements()).
	protected function _isSimulated() : bool {
		return (bool)(\DevblocksPlatform::services()->template()->getTemplateVars('is_automation_simulated') ?? false);
	}

	// True ONLY for the design-time form-builder preview — simulated, but NOT the simulator form-fill. The builder
	// has no real state, so data-driven components (transcript, map, file download) render mock/placeholder data to
	// stay visible while designing. The simulator (form-fill) has real resolved state, so it mirrors the runtime:
	// real data when it exists, otherwise blank (never mock).
	protected function _isBuilderPreview() : bool {
		return $this->_isSimulated()
			&& !(bool)(\DevblocksPlatform::services()->template()->getTemplateVars('is_automation_form_fill') ?? false);
	}

	// Components that need live data (map, transcript, file download, …) would otherwise render nothing at
	// design time. In simulated mode they emit a labeled placeholder instead so the field is visible/selectable.
	protected function _renderSimulatedPlaceholder(?string $label, string $icon, string $note) : void {
		$tpl = \DevblocksPlatform::services()->template();
		$tpl->assign('label', $label);
		$tpl->assign('icon', $icon);
		$tpl->assign('note', $note);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.worker/await/_simulated_placeholder.tpl');
	}
}