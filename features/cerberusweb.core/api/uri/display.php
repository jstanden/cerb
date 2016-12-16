<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class ChDisplayPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
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
		
		if(null != ($sender_addy = CerberusApplication::hashLookupAddress($message->address_id))) {
			$message_senders[$sender_addy->id] = $sender_addy;
			
			if(null != $sender_org = CerberusApplication::hashLookupOrg($sender_addy->contact_org_id)) {
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
			$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_MESSAGE, $message->id);
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
		$tpl->assign('is_refreshed', true);

		$tpl->display('devblocks:cerberusweb.core::display/modules/conversation/message.tpl');
	}

	function updatePropertiesAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // ticket id
		@$status_id = DevblocksPlatform::importGPC($_REQUEST['status_id'],'integer',0);
		@$spam = DevblocksPlatform::importGPC($_REQUEST['spam'],'integer',0);
		
		if(null == ($ticket = DAO_Ticket::get($id)))
			return;
		
		// Group security
		if(!$active_worker->isGroupMember($ticket->group_id))
			return;
			
		// Anti-Spam
		if(!empty($spam)) {
			CerberusBayes::markTicketAsSpam($id);
			$status_id = Model_Ticket::STATUS_DELETED;
		}

		// Properties
		$properties = array(
			DAO_Ticket::STATUS_ID => $status_id,
		);
		
		if($status_id == Model_Ticket::STATUS_DELETED) {
			$properties[DAO_Ticket::REOPEN_AT] = 0;
		}

		// Undeleting?
		if(empty($spam) && $status_id == Model_Ticket::STATUS_OPEN && $ticket->status_id != Model_Ticket::STATUS_OPEN
			&& $ticket->spam_training == CerberusTicketSpamTraining::SPAM) {
				$score = CerberusBayes::calculateTicketSpamProbability($id);
				$properties[DAO_Ticket::SPAM_SCORE] = $score['probability'];
				$properties[DAO_Ticket::SPAM_TRAINING] = CerberusTicketSpamTraining::BLANK;
				$properties[DAO_Ticket::INTERESTING_WORDS] = $score['interesting_words'];
				$properties[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_OPEN;
		}
		
		// Only update fields that changed
		$properties = Cerb_ORMHelper::uniqueFields($properties, $ticket);
		
		DAO_Ticket::update($id, $properties);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket->mask)));
		exit;
	}
	
	function showMessagePeekPopupAction() {
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(false == ($message = DAO_Message::get($context_id)))
			return;
		
		$tpl->assign('model', $message);
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_MESSAGE, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_MESSAGE, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/messages/peek.tpl');
	}
	
	function saveMessagePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			// ACL
			if(!Context_Message::isWriteableByActor($id, $active_worker))
				throw new Exception_DevblocksAjaxValidationError("You are not authorized to modify this record.");
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv('core.display.message.actions.delete'))
					throw new Exception_DevblocksAjaxValidationError("You are not authorized to delete this record.");
				
				DAO_Message::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				
				// Custom fields
				@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'], 'array', array());
				DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_MESSAGE, $id, $field_ids);
			}
		
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => '',
				'view_id' => $view_id,
			));
			return;
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode(array(
				'status' => false,
				'error' => $e->getMessage(),
				'field' => $e->getFieldName(),
			));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => false,
				'error' => 'An error occurred.',
			));
			return;
			
		}
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
		
		$dst_tickets = DAO_Ticket::getIds($dst_ticket_ids);
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
		
		if(false == ($message = DAO_Message::get($message_id)))
			return false;
		
		if(false == ($ticket = $message->getTicket()))
			return false;
		
		$tpl->assign('message_id', $message_id);
		$tpl->assign('ticket', $ticket);
		
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
	function showAddNotePopupAction() {
		@$message_id = DevblocksPlatform::importGPC($_REQUEST['message_id']);

		if(false == ($message = DAO_Message::get($message_id)))
			return;
		
		if(false == ($ticket = DAO_Ticket::get($message->ticket_id)))
			return;

		if(false == ($worker = CerberusApplication::getActiveWorker()))
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('message', $message);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('worker', $worker);
		
		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/add_note.tpl');
	}
	
	function saveAddNotePopupAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$message_id = DevblocksPlatform::importGPC($_REQUEST['message_id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');

		if(empty($ticket_id) || empty($message_id))
			return;
		
		$worker = CerberusApplication::getActiveWorker();
		
		$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($content));

		// Get watchers
		$watcher_ids = array_keys(CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id));
		
		// Remove the active worker from the watcher list
		$watcher_ids = array_diff($watcher_ids, array($worker->id));
		
		// Merge ticket watchers with the notify list
		$also_notify_worker_ids = array_merge(
			$also_notify_worker_ids,
			$watcher_ids
		);
		
		$fields = array(
			DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_MESSAGE,
			DAO_Comment::CONTEXT_ID => $message_id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
			DAO_Comment::COMMENT => $content,
		);
		$note_id = DAO_Comment::create($fields, $also_notify_worker_ids);
		
		$this->_renderNotes($message_id);
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
	
	function getReplyMarkdownPreviewAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'],'string','');
		@$html_template_id = DevblocksPlatform::importGPC($_REQUEST['html_template_id'],'integer',0);

		if(false == ($group = DAO_Group::get($group_id)))
			return;
		
		header("Content-Type: text/html; charset=" . LANG_CHARSET_CODE);

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Determine if we have an HTML template
		
		if(!$html_template_id || false == ($html_template = DAO_MailHtmlTemplate::get($html_template_id))) {
			if(false == ($html_template = $group->getReplyHtmlTemplate($bucket_id)))
				$html_template = null;
		}
		
		// Parse #commands
		
		$message_properties = array(
			'group_id' => $group_id,
			'bucket_id' => $bucket_id,
			'content' => $content,
			'content_format' => $format,
			'html_template_id' => ($html_template) ? $html_template->id : 0,
		);
		
		$hash_commands = array();
		
		$this->_parseReplyHashCommands($active_worker, $message_properties, $hash_commands);
		
		// Markdown
		
		$output = DevblocksPlatform::parseMarkdown($message_properties['content']);
		
		// Wrap the reply in a template if we have one
		
		if($html_template) {
			$output = $tpl_builder->build(
				$html_template->content,
				array(
					'message_body' => $output,
				)
			);
		}
			
		echo sprintf('<html><head><meta http-equiv="content-type" content="text/html; charset=%s"></head><body>',
			LANG_CHARSET_CODE
		);
		echo DevblocksPlatform::purifyHTML($output, true);
		echo '</body></html>';
	}
	
	function getReplyPreviewAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');

		header("Content-Type: text/html; charset=" . LANG_CHARSET_CODE);

		// Parse #commands
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$message_properties = array(
			'group_id' => $group_id,
			'bucket_id' => $bucket_id,
			'content' => $content,
		);
		
		$hash_commands = array();
		
		$this->_parseReplyHashCommands($active_worker, $message_properties, $hash_commands);
		
		echo sprintf('<html><head><meta http-equiv="content-type" content="text/html; charset=%s"></head><body>',
			LANG_CHARSET_CODE
		);
		echo DevblocksPlatform::purifyHTML(nl2br($message_properties['content']), true);
		echo '</body></html>';
	}
	
	function replyAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['forward'],'integer',0);
		@$is_confirmed = DevblocksPlatform::importGPC($_REQUEST['is_confirmed'],'integer',0);
		@$reply_mode = DevblocksPlatform::importGPC($_REQUEST['reply_mode'],'integer',0);
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0);

		$settings = DevblocksPlatform::getPluginSettingsService();
		$active_worker = CerberusApplication::getActiveWorker();  /* @var $active_worker Model_Worker */
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id',$id);
		$tpl->assign('is_forward',$is_forward);
		$tpl->assign('reply_mode',$reply_mode);
		
		$message = DAO_Message::get($id);
		$tpl->assign('message',$message);

		$message_headers = $message->getHeaders();
		$tpl->assign('message_headers', $message_headers);
		
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
		
		$requesters = $ticket->getRequesters();
		
		// Workers
		
		$object_recommendations = DAO_ContextRecommendation::getByContexts(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		$tpl->assign('object_recommendations', $object_recommendations);
		
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
				Cerb_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_REPLY),
				DAO_MailQueue::TYPE,
				Cerb_ORMHelper::qstr(Model_MailQueue::TYPE_TICKET_FORWARD),
				DAO_MailQueue::ID,
				$draft_id
			));
			
			if(isset($drafts[$draft_id])) {
				$draft = $drafts[$draft_id];
				$tpl->assign('draft', $draft);
				
				$tpl->assign('to', $draft->params['to']);
				$tpl->assign('cc', $draft->params['cc']);
				$tpl->assign('bcc', $draft->params['bcc']);
				$tpl->assign('subject', $draft->params['subject']);
			}
			
		// Or are we replying without a draft?
		} else {
			$to = '';
			$cc = '';
			$bcc = '';
			$subject = $ticket->subject;
			
			// Reply to only these recipients
			if(2 == $reply_mode) {
				if(isset($message_headers['to'])) {
					$addys = CerberusMail::parseRfcAddresses($message_headers['from'] . ', ' . $message_headers['to'], true);
					$recipients = array();
					
					if(is_array($addys))
					foreach($addys as $addy) {
						$recipients[] = $addy['full_email'];
					}
					
					$to = implode(', ', $recipients);
				}
				
				if(isset($message_headers['cc'])) {
					$addys = CerberusMail::parseRfcAddresses($message_headers['cc'], true);
					$recipients = array();
					
					if(is_array($addys))
					foreach($addys as $addy) {
						$recipients[] = $addy['full_email'];
					}
					
					$cc = implode(', ', $recipients);
				}
				
			// Forward
			} else if($is_forward) {
				$subject = sprintf("Fwd: %s",
					$ticket->subject
				);
				
			// Normal reply quoted or not
			} else {
				$recipients = array();
				
				if(is_array($requesters))
				foreach($requesters as $requester) {
					$requester_personal = $requester->getName();
					$requester_addy = $requester->email;
					@list($requester_mailbox, $requester_host) = explode('@', $requester_addy); 
					
					if(false !== ($recipient = imap_rfc822_write_address($requester_mailbox, $requester_host, $requester_personal)))
						$recipients[] = $recipient;
				}
				
				$to = implode(', ', $recipients);
				
				// Suggested recipients
				$suggested_recipients = DAO_Ticket::findMissingRequestersInHeaders($message_headers, $requesters);
				$tpl->assign('suggested_recipients', $suggested_recipients);
			}
			
			$tpl->assign('to', $to);
			$tpl->assign('cc', $cc);
			$tpl->assign('bcc', $bcc);
			$tpl->assign('subject', $subject);
		}

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
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
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
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		if(isset($custom_field_values[$ticket->id]))
			$tpl->assign('custom_field_values', $custom_field_values[$ticket->id]);
		
		// HTML templates
		
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		// VA macros
		
		// [TODO] Filter by $ticket->group_id
		$macros = DAO_TriggerEvent::getReadableByActor(
			$active_worker,
			Event_MailDuringUiReplyByWorker::ID
		);
		$tpl->assign('macros', $macros);
		
		// VA behavior
		
		if(null != $active_worker) {
			$actions = array();

			// [TODO] Filter by $ticket->group_id
			$macros = DAO_TriggerEvent::getReadableByActor(
				$active_worker,
				Event_MailBeforeUiReplyByWorker::ID
			);

			if(is_array($macros))
			foreach($macros as $macro)
				Event_MailBeforeUiReplyByWorker::trigger($macro->id, $message->id, $actions);

			if(isset($actions['jquery_scripts']) && is_array($actions['jquery_scripts'])) {
				$tpl->assign('jquery_scripts', $actions['jquery_scripts']);
			}
		}
		
		// Display template
		
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
		
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			return false;
		
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
			'content_format' => DevblocksPlatform::importGPC(@$_REQUEST['format'],'string',''),
			'html_template_id' => DevblocksPlatform::importGPC(@$_REQUEST['html_template_id'],'integer',0),
			'status_id' => DevblocksPlatform::importGPC(@$_REQUEST['status_id'],'integer',0),
			'group_id' => DevblocksPlatform::importGPC(@$_REQUEST['group_id'],'integer',0),
			'bucket_id' => DevblocksPlatform::importGPC(@$_REQUEST['bucket_id'],'integer',0),
			'owner_id' => DevblocksPlatform::importGPC(@$_REQUEST['owner_id'],'integer',0),
			'ticket_reopen' => DevblocksPlatform::importGPC(@$_REQUEST['ticket_reopen'],'string',''),
			'worker_id' => @$worker->id,
			'forward_files' => $file_ids,
			'link_forward_files' => true,
		);
		
		$hash_commands = array();
		
		$this->_parseReplyHashCommands($worker, $properties, $hash_commands);
		
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
		if(false != ($new_message_id = CerberusMail::sendTicketMessage($properties))) {
			if(!empty($draft_id))
				DAO_MailQueue::delete($draft_id);
			
			// Run hash commands
			if(!empty($hash_commands))
				$this->_handleReplyHashCommands($hash_commands, $ticket, $worker);
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
	
	private function _parseReplyHashCommands(Model_worker $worker, array &$message_properties, array &$commands) {
		$lines_in = DevblocksPlatform::parseCrlfString($message_properties['content'], true, false);
		$lines_out = array();
		
		$is_cut = false;
		
		foreach($lines_in as $line) {
			$handled = false;
			
			if(preg_match('/^\#([A-Za-z0-9_]+)(.*)$/', $line, $matches)) {
				@$command = $matches[1];
				@$args = ltrim($matches[2]);
				
				switch($command) {
					case 'attach':
						@$bundle_tag = $args;
						$handled = true;
						
						if(empty($bundle_tag))
							break;
						
						if(false == ($bundle = DAO_FileBundle::getByTag($bundle_tag)))
							break;
						
						$attachments = $bundle->getAttachments();
						
						$message_properties['link_forward_files'] = true;
						
						if(!isset($message_properties['forward_files']))
							$message_properties['forward_files'] = array();
						
						$message_properties['forward_files'] = array_merge($message_properties['forward_files'], array_keys($attachments));
						break;
					
					case 'cut':
						$is_cut = true;
						$handled = true;
						break;
						
					case 'signature':
						@$group_id = $message_properties['group_id'];
						@$bucket_id = $message_properties['bucket_id'];
						@$content_format = $message_properties['content_format'];
						@$html_template_id = $message_properties['html_template_id'];
						
						$group = DAO_Group::get($group_id);
						
						switch($content_format) {
							case 'parsedown':
								// Determine if we have an HTML template
								
								if(!$html_template_id || false == ($html_template = DAO_MailHtmlTemplate::get($html_template_id))) {
									if(false == ($html_template = $group->getReplyHtmlTemplate($bucket_id)))
										$html_template = null;
								}
								
								// Determine signature
								
								if(!$html_template || false == ($signature = $html_template->getSignature($worker))) {
									$signature = $group->getReplySignature($bucket_id, $worker);
								}
								
								// Replace signature
								
								$line = $signature;
								break;
								
							default:
								if($group instanceof Model_Group)
									$line = $group->getReplySignature($bucket_id, $worker);
								else
									$line = '';
								break;
						}
						break;
						
					case 'comment':
					case 'watch':
					case 'unwatch':
						$handled = true;
						$commands[] = array(
							'command' => $command,
							'args' => $args,
						);
						break;	
						
					default:
						$handled = false;
						break;
				}
			}
			
			if(!$handled && !$is_cut) {
				$lines_out[] = $line;
			}
		}
		
		$message_properties['content'] = implode("\n", $lines_out);
	}
	
	private function _handleReplyHashCommands(array $commands, Model_Ticket $ticket, Model_Worker $worker) {
		foreach($commands as $command_data) {
			switch($command_data['command']) {
				case 'comment':
					@$comment = $command_data['args'];
					
					if(!empty($comment)) {
						$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
						
						$fields = array(
							DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
							DAO_Comment::CONTEXT_ID => $ticket->id,
							DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
							DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
							DAO_Comment::CREATED => time()+2,
							DAO_Comment::COMMENT => $comment,
						);
						$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
					}
					break;
		
				case 'watch':
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id, array($worker->id));
					break;
		
				case 'unwatch':
					CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id, array($worker->id));
					break;
			}
		}
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
			$visit = CerberusApplication::getVisit();
			$valid_worker_ids = array($active_worker->id);
			
			if($visit->isImposter()) {
				$valid_worker_ids[] = $visit->getImposter()->id;
			}
			
			$draft = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s IN (%s)",
				DAO_MailQueue::ID,
				$draft_id,
				DAO_MailQueue::WORKER_ID,
				implode(',', $valid_worker_ids)
			));
			
			if(!isset($draft[$draft_id]))
				$draft_id = null;
		}
		
		// Save
		if(empty($draft_id)) {
			$fields[DAO_MailQueue::TYPE] = empty($is_forward) ? Model_MailQueue::TYPE_TICKET_REPLY : Model_MailQueue::TYPE_TICKET_FORWARD;
			$fields[DAO_MailQueue::TICKET_ID] = $ticket_id;
			$fields[DAO_MailQueue::WORKER_ID] = $active_worker->id;
			
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
			
			header('Content-Type: application/json;');
			
			// Response
			echo json_encode(array('draft_id'=>$draft_id, 'html'=>$html));
			
		} else {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket->mask)));
		}
	}
	
	function showRelayMessagePopupAction() {
		@$message_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		if(false == ($message = DAO_Message::get($message_id)))
			return;
		
		$tpl->assign('message', $message);
		
		if(false == ($ticket = DAO_Ticket::get($message->ticket_id)))
			return;
		
		$tpl->assign('ticket', $ticket);
		
		if(false == ($sender = $message->getSender()))
			return;
		
		$tpl->assign('sender', $sender);
		
		$workers_with_relays = DAO_AddressToWorker::getByWorkers();
		$tpl->assign('workers_with_relays', $workers_with_relays);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/relay_message.tpl');
	}
	
	function saveRelayMessagePopupAction() {
		@$message_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$emails = DevblocksPlatform::importGPC($_REQUEST['emails'],'array',array());
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'], 'string', '');
		@$include_attachments = DevblocksPlatform::importGPC($_REQUEST['include_attachments'], 'integer', 0);

		$active_worker = CerberusApplication::getActiveWorker();

		CerberusMail::relay($message_id, $emails, $include_attachments, $content, CerberusContexts::CONTEXT_WORKER, $active_worker->id);
	}
	
	function showMacroReplyPopupAction() {
		@$macro_id = DevblocksPlatform::importGPC($_REQUEST['macro'],'integer',0);
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$message_id = DevblocksPlatform::importGPC($_REQUEST['message_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		try {
			if(null == ($macro = DAO_TriggerEvent::get($macro_id)))
				throw new Exception("Missing macro.");
			
			$tpl->assign('macro', $macro);
			
		} catch(Exception $e) {
			DevblocksPlatform::redirectURL($return_url);
			exit;
		}
			
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('message_id', $message_id);
		
		// Verify permission
		$editable = Context_Ticket::isWriteableByActor($ticket_id, $active_worker);
		
		if(!$editable)
			return;

		$event = $macro->getEvent();
		$conditions = $event->getConditions($macro);
		
		$tpl->display('devblocks:cerberusweb.core::internal/macros/reply/run_popup.tpl');
	}
	
	function getMacroReplyAction() {
		@$macro_id = DevblocksPlatform::importGPC($_REQUEST['macro'],'integer',0);
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$message_id = DevblocksPlatform::importGPC($_REQUEST['message_id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		try {
			if(null == ($macro = DAO_TriggerEvent::get($macro_id)))
				throw new Exception("Missing macro.");
			
		} catch(Exception $e) {
			return;
		}
			
		// Verify permission
		$editable = Context_Ticket::isWriteableByActor($ticket_id, $active_worker);
		
		if(!$editable)
			return;

		$event = $macro->getEvent();
		$conditions = $event->getConditions($macro);
		
		$actions = array();
		
		// Variables
		@$var_keys = DevblocksPlatform::importGPC($_REQUEST['var_keys'],'array',array());
		@$var_vals = DevblocksPlatform::importGPC($_REQUEST['var_vals'],'array',array());

		// [TODO] Abstract this?
		$vars = DAO_ContextScheduledBehavior::buildVariables($var_keys, $var_vals, $macro);
		
		Event_MailDuringUiReplyByWorker::trigger($macro_id, $message_id, $actions, $vars);

		// [TODO] Move script block to template?
		if(isset($actions['jquery_scripts'])) {
			echo '<script type="text/javascript">';
			echo '$("#reply' . $message_id . '_part1").closest("div.reply_frame").each(function(e) { try {';
			echo implode("\n", $actions['jquery_scripts']);
			echo ' } catch(e) {} });';
			echo '</script>';
		}
		
		exit;
	}
	
	function showConversationAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$focus_ctx = DevblocksPlatform::importGPC($_REQUEST['focus_ctx'],'string','');
		@$focus_ctx_id = DevblocksPlatform::importGPC($_REQUEST['focus_ctx_id'],'integer',0);
		@$expand_all = DevblocksPlatform::importGPC($_REQUEST['expand_all'],'integer',0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();

		@$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('expand_all', $expand_all);
		$tpl->assign('focus_ctx', $focus_ctx);
		$tpl->assign('focus_ctx_id', $focus_ctx_id);
		
		$ticket = DAO_Ticket::get($id);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());

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
		
		// Inline activity log
		
		if(DAO_WorkerPref::get($active_worker->id, 'mail_display_inline_log', 0)) {
			$activity_log = DAO_ContextActivityLog::getWhere(
				sprintf("%s = %s AND %s = %d",
					DAO_ContextActivityLog::TARGET_CONTEXT,
					Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TICKET),
					DAO_ContextActivityLog::TARGET_CONTEXT_ID,
					$ticket->id
				),
				DAO_ContextActivityLog::CREATED,
				true
			);
			
			$activity_log = array_filter($activity_log, function($entry) {
				// Filter these events out
				switch($entry->activity_point) {
					case 'comment.create':
					case 'ticket.message.outbound':
					case 'ticket.message.inbound':
						return false;
						break;
				}
				
				return true;
			});
			
			if(!empty($activity_log)) {
				foreach($activity_log as $activity_entry) { /* @var $activity_entry Model_ContextActivityLog */
					$key = $activity_entry->created . '_l' . $activity_entry->id;
					$convo_timeline[$key] = array('l', $activity_entry->id);
				}
			}
			
			$tpl->assign('activity_log', $activity_log);
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
		
		// Draft Notes
		$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_DRAFT, array_keys($drafts));
		$draft_notes = array();
		// Index notes by draft id
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($draft_notes[$note->context_id]))
				$draft_notes[$note->context_id] = array();
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
		
		$fields = array(
			DAO_Ticket::CREATED_DATE => $orig_message->created_date,
			DAO_Ticket::UPDATED_DATE => $orig_message->created_date,
			DAO_Ticket::FIRST_MESSAGE_ID => $orig_message->id,
			DAO_Ticket::LAST_MESSAGE_ID => $orig_message->id,
			DAO_Ticket::FIRST_WROTE_ID => $orig_message->address_id,
			DAO_Ticket::LAST_WROTE_ID => $orig_message->address_id,
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
			DAO_Ticket::MASK => $new_ticket_mask,
			DAO_Ticket::SUBJECT => (isset($orig_headers['subject']) ? $orig_headers['subject'] : $orig_ticket->subject),
			DAO_Ticket::GROUP_ID => $orig_ticket->group_id,
			DAO_Ticket::BUCKET_ID => $orig_ticket->bucket_id,
			DAO_Ticket::ORG_ID => $orig_ticket->org_id,
			DAO_Ticket::IMPORTANCE => $orig_ticket->importance,
		);

		$new_ticket_id = DAO_Ticket::create($fields);

		if(null == ($new_ticket = DAO_Ticket::get($new_ticket_id)))
			return false;
		
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
		
		$fields = array(
			DAO_Ticket::LAST_MESSAGE_ID => $last_message->id,
			DAO_Ticket::LAST_WROTE_ID => $last_message->address_id
		);
		
		DAO_Ticket::update($orig_ticket->id, $fields, false);
		
		DAO_Ticket::updateMessageCount($new_ticket_id);
		DAO_Ticket::updateMessageCount($orig_ticket->id);
			
		/*
		 * Log activity (Ticket Split)
		 */
		
		$entry = array(
			//{{actor}} split from ticket {{target}} into ticket {{source}}
			'message' => 'activities.ticket.split',
			'variables' => array(
				'target' => sprintf("[%s] %s", $orig_ticket->mask, $orig_ticket->subject),
				'source' => sprintf("[%s] %s", $new_ticket->mask, $new_ticket->subject),
				),
			'urls' => array(
				'target' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $orig_ticket->mask),
				'source' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $new_ticket->mask),
				)
		);
		CerberusContexts::logActivity('ticket.split', CerberusContexts::CONTEXT_TICKET, $orig_ticket->id, $entry);
		
		/*
		 * Log activity (Ticket Split From)
		 */
		
		$entry = array(
			//{{actor}} split into ticket {{target}} from ticket {{source}}
			'message' => 'activities.ticket.split.from',
			'variables' => array(
				'target' => sprintf("[%s] %s", $new_ticket->mask, $new_ticket->subject),
				'source' => sprintf("[%s] %s", $orig_ticket->mask, $orig_ticket->subject),
				),
			'urls' => array(
				'target' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $new_ticket->mask),
				'source' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $orig_ticket->mask),
				)
		);
		CerberusContexts::logActivity('ticket.split', CerberusContexts::CONTEXT_TICKET, $new_ticket->id, $entry);
		
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
				
		// Scope
		$scope = $visit->get('display.history.scope', '');
		
		// Ticket
		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket', $ticket);

		// Scope
		$tpl->assign('scope', $scope);

		$view = DAO_Ticket::getViewForRequesterHistory('contact_history', $ticket, $scope);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::display/modules/history/index.tpl');
	}

	// Display actions
	
	function doTakeAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			return;

		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			return;
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
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
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			return;
		
		if($ticket->owner_id == $active_worker->id) {
			$fields = array(
				DAO_Ticket::OWNER_ID => 0,
			);
			
			DAO_Ticket::update($ticket_id, $fields);
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
		$tpl->assign('is_refresh', true);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/requester_list.tpl');
	}
	
};
