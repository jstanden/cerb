<?php
namespace Cerb\Automation\Builder\Trigger\InteractionWebsite\Awaits;

use _DevblocksValidationService;
use CerbPortalWebsiteInteractions_Model;
use Model_AutomationContinuation;

abstract class AbstractAwait {
	protected $_key;
	protected $_data;
	protected $_value;
	protected ?CerbPortalWebsiteInteractions_Model $_schema;
	private $_mock_session = null;

	function __construct($key, $value, $data, ?CerbPortalWebsiteInteractions_Model $schema = null) {
		$this->_key = $key;
		$this->_data = $data;
		$this->_value = $value;
		$this->_schema = $schema;
	}

	abstract function validate(_DevblocksValidationService $validation);
	abstract function formatValue();
	abstract function render(Model_AutomationContinuation $continuation);
	abstract function invoke(string $prompt_key, string $action, Model_AutomationContinuation $continuation);

	function setValue($key, $value, $dict) {
		if($dict instanceof \DevblocksDictionaryDelegate) {
			$dict->set($key, $value);
		} elseif (is_array($dict) && $key) {
			$dict[$key] = $value;
		}

		return $dict;
	}

	// True when rendering inert (no live scripting) — the design-time form-builder preview OR the simulator's
	// form-fill (set by the automation editor's _renderFormElements()).
	protected function _isSimulated() : bool {
		return (bool)(\DevblocksPlatform::services()->template()->getTemplateVars('is_automation_simulated') ?? false);
	}

	// True ONLY for the design-time form-builder preview — simulated, but NOT the simulator form-fill. The builder
	// has no real state, so data-driven components render mock/placeholder data to stay visible while designing;
	// the simulator (form-fill) has real resolved state, so it mirrors the runtime (real data, else blank).
	protected function _isBuilderPreview() : bool {
		return $this->_isSimulated()
			&& !(bool)(\DevblocksPlatform::services()->template()->getTemplateVars('is_automation_form_fill') ?? false);
	}

	// Data-driven components (transcript, file upload, …) would render nothing at design time. In simulated mode
	// they emit a labeled placeholder instead so the field stays visible/selectable.
	protected function _renderSimulatedPlaceholder(?string $label, string $icon, string $note) : void {
		$tpl = \DevblocksPlatform::services()->template();
		$tpl->assign('label', $label);
		$tpl->assign('icon', $icon);
		$tpl->assign('note', $note);
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/_simulated_placeholder.tpl');
	}

	// The runtime pulls the portal session from the cookie/DB via ChPortalHelper; in simulated mode there's no
	// portal, so hand templates a mock session that supplies the CSP nonce (the only field form elements read).
	protected function _getSession() {
		if(!$this->_isSimulated())
			return \ChPortalHelper::getSession();

		if(is_null($this->_mock_session)) {
			$session = new \Model_CommunitySession();
			$session->nonce = \DevblocksPlatform::getRequestNonce();
			$session->csrf_token = $session->nonce;
			$this->_mock_session = $session;
		}

		return $this->_mock_session;
	}
}