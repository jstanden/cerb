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

class Subcontroller_Internal_VirtualAttendants {

	public static function showScheduledBehaviorAction() {
		@$va_id = DevblocksPlatform::importGPC($_REQUEST['va_id'],'integer',0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		// Remember tab
		if(!empty($point))
			$visit->set($point, 'behavior');

		// Admins can see all owners at once
		if(empty($va_id) && !$active_worker->is_superuser)
			return;

		// [TODO] ACL

		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'va_schedbeh_' . $va_id;
		$defaults->class_name = 'View_ContextScheduledBehavior';
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);

		if(empty($va_id) && $active_worker->is_superuser) {
			$view->addParamsRequired(array(), true);
			
		} else {
			$view->addParamsRequired(array(
				'_privs' => array(
					DevblocksSearchCriteria::GROUP_AND,
					new DevblocksSearchCriteria(SearchFields_ContextScheduledBehavior::BEHAVIOR_VIRTUAL_ATTENDANT_ID, '=', $va_id),
				)
			), true);
		}
		
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);

		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/va/scheduled_behavior/tab.tpl');
	}
	
};