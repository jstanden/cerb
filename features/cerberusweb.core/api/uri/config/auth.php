<?php /** @noinspection PhpUnused */

/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_SetupAuth extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'auth');
		
		// ============================================
		// SSO
		
		// Find provider extensions with 'sso' flag
		$provider_mfts = Extension_ConnectedServiceProvider::getAll(false, ['sso']);
		
		$sso_services_available = DAO_ConnectedService::getByExtensions(array_keys($provider_mfts));
		DevblocksPlatform::sortObjects($sso_services_available, 'name');
		
		$sso_services_enabled_ids = explode(',', DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_SSO_SERVICE_IDS, CerberusSettingsDefaults::AUTH_SSO_SERVICE_IDS));
		$sso_services_enabled = array_intersect_key($sso_services_available, array_flip($sso_services_enabled_ids));
		
		// Sort enabled services first by dragged rank, disabled lexicographically
		/** @noinspection DuplicatedCode */
		usort($sso_services_available, function($a, $b) use ($sso_services_enabled_ids) {
			/* @var $a Model_ConnectedService */
			/* @var $b Model_ConnectedService */
			
			if(false === @$a_pos = array_search($a->id, $sso_services_enabled_ids))
				$a_pos = PHP_INT_MAX;
			
			if(false === (@$b_pos = array_search($b->id, $sso_services_enabled_ids)))
				$b_pos = PHP_INT_MAX;
			
			if($a_pos == $b_pos) {
				if($a_pos == PHP_INT_MAX)
					return strcmp($a->name, $b->name);
				
				return 0;
			}
			
			return $a_pos < $b_pos ? -1 : 1;
		});
		
		$tpl->assign('sso_services_available', $sso_services_available);
		$tpl->assign('sso_services_enabled', $sso_services_enabled);
		
		// ============================================
		// MFA
		
		$params = [
			'auth_mfa_allow_remember' => DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_MFA_ALLOW_REMEMBER, CerberusSettingsDefaults::AUTH_MFA_ALLOW_REMEMBER),
			'auth_mfa_remember_days' => DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_MFA_REMEMBER_DAYS, CerberusSettingsDefaults::AUTH_MFA_REMEMBER_DAYS),
			'auth_new_worker_disable_password' => DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_DEFAULT_WORKER_DISABLE_PASSWORD, CerberusSettingsDefaults::AUTH_DEFAULT_WORKER_DISABLE_PASSWORD),
			'auth_new_worker_require_mfa' => DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_DEFAULT_WORKER_REQUIRE_MFA, CerberusSettingsDefaults::AUTH_DEFAULT_WORKER_REQUIRE_MFA),
		];
		$tpl->assign('params', $params);
		
		// ============================================
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/auth/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'saveJson':
					return $this->_configAction_saveJson();
			}
		}
		return false;
	}
	
	private function _configAction_saveJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
			
			$validation = DevblocksPlatform::services()->validation();
			$error = null;
			
			$validation
				->addField('auth_sso_service_ids', 'SSO Services')
				->idArray()
				->addValidator($validation->validators()->contextIds(Context_ConnectedService::ID, true))
				;
			$validation
				->addField('auth_mfa_allow_remember', 'Remember trusted MFA devices')
				->bit()
				;
			$validation
				->addField('auth_mfa_remember_days', 'Remember MFA days')
				->number()
				->setMin(0)
				->setMax(60)
				;
			$validation
				->addField('auth_new_worker_disable_password', 'Disable password-based authentication')
				->bit()
				;
			$validation
				->addField('auth_new_worker_require_mfa', 'Require multi-factor authentication')
				->bit()
				;
			
			if(false == $validation->validateAll($params, $error))
				throw new Exception_DevblocksValidationError($error);
			
			// ============================================
			// SSO
			
			$auth_sso_service_ids = DevblocksPlatform::importGPC($params['auth_sso_service_ids'] ?? null,'array:int',[]);
			
			if($auth_sso_service_ids && false != ($sso_services = DAO_ConnectedService::getIds($auth_sso_service_ids))) {
				// Validate as service IDs with 'sso' enabled
				foreach($sso_services as $sso_service) {
					if(!$sso_service->getExtension()->hasOption('sso'))
						throw new Exception_DevblocksValidationError(sprintf("The `%s` service does not support SSO.", $sso_service->name));
					
					if(!$sso_service->uri)
						throw new Exception_DevblocksValidationError(sprintf("The `%s` service must have a URI configured for SSO.", $sso_service->name));
				}
			} else {
				$sso_services = [];
			}
			
			DevblocksPlatform::setPluginSetting('cerberusweb.core', CerberusSettings::AUTH_SSO_SERVICE_IDS, implode(',', array_keys($sso_services)));
			
			// ============================================
			// MFA
			
			$auth_mfa_allow_remember = DevblocksPlatform::importGPC($params['auth_mfa_allow_remember'] ?? null, 'int', 0);
			$auth_mfa_remember_days = DevblocksPlatform::importGPC($params['auth_mfa_remember_days'] ?? null, 'int', 0);
			$auth_new_worker_disable_password = DevblocksPlatform::importGPC($params['auth_new_worker_disable_password'] ?? null, 'int', 0);
			$auth_new_worker_require_mfa = DevblocksPlatform::importGPC($params['auth_new_worker_require_mfa'] ?? null, 'int', 0);
			
			DevblocksPlatform::setPluginSetting('cerberusweb.core', CerberusSettings::AUTH_MFA_ALLOW_REMEMBER, $auth_mfa_allow_remember);
			DevblocksPlatform::setPluginSetting('cerberusweb.core', CerberusSettings::AUTH_MFA_REMEMBER_DAYS, $auth_mfa_remember_days);
			DevblocksPlatform::setPluginSetting('cerberusweb.core', CerberusSettings::AUTH_DEFAULT_WORKER_DISABLE_PASSWORD, $auth_new_worker_disable_password);
			DevblocksPlatform::setPluginSetting('cerberusweb.core', CerberusSettings::AUTH_DEFAULT_WORKER_REQUIRE_MFA, $auth_new_worker_require_mfa);
			
			echo json_encode(array('status'=>true, 'message'=>DevblocksPlatform::translate('success.saved_changes')));
			return;
				
		} catch(Exception_DevblocksValidationError $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
}