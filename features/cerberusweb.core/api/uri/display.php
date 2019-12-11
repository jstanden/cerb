<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class ChDisplayPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == (CerberusApplication::getActiveWorker()))
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
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$message = DAO_Message::get($id);
		$tpl->assign('message', $message);
		$tpl->assign('message_id', $message->id);
		
		// Sender info
		$message_senders = [];
		$message_sender_orgs = [];
		
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
			$message_notes = [];
			// Index notes by message id
			if(is_array($notes))
			foreach($notes as $note) {
				if(!isset($message_notes[$note->context_id]))
					$message_notes[$note->context_id] = [];
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

	// [TODO] Move to profiles
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
				if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.message.delete'))
					throw new Exception_DevblocksAjaxValidationError("You are not authorized to delete this record.");
				
				DAO_Message::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$error = null;
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_MESSAGE, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
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
	
	function getDraftAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($draft = DAO_MailQueue::get($id)))
			return;
		
		if(!Context_Draft::isReadableByActor($draft, $active_worker))
			return;
		
		$tpl->assign('draft', $draft);
		$tpl->display('devblocks:cerberusweb.core::display/modules/conversation/draft.tpl');
	}

	private function _checkRecentTicketActivity($ticket_id, $since_timestamp) {
		$active_worker = CerberusApplication::getActiveWorker();
		$workers = DAO_Worker::getAll();
		$activities = [];
		
		// Check drafts
		list($results,) = DAO_MailQueue::search(
			[],
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
		
		list($results,) = DAO_ContextActivityLog::search(
			[],
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
				'message' => CerberusContexts::formatActivityLogEntry($json, [], array('target')),
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
		@$reply_mode = DevblocksPlatform::importGPC($_REQUEST['reply_mode'],'integer',0);
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0);
		@$reply_format = DevblocksPlatform::importGPC($_REQUEST['reply_format'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();  /* @var $active_worker Model_Worker */
		
		$tpl->assign('id',$id);
		$tpl->assign('is_forward', $is_forward);
		$tpl->assign('reply_mode', $reply_mode);
		$tpl->assign('reply_format', $reply_format);
		
		if(false == ($message = DAO_Message::get($id))) {
			if(!$draft_id || false == ($draft = DAO_MailQueue::get($draft_id)))
				return false;
			
			if(false == ($ticket = $draft->getTicket()))
				return false;
			
			if(false == ($message = $ticket->getLastMessage()))
				return false;
		}
		
		$tpl->assign('message', $message);
		
		$message_headers = $message->getHeaders();
		$tpl->assign('message_headers', $message_headers);
		
		// Check to see if other activity has happened on this ticket since the worker started looking
		
		if(!$draft_id && !$is_forward && !$is_confirmed) {
			@$since_timestamp = DevblocksPlatform::importGPC($_REQUEST['timestamp'],'integer',0);
			$recent_activity = $this->_checkRecentTicketActivity($message->ticket_id, $since_timestamp);
			
			if(!empty($recent_activity))
				$tpl->assign('recent_activity', $recent_activity);
		}
		
		// Requesters
		
		$ticket = $message->getTicket();
		$requesters = $ticket->getRequesters();
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		$custom_field_values = @$custom_field_values[$ticket->id] ?: [];
		
		// Are we continuing a draft?
		if($draft_id) {
			if(false == ($draft = DAO_MailQueue::get($draft_id)))
				return;
			
			if(!Context_Draft::isWriteableByActor($draft, $active_worker))
				return;
			
			if($draft->worker_id != $active_worker->id)
				return;
			
			$tpl->assign('draft', $draft);
			
			$tpl->assign('to', @$draft->params['to']);
			$tpl->assign('cc', @$draft->params['cc']);
			$tpl->assign('bcc', @$draft->params['bcc']);
			$tpl->assign('subject', @$draft->params['subject']);
			
			if(array_key_exists('owner_id', $draft->params))
				$ticket->owner_id = $draft->params['owner_id'];
			
			if(array_key_exists('group_id', $draft->params))
				$ticket->group_id = $draft->params['group_id'];
			
			if(array_key_exists('bucket_id', $draft->params))
				$ticket->bucket_id = $draft->params['bucket_id'];
			
			if(array_key_exists('custom_fields', $draft->params)) {
				foreach($draft->params['custom_fields'] as $field_id => $field_value)
					$custom_field_values[$field_id] = $field_value;
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
					$from = isset($message_headers['reply-to']) ? $message_headers['reply-to'] : $message_headers['from'];
					$addys = CerberusMail::parseRfcAddresses($from . ', ' . $message_headers['to'], true);
					$recipients = [];
					
					if(is_array($addys))
						foreach($addys as $addy) {
							$recipients[] = $addy['full_email'];
						}
					
					$to = implode(', ', $recipients);
				}
				
				if(isset($message_headers['cc'])) {
					$addys = CerberusMail::parseRfcAddresses($message_headers['cc'], true);
					$recipients = [];
					
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
				$recipients = [];
				
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
		
		$tpl->assign('ticket', $ticket);
		$tpl->assign('custom_field_values', $custom_field_values);
		
		if(false == ($bucket = $ticket->getBucket()))
			return;
		
		$tpl->assign('bucket', $bucket);
		
		// Transport
		
		if(false != ($reply_from = $bucket->getReplyTo())) {
			$reply_transport = $reply_from->getMailTransport();
			
			$tpl->assign('reply_from', $reply_from);
			$tpl->assign('reply_transport', $reply_transport);
		}
		
		$reply_as = $bucket->getReplyPersonal($active_worker);
		$tpl->assign('reply_as', $reply_as);

		// Workers
		
		$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TICKET, array($ticket->id), CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('object_watchers', $object_watchers);

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
			
			$tpl->assign('signature_pos', DAO_WorkerPref::get($active_worker->id, 'mail_signature_pos', 2));
			$tpl->assign('mail_status_reply', DAO_WorkerPref::get($active_worker->id,'mail_status_reply','waiting'));
		}
		
		$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));
		
		// GPG
		
		$gpg = DevblocksPlatform::services()->gpg();
		$tpl->assign('gpg', $gpg);
		
		// Bot behaviors
		
		if(null != $active_worker) {
			$actions = [];

			// [TODO] Filter by $ticket->group_id
			$macros = DAO_TriggerEvent::getReadableByActor(
				$active_worker,
				Event_MailBeforeUiReplyByWorker::ID,
				false
			);

			if(is_array($macros))
			foreach($macros as $macro)
				Event_MailBeforeUiReplyByWorker::trigger($macro->id, $message->id, $active_worker->id, $actions);

			if(isset($actions['jquery_scripts']) && is_array($actions['jquery_scripts'])) {
				$tpl->assign('jquery_scripts', $actions['jquery_scripts']);
			}
		}
		
		// Dictionary
		$dict = DevblocksDictionaryDelegate::instance([
			'_context' => CerberusContexts::CONTEXT_MESSAGE,
			'id' => $message->id,
		]);
		
		// Interactions
		$interactions = Event_GetInteractionsForWorker::getInteractionsByPointAndWorker('mail.reply', $dict, $active_worker);
		$interactions_menu = Event_GetInteractionsForWorker::getInteractionMenu($interactions);
		$tpl->assign('interactions_menu', $interactions_menu);
		
		// Display template
		$tpl->display('devblocks:cerberusweb.core::display/rpc/reply.tpl');
	}
	
	function validateReplyJsonAction() {
		header('Content-Type: application/json; charset=utf-8');
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer');
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['is_forward'],'integer',0);
		
		@$to = DevblocksPlatform::importGPC(@$_REQUEST['to']);
		
		// Attachments
		@$file_ids = DevblocksPlatform::importGPC($_POST['file_ids'],'array',[]);
		$file_ids = DevblocksPlatform::sanitizeArray($file_ids, 'integer', array('unique', 'nonzero'));
		
		try {
			if(null == ($worker = CerberusApplication::getActiveWorker()))
				throw new Exception_DevblocksAjaxValidationError("You're not signed in.");
			
			if(null == (DAO_Ticket::get($ticket_id)))
				throw new Exception_DevblocksAjaxValidationError("You're replying to an invalid ticket.");
			
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
				'gpg_encrypt' => DevblocksPlatform::importGPC(@$_REQUEST['options_gpg_encrypt'],'integer',0),
				'gpg_sign' => DevblocksPlatform::importGPC(@$_REQUEST['options_gpg_sign'],'integer',0),
				'worker_id' => @$worker->id,
				'forward_files' => $file_ids,
				'link_forward_files' => true,
			);
			
			if(empty($properties['to']))
				throw new Exception_DevblocksAjaxValidationError("The 'To:' is required.");
			
			if(empty($properties['subject']))
				throw new Exception_DevblocksAjaxValidationError("The 'Subject:' is required.");
			
			// Validate GPG if used (we need public keys for all recipients)
			if($properties['gpg_encrypt']) {
				if(false == ($gpg = DevblocksPlatform::services()->gpg()) ||!$gpg->isEnabled())
					throw new Exception_DevblocksAjaxValidationError("The 'gnupg' PHP extension is not installed.");
				
				$email_addresses = DevblocksPlatform::parseCsvString(sprintf("%s%s%s",
					!empty($properties['to']) ? ($properties['to'] . ', ') : '',
					!empty($properties['cc']) ? ($properties['cc'] . ', ') : '',
					!empty($properties['bcc']) ? ($properties['bcc'] . ', ') : ''
				));
				
				$email_models = DAO_Address::lookupAddresses($email_addresses, true);
				$emails_to_check = array_flip(array_column(DevblocksPlatform::objectsToArrays($email_models), 'email'));
				
				foreach($email_models as $email_model) {
					if(false == ($info = $gpg->keyinfo(sprintf("<%s>", $email_model->email))) || !is_array($info))
						continue;
					
					foreach($info as $key) {
						foreach($key['uids'] as $uid) {
							unset($emails_to_check[$uid['email']]);
						}
					}
				}
				
				if(!empty($emails_to_check)) {
					throw new Exception_DevblocksAjaxValidationError("Can't send encrypted message. We don't have a GPG public key for: " . implode(', ', array_keys($emails_to_check)));
				}
			}
			
			//throw new Exception_DevblocksAjaxValidationError("You did it!");
			
			// [TODO] Give bot behaviors a stab at it
			
			echo json_encode([
				'status' => true,
			]);
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			echo json_encode([
				'status' => false,
				'message' => $e->getMessage(),
			]);
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'message' => 'An unexpected error occurred.',
			]);
		}
	}

	function sendReplyAction() {
		// Save the draft one last time
		if(false == ($result = $this->_saveDraft()))
			return false;
		
		$draft_id = $result['draft_id'];
		$ticket = $result['ticket'];
		
		if(false == ($draft = DAO_MailQueue::get($draft_id)))
			return false;
		
		$draft->send();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket->mask)));
	}
	
	private function _saveDraft() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$msg_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0);
		
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['is_forward'],'integer',0);

		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		// Validate
		if(empty($msg_id)
			|| empty($ticket_id)
			|| null == ($ticket = DAO_Ticket::get($ticket_id)))
			return false;
		
		// Params
		$params = [];
		
		foreach($_POST as $k => $v) {
			if(is_string($v)) {
				$v = DevblocksPlatform::importGPC($_POST[$k], 'string', null);
				
			} elseif(is_array($v)) {
				$v = DevblocksPlatform::importGPC($_POST[$k], 'array', []);
				
			} else {
				continue;
			}
			
			if(DevblocksPlatform::strStartsWith($k, 'field_'))
				continue;
			
			$params[$k] = $v;
		}
		
		// Clear owner if not set
		if(!array_key_exists('owner_id', $params))
			$params['owner_id'] = 0;
		
		// We don't need to persist these fields
		unset($params['c']);
		unset($params['a']);
		unset($params['view_id']);
		unset($params['draft_id']);
		unset($params['is_ajax']);
		unset($params['reply_mode']);
		unset($params['ticket_id']);
		unset($params['ticket_mask']);
		unset($params['_csrf_token']);
		
		@$field_ids = DevblocksPlatform::importGPC($_REQUEST['field_ids'],'array',[]);
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
			$addys = [];
			
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
			DAO_MailQueue::NAME => $subject,
			DAO_MailQueue::PARAMS_JSON => json_encode($params),
			DAO_MailQueue::IS_QUEUED => 0,
			DAO_MailQueue::QUEUE_DELIVERY_DATE => time(),
		);
		
		// Make sure the current worker is the draft author
		if(!empty($draft_id)) {
			$visit = CerberusApplication::getVisit();
			$valid_worker_ids = [$active_worker->id];
			
			if($visit->isImposter()) {
				$valid_worker_ids[] = $visit->getImposter()->id;
			}
			
			// If the given draft ID is invalid, ignore
			if(false == ($draft = DAO_MailQueue::get($draft_id)))
				return false;
			
			// If the draft isn't owned by this worker, save a new one
			if(!in_array($draft->worker_id, $valid_worker_ids))
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
		
		// If there are attachments, link them to this draft record
		if(isset($params['file_ids']) && is_array($params['file_ids']))
			DAO_Attachment::setLinks(CerberusContexts::CONTEXT_DRAFT, $draft_id, $params['file_ids']);
		
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
			$tpl = DevblocksPlatform::services()->template();
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
		
		$tpl = DevblocksPlatform::services()->template();
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
		
		$workers_with_relays = DAO_Address::getByWorkers();
		$tpl->assign('workers_with_relays', $workers_with_relays);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/relay_message.tpl');
	}
	
	function saveRelayMessagePopupAction() {
		@$message_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$emails = DevblocksPlatform::importGPC($_REQUEST['emails'],'array',[]);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'], 'string', '');
		@$include_attachments = DevblocksPlatform::importGPC($_REQUEST['include_attachments'], 'integer', 0);

		$active_worker = CerberusApplication::getActiveWorker();

		CerberusMail::relay($message_id, $emails, $include_attachments, $content, CerberusContexts::CONTEXT_WORKER, $active_worker->id);
	}
	
	function doDeleteMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.message.delete'))
			return;
		
		if(null == ($message = DAO_Message::get($id)))
			return;
			
		if(null == ($ticket = DAO_Ticket::get($message->ticket_id)))
			return;
			
		DAO_Message::delete($id);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket->mask)));
	}
	
	function doSplitMessageAction() {
		@$message_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($message = DAO_Message::get($message_id)))
			return;
		
		if(!Context_Message::isWriteableByActor($message, $active_worker))
			return;
		
		$error = null;
		
		if(false == ($results = DAO_Ticket::split($message, $error)))
			return;
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$results['mask'])));
	}
	
	function doTicketHistoryScopeAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','');
		
		$visit = CerberusApplication::getVisit();
		$visit->set('display.history.scope', $scope);

		$ticket = DAO_Ticket::get($ticket_id);

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket->mask,'history')));
	}
	
	// Display actions
	
	function doMoveAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			return;

		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			return;
		
		if(null == ($bucket = DAO_Bucket::get($bucket_id)))
			return;
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			return;
		
		$fields = [
			DAO_Ticket::GROUP_ID => $bucket->group_id,
			DAO_Ticket::BUCKET_ID => $bucket->id,
		];
		
		DAO_Ticket::update($ticket_id, $fields);
	}
	
	function doStatusAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			return;

		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			return;
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			return;
		
		$status_id = null;
		
		// Sanitize
		switch(DevblocksPlatform::strLower($status)) {
			case 'o':
			case 'open':
			case '0':
				$status_id = Model_Ticket::STATUS_OPEN;
				break;
				
			case 'w':
			case 'waiting':
			case '1':
				$status_id = Model_Ticket::STATUS_WAITING;
				break;
				
			case 'c':
			case 'closed':
			case '2':
				$status_id = Model_Ticket::STATUS_CLOSED;
				break;
				
			case 'd':
			case 'deleted':
			case '3':
				$status_id = Model_Ticket::STATUS_DELETED;
				break;
		}
		
		if(is_null($status_id))
			return;
		
		$fields = [
			DAO_Ticket::STATUS_ID => $status_id,
		];
		
		DAO_Ticket::update($ticket_id, $fields);
	}
	
	function doAssignAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$owner_id = DevblocksPlatform::importGPC($_REQUEST['owner_id'],'integer');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			return;

		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			return;
		
		// If we're assigning an owner
		if($owner_id) {
			if(null == ($worker = DAO_Worker::get($owner_id)))
				return;
			
			$owner_id = $worker->id;
		// Or unassigning
		} else {
			$owner_id = 0;
		}
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			return;
		
		$fields = [
			DAO_Ticket::OWNER_ID => $owner_id,
		];
		
		DAO_Ticket::update($ticket_id, $fields);
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
	
	function doReportSpamAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$is_spam = DevblocksPlatform::importGPC($_REQUEST['is_spam'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			return;

		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			return;
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			return;
		
		if($is_spam) {
			CerberusBayes::markTicketAsSpam($ticket->id);
			
			DAO_Ticket::update($ticket->id, [
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
			]);
			
		} else {
			CerberusBayes::markTicketAsNotSpam($ticket->id);
		}
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

		$tpl = DevblocksPlatform::services()->template();
				
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('requesters', $requesters);
		$tpl->assign('is_refresh', true);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/requester_list.tpl');
	}
	
};
