<?php
class WorkspaceWidget_BehaviorTree extends Extension_WorkspaceWidget {
	const ID = 'cerb.workspace.widget.behavior.tree';
	
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$behavior_id_template = $widget->params['behavior'];
		
		$labels = $values = $merge_token_labels = $merge_token_values = [];
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $merge_token_labels, $merge_token_values, null, true, true);
		
		CerberusContexts::merge(
			'current_worker_',
			'Current Worker:',
			$merge_token_labels,
			$merge_token_values,
			$labels,
			$values
		);
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $widget, $merge_token_labels, $merge_token_values, null, true, true);
		
		CerberusContexts::merge(
			'widget_',
			'Widget:',
			$merge_token_labels,
			$merge_token_values,
			$labels,
			$values
		);
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$behavior_id = $tpl_builder->build($behavior_id_template, $dict);
		
		if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return;
		
		if(false == ($event = $behavior->getEvent()))
			return;
		
		if(false == ($behavior->getBot()))
			return;
		
		$tpl->assign('behavior', $behavior);
		$tpl->assign('event', $event->manifest);
		
		$tpl->display('devblocks:cerberusweb.core::internal/bot/behavior/tab.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/behavior_tree/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
};