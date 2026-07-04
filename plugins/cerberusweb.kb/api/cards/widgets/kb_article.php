<?php
class CardWidget_KbArticle extends Extension_CardWidget {
	const ID = 'cerb.card.widget.kb_article.viewer';

	function invoke(string $action, Model_CardWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		return false;
	}

	function render(Model_CardWidget $model, $context, $context_id) {
		$target_context_id = $model->extension_params['article_id'] ?? null;

		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();

		$dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);

		$target_context_id = intval($tpl_builder->build($target_context_id, $dict));

		if(!($article = DAO_KbArticle::get($target_context_id)))
			return;

		$tpl->assign('content', $article->getContentProtected());
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.kb::cards/widgets/kb_article/render.tpl');
	}

	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.kb::cards/widgets/kb_article/config.tpl');
	}

	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}
