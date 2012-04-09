<?php
class Subcontroller_Internal_VirtualAttendants {

	public static function showScheduledBehaviorAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		// Remember tab
		if(!empty($point))
			$visit->set($point, 'behavior');

		// Admins can see all owners at once
		if(empty($context) && !$active_worker->is_superuser)
			return;
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		/*
		 * Secure looking at other worker tabs (check superuser, worker_id)
		 */
		
		if(null == ($ctx = Extension_DevblocksContext::get($context)))
			return;
		
		if(!$active_worker->is_superuser) {
			if(!$ctx->authorize($context_id, $active_worker))
				return;
		}

		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'va_schedbeh_' . DevblocksPlatform::strAlphaNum($context . '_' . $context_id, '_');
		$defaults->class_name = 'View_ContextScheduledBehavior';
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);

		if(empty($context) && $active_worker->is_superuser) {
			$view->addParamsRequired(array(), true);
			
		} else {
			$view->addParamsRequired(array(
				'_privs' => array(
					DevblocksSearchCriteria::GROUP_AND,
					new DevblocksSearchCriteria(SearchFields_ContextScheduledBehavior::BEHAVIOR_OWNER_CONTEXT, '=', $context),
					new DevblocksSearchCriteria(SearchFields_ContextScheduledBehavior::BEHAVIOR_OWNER_CONTEXT_ID, '=', $context_id),
				)
			), true);
		}
		
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);

		// Macros

		$meta = $ctx->getMeta($context_id);		
		$tpl->assign('return_url', @$meta['permalink']);
		
		$macros = DAO_TriggerEvent::getByOwner($context, $context_id, 'event.macro.worker');
		$tpl->assign('macros', $macros);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/va/scheduled_behavior/tab.tpl');
	}
	
};