<?php
class ProfileWidget_Comments extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.comments';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		
		$comments = DAO_Comment::getByContext($context, $context_id);
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('comments', $comments);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/comments/comments.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$context_mfts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('context_mfts', $context_mfts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/comments/config.tpl');
	}
}