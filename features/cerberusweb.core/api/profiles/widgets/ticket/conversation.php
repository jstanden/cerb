<?php
class ProfileWidget_TicketConvo extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.ticket.convo';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/ticket/convo/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$this->_showConversation($model, $context, $context_id);
	}
	
	private function _showConversation(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('widget', $model);
		
		// Display options
		
		$display_options = [];
		
		$mail_always_read_all = DAO_WorkerPref::get($active_worker->id, 'mail_always_read_all', 0);
		$mail_reply_button = DAO_WorkerPref::get($active_worker->id, 'mail_reply_button', 0);
		$mail_reply_format = DAO_WorkerPref::get($active_worker->id, 'mail_reply_format', '');
		
		if($mail_always_read_all) {
			$display_options['expand_all'] = 1;
		} else if(array_key_exists('expand_all', $_POST)) {
			$display_options['expand_all'] = DevblocksPlatform::importGPC($_POST['expand_all'], 'bit', 0);
		}
		
		$display_options['comments_mode'] = DevblocksPlatform::importVar(@$model->extension_params['comments_mode'], 'int', 0);
		
		// Assignments
		
		$tpl->assign('comments_mode', $display_options['comments_mode'] ?? 0);
		$tpl->assign('expand_all', $display_options['expand_all'] ?? 0);
		$tpl->assign('mail_reply_button', $mail_reply_button);
		$tpl->assign('mail_reply_format', $mail_reply_format);
		$tpl->assign('workers', DAO_Worker::getAll());
		
		// Display by record type
		
		if($context == CerberusContexts::CONTEXT_TICKET) {
			$this->_showTicketConversation($context_id, $display_options);
		} else if ($context == CerberusContexts::CONTEXT_MESSAGE) {
			$this->_showMessageConversation($context_id, $display_options);
		} else if ($context == CerberusContexts::CONTEXT_DRAFT) {
			$this->_showDraftConversation($context_id, $display_options);
		}
	}
	
	private function _threadDrafts(array $drafts, array &$convo_timeline, array $display_options) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!empty($drafts))
			$tpl->assign('drafts', $drafts);
		
		// Draft Notes
		
		$draft_notes = [];
		
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_DRAFT, array_keys($drafts));
		
		// Index notes by draft id
		if(is_array($notes)) {
			foreach ($notes as $note) {
				if (!isset($draft_notes[$note->context_id]))
					$draft_notes[$note->context_id] = [];
				$draft_notes[$note->context_id][$note->id] = $note;
			}
		}
		
		$tpl->assign('draft_notes', $draft_notes);
		
		// Thread drafts into conversation (always at top)
		if(!empty($drafts)) {
			foreach($drafts as $draft_id => $draft) { /* @var $draft Model_MailQueue */
				if(!empty($draft->queue_delivery_date)) {
					$key = $draft->queue_delivery_date . '_d' . $draft_id;
				} else {
					$key = $draft->updated . '_d' . $draft_id;
				}
				$convo_timeline[$key] = [
					'type' => 'd',
					'id' => $draft_id
				];
			}
		}		
	}
	
	private function _threadMessages(array $messages, &$convo_timeline, array $display_options) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$expand_all = $display_options['expand_all'] ?? 0;
		
		// Track senders and their orgs
		
		$message_senders = [];
		$message_sender_orgs = [];
		
		// Message Notes
		
		$message_notes = [];
		
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, array_keys($messages));
		
		// Index notes by message id
		if(is_array($notes))
			foreach($notes as $note) {
				if(!isset($message_notes[$note->context_id]))
					$message_notes[$note->context_id] = [];
				$message_notes[$note->context_id][$note->id] = $note;
			}
		$tpl->assign('message_notes', $message_notes);
		
		// Loop messages
		
		arsort($messages);
		
		$has_seen_worker_reply = false;
		$messages_seen = 0;
		$messages_highlighted = [];
		
		foreach($messages as $message_id => $message) { /* @var $message Model_Message */
			$key = $message->created_date . '_m' . $message_id;
			
			if($message->is_outgoing)
				$has_seen_worker_reply = true;
			
			$messages_seen++;
			
			$expanded =
				$expand_all // If expanding everything
				|| 1 == $messages_seen // If it's the first message
				|| !$has_seen_worker_reply // If it's a series of client messages
				|| array_key_exists($message_id, $message_notes) // If we have sticky notes
			;
			
			if(!$has_seen_worker_reply)
				$messages_highlighted[$message_id] = $message;
			
			// build a chrono index of messages
			$convo_timeline[$key] = [
				'type' => 'm',
				'id' => $message_id,
				'expand' => $expanded,
			];
			
			// If we haven't cached this sender address yet
			if($message->address_id)
				$message_senders[$message->address_id] = null;
		}
		
		if(1 == count($messages_highlighted))
			$messages_highlighted = [];
		
		$tpl->assign('messages', $messages);
		$tpl->assign('messages_highlighted', $messages_highlighted);
		
		// Bulk load sender address records
		$message_senders = CerberusApplication::hashLookupAddresses(array_keys($message_senders));
		
		// Bulk load org records
		array_walk($message_senders, function($sender) use (&$message_sender_orgs) { /* @var $sender Model_Address */
			if($sender->contact_org_id)
				$message_sender_orgs[$sender->contact_org_id] = null;
		});
		$message_sender_orgs = CerberusApplication::hashLookupOrgs(array_keys($message_sender_orgs));
		
		$tpl->assign('message_senders', $message_senders);
		$tpl->assign('message_sender_orgs', $message_sender_orgs);
	}
	
	private function _threadComments(array $comments, &$convo_timeline, array $display_options) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$comments_mode = $display_options['comments_mode'] ?? 0;
		
		$tpl->assign('comments', $comments);
		
		if($comments) {
			$pin_ts = null;
			
			if(2 == $comments_mode) {
				$pin_ts = max(array_column(DevblocksPlatform::objectsToArrays($comments), 'created'));
			}
			
			// build a chrono index of comments
			foreach($comments as $comment_id => $comment) { /* @var $comment Model_Comment */
				if($pin_ts && $comment->created == $pin_ts) {
					$key = time() . '_c' . $comment_id;
				} else {
					$key = $comment->created . '_c' . $comment_id;
				}
				$convo_timeline[$key] = [
					'type' => 'c',
					'id' => $comment_id,
				];
			}
		}
		
		// Comment notes
		
		$comment_notes = [];
		
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_COMMENT, array_keys($comments));
		
		// Index notes by comment id
		if(is_array($notes))
			foreach($notes as $note) {
				if(!isset($comment_notes[$note->context_id]))
					$comment_notes[$note->context_id] = [];
				$comment_notes[$note->context_id][$note->id] = $note;
			}
		$tpl->assign('comment_notes', $comment_notes);
	}
	
	private function _renderTimeline(array $convo_timeline, array $display_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		// Sort the timeline
		
		uksort(
			$convo_timeline,
			[$this, '_sortTimeline']
		);
		
		if($display_options['expand_all'] ?? 0) {
			$convo_timeline = array_reverse($convo_timeline, true);
		}
		
		$tpl->assign('convo_timeline', $convo_timeline);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/ticket/convo/conversation.tpl');
	}
	
	private function _showTicketConversation($context_id, $display_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		// Thread comments and messages on the same level
		
		$convo_timeline = [];
		
		if(false == ($ticket = DAO_Ticket::get($context_id)))
			return;
		
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());
		
		// If deleted, check for a new merge parent URL
		
		if($ticket->status_id == Model_Ticket::STATUS_DELETED) {
			if(false !== ($new_mask = DAO_Ticket::getMergeParentByMask($ticket->mask))) {
				if(false !== ($merge_parent = DAO_Ticket::getTicketByMask($new_mask)))
					if(!empty($merge_parent->mask)) {
						$tpl->assign('merge_parent', $merge_parent);
					}
			}
		}
		
		// Drafts
		
		$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d AND (%s = %s OR %s = %s)",
			DAO_MailQueue::TICKET_ID,
			$context_id,
			DAO_MailQueue::TYPE,
			Cerb_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_REPLY),
			DAO_MailQueue::TYPE,
			Cerb_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_FORWARD)
		));
		
		$this->_threadDrafts($drafts, $convo_timeline, $display_options);
		
		// Messages
		
		if(false == ($messages = $ticket->getMessages()))
			$messages = [];
		
		$this->_threadMessages($messages, $convo_timeline, $display_options);
		
		// Comments
		
		if(1 != ($display_options['comments_mode'] ?? 0)) {
			$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $context_id);
			
			$this->_threadComments($comments, $convo_timeline, $display_options);
		}
		
		$this->_renderTimeline($convo_timeline, $display_options);
	}
	
	private function _showMessageConversation($message_id, $display_options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		$convo_timeline = [];
		
		if(false == ($message = DAO_Message::get($message_id)))
			return;
		
		if(false == ($ticket = $message->getTicket()))
			return;
		
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());
		
		// Messages
		
		$this->_threadMessages([$message_id => $message], $convo_timeline, $display_options);
		
		$this->_renderTimeline($convo_timeline, $display_options);
	}
	
	private function _showDraftConversation($draft_id, $display_options=[]) {
		$convo_timeline = [];
		
		if(false == ($draft = DAO_MailQueue::get($draft_id)))
			return;
		
		// Drafts
		
		$this->_threadDrafts([$draft_id => $draft], $convo_timeline, $display_options);
		
		$this->_renderTimeline($convo_timeline, $display_options);
	}
	
	private function _sortTimeline($a, $b) {
		$a_type = DevblocksPlatform::services()->string()->strAfter($a, '_')[0];
		$b_type = DevblocksPlatform::services()->string()->strAfter($b, '_')[0];
		
		if($a_type == 'd' && $b_type != 'd') {
			return -1;
		} else if($b_type == 'd' && $a_type != 'd') {
			return 1;
		} else {
			return $b <=> $a;
		}
	}
}