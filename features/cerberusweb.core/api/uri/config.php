<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

class ChConfigurationPage extends CerberusPageExtension  {
	function isVisible() {
		// Must be logged in
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		// Must be a superuser
		return !empty($worker->is_superuser);
	}
	
	function getActivity() {
	    return new Model_Activity('activity.config');
	}
	
	function render() {
		$translate = DevblocksPlatform::getTranslationService();
		$tpl = DevblocksPlatform::getTemplateService();
		$worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}

		if(file_exists(APP_PATH . '/install/')) {
			$tpl->assign('install_dir_warning', true);
		}
		
		$tab_manifests = DevblocksPlatform::getExtensions(Extension_ConfigTab::POINT, false);
		uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Selected tab
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		array_shift($stack); // config
		
		// Remember the last tab/URL
		if(null == ($section_uri = @$response->path[1])) {
			if(null == ($section_uri = $visit->get(Extension_ConfigTab::POINT, '')))
				$section_uri = 'branding';
		}

		// Subpage
		$subpage = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		$tpl->assign('subpage', $subpage);
		
		// [TODO] Search for submenu on the 'config' page.
		//Extension_PageSubmenu::
		
		// [TODO] check showTab* hooks for active_worker->is_superuser (no ajax bypass)
		
		$tpl->display('devblocks:cerberusweb.core::configuration/index.tpl');
	}
	
	// Ajax
//	function showTabAction() {
//		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
//		
//		$visit = CerberusApplication::getVisit();
//		
//		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
//			&& null != ($inst = $tab_mft->createInstance()) 
//			&& $inst instanceof Extension_ConfigTab) {
//				$visit->set(Extension_ConfigTab::POINT, $inst->manifest->params['uri']);
//				$inst->showTab();
//		}
//	}
	
	// Post
//	function saveTabAction() {
//		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
//		
//		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
//			&& null != ($inst = $tab_mft->createInstance()) 
//			&& $inst instanceof Extension_ConfigTab) {
//				$inst->saveTab();
//		}
//	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly, 
	 * instead of forcing tabs to implement controllers.  This should check 
	 * for the *Action() functions just as a handleRequest would
	 */
//	function handleTabActionAction() {
//		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'],'string','');
//		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');
//
//		if(null != ($tab_mft = DevblocksPlatform::getExtension($tab)) 
//			&& null != ($inst = $tab_mft->createInstance()) 
//			&& $inst instanceof Extension_ConfigTab) {
//				if(method_exists($inst,$action.'Action')) {
//					call_user_func(array(&$inst, $action.'Action'));
//				}
//		}
//	}
	
	function handleSectionActionAction() {
		@$section_uri = DevblocksPlatform::importGPC($_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	/********/
	
	function showTabStorageAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(Extension_ConfigTab::POINT, 'storage');
		
		// Scope
		
		$storage_engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false, true);
		$tpl->assign('storage_engines', $storage_engines);

		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);

		$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', true, true);
		$tpl->assign('storage_schemas', $storage_schemas);
		
		// Totals
		
		$db = DevblocksPlatform::getDatabaseService();
		$rs = $db->Execute("SHOW TABLE STATUS");

		$total_db_size = 0;
		$total_db_data = 0;
		$total_db_indexes = 0;
		$total_db_slack = 0;
		
		// [TODO] This would likely be helpful to the /debug controller
		
		while($row = mysql_fetch_assoc($rs)) {
			$table_size_data = floatval($row['Data_length']);
			$table_size_indexes = floatval($row['Index_length']);
			$table_size_slack = floatval($row['Data_free']);
			
			$total_db_size += $table_size_data + $table_size_indexes;
			$total_db_data += $table_size_data;
			$total_db_indexes += $table_size_indexes;
			$total_db_slack += $table_size_slack;
		}
		
		mysql_free_result($rs);
		
		$tpl->assign('total_db_size', $total_db_size);
		$tpl->assign('total_db_data', $total_db_data);
		$tpl->assign('total_db_indexes', $total_db_indexes);
		$tpl->assign('total_db_slack', $total_db_slack);
		
		// View
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_DevblocksStorageProfile';
		$defaults->id = View_DevblocksStorageProfile::DEFAULT_ID;

		$view = C4_AbstractViewLoader::getView(View_DevblocksStorageProfile::DEFAULT_ID, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/tabs/storage/index.tpl');		
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
		
		$tpl->display('devblocks:cerberusweb.core::configuration/tabs/storage/profiles/peek.tpl');
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
	
	function testStorageProfilePeekAction() {
		@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string','');

		if(empty($extension_id) 
			|| null == ($ext = $ext = DevblocksPlatform::getExtension($extension_id, true)))
			return false;
			
		$tpl = DevblocksPlatform::getTemplateService();
			
		/* @var $ext Extension_DevblocksStorageEngine */
			
		if($ext->testConfig()) {
			$output = 'Your storage profile is configured properly.';
			$success = true;
		} else {
			$output = 'Your storage profile is not configured properly.';
			$success = false;
		}
		
		$tpl->assign('success', $success);
		$tpl->assign('output', $output);
		
		$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
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
	
	function showStorageSchemaAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$storage_engines = DevblocksPlatform::getExtensions('devblocks.storage.engine', false, true);
		$tpl->assign('storage_engines', $storage_engines);
		
		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);
		
		$extension = DevblocksPlatform::getExtension($ext_id, true, true);
		$tpl->assign('schema', $extension);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/tabs/storage/schemas/display.tpl');
	}
	
	function showStorageSchemaPeekAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$extension = DevblocksPlatform::getExtension($ext_id, true, true);
		$tpl->assign('schema', $extension);
		
		$storage_profiles = DAO_DevblocksStorageProfile::getAll();
		$tpl->assign('storage_profiles', $storage_profiles);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/tabs/storage/schemas/peek.tpl');
	}
	
	function saveStorageSchemaPeekAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(ONDEMAND_MODE)
			return;
		
		$extension = DevblocksPlatform::getExtension($ext_id, true, true);
		/* @var $extension Extension_DevblocksStorageSchema */
		$extension->saveConfig();
		
		$this->showStorageSchemaAction();
	}
	
	// Ajax
	function showTabAttachmentsAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(Extension_ConfigTab::POINT, 'attachments');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_AttachmentLink';
		$defaults->id = View_AttachmentLink::DEFAULT_ID;

		$view = C4_AbstractViewLoader::getView(View_AttachmentLink::DEFAULT_ID, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/tabs/attachments/index.tpl');
	}
	
	function showAttachmentsBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($id_csv)) {
	        $ids = DevblocksPlatform::parseCsvString($id_csv);
	        $tpl->assign('ids', implode(',', $ids));
	    }
		
	    // Lists
//	    $lists = DAO_FeedbackList::getWhere();
//	    $tpl->assign('lists', $lists);
	    
		// Custom Fields
//		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FEEDBACK);
//		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/tabs/attachments/bulk.tpl');
	}
	
	function doAttachmentsBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    $ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Attachment fields
		@$deleted = DevblocksPlatform::importGPC($_POST['deleted'],'string');

		$do = array();
		
		// Do: Deleted
		if(0 != strlen($deleted))
			$do['deleted'] = intval($deleted);
			
		// Do: Custom fields
//		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		switch($filter) {
			// Checked rows
			case 'checks':
			    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
			
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
	
};
