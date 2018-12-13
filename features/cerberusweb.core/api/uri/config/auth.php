<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2018, Webgroup Media LLC
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
		
		$visit->set(ChConfigurationPage::ID, 'auth');
		
		// Find provider extensions with 'sso' flag
		$provider_mfts = Extension_ConnectedServiceProvider::getAll(false, ['sso']);
		
		$sso_services_available = DAO_ConnectedService::getByExtensions(array_keys($provider_mfts));
		DevblocksPlatform::sortObjects($sso_services_available, 'name');
		
		$sso_services_enabled_ids = explode(',', DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_SSO_SERVICE_IDS, CerberusSettingsDefaults::AUTH_SSO_SERVICE_IDS));
		$sso_services_enabled = array_intersect_key($sso_services_available, array_flip($sso_services_enabled_ids));
		
		// Sort enabled services first by dragged rank, disabled lexicographically
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
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/auth/index.tpl');
	}
	
	function saveJsonAction() {
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
			
			$worker = CerberusApplication::getActiveWorker();
			$validation = DevblocksPlatform::services()->validation();
			$error = null;
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			$validation
				->addField('auth_sso_service_ids', 'SSO Services')
				->idArray()
				->addValidator($validation->validators()->contextIds(Context_ConnectedService::ID, true))
				;
			
			if(false == $validation->validateAll($params, $error))
				throw new Exception_DevblocksValidationError($error);
			
			@$auth_sso_service_ids = DevblocksPlatform::importGPC($params['auth_sso_service_ids'],'array:int',[]);
			
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
			
			echo json_encode(array('status'=>true, 'message'=>DevblocksPlatform::translate('success.saved_changes')));
			return;
				
		} catch(Exception_DevblocksValidationError $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};