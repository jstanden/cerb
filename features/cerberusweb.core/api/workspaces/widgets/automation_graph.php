<?php
class WorkspaceWidget_AutomationGraph extends Extension_WorkspaceWidget {
	const ID = 'cerb.workspace.widget.automation.graph';

	function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		return false;
	}

	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		WidgetHelper_AutomationGraph::renderForWidget($tpl, $widget);
	}

	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/automation_graph/config.tpl');
	}

	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}

	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);

		DAO_WorkspaceWidget::update($widget->id, [
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		]);

		return true;
	}
}
