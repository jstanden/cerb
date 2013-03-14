<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_SetupACL extends Extension_PageSection {
	function render() {
		$settings = DevblocksPlatform::getPluginSettingsService();
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$request = DevblocksPlatform::getHttpRequest();
		
		$stack = $request->path;
		@array_shift($stack); // config
		@array_shift($stack); // acl
		
		$visit->set(ChConfigurationPage::ID, 'acl');
				
		$plugins = DevblocksPlatform::getPluginRegistry();
		$tpl->assign('plugins', $plugins);
		
		$acl = DevblocksPlatform::getAclRegistry();
		$tpl->assign('acl', $acl);
		
		$roles = DAO_WorkerRole::getAll();
		$tpl->assign('roles', $roles);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);

		// Specific role?
		if(!empty($stack)) {
			@$role_id = intval(array_shift($stack));
			if(isset($roles[$role_id])) {
				$tpl->assign('role', $roles[$role_id]);
				
				$role_privs = DAO_WorkerRole::getRolePrivileges($role_id);
				$tpl->assign('role_privs', $role_privs);
				
				if(isset($request->query['saved']))
					$tpl->assign('saved', true);
			}
		}
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/acl/index.tpl');
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

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$role = DAO_WorkerRole::get($id);
		$tpl->assign('role', $role);
		
		$role_privs = DAO_WorkerRole::getRolePrivileges($id);
		$tpl->assign('role_privs', $role_privs);
		
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
		@$who = DevblocksPlatform::importGPC($_REQUEST['who'],'string','');
		@$what = DevblocksPlatform::importGPC($_REQUEST['what'],'string','');
		@$acl_privs = DevblocksPlatform::importGPC($_REQUEST['acl_privs'],'array',array());
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		// Sanity checks
		if(empty($name))
			$name = 'New Role';
		
		// Delete
		if(!empty($do_delete) && !empty($id)) {
			DAO_WorkerRole::delete($id);
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','acl')));
			return;
		}
		
		$params = array();
		
		// Apply to
		switch($who) {
			case 'all':
				$params['who'] = $who;
				break;
				
			case 'groups':
				$params['who'] = $who;
				@$who_ids = DevblocksPlatform::importGPC($_REQUEST['group_ids'],'array',array());
				$params['who_list'] = DevblocksPlatform::sanitizeArray($who_ids, 'integer');
				break;
				
			case 'workers':
				$params['who'] = $who;
				@$who_ids = DevblocksPlatform::importGPC($_REQUEST['worker_ids'],'array',array());
				$params['who_list'] = DevblocksPlatform::sanitizeArray($who_ids, 'integer');
				break;
				
			default:
				$who = null;
				break;
		}
		
		// Privs
		switch($what) {
			case 'all': // all
				$params['what'] = $what;
				$acl_privs = array();
				break;
				
			case 'none': // none
				$params['what'] = $what;
				$acl_privs = array();
				break;
				
			case 'itemized': // itemized
				$params['what'] = $what;
				break;
				
			default: // itemized
				$what = null;
				break;
		}
		
		// Abort if incomplete or invalid
		if(empty($who) || empty($what)) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','acl')));
			return;
		}
		
		$fields = array(
			DAO_WorkerRole::NAME => $name,
			DAO_WorkerRole::PARAMS_JSON => json_encode($params),
		);
			
		if(empty($id)) { // create
			$id = DAO_WorkerRole::create($fields);
					
		} else { // edit
			DAO_WorkerRole::update($id, $fields);
		}

		// Update role privs
		DAO_WorkerRole::setRolePrivileges($id, $acl_privs, true);
		
		// Clear cache
		DAO_WorkerRole::clearCache();
		DAO_WorkerRole::clearWorkerCache();
		
		$friendly = sprintf("%d-%s",
			$id,
			DevblocksPlatform::strToPermalink($name)
		);
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','acl',$friendly),array('saved'=>'')));
		exit;
	}
};