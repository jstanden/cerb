<?php
class CardWidget_KbArticle extends Extension_CardWidget {
	const ID = 'cerb.card.widget.kb_article.viewer';
	
	function __construct($manifest = null) {
		parent::__construct($manifest);
	}
	
	function render(Model_CardWidget $model, $context, $context_id) {
		@$target_context_id = $model->extension_params['article_id'];
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$target_context_id = $tpl_builder->build($target_context_id, $dict);
		
		if(false == ($article = DAO_KbArticle::get($target_context_id)))
			return;
		
		$tpl->assign('content', $article->getContentProtected());
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/kb_article/render.tpl');
	}
	
	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/kb_article/config.tpl');
	}
}