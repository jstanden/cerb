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
		
		$dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$target_behavior_id = $tpl_builder->build($target_behavior_id, $dict);
		
		if(false == ($model = DAO_TriggerEvent::get($target_behavior_id)))
			return;
		
		if(false == ($event = $model->getEvent()))
			return;
		
		if(false == ($bot = $model->getBot()))
			$bot = new Model_Bot();
		
		$tpl->assign('behavior', $model);
		$tpl->assign('event', $event->manifest);
		$tpl->assign('va', $bot);
		
		$tpl->assign('dict', $dict);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/behavior_tree/render.tpl');
	}
	
	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/behavior_tree/config.tpl');
	}
}