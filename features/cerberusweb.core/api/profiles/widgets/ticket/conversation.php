<?php
class ProfileWidget_TicketConvo extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.ticket.convo';

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
		$tpl->assign('widget', $model);
		
		// [TODO] Handle focus?
		
		$display_options = [];
		
		if(array_key_exists('expand_all', $_POST))
			$display_options['expand_all'] = DevblocksPlatform::importGPC($_POST['expand_all'], 'bit', 0);
		
		$display_options['comments_mode'] = DevblocksPlatform::importVar(@$model->extension_params['comments_mode'], 'int', 0);
		
		$this->_showConversation($context_id, $display_options);
	}
	
	private function _showConversation($id, $display_options=[]) {
		@$expand_all = DevblocksPlatform::importVar($display_options['expand_all'], 'bit', 0);
		@$comments_mode = DevblocksPlatform::importVar($display_options['comments_mode'], 'int', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('comments_mode', $comments_mode);
		
		@$active_worker = CerberusApplication::getActiveWorker();
		
		$prefs_mail_always_read_all = DAO_WorkerPref::get($active_worker->id, 'mail_always_read_all', 0);
		
		if($expand_all || $prefs_mail_always_read_all)
			$expand_all = 1;
		
		$tpl->assign('expand_all', $expand_all);
		
		$ticket = DAO_Ticket::get($id);
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
			$id,
			DAO_MailQueue::TYPE,
			Cerb_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_REPLY),
			DAO_MailQueue::TYPE,
			Cerb_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_FORWARD)
		));
		
		if(!empty($drafts))
			$tpl->assign('drafts', $drafts);
		
		// Only unqueued drafts
		$pending_drafts = [];
		
		if(!empty($drafts) && is_array($drafts))
		foreach($drafts as $draft_id => $draft) {
			if(!$draft->is_queued)
				$pending_drafts[$draft_id] = $draft;
		}
		
		if(!empty($pending_drafts))
			$tpl->assign('pending_drafts', $pending_drafts);
		
		// Messages
		$messages = $ticket->getMessages();
		
		arsort($messages);
		
		$tpl->assign('latest_message_id',key($messages));
		$tpl->assign('messages', $messages);
		
		// Thread comments and messages on the same level
		$convo_timeline = [];
		
		// Track senders and their orgs
		$message_senders = [];
		$message_sender_orgs = [];
		
		// Loop messages
		if(is_array($messages))
		foreach($messages as $message_id => $message) { /* @var $message Model_Message */
			$key = $message->created_date . '_m' . $message_id;
			// build a chrono index of messages
			$convo_timeline[$key] = array('m', $message_id);
			
			// If we haven't cached this sender address yet
			if($message->address_id)
				$message_senders[$message->address_id] = null;
		}
		
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

		// Comments
		
		// If we're not hiding them
		if(1 != $comments_mode) {
			$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $id);
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
					$convo_timeline[$key] = array('c',$comment_id);
				}
			}
		}
		
		// Thread drafts into conversation
		if(!empty($drafts)) {
			foreach($drafts as $draft_id => $draft) { /* @var $draft Model_MailQueue */
				if(!empty($draft->queue_delivery_date)) {
					$key = $draft->queue_delivery_date . '_d' . $draft_id;
				} else {
					$key = $draft->updated . '_d' . $draft_id;
				}
				$convo_timeline[$key] = array('d', $draft_id);
			}
		}
		
		// Sort the timeline
		if(!$expand_all) {
			krsort($convo_timeline);
		} else {
			ksort($convo_timeline);
		}
		$tpl->assign('convo_timeline', $convo_timeline);
		
		// Message Notes
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, array_keys($messages));
		$message_notes = [];
		// Index notes by message id
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($message_notes[$note->context_id]))
				$message_notes[$note->context_id] = [];
			$message_notes[$note->context_id][$note->id] = $note;
		}
		$tpl->assign('message_notes', $message_notes);
		
		// Draft Notes
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_DRAFT, array_keys($drafts));
		$draft_notes = [];
		// Index notes by draft id
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($draft_notes[$note->context_id]))
				$draft_notes[$note->context_id] = [];
			$draft_notes[$note->context_id][$note->id] = $note;
		}
		$tpl->assign('draft_notes', $draft_notes);
		
		// Message toolbar items
		$messageToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.message.toolbaritem', true);
		if(!empty($messageToolbarItems))
			$tpl->assign('message_toolbaritems', $messageToolbarItems);
		
		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Prefs
		$mail_reply_button = DAO_WorkerPref::get($active_worker->id, 'mail_reply_button', 0);
		$tpl->assign('mail_reply_button', $mail_reply_button);
		
		$mail_reply_format = DAO_WorkerPref::get($active_worker->id, 'mail_reply_format', '');
		$tpl->assign('mail_reply_format', $mail_reply_format);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/ticket/convo/conversation.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/ticket/convo/config.tpl');
	}
}