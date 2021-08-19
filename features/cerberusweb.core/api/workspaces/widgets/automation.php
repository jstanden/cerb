<?php
class WorkspaceWidget_Automation extends Extension_WorkspaceWidget {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$automations_kata = $widget->params['automations_kata'] ?? '';
		
		$initial_state = [
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		];
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance($initial_state);

		$widget->_loadDashboardPrefsForWorker($active_worker, $toolbar_dict);
		
		if(false == ($handlers = DevblocksPlatform::services()->ui()->eventHandler()->parse($automations_kata, $toolbar_dict, $error)))
			return;
		
		$automation_results = DevblocksPlatform::services()->ui()->eventHandler()->handleOnce(
			AutomationTrigger_UiWidget::ID,
			$handlers,
			$initial_state,
			$error
		);
		
		if(false == $automation_results || !($automation_results instanceof DevblocksDictionaryDelegate))
			return;
		
		$html = $automation_results->getKeyPath('__return.html');
	
		echo $html;
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/automation/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, [
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		]);
		
		return true;
	}
};