<?php
class CardWidget_ClassifierTrainer extends Extension_CardWidget {
	const ID = 'cerb.card.widget.classifier.trainer';
	
	function invoke(string $action, Model_CardWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_CardWidget $model, $context, $context_id) {
		$target_context_id = $model->extension_params['classifier_id'] ?? null;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$target_context_id = intval($tpl_builder->build($target_context_id, $dict));
		
		if(!($classifier = DAO_Classifier::get($target_context_id)))
			return;
		
		$tpl->assign('classifier', $classifier);

		$tpl->assign('is_writeable', Context_Classifier::isWriteableByActor($classifier, $active_worker));
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/classifier_trainer/render.tpl');
	}
	
	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/classifier_trainer/config.tpl');
	}
	
	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}