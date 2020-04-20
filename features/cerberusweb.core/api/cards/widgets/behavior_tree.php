<?php
class CardWidget_BehaviorTree extends Extension_CardWidget {
	const ID = 'cerb.card.widget.behavior.tree';
	
	function __construct($manifest = null) {
		parent::__construct($manifest);
	}
	
	function render(Model_CardWidget $model, $context, $context_id) {
		@$target_behavior_id = $model->extension_params['behavior_id'];
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$target_behavior_id = $tpl_builder->build($target_behavior_id, $dict);
		
		if(false == ($behavior = DAO_TriggerEvent::get($target_behavior_id)))
			return;
		
		if(false == ($event = $behavior->getEvent()))
			return;
		
		if(false == ($bot = $behavior->getBot()))
			$bot = new Model_Bot();
		
		$tpl->assign('behavior', $behavior);
		$tpl->assign('event', $event->manifest);
		$tpl->assign('va', $bot);
		$tpl->assign('is_writeable', Context_Bot::isWriteableByActor($model, $active_worker));
		
		$tpl->assign('dict', $dict);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/behavior_tree/render.tpl');
	}
	
	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/behavior_tree/config.tpl');
	}
	
	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}