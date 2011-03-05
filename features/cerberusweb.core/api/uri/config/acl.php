<?php
class PageSection_SetupACL extends Extension_PageSection {
	function render() {
		$settings = DevblocksPlatform::getPluginSettingsService();
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'acl');
				
		$plugins = DevblocksPlatform::getPluginRegistry();
		$tpl->assign('plugins', $plugins);
		
		$acl = DevblocksPlatform::getAclRegistry();
		$tpl->assign('acl', $acl);
		
		$roles = DAO_WorkerRole::getWhere();
		$tpl->assign('roles', $roles);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Permissions enabled
		$acl_enabled = $settings->get('cerberusweb.core',CerberusSettings::ACL_ENABLED,CerberusSettingsDefaults::ACL_ENABLED);
		$tpl->assign('acl_enabled', $acl_enabled);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/acl/index.tpl');
	}
	
	function toggleACLAction() {
		$worker = CerberusApplication::getActiveWorker();
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		if(!$worker || !$worker->is_superuser) {
			return;
		}
		
		@$enabled = DevblocksPlatform::importGPC($_REQUEST['enabled'],'integer',0);
		
		$settings->set('cerberusweb.core',CerberusSettings::ACL_ENABLED, $enabled);
	}
	
	function getRoleAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		
		$plugins = DevblocksPlatform::getPluginRegistry();
		$tpl->assign('plugins', $plugins);
		
		$acl = DevblocksPlatform::getAclRegistry();
		$tpl->assign('acl', $acl);

		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$role = DAO_WorkerRole::get($id);
		$tpl->assign('role', $role);
		
		$role_privs = DAO_WorkerRole::getRolePrivileges($id);
		$tpl->assign('role_privs', $role_privs);
		
		$role_roster = DAO_WorkerRole::getRoleWorkers($id);
		$tpl->assign('role_workers', $role_roster);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/acl/edit_role.tpl');
	}
	
	// Post
	function saveRoleAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_ids'],'array',array());
		@$acl_privs = DevblocksPlatform::importGPC($_REQUEST['acl_privs'],'array',array());
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		// Sanity checks
		if(empty($name))
			$name = 'New Role';
		
		// Delete
		if(!empty($do_delete) && !empty($id)) {
			DAO_WorkerRole::delete($id);
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','acl')));
		}

		$fields = array(
			DAO_WorkerRole::NAME => $name,
		);
			
		if(empty($id)) { // create
			$id = DAO_WorkerRole::create($fields);
					
		} else { // edit
			DAO_WorkerRole::update($id, $fields);
		}

		// Update role roster
		DAO_WorkerRole::setRoleWorkers($id, $worker_ids);
		
		// Update role privs
		DAO_WorkerRole::setRolePrivileges($id, $acl_privs, true);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','acl')));
	}	
};