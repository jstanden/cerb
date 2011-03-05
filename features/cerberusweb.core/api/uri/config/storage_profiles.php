<?php
class PageSection_SetupStorageProfiles extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'storage_profiles');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_DevblocksStorageProfile';
		$defaults->id = View_DevblocksStorageProfile::DEFAULT_ID;

		$view = C4_AbstractViewLoader::getView(View_DevblocksStorageProfile::DEFAULT_ID, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/storage_profiles/index.tpl');
	}
	
	function showStorageProfilePeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		// Storage engines
		
		$engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false);
		$tpl->assign('engines', $engines);
		
		// Profile
		
		if(null == ($profile = DAO_DevblocksStorageProfile::get($id)))
			$profile = new Model_DevblocksStorageProfile();
			
		$tpl->assign('profile', $profile);
		
		if(!empty($profile->id)) {
			$storage_ext_id = $profile->extension_id;
		} else {
			$storage_ext_id = 'devblocks.storage.engine.disk';
		}

		if(!empty($id)) {
			$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', false, true);
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
	
	function showStorageProfileConfigAction() {
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
	
	function testProfileJsonAction() {
		@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string','');

		try {
			if(empty($extension_id) 
				|| null == ($ext = $ext = DevblocksPlatform::getExtension($extension_id, true)))
				throw new Exception("Can't load extension.");
				
			/* @var $ext Extension_DevblocksStorageEngine */
				
			if(!$ext->testConfig()) {
				throw new Exception('Your storage profile is not configured properly.');
			}
			
		    echo json_encode(array('status'=>true,'message'=>'Your storage profile is configured properly.'));
		    return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
	}
	
	function saveStorageProfilePeekAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();

		// ACL
		if(!$active_worker->is_superuser)
			return;
		
		if(ONDEMAND_MODE)
			return;
			
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string');
		@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string');
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		if(empty($name)) $name = "New Storage Profile";
		
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
				
			// Custom field saves
			//@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			//DAO_CustomFieldValue::handleFormPost(PsCustomFieldSource_Sensor::ID, $id, $field_ids);
		}
		
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView($view_id);
			$view->render();
		}		
	}	
}