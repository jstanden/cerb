<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class ChDisplayPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return $worker->hasPriv('core.mail');
	}
	
	function render() {
	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly,
	 * instead of forcing tabs to implement controllers.  This should check
	 * for the *Action() functions just as a handleRequest would
	 */
	/*
	function handleTabActionAction() {
	}
	*/

	function getMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // message id
		@$hide = DevblocksPlatform::importGPC($_REQUEST['hide'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$message = DAO_Message::get($id);
		$tpl->assign('message', $message);
		$tpl->assign('message_id', $message->id);
		
		// Sender info
		$message_senders = array();
		$message_sender_orgs = array();
		
		if(null != ($sender_addy = DAO_Address::get($message->address_id))) {
			$message_senders[$sender_addy->id] = $sender_addy;
			
			if(null != $sender_org = DAO_ContactOrg::get($sender_addy->contact_org_id)) {
				$message_sender_orgs[$sender_org->id] = $sender_org;
			}
		}

		$tpl->assign('message_senders', $message_senders);
		$tpl->assign('message_sender_orgs', $message_sender_orgs);
		
		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Ticket
		$ticket = DAO_Ticket::get($message->ticket_id);
		$tpl->assign('ticket', $ticket);
		
		// Requesters
		$requesters = $ticket->getRequesters();
		$tpl->assign('requesters', $requesters);
		
		// Expanded/Collapsed
		if(empty($hide)) {
			$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $message->ticket_id);
			$message_notes = array();
			// Index notes by message id
			if(is_array($notes))
			foreach($notes as $note) {
				if(!isset($message_notes[$note->context_id]))
					$message_notes[$note->context_id] = array();
				$message_notes[$note->context_id][$note->id] = $note;
			}
			$tpl->assign('message_notes', $message_notes);
		}

		// Message toolbar items
		$messageToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.message.toolbaritem', true);
		if(!empty($messageToolbarItems))
			$tpl->assign('message_toolbaritems', $messageToolbarItems);
		
		// Prefs
		$mail_reply_button = DAO_WorkerPref::get($active_worker->id, 'mail_reply_button', 0);
		$tpl->assign('mail_reply_button', $mail_reply_button);
			
		$tpl->assign('expanded', (empty($hide) ? true : false));

		$tpl->display('devblocks:cerberusweb.core::display/modules/conversation/message.tpl');
	}

	function updatePropertiesAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // ticket id
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		@$spam = DevblocksPlatform::importGPC($_REQUEST['spam'],'integer',0);
		@$deleted = DevblocksPlatform::importGPC($_REQUEST['deleted'],'integer',0);
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			return;
		
		// Group security
		if(!$active_worker->isGroupMember($ticket->group_id))
			return;
			
		// Anti-Spam
		if(!empty($spam)) {
			CerberusBayes::markTicketAsSpam($id);
			// [mdf] if the spam button was clicked override the default params for deleted/closed
			$closed=1;
			$deleted=1;
		}

		// Properties
		$properties = array(
			DAO_Ticket::IS_CLOSED => intval($closed),
			DAO_Ticket::IS_DELETED => intval($deleted),
		);

		// Undeleting?
		if(empty($spam) && empty($closed) && empty($deleted)
			&& $ticket->spam_training == CerberusTicketSpamTraining::SPAM && $ticket->is_closed) {
				$score = CerberusBayes::calculateTicketSpamProbability($id);
				$properties[DAO_Ticket::SPAM_SCORE] = $score['probability'];
				$properties[DAO_Ticket::SPAM_TRAINING] = CerberusTicketSpamTraining::BLANK;
		}
		
		// Don't double set the closed property (auto-close replies)
		if(isset($properties[DAO_Ticket::IS_CLOSED]) && $properties[DAO_Ticket::IS_CLOSED]==$ticket->is_closed)
			unset($properties[DAO_Ticket::IS_CLOSED]);
		
		DAO_Ticket::update($id, $properties);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket->mask)));
		exit;
	}

	function showMergePanelAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// ACL
		if(!$active_worker->hasPriv('core.ticket.view.actions.merge')) {
			return;
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
				
		$tpl->assign('ticket_id', $ticket_id);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/merge_panel.tpl');
	}
	
	function saveMergePanelAction() {
		@$src_ticket_id = DevblocksPlatform::importGPC($_REQUEST['src_ticket_id'],'integer',0);
		@$dst_ticket_ids = DevblocksPlatform::importGPC($_REQUEST['dst_ticket_id'],'array');
		
		$active_worker = CerberusApplication::getActiveWorker();

		if(null == ($src_ticket = DAO_Ticket::get($src_ticket_id)))
			return;
			
		// Group security
		if(!$active_worker->isGroupMember($src_ticket->group_id))
			return;
		
		$refresh_id = !empty($src_ticket) ? $src_ticket->mask : $src_ticket_id;
		
		// ACL
		if(!$active_worker->hasPriv('core.ticket.view.actions.merge')) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$refresh_id)));
			exit;
		}
		
		// Load and filter by the current worker permissions
		$active_worker_memberships = $active_worker->getMemberships();
		
		$dst_tickets = DAO_Ticket::getTickets($dst_ticket_ids);
		foreach($dst_tickets as $dst_ticket_id => $dst_ticket) {
			if($active_worker->is_superuser
				|| (isset($active_worker_memberships[$dst_ticket->group_id]))) {
					// Permission
			} else {
				unset($dst_tickets[$dst_ticket_id]);
			}
		}
		
		// Load the merge IDs
		$merge_ids = array_merge(array($src_ticket_id), array_keys($dst_tickets));

		// Abort if we don't have a source and at least one target
		if(count($merge_ids) < 2) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$refresh_id)));
		}
		
		if(false != ($oldest_id = DAO_Ticket::merge($merge_ids))) {
			if($oldest_id == $src_ticket->id)
				$refresh_id = $src_ticket->mask;
			elseif(isset($dst_tickets[$oldest_id]))
				$refresh_id = $dst_tickets[$oldest_id]->mask;
		}
		
		// Redisplay
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$refresh_id)));
	}
	
	/**
	 * Enter description here...
	 * @param string $message_id
	 */
	private function _renderNotes($message_id) {
		$tpl = DevblocksPlatform::getTemplateService();
				$tpl->assign('message_id', $message_id);
		
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, $message_id);
		$message_notes = array();
		
		// [TODO] DAO-ize? (shared in render())
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($message_notes[$note->context_id]))
				$message_notes[$note->context_id] = array();
			$message_notes[$note->context_id][$note->id] = $note;
		}
		$tpl->assign('message_notes', $message_notes);
				
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->display('devblocks:cerberusweb.core::display/modules/conversation/notes.tpl');
	}
	
	// [TODO] Merge w/ the new comments functionality?
	function addNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id',$id);
		
		$message = DAO_Message::get($id);
		$ticket = DAO_Ticket::get($message->ticket_id);
		$tpl->assign('message',$message);
		$tpl->assign('ticket',$ticket);
		
		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/add_note.tpl');
	}
	
	function doAddNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$worker = CerberusApplication::getActiveWorker();
		
		@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		
		$fields = array(
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_MESSAGE,
			DAO_Comment::CONTEXT_ID => $id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::ADDRESS_ID => $worker->getAddress()->id,
			DAO_Comment::COMMENT => $content,
		);
		$note_id = DAO_Comment::create($fields, $also_notify_worker_ids);
		
		$this->_renderNotes($id);
	}
	
	private function _checkRecentTicketActivity($ticket_id, $since_timestamp) {
		$active_worker = CerberusApplication::getActiveWorker();
		$workers = DAO_Worker::getAll();
		$activities = array();
		
		// Check drafts
		list($results, $null) = DAO_MailQueue::search(
			array(),
			array(
				SearchFields_MailQueue::IS_QUEUED => new DevblocksSearchCriteria(SearchFields_MailQueue::IS_QUEUED, '=', 0),
				SearchFields_MailQueue::TICKET_ID => new DevblocksSearchCriteria(SearchFields_MailQueue::TICKET_ID, '=', $ticket_id),
				SearchFields_MailQueue::WORKER_ID => new DevblocksSearchCriteria(SearchFields_MailQueue::WORKER_ID, '!=', $active_worker->id),
				SearchFields_MailQueue::UPDATED => new DevblocksSearchCriteria(SearchFields_MailQueue::UPDATED, DevblocksSearchCriteria::OPER_GTE, $since_timestamp-300),
			),
			1,
			0,
			SearchFields_MailQueue::UPDATED,
			false,
			false
		);
		
		if(!empty($results))
		foreach($results as $row) {
			if(null == ($worker = @$workers[$row['m_worker_id']]))
				continue;
		
			$activities[] = array(
				'message' => sprintf("%s is currently replying",
					$worker->getName()
				),
				'timestamp' => intval($row['m_updated']),
			);
		}
		
		unset($results);
		
		// Check activity log
		$find_events = array(
			'ticket.status.waiting',
			'ticket.status.closed',
			'ticket.status.deleted',
			'ticket.message.outbound',
		);
		
		list($results, $null) = DAO_ContextActivityLog::search(
			array(),
			array(
				SearchFields_ContextActivityLog::TARGET_CONTEXT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT, '=', CerberusContexts::CONTEXT_TICKET),
				SearchFields_ContextActivityLog::TARGET_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::TARGET_CONTEXT_ID, '=', $ticket_id),
				SearchFields_ContextActivityLog::ACTIVITY_POINT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::ACTIVITY_POINT, 'in', $find_events),
				SearchFields_ContextActivityLog::CREATED => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::CREATED, DevblocksSearchCriteria::OPER_GTE, $since_timestamp),
			),
			10,
			0,
			SearchFields_ContextActivityLog::CREATED,
			false,
			false
		);

		if(!empty($results))
		foreach($results as $row) {
			if(false == ($json = json_decode($row['c_entry_json'], true)))
				continue;
			
			// Skip any events from the current worker
			if($row[SearchFields_ContextActivityLog::ACTOR_CONTEXT] == CerberusContexts::CONTEXT_WORKER
					&& $row[SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID] == $active_worker->id)
						continue;
			
			$activities[] = array(
				'message' => CerberusContexts::formatActivityLogEntry($json, array(), array('target')),
				'timestamp' => intval($row['c_created']),
			);
		}
		
		unset($results);
		
		if(!empty($activities))
			DevblocksPlatform::sortObjects($activities, '[timestamp]', false);
		
		return $activities;
	}
	
	function replyAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['forward'],'integer',0);
		@$is_confirmed = DevblocksPlatform::importGPC($_REQUEST['is_confirmed'],'integer',0);
		@$is_quoted = DevblocksPlatform::importGPC($_REQUEST['is_quoted'],'integer',1);
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0);

		$settings = DevblocksPlatform::getPluginSettingsService();
		$active_worker = CerberusApplication::getActiveWorker();  /* @var $active_worker Model_Worker */
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id',$id);
		$tpl->assign('is_forward',$is_forward);
		$tpl->assign('is_quoted',$is_quoted);
		
		$message = DAO_Message::get($id);
		$tpl->assign('message',$message);

		// Check to see if other activity has happened on this ticket since the worker started looking
		
		if(!$draft_id && !$is_forward && !$is_confirmed) {
			@$since_timestamp = DevblocksPlatform::importGPC($_REQUEST['timestamp'],'integer',0);
			$recent_activity = $this->_checkRecentTicketActivity($message->ticket_id, $since_timestamp);
			
			if(!empty($recent_activity)) {
				$tpl->assign('recent_activity', $recent_activity);
				$tpl->display('devblocks:cerberusweb.core::display/rpc/reply_confirm.tpl');
				return;
			}
		}
		
		// Continue
		
		$ticket = DAO_Ticket::get($message->ticket_id);
		$tpl->assign('ticket',$ticket);
		
		// Workers
		$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TICKET, array($ticket->id), CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('object_watchers', $object_watchers);
		
		// Are we continuing a draft?
		if(!empty($draft_id)) {
			// Drafts
			$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d AND (%s = %s OR %s = %s) AND %s = %d",
				DAO_MailQueue::TICKET_ID,
				$message->ticket_id,
				DAO_MailQueue::WORKER_ID,
				$active_worker->id,
				DAO_MailQueue::TYPE,
				C4_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_REPLY),
				DAO_MailQueue::TYPE,
				C4_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_FORWARD),
				DAO_MailQueue::ID,
				$draft_id
			));
			
			if(isset($drafts[$draft_id])) {
				$tpl->assign('draft', $drafts[$draft_id]);
			}
		}

		// Suggested recipients
		if(!$is_forward) {
			$requesters = $ticket->getRequesters();
			$tpl->assign('requesters', $requesters);
			
			$message_headers = $message->getHeaders();
			$suggested_recipients = DAO_Ticket::findMissingRequestersInHeaders($message_headers, $requesters);
			$tpl->assign('suggested_recipients', $suggested_recipients);
		}
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContextAndGroupId(CerberusContexts::CONTEXT_TICKET, 0);
		$tpl->assign('custom_fields', $custom_fields);

		$group_fields = DAO_CustomField::getByContextAndGroupId(CerberusContexts::CONTEXT_TICKET, $ticket->group_id);
		$tpl->assign('group_fields', $group_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		if(isset($custom_field_values[$ticket->id]))
			$tpl->assign('custom_field_values', $custom_field_values[$ticket->id]);
		
		// ReplyToolbarItem Extensions
		$replyToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.reply.toolbaritem', true);
		if(!empty($replyToolbarItems))
			$tpl->assign('reply_toolbaritems', $replyToolbarItems);
		
		// Show attachments for forwarded messages
		if($is_forward) {
			$forward_attachments = $message->getAttachments();
			$tpl->assign('forward_attachments', $forward_attachments);
		}
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);

		if(null != $active_worker) {
			// Signatures
			@$ticket_group = $groups[$ticket->group_id]; /* @var $ticket_group Model_Group */
			
			if(!empty($ticket_group)) {
				$signature = $ticket_group->getReplySignature($ticket->bucket_id, $active_worker);
				$tpl->assign('signature', $signature);
			}

			$tpl->assign('signature_pos', DAO_WorkerPref::get($active_worker->id, 'mail_signature_pos', 2));
			$tpl->assign('mail_status_reply', DAO_WorkerPref::get($active_worker->id,'mail_status_reply','waiting'));
		}
		
		$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/reply.tpl');
	}
	
	function sendReplyAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$ticket_mask = DevblocksPlatform::importGPC($_REQUEST['ticket_mask'],'string');
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer');
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['is_forward'],'integer',0);
		@$reply_mode = DevblocksPlatform::importGPC($_REQUEST['reply_mode'],'string','');

		@$to = DevblocksPlatform::importGPC(@$_REQUEST['to']);

		// Attachments
		@$file_ids = DevblocksPlatform::importGPC($_POST['file_ids'],'array',array());
		$file_ids = DevblocksPlatform::sanitizeArray($file_ids, 'integer', array('unique', 'nonzero'));
		
		$worker = CerberusApplication::getActiveWorker();
		
		$properties = array(
			'draft_id' => $draft_id,
			'message_id' => DevblocksPlatform::importGPC(@$_REQUEST['id']),
			'ticket_id' => $ticket_id,
			'is_forward' => $is_forward,
			'to' => $to,
			'cc' => DevblocksPlatform::importGPC(@$_REQUEST['cc']),
			'bcc' => DevblocksPlatform::importGPC(@$_REQUEST['bcc']),
			'subject' => DevblocksPlatform::importGPC(@$_REQUEST['subject'],'string'),
			'content' => DevblocksPlatform::importGPC(@$_REQUEST['content']),
			'closed' => DevblocksPlatform::importGPC(@$_REQUEST['closed'],'integer',0),
			'bucket_id' => DevblocksPlatform::importGPC(@$_REQUEST['bucket_id'],'string',''),
			'owner_id' => DevblocksPlatform::importGPC(@$_REQUEST['owner_id'],'integer',0),
			'ticket_reopen' => DevblocksPlatform::importGPC(@$_REQUEST['ticket_reopen'],'string',''),
			'worker_id' => @$worker->id,
			'forward_files' => $file_ids,
			'link_forward_files' => true,
		);
		
		// Custom fields
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		$field_values = DAO_CustomFieldValue::parseFormPost(CerberusContexts::CONTEXT_TICKET, $field_ids);
		if(!empty($field_values)) {
			$properties['custom_fields'] = $field_values;
		}
		
		// Save the draft one last time
		if(!empty($draft_id)) {
			if(false === $this->_saveDraft()) {
				DAO_MailQueue::delete($draft_id);
				$draft_id = null;
			}
		}
		
		// Options
		if('save' == $reply_mode)
			$properties['dont_send'] = true;

		// Send
		if(CerberusMail::sendTicketMessage($properties)) {
			if(!empty($draft_id))
				DAO_MailQueue::delete($draft_id);
		}

		// Automatically add new 'To:' recipients?
		if(!$is_forward) {
			try {
				$to_addys = CerberusMail::parseRfcAddresses($to);
				if(empty($to_addys))
					throw new Exception("Blank recipients list.");

				foreach($to_addys as $to_addy => $to_data)
					DAO_Ticket::createRequester($to_addy, $ticket_id);
				
			} catch(Exception $e) {}
		}
		
		$ticket_uri = !empty($ticket_mask) ? $ticket_mask : $ticket_id;
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket_uri)));
	}
	
	private function _saveDraft() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$msg_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0);
		 
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['is_forward'],'integer',0);

		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		// Validate
		if(empty($msg_id)
			|| empty($ticket_id)
			|| null == ($ticket = DAO_Ticket::get($ticket_id)))
			return false;
		
		// Params
		$params = array();
		
		foreach($_POST as $k => $v) {
			if(is_string($v)) {
				$v = DevblocksPlatform::importGPC($_POST[$k], 'string', null);
				
			} elseif(is_array($v)) {
				$v = DevblocksPlatform::importGPC($_POST[$k], 'array', array());
				
			} else {
				continue;
			}
			
			if(substr($k,0,6) == 'field_')
				continue;
			
			$params[$k] = $v;
		}
		
		// We don't need to persist these fields
		unset($params['c']);
		unset($params['a']);
		unset($params['view_id']);
		unset($params['draft_id']);
		unset($params['is_ajax']);
		unset($params['reply_mode']);
		
		@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'],'array',array());
		$field_ids = DevblocksPlatform::sanitizeArray($field_ids, 'integer', array('nonzero','unique'));

		if(!empty($field_ids)) {
			$field_values = DAO_CustomFieldValue::parseFormPost(CerberusContexts::CONTEXT_TICKET, $field_ids);
			
			if(!empty($field_values)) {
				$params['custom_fields'] = DAO_CustomFieldValue::formatFieldValues($field_values);
			}
		}
		
		if(!empty($msg_id))
			$params['in_reply_message_id'] = $msg_id;
		
		// Hint to
		$hint_to = '';
		if(isset($params['to']) && !empty($params['to'])) {
			$hint_to = $params['to'];
			
		} else {
			$reqs = $ticket->getRequesters();
			$addys = array();
			
			if(is_array($reqs))
			foreach($reqs as $addy) {
				$addys[] = $addy->email;
			}
			
			if(!empty($addys))
				$hint_to = implode(', ', $addys);
			
			unset($reqs);
			unset($addys);
		}
			
		// Fields
		$fields = array(
			DAO_MailQueue::TYPE => empty($is_forward) ? Model_MailQueue::TYPE_TICKET_REPLY : Model_MailQueue::TYPE_TICKET_FORWARD,
			DAO_MailQueue::TICKET_ID => $ticket_id,
			DAO_MailQueue::WORKER_ID => $active_worker->id,
			DAO_MailQueue::UPDATED => time(),
			DAO_MailQueue::HINT_TO => $hint_to,
			DAO_MailQueue::SUBJECT => $subject,
			DAO_MailQueue::BODY => $content,
			DAO_MailQueue::PARAMS_JSON => json_encode($params),
			DAO_MailQueue::IS_QUEUED => 0,
			DAO_MailQueue::QUEUE_DELIVERY_DATE => time(),
		);
		
		// Make sure the current worker is the draft author
		if(!empty($draft_id)) {
			$draft = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d",
				DAO_MailQueue::ID,
				$draft_id,
				DAO_MailQueue::WORKER_ID,
				$active_worker->id
			));
			
			if(!isset($draft[$draft_id]))
				$draft_id = null;
		}
		
		// Save
		if(empty($draft_id)) {
			$draft_id = DAO_MailQueue::create($fields);
		} else {
			DAO_MailQueue::update($draft_id, $fields);
		}
		
		return array(
			'draft_id' => $draft_id,
			'ticket' => $ticket,
		);
	}
	
	function saveDraftReplyAction() {
		@$is_ajax = DevblocksPlatform::importGPC($_REQUEST['is_ajax'],'integer',0);
		
		if(false === ($results = $this->_saveDraft()))
			return;
		
		$draft_id = $results['draft_id'];
		$ticket = $results['ticket'];
		
		if($is_ajax) {
			// Template
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('timestamp', time());
			$html = $tpl->fetch('devblocks:cerberusweb.core::mail/queue/saved.tpl');
			
			// Response
			echo json_encode(array('draft_id'=>$draft_id, 'html'=>$html));
			
		} else {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket->mask)));
		}
	}
	
	function showConversationAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$expand_all = DevblocksPlatform::importGPC($_REQUEST['expand_all'],'integer','0');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		@$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($point))
			$visit->set($point, 'conversation');
				
		$tpl->assign('expand_all', $expand_all);
		
		$ticket = DAO_Ticket::get($id);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());

		// Drafts
		$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d AND (%s = %s OR %s = %s)",
			DAO_MailQueue::TICKET_ID,
			$id,
			DAO_MailQueue::TYPE,
			C4_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_REPLY),
			DAO_MailQueue::TYPE,
			C4_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_FORWARD)
		));
		
		if(!empty($drafts))
			$tpl->assign('drafts', $drafts);
		
		// Only unqueued drafts
		$pending_drafts = array();
		
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
		$convo_timeline = array();

		// Track senders and their orgs
		$message_senders = array();
		$message_sender_orgs = array();

		// Loop messages
		foreach($messages as $message_id => $message) { /* @var $message Model_Message */
			$key = $message->created_date . '_m' . $message_id;
			// build a chrono index of messages
			$convo_timeline[$key] = array('m',$message_id);
			
			// If we haven't cached this sender address yet
			if(!isset($message_senders[$message->address_id])) {
				if(null != ($sender_addy = DAO_Address::get($message->address_id))) {
					$message_senders[$sender_addy->id] = $sender_addy;

					// If we haven't cached this sender org yet
					if(!isset($message_sender_orgs[$sender_addy->contact_org_id])) {
						if(null != ($sender_org = DAO_ContactOrg::get($sender_addy->contact_org_id))) {
							$message_sender_orgs[$sender_org->id] = $sender_org;
						}
					}
				}
			}
		}
		
		$tpl->assign('message_senders', $message_senders);
		$tpl->assign('message_sender_orgs', $message_sender_orgs);
		
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $id);
		arsort($comments);
		$tpl->assign('comments', $comments);
		
		// build a chrono index of comments
		foreach($comments as $comment_id => $comment) { /* @var $comment Model_Comment */
			$key = $comment->created . '_c' . $comment_id;
			$convo_timeline[$key] = array('c',$comment_id);
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
		
		// sort the timeline
		if(!$expand_all) {
			krsort($convo_timeline);
		} else {
			ksort($convo_timeline);
		}
		$tpl->assign('convo_timeline', $convo_timeline);
		
		// Message Notes
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, array_keys($messages));
		$message_notes = array();
		// Index notes by message id
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($message_notes[$note->context_id]))
				$message_notes[$note->context_id] = array();
			$message_notes[$note->context_id][$note->id] = $note;
		}
		$tpl->assign('message_notes', $message_notes);
		
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
		
		$tpl->display('devblocks:cerberusweb.core::display/modules/conversation/index.tpl');
	}
	
	function doDeleteMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.display.message.actions.delete'))
			return;
		
		if(null == ($message = DAO_Message::get($id)))
			return;
			
		if(null == ($ticket = DAO_Ticket::get($message->ticket_id)))
			return;
			
		DAO_Message::delete($id);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket->mask)));
	}
	
	function doSplitMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		if(null == ($orig_message = DAO_Message::get($id)))
			return;
		
		if(null == ($orig_headers = $orig_message->getHeaders()))
			return;
			
		if(null == ($orig_ticket = DAO_Ticket::get($orig_message->ticket_id)))
			return;

		if(null == ($messages = DAO_Message::getMessagesByTicket($orig_message->ticket_id)))
			return;
			
		// Create a new ticket
		$new_ticket_mask = CerberusApplication::generateTicketMask();
		
		$new_ticket_id = DAO_Ticket::create(array(
			DAO_Ticket::CREATED_DATE => $orig_message->created_date,
			DAO_Ticket::UPDATED_DATE => $orig_message->created_date,
			DAO_Ticket::BUCKET_ID => $orig_ticket->bucket_id,
			DAO_Ticket::FIRST_MESSAGE_ID => $orig_message->id,
			DAO_Ticket::LAST_MESSAGE_ID => $orig_message->id,
			DAO_Ticket::FIRST_WROTE_ID => $orig_message->address_id,
			DAO_Ticket::LAST_WROTE_ID => $orig_message->address_id,
			DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_OPENED,
			DAO_Ticket::IS_CLOSED => CerberusTicketStatus::OPEN,
			DAO_Ticket::IS_DELETED => 0,
			DAO_Ticket::MASK => $new_ticket_mask,
			DAO_Ticket::SUBJECT => (isset($orig_headers['subject']) ? $orig_headers['subject'] : $orig_ticket->subject),
			DAO_Ticket::GROUP_ID => $orig_ticket->group_id,
			DAO_Ticket::ORG_ID => $orig_ticket->org_id,
		));

		// Copy all the original tickets requesters
		$orig_requesters = DAO_Ticket::getRequestersByTicket($orig_ticket->id);
		foreach($orig_requesters as $orig_req_addy) {
			DAO_Ticket::createRequester($orig_req_addy->email, $new_ticket_id);
		}
		
		// Pull the message off the ticket (reparent)
		unset($messages[$orig_message->id]);
		
		DAO_Message::update($orig_message->id,array(
			DAO_Message::TICKET_ID => $new_ticket_id
		));
		
		// Reindex the original ticket (last wrote, etc.)
		$last_message = end($messages); /* @var Model_Message $last_message */
		
		DAO_Ticket::update($orig_ticket->id, array(
			DAO_Ticket::LAST_MESSAGE_ID => $last_message->id,
			DAO_Ticket::LAST_WROTE_ID => $last_message->address_id
		));
		
		DAO_Ticket::updateMessageCount($new_ticket_id);
		DAO_Ticket::updateMessageCount($orig_ticket->id);
			
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$new_ticket_mask)));
	}
	
	function doTicketHistoryScopeAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','');
		
		$visit = CerberusApplication::getVisit();
		$visit->set('display.history.scope', $scope);

		$ticket = DAO_Ticket::get($ticket_id);

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket->mask,'history')));
	}
	
	function showContactHistoryAction() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$tpl = DevblocksPlatform::getTemplateService();
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
				
		if(!empty($point))
			$visit->set($point, 'history');

		// Scope
		$scope = $visit->get('display.history.scope', '');
		
		// Ticket
		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket', $ticket);

		// Scope
		$tpl->assign('scope', $scope);

		$view = DAO_Ticket::getViewForRequesterHistory('contact_history', $ticket, $scope);
		$view->renderPage = 0;
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		
		$tpl->display('devblocks:cerberusweb.core::display/modules/history/index.tpl');
	}

	// Display actions
	
	function doTakeAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			return;

		// Check context for worker auth
		$ticket_context = DevblocksPlatform::getExtension(CerberusContexts::CONTEXT_TICKET, true, true); /* @var $ticket_context Extension_DevblocksContext */

		if(!$ticket_context->authorize($ticket_id, $active_worker))
			return;

		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			return;
		
		if(empty($ticket->owner_id)) {
			DAO_Ticket::update($ticket_id, array(
				DAO_Ticket::OWNER_ID => $active_worker->id,
			));
		}
	}
	
	function doSurrenderAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			return;

		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			return;
		
		if($ticket->owner_id == $active_worker->id) {
			DAO_Ticket::update($ticket_id, array(
				DAO_Ticket::OWNER_ID => 0,
			));
		}
	}
	
	// Requesters
	
	function showRequestersPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$tpl->assign('ticket_id', $ticket_id);
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		$tpl->assign('requesters', $requesters);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/requester_panel.tpl');
	}
	
	function saveRequestersPanelAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$address_ids = DevblocksPlatform::importGPC($_POST['address_id'],'array',array());
		@$lookup_str = DevblocksPlatform::importGPC($_POST['lookup'],'string','');

		if(empty($ticket_id))
			return;
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		
		// Delete requesters we've removed
		foreach($requesters as $req_id => $req_addy) {
			if(false === array_search($req_id, $address_ids))
				DAO_Ticket::deleteRequester($ticket_id, $req_id);
		}
		
		// Add chooser requesters
		foreach($address_ids as $id) {
			if(is_numeric($id) && !isset($requesters[$id])) {
				if(null != ($address = DAO_Address::get($id)))
					DAO_Ticket::createRequester($address->email, $ticket_id);
			}
		}
		
		// Perform lookups
		if(!empty($lookup_str)) {
			$lookups = CerberusMail::parseRfcAddresses($lookup_str);
			foreach($lookups as $lookup => $lookup_data) {
				// Create if a valid email and we haven't heard of them
				if(null != ($address = DAO_Address::lookupAddress($lookup, true)))
					DAO_Ticket::createRequester($address->email, $ticket_id);
			}
		}
		
		exit;
	}
	
	function requesterAddAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string');
		
		DAO_Ticket::createRequester($email, $ticket_id);
	}
	
	function requesterRemoveAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$address_id = DevblocksPlatform::importGPC($_REQUEST['address_id'],'integer');
		
		DAO_Ticket::deleteRequester($ticket_id, $address_id);
	}
	
	function requestersRefreshAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);

		$tpl = DevblocksPlatform::getTemplateService();
				
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('requesters', $requesters);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/requester_list.tpl');
	}
	
};
