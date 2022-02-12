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
		$messages_expanded = [];
		
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
			
			if($expanded)
				$messages_expanded[] = $message_id;
		}
		
		if(1 == count($messages_highlighted))
			$messages_highlighted = [];
		
		$tpl->assign('messages', $messages);
		$tpl->assign('messages_highlighted', $messages_highlighted);
		
		// Bulk load sender address records
		$message_sender_ids = array_unique(array_column($messages, 'address_id'));
		$message_senders = DAO_Address::getIds($message_sender_ids);
		
		// Bulk load worker records
		$message_worker_ids = array_diff(array_unique(array_column($messages, 'worker_id')), [0]);
		$message_workers = DAO_Worker::getIds($message_worker_ids);
		
		// Bulk load contact records
		$message_contact_ids = array_diff(array_unique(array_column($message_senders, 'contact_id')), [0]);
		$message_contacts = DAO_Contact::getIds($message_contact_ids);
		
		// Bulk load org records
		$message_sender_org_ids = array_diff(
			array_unique(
				array_column($message_senders, 'contact_org_id')
				+ array_column($message_contacts, 'org_id')
			),
			[0]
		);
		$message_sender_orgs = DAO_ContactOrg::getIds($message_sender_org_ids);
		
		// Bulk load custom fields
		$message_custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MESSAGE, $messages_expanded);
		
		// Bulk load attachments
		$message_attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MESSAGE, $messages_expanded, false);
		
		// Bulk load headers
		$message_headers = DAO_MessageHeaders::getRaws(array_keys($messages));
		
		// Cache references to reduce lookups
		foreach($messages as $message_id => $message) {
			if($message->address_id && array_key_exists($message->address_id, $message_senders)) {
				$message_sender = $message_senders[$message->address_id]; /* @var $message_sender Model_Address */
				
				if($message_sender->contact_id && array_key_exists($message_sender->contact_id, $message_contacts)) {
					$contact = $message_contacts[$message_sender->contact_id];
					
					if($contact->org_id && array_key_exists($contact->org_id, $message_sender_orgs)) {
						$contact->setOrg($message_sender_orgs[$contact->org_id]);
					}
					
					$message_sender->setContact($contact);
				}
				
				if($message_sender->contact_org_id && array_key_exists($message_sender->contact_org_id, $message_sender_ids)) {
					$message_sender->setOrg($message_sender_orgs[$message_sender->contact_org_id]);
				}
					
				$message->setSender($message_sender);
			}
			
			if(array_key_exists($message_id, $message_custom_field_values))
				$message->setCustomFieldValues($message_custom_field_values[$message_id]);
			
			if($message->worker_id && array_key_exists($message->worker_id, $message_workers)) {
				$message->setWorker($message_workers[$message->worker_id]);
			}
			
			if(array_key_exists($message_id, $message_headers)) {
				$message->setHeadersRaw($message_headers[$message_id]);
			}
			
			if(array_key_exists($message_id, $message_attachments)) {
				$message->setAttachments($message_attachments[$message_id]);
			}
		}
	}
	
	private function _threadComments(array $comments, &$convo_timeline, array $display_options) {
		$tpl = DevblocksPlatform::services()->template();
		
		$comments_mode = $display_options['comments_mode'] ?? 0;
		
		$tpl->assign('comments', $comments);
		
		if($comments) {
			$pin_ts = null;
			
			// [TODO] This comments mode is deprecated as of 10.2 @deprecated 
			if(2 == $comments_mode) {
				$pin_ts = max(array_column(DevblocksPlatform::objectsToArrays($comments), 'created'));
			}
			
			// build a chrono index of comments
			foreach($comments as $comment_id => $comment) { /* @var $comment Model_Comment */
				if($comment->is_pinned || ($pin_ts && $comment->created == $pin_ts)) {
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