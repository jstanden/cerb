<?php
class CardWidget_Conversation extends Extension_CardWidget {
	const ID = 'cerb.card.widget.conversation';
	
	function invoke(string $action, Model_CardWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_CardWidget $model, $context, $context_id) {
		$target_context = $model->extension_params['context'] ?? null;
		$target_context_id = $model->extension_params['context_id'] ?? null;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
		]);
		
		if(!$target_context)
			$target_context = $context;
		
		$target_context_id = intval($tpl_builder->build($target_context_id, $dict));
		$timeline_json = '[]';
		$start_at = 0;
		
		if (in_array($target_context, [CerberusContexts::CONTEXT_MESSAGE, CerberusContexts::CONTEXT_TICKET])) {
			if($target_context == CerberusContexts::CONTEXT_MESSAGE) {
				if(!($ticket = DAO_Ticket::getTicketByMessageId($target_context_id)))
					return;
			} else {
				if(!($ticket = DAO_Ticket::get($target_context_id)))
					return;
			}
			
			// Worker permissions
			if(!Context_Ticket::isReadableByActor($ticket, $active_worker)) {
				echo DevblocksPlatform::translate('error.core.no_acl.view');
				return;
			}
			
			$ticket_timeline = $ticket->getTimeline(true, $target_context, $target_context_id, $start_at);
			
			if($target_context == CerberusContexts::CONTEXT_TICKET && count($ticket_timeline))
				$start_at = count($ticket_timeline) - 1;
			
			$timeline_json = Page_Profiles::getTimelineJson($ticket_timeline, true, $start_at);
			
		} else if($target_context == CerberusContexts::CONTEXT_COMMENT) {
			if(($comment = DAO_Comment::get($target_context_id))) {
				$comment_timeline = $comment->getTimeline(true, $target_context_id, $start_at);
				$timeline_json = Page_Profiles::getTimelineJson($comment_timeline, true, $start_at);
			}
			
		} else if ($target_context == CerberusContexts::CONTEXT_DRAFT) {
			if(($draft = DAO_MailQueue::get($target_context_id))) {
				$draft_timeline = $draft->getTimeline();
				$timeline_json = Page_Profiles::getTimelineJson($draft_timeline, true);
			}
		
		} else {
			$timeline_json = Page_Profiles::getTimelineJson(Extension_DevblocksContext::getTimelineComments($target_context, $target_context_id));
		}
		
		if(!is_string($timeline_json))
			return;
		
		$tpl->assign('timeline_json', $timeline_json);
		
		$tpl->assign('dict', $dict);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/conversation/render.tpl');
	}
	
	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		$context_mfts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('context_mfts', $context_mfts);
		
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/conversation/config.tpl');
	}
	
	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}