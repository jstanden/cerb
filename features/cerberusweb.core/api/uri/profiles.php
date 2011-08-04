<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
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

class Page_Profiles extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;

		@array_shift($stack); // profiles
		@$type = array_shift($stack); // group | worker

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		switch($type) {
			case 'group':
				@$group_id = intval(array_shift($stack));
				$point = 'cerberusweb.profiles.group.' . $group_id;

				if(empty($group_id) || null == ($group = DAO_Group::get($group_id)))
					throw new Exception();
				
				$tpl->assign('group', $group);
				
				// Remember the last tab/URL
				if(null == ($selected_tab = @$response->path[3])) {
					$selected_tab = $visit->get($point, '');
				}
				$tpl->assign('selected_tab', $selected_tab);
				
				$tpl->display('devblocks:cerberusweb.core::profiles/group/index.tpl');
				break;
				
			case 'worker':
				@$id = array_shift($stack);
				
				switch($id) {
					case 'me':
						$worker_id = $active_worker->id;
						break;
						
					default:
						@$worker_id = intval($id);
						break;
				}

				$point = 'cerberusweb.profiles.worker.' . $worker_id;
				
				if(empty($worker_id) || null == ($worker = DAO_Worker::get($worker_id)))
					throw new Exception();
					
				$tpl->assign('worker', $worker);
				
				// Remember the last tab/URL
				if(null == ($selected_tab = @$response->path[3])) {
					$selected_tab = $visit->get($point, '');
				}
				$tpl->assign('selected_tab', $selected_tab);
				
				// Counts
				$counts = DAO_ContextLink::getContextLinkCounts(CerberusContexts::CONTEXT_WORKER, $worker_id);
				$watching_total = intval(array_sum($counts));
				$tpl->assign('watching_total', $watching_total);
				
				$tpl->display('devblocks:cerberusweb.core::profiles/worker/index.tpl');
				break;
				
			default:
				$tpl->display('devblocks:cerberusweb.core::profiles/index.tpl');
				break;
		}
	}
};
