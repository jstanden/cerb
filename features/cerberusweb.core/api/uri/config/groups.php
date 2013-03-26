<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
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

class PageSection_SetupGroups extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'groups');
				
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/groups/index.tpl');
	}
	
	function getGroupAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		@$group = $groups[$id];
		$tpl->assign('group', $group);
		
		if(!empty($id)) {
			@$members = DAO_Group::getGroupMembers($id);
			$tpl->assign('members', $members);
		}
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_GROUP);
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_GROUP, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Workers

		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/groups/edit_group.tpl');
	}
	
	function saveGroupAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		@$name = DevblocksPlatform::importGPC($_POST['name']);
		@$delete = DevblocksPlatform::importGPC($_POST['delete_box']);
		@$delete_move_id = DevblocksPlatform::importGPC($_POST['delete_move_id'],'integer',0);
		
		if(empty($name)) $name = "No Name";
		
		if(!empty($id) && !empty($delete)) {
			if(!empty($delete_move_id)) {
				if(null != ($group = DAO_Group::get($id))) {
					$fields = array(
						DAO_Ticket::GROUP_ID => $delete_move_id
					);
					$where = sprintf("%s=%d",
						DAO_Ticket::GROUP_ID,
						$id
					);
					DAO_Ticket::updateWhere($fields, $where);
					
					// If this was the default group, move it.
					if($group->is_default)
						DAO_Group::setDefaultGroup($delete_move_id);
					
					DAO_Group::delete($group->id);
				}
				
			}
			
		} elseif(!empty($id)) {
			$fields = array(
				DAO_Group::NAME => $name,
			);
			DAO_Group::update($id, $fields);
			
		} else {
			$fields = array(
				DAO_Group::NAME => $name,
			);
			$id = DAO_Group::create($fields);
		}
		
		// Members
		
		@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_ids'],'array',array());
		@$worker_levels = DevblocksPlatform::importGPC($_POST['worker_levels'],'array',array());
		
		@$members = DAO_Group::getGroupMembers($id);
		
		if(is_array($worker_ids) && !empty($worker_ids))
		foreach($worker_ids as $idx => $worker_id) {
			@$level = $worker_levels[$idx];
			if(isset($members[$worker_id]) && empty($level)) {
				DAO_Group::unsetGroupMember($id, $worker_id);
			} elseif(!empty($level)) { // member|manager
				 DAO_Group::setGroupMember($id, $worker_id, (1==$level)?false:true);
			}
		}
		
		// Custom fields
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_GROUP, $id, $field_ids);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','groups')));
	}
}