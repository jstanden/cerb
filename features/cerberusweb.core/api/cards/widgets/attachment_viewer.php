<?php
class CardWidget_AttachmentViewer extends Extension_CardWidget {
	const ID = 'cerb.card.widget.attachment.viewer';
	
	function __construct($manifest = null) {
		parent::__construct($manifest);
	}
	
	function render(Model_CardWidget $model, $context, $context_id) {
		@$target_context_id = $model->extension_params['attachment_id'];
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$target_context_id = $tpl_builder->build($target_context_id, $dict);
		
		if(false == ($attachment = DAO_Attachment::get($target_context_id)))
			return;
		
		$dict = DevblocksDictionaryDelegate::instance([
			'_context' => CerberusContexts::CONTEXT_ATTACHMENT,
			'id' => $attachment->id,
		]);
		
		if(!Context_Attachment::isDownloadableByActor($attachment, $active_worker))
			return;
		
		// Attachment context counts
		$tpl->assign('contexts', Extension_DevblocksContext::getAll(false));
		$tpl->assign('context_counts', DAO_Attachment::getLinkCounts($target_context_id));
		
		$tpl->assign('dict', $dict);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/attachment_viewer/render.tpl');
	}
	
	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/attachment_viewer/config.tpl');
	}
	
	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}