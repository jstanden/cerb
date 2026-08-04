<?php
class CardWidget_AutomationGraph extends Extension_CardWidget {
	const ID = 'cerb.card.widget.automation.graph';

	function invoke(string $action, Model_CardWidget $model) {
		return false;
	}

	function render(Model_CardWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		WidgetHelper_AutomationGraph::render($tpl, $model, $context, $context_id, CerberusContexts::CONTEXT_CARD_WIDGET);
	}

	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/automation_graph/config.tpl');
	}

	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}
