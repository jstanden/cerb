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

class PageSection_SetupStorageProfiles extends Extension_PageSection {
	function render() {
		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE)
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'storage_profiles');
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_DevblocksStorageProfile');
		$defaults->id = View_DevblocksStorageProfile::DEFAULT_ID;

		$view = C4_AbstractViewLoader::getView(View_DevblocksStorageProfile::DEFAULT_ID, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_profiles/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'showStorageProfilePeek':
					return $this->_configAction_showStorageProfilePeek();
				case 'showStorageProfileConfig':
					return $this->_configAction_showStorageProfileConfig();
				case 'testProfileJson':
					return $this->_configAction_testProfileJson();
				case 'saveStorageProfilePeek':
					return $this->_configAction_saveStorageProfilePeek();
			}
		}
		return false;
	}
	
	private function _configAction_showStorageProfilePeek() {
		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE)
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl->assign('view_id', $view_id);
		
		// Storage engines
		
		$engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);
		$tpl->assign('engines', $engines);
		
		// Profile
		
		if(null == ($profile = DAO_DevblocksStorageProfile::get($id)))
			$profile = new Model_DevblocksStorageProfile();
			
		if(!empty($profile->id)) {
			$storage_ext_id = $profile->extension_id;
		} else {
			$storage_ext_id = 'devblocks.storage.engine.disk';
			$profile->extension_id = $storage_ext_id;
		}
		
		$tpl->assign('profile', $profile);

		if(!empty($id)) {
			$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', false);
			$tpl->assign('storage_schemas', $storage_schemas);
			
			$storage_schema_stats = $profile->getUsageStats();
			
			if(!empty($storage_schema_stats))
				$tpl->assign('storage_schema_stats', $storage_schema_stats);
		}
		
		if(false !== ($storage_ext = DevblocksPlatform::getExtension($storage_ext_id, true))) {
			$tpl->assign('storage_engine', $storage_ext);
		}
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_profiles/peek.tpl');
	}
	
	private function _configAction_showStorageProfileConfig() {
		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE)
			return;
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		if(null == ($profile = DAO_DevblocksStorageProfile::get($id)))
			$profile = new Model_DevblocksStorageProfile();
		
		if(!empty($ext_id)) {
			if(null != ($ext = DevblocksPlatform::getExtension($ext_id, true))) {
				if($ext instanceof Extension_DevblocksStorageEngine) {
					$ext->renderConfig($profile);
				}
			}
		}
	}
	
	private function _configAction_testProfileJson() {
		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE)
			return;
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null,'string','');
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null,'integer',0);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(null == ($profile = DAO_DevblocksStorageProfile::get($id)))
				$profile = new Model_DevblocksStorageProfile();
			
			if(empty($extension_id)
				|| null == ($ext = DevblocksPlatform::getExtension($extension_id, true)))
				throw new Exception("Can't load extension.");
				
			/* @var $ext Extension_DevblocksStorageEngine */
			
			if(!$ext->testConfig($profile)) {
				throw new Exception('Your storage profile is not configured properly.');
			}
			
			echo json_encode(array('status'=>true,'message'=>'Your storage profile is configured properly.'));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
	
	private function _configAction_saveStorageProfilePeek() {
		if(DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE)
			return;
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string');
		@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string');
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		if(empty($name))
			$name = "New Storage Profile";
		
		if(!empty($id) && !empty($delete)) {
			// Double check that the profile is empty
			if(null != ($profile = DAO_DevblocksStorageProfile::get($id))) {
				$stats = $profile->getUsageStats();
				if(empty($stats)) {
					DAO_DevblocksStorageProfile::delete($id);
				}
			}
			
		} else {
			$fields = array(
				DAO_DevblocksStorageProfile::NAME => $name,
			);

			if(empty($id)) {
				$fields[DAO_DevblocksStorageProfile::EXTENSION_ID] = $extension_id;
				
				$id = DAO_DevblocksStorageProfile::create($fields);
				
			} else {
				DAO_DevblocksStorageProfile::update($id, $fields);
			}
			
			// Save sensor extension config
			if(!empty($extension_id)) {
				if(null != ($ext = DevblocksPlatform::getExtension($extension_id, true))) {
					if(null != ($profile = DAO_DevblocksStorageProfile::get($id))
					&& $ext instanceof Extension_DevblocksStorageEngine) {
						$ext->saveConfig($profile);
					}
				}
			}
		}
		
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView($view_id);
			$view->render();
		}
	}
}