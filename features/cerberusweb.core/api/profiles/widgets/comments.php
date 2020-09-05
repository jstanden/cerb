<?php
class ProfileWidget_Comments extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.comments';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$target_context = $context;
		$target_context_id = $context_id;
		
		if(array_key_exists('context', $model->extension_params) && $model->extension_params['context']) {
			$dict = DevblocksDictionaryDelegate::instance([
				'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
				'current_worker_id' => $active_worker->id,
				
				'record__context' => $context,
				'record_id' => $context_id,
				
				'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
				'widget_id' => $model->id,
			]);
			
			@$target_context = $tpl_builder->build($model->extension_params['context'], $dict);
			@$target_context_id = $tpl_builder->build($model->extension_params['context_id'], $dict);
		}
		
		$comments = DAO_Comment::getByContext($target_context, $target_context_id);
		
		// Comment notes
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_COMMENT, array_keys($comments));
		$comment_notes = [];
		// Index notes by comment id
		if(is_array($notes))
			foreach($notes as $note) {
				if(!isset($comment_notes[$note->context_id]))
					$comment_notes[$note->context_id] = [];
				$comment_notes[$note->context_id][$note->id] = $note;
			}
		$tpl->assign('comment_notes', $comment_notes);
		
		$tpl->assign('context', $target_context);
		$tpl->assign('context_id', $target_context_id);
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
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
}