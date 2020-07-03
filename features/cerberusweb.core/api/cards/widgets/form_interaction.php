<?php
/**
 * Class CardWidget_FormInteraction
 */
class CardWidget_FormInteraction extends Extension_CardWidget {
	const ID = 'cerb.card.widget.form_interaction';
	
	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/form_interaction/config.tpl');
	}
	
	function invokeConfig($action, Model_CardWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_CardWidget::isWriteableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		switch($action) {
			case 'previewInteractions':
				return $this->_cardWidgetConfig_previewInteractions($model);
		}
		return false;
	}
	
	private function _cardWidgetConfig_previewInteractions(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$interactions_kata = DevblocksPlatform::importGPC($_POST['interactions_kata'], 'string', '');
		
		$model->extension_params['interactions_kata'] = $interactions_kata;
		
		if(false == ($record_context_ext = Extension_DevblocksContext::getByAlias($model->record_type, true)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$dao_class = $record_context_ext->getDaoClass();
		
		if($dao_class && method_exists($dao_class, 'random')) {
			$values = [
				'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
				'widget_id' => $model->id,
				
				'record__context' => $record_context_ext->id,
				'record_id' => $dao_class::random(),
			];
			
		} else {
			$values = [];
		}
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		$toolbar = $this->getInteractions($model, $dict);
		
		$tpl->assign('toolbar', $toolbar);
		$tpl->display('devblocks:devblocks.core::ui/toolbar/preview.tpl');
	}
	
	function render(Model_CardWidget $widget, $context, $context_id) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $widget->id,
			
			'record__context' => $context,
			'record_id' => $context_id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);
		
		$this->renderInteractionChooser($widget, $dict);
	}
	
	function getInteractions(Model_CardWidget $widget, DevblocksDictionaryDelegate $dict) {
		$interactions_kata = $widget->extension_params['interactions_kata'];
		return DevblocksPlatform::services()->ui()->toolbar()->parse($interactions_kata, $dict);
	}
	
	function renderInteractionChooser(Model_CardWidget $widget, DevblocksDictionaryDelegate $dict) {
		$tpl = DevblocksPlatform::services()->template();
		
		$interactions = $this->getInteractions($widget, $dict);
		$tpl->assign('interactions', $interactions);
		
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/form_interaction/interaction_chooser.tpl');
	}
}