<?php
class WidgetHelper_AutomationGraph {
	/**
	 * Card + profile widgets: config lives in extension_params; the configured automation id may contain
	 * {{placeholders}} resolved against the host record. Builds the read-only syntax graph and displays
	 * the Graph|Code switcher shell.
	 */
	static function render($tpl, $model, $context, $context_id, string $widget_context) : void {
		$labels = $values = $merge_token_labels = $merge_token_values = [];

		if($context && $context_id && ($context_ext = Extension_DevblocksContext::get($context))) {
			$dao_class = $context_ext->getDaoClass();

			if(($record = $dao_class::get($context_id))) {
				CerberusContexts::getContext($context, $record, $merge_token_labels, $merge_token_values, null, true, true);
				CerberusContexts::merge('record_', 'Record:', $merge_token_labels, $merge_token_values, $labels, $values);
			}
		}

		$values['widget__context'] = $widget_context;
		$values['widget_id'] = $model->id;
		$dict = DevblocksDictionaryDelegate::instance($values);

		self::_render($tpl, $model->extension_params ?? [], $dict, sprintf('%d_%d', $model->id, intval($context_id)));
	}

	/**
	 * Workspace dashboard widget: config lives in params and there's no host record, so the configured
	 * automation id resolves against the current worker + widget (+ dashboard prefs).
	 */
	static function renderForWidget($tpl, Model_WorkspaceWidget $widget) : void {
		$active_worker = CerberusApplication::getActiveWorker();

		$labels = $values = $merge_token_labels = $merge_token_values = [];

		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $merge_token_labels, $merge_token_values, null, true, true);
		CerberusContexts::merge('current_worker_', 'Current Worker:', $merge_token_labels, $merge_token_values, $labels, $values);

		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $widget, $merge_token_labels, $merge_token_values, null, true, true);
		CerberusContexts::merge('widget_', 'Widget:', $merge_token_labels, $merge_token_values, $labels, $values);

		$dict = DevblocksDictionaryDelegate::instance($values);
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);

		self::_render($tpl, $widget->params ?? [], $dict, sprintf('%d', $widget->id));
	}

	private static function _render($tpl, array $params, DevblocksDictionaryDelegate $dict, string $el_suffix) : void {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();

		$target_automation_id = $params['automation_id'] ?? null;

		if(!$target_automation_id) {
			echo "An automation isn't linked to this widget. Configure it to select one.";
			return;
		}

		$automation_id = intval($tpl_builder->build($target_automation_id, $dict));

		if(!($automation = DAO_Automation::get($automation_id))) {
			echo "The linked automation could not be found.";
			return;
		}

		if(!Context_Automation::isReadableByActor($automation, $active_worker))
			return;

		if(!($graph = $automation->getSyntaxGraphForViewer($error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}

		$tpl->assign('el_id', 'automationGraphWidget' . $el_suffix);
		$tpl->assign('graph', $graph);
		$tpl->assign('automation', $automation);
		$tpl->assign('minimap', !empty($params['minimap']));
		$tpl->assign('graph_height', intval($params['height'] ?? 0) ?: 500);
		$tpl->display('devblocks:cerberusweb.core::internal/automation/graph/widget_shell.tpl');
	}
}
