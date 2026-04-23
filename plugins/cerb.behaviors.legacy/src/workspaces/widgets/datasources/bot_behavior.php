<?php
class WorkspaceWidgetDatasource_BotBehavior extends Extension_WorkspaceWidgetDatasource {
	function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $widget);
		$tpl->assign('params', $params);
		$tpl->assign('params_prefix', $params);
		
		$tpl->display('devblocks:cerb.behaviors.legacy::internal/workspaces/widgets/datasources/config_bot_behavior.tpl');
	}
	
	function getData(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null) {
		$behavior_id = $widget->params['behavior_id'] ?? null;
		
		if(!$behavior_id
			|| !($widget_behavior = DAO_TriggerEvent::get($behavior_id))
			|| $widget_behavior->event_point != Event_DashboardWidgetGetMetric::ID
		) {
			return false;
		}
		
		$actions = [];
		
		$event_model = new Model_DevblocksEvent(
			Event_DashboardWidgetGetMetric::ID,
			array(
				'widget' => $widget,
				'actions' => &$actions,
			)
		);
		
		if(!($event = $widget_behavior->getEvent()))
			return;
		
		$event->setEvent($event_model, $widget_behavior);
		
		$values = $event->getValues();
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$widget_behavior->runDecisionTree($dict, false, $event);
		
		$metric_value = 0;
		
		foreach($actions as $action) {
			switch($action['_action']) {
				case 'return_value':
					$metric_value = @$action['value'];
					break;
			}
		}
		
		$metric_value = floatval(str_replace(',','', $metric_value));
		$params['metric_value'] = $metric_value;
		return $params;
	}
};