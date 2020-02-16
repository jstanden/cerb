<?php
class WorkspaceWidget_BotBehavior extends Extension_WorkspaceWidget {
	function render(Model_WorkspaceWidget $widget) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$behavior_id = $widget->params['behavior_id'];
		@$behavior_vars = DevblocksPlatform::importVar(@$widget->params['behavior_vars'], 'array', []);
		
		if(!$behavior_id 
			|| false == ($widget_behavior = DAO_TriggerEvent::get($behavior_id))
			|| $widget_behavior->event_point != Event_DashboardWidgetRender::ID
			) {
			echo "A bot behavior isn't configured.";
			return;
		}
		
		// Event model
		
		$actions = [];
		
		$event_model = new Model_DevblocksEvent(
			Event_DashboardWidgetRender::ID,
			[
				'widget' => $widget,
				'_variables' => $behavior_vars,
				'actions' => &$actions,
			]
		);
		
		if(false == ($event = $widget_behavior->getEvent()))
			return;
			
		$event->setEvent($event_model, $widget_behavior);
		
		$values = $event->getValues();
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		// Format behavior vars
		
		if(is_array($behavior_vars))
		foreach($behavior_vars as $k => &$v) {
			if(DevblocksPlatform::strStartsWith($k, 'var_')) {
				if(!isset($widget_behavior->variables[$k]))
					continue;
				
				$value = $widget_behavior->formatVariable($widget_behavior->variables[$k], $v, $dict);
				$dict->set($k, $value);
			}
		}
		
		// Run tree
		
		$widget_behavior->runDecisionTree($dict, false, $event);
		
		$value = null;
		
		// [TODO] Do we need to sanitize this output for non-admins?
		
		foreach($actions as $action) {
			switch($action['_action']) {
				case 'render_html':
					$html = @$action['html'];
					echo $html;
					break;
			}
		}
	}
	
	// Config
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		if(empty($widget))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		// Widget
		
		$tpl->assign('widget', $widget);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/bot/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
};