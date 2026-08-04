<?php
class ProfileWidget_AutomationGraph extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.automation.graph';

	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		return false;
	}

	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		WidgetHelper_AutomationGraph::render($tpl, $model, $context, $context_id, CerberusContexts::CONTEXT_PROFILE_WIDGET);
	}

	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/automation_graph/config.tpl');
	}

	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
}
