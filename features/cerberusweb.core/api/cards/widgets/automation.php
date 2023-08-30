<?php
class CardWidget_Automation extends Extension_CardWidget {
	const ID = 'cerb.card.widget.automation';
	
	function invoke(string $action, Model_CardWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_CardWidget $model, $context, $context_id) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$automations_kata = $model->extension_params['automations_kata'] ?? '';
		
		$initial_state = [
			'record__context' => $context,
			'record_id' => $context_id,
			
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		];
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance($initial_state);
		
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
	
	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/automation/config.tpl');
	}
	
	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}