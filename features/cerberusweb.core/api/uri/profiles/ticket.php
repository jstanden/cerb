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

class PageSection_ProfilesTicket extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // ticket
		@$id_string = array_shift($stack);
		
		$context = CerberusContexts::CONTEXT_TICKET;
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Translate masks to IDs
		if(null == ($context_id= DAO_Ticket::getTicketIdByMask($id_string))) {
			$context_id = intval($id_string);
		}
		
		// Load the record
		if(false == ($ticket = DAO_Ticket::get($context_id))) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest());
			return;
		}
		
		// Dictionary
		
		if(false == (Extension_DevblocksContext::get(CerberusContexts::CONTEXT_TICKET, true)))
			return;

		// Trigger ticket view event (before we load it, in case we change it)
		Event_TicketViewedByWorker::trigger($ticket->id, $active_worker->id);
		
		// Permissions
		
		if(!Context_Ticket::isReadableByActor($ticket, $active_worker)) {
			echo DevblocksPlatform::translateCapitalized('common.access_denied');
			exit;
		}
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'splitMessage':
					return $this->_profileAction_splitMessage();
				case 'requesterAdd':
					return $this->_profileAction_requesterAdd();
				case 'reply':
					return $this->_profileAction_reply();
				case 'validateReplyJson':
					return $this->_profileAction_validateReplyJson();
				case 'sendReply':
					return $this->_profileAction_sendReply();
				case 'showBulkPopup':
					return $this->_profileAction_showBulkPopup();
				case 'startBulkUpdateJson':
					return $this->_profileAction_startBulkUpdateJson();
				case 'getPeekPreview':
					return $this->_profileAction_getPeekPreview();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'previewReplyMessage':
					return $this->_profileAction_previewReplyMessage();
				case 'quickAssign':
					return $this->_profileAction_quickAssign();
				case 'quickMove':
					return $this->_profileAction_quickMove();
				case 'quickSpam':
					return $this->_profileAction_quickSpam();
				case 'quickStatus':
					return $this->_profileAction_quickStatus();
				case 'quickSurrender':
					return $this->_profileAction_quickSurrender();
				case 'showMessageFullHeadersPopup':
					return $this->_profileAction_showMessageFullHeadersPopup();
				case 'showResendMessagePopup':
					return $this->_profileAction_showResendMessagePopup();
				case 'saveResendMessagePopupJson':
					return $this->_profileAction_saveResendMessagePopupJson();
				case 'viewMarkClosed':
					return $this->_profileAction_viewMarkClosed();
				case 'viewMarkDeleted':
					return $this->_profileAction_viewMarkDeleted();
				case 'viewMarkSpam':
					return $this->_profileAction_viewMarkSpam();
				case 'viewMarkNotSpam':
					return $this->_profileAction_viewMarkNotSpam();
				case 'viewMarkNotWaiting':
					return $this->_profileAction_viewMarkNotWaiting();
				case 'viewMarkWaiting':
					return $this->_profileAction_viewMarkWaiting();
				case 'viewMove':
					return $this->_profileAction_viewMove();
				case 'viewUndo':
					return $this->_profileAction_viewUndo();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_getPeekPreview() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$tpl->assign('view_id', $view_id);
		
		switch($context) {
			case CerberusContexts::CONTEXT_MESSAGE:
				if(false == ($message = DAO_Message::get($context_id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
					
				$tpl->assign('message', $message);
				
				if(!Context_Message::isReadableByActor($message, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$is_writeable = Context_Message::isWriteableByActor($message, $active_worker);
				$tpl->assign('is_writeable', $is_writeable);
				
				$sender = $message->getSender();
				
				$tpl->assign('message_senders', [
					$message->address_id => $sender,
				]);
				
				$tpl->assign('message_senders_orgs', [
					0 => $sender->getOrg(),
				]);
				
				$tpl->display('devblocks:cerberusweb.core::tickets/peek_preview.tpl');
				break;
				
			case CerberusContexts::CONTEXT_COMMENT:
				if(false == ($comment = DAO_Comment::get($context_id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if(!Context_Comment::isReadableByActor($comment, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('comment', $comment);
				$tpl->display('devblocks:cerberusweb.core::tickets/peek_preview.tpl');
				break;
				
			case CerberusContexts::CONTEXT_DRAFT:
				if(false == ($draft = DAO_MailQueue::get($context_id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if(!Context_Draft::isReadableByActor($draft, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
					
				$tpl->assign('draft', $draft);
				$tpl->display('devblocks:cerberusweb.core::tickets/peek_preview.tpl');
				break;
		}
	}
	
	private function _profileAction_savePeekJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','');
			@$org_id = DevblocksPlatform::importGPC($_POST['org_id'],'integer',0);
			@$status_id = DevblocksPlatform::importGPC($_POST['status_id'],'integer',0);
			@$importance = DevblocksPlatform::importGPC($_POST['importance'],'integer',0);
			@$owner_id = DevblocksPlatform::importGPC($_POST['owner_id'],'integer',0);
			@$participants = DevblocksPlatform::importGPC($_POST['participants'],'array',[]);
			@$group_id = DevblocksPlatform::importGPC($_POST['group_id'],'integer',0);
			@$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'],'integer',0);
			@$spam_training = DevblocksPlatform::importGPC($_POST['spam_training'],'string','');
			@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.update', CerberusContexts::CONTEXT_TICKET)))
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
			
			// Load the existing model so we can detect changes
			if(false == ($ticket = DAO_Ticket::get($id)))
				throw new Exception_DevblocksAjaxValidationError("There was an unexpected error when loading this record.");
			
			$fields = array(
				DAO_Ticket::SUBJECT => $subject,
			);
			
			// Group
			if(!$group_id || false == ($group = DAO_Group::get($group_id)))
				throw new Exception_DevblocksAjaxValidationError("The given 'Group' is invalid.", 'group_id');
			
			// Owner
			if(!empty($owner_id)) {
				if(false == ($owner = DAO_Worker::get($owner_id)))
					throw new Exception_DevblocksAjaxValidationError("The given 'Owner' is invalid.", 'owner_id');
				
				if(!$owner->isGroupMember($group->id))
					throw new Exception_DevblocksAjaxValidationError(
						sprintf("%s can't own this ticket because they are not a member of the %s group.", $owner->getName(), $group->name),
						'owner_id'
					);
			}
			
			$fields[DAO_Ticket::OWNER_ID] = $owner_id;
			
			// Status
			switch($status_id) {
				case Model_Ticket::STATUS_OPEN:
					$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_OPEN;
					$fields[DAO_Ticket::REOPEN_AT] = 0;
					break;
				case Model_Ticket::STATUS_CLOSED:
					$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_CLOSED;
					break;
				case Model_Ticket::STATUS_WAITING:
					$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_WAITING;
					break;
				case Model_Ticket::STATUS_DELETED:
					$fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_DELETED;
					$fields[DAO_Ticket::REOPEN_AT] = 0;
					break;
			}
			
			if(in_array($status_id, array(Model_Ticket::STATUS_WAITING, Model_Ticket::STATUS_CLOSED))) {
				if(!empty($ticket_reopen) && false !== ($due = strtotime($ticket_reopen))) {
					$fields[DAO_Ticket::REOPEN_AT] = $due;
				} else {
					$fields[DAO_Ticket::REOPEN_AT] = 0;
				}
			}
			
			// Group/Bucket
			if(!empty($group_id)) {
				$fields[DAO_Ticket::GROUP_ID] = $group_id;
				$fields[DAO_Ticket::BUCKET_ID] = $bucket_id;
			}
			
			// Org
			$fields[DAO_Ticket::ORG_ID] = $org_id;
			
			// Importance
			$importance = DevblocksPlatform::intClamp($importance, 0, 100);
			$fields[DAO_Ticket::IMPORTANCE] = $importance;
			
			// Spam Training
			if(!empty($spam_training)) {
				if('S'==$spam_training)
					CerberusBayes::markTicketAsSpam($id);
				elseif('N'==$spam_training)
					CerberusBayes::markTicketAsNotSpam($id);
			}
			
			// Participants
			$requesters = DAO_Ticket::getRequestersByTicket($id);
			
			// Delete requesters we've removed
			$requesters_removed = array_diff(array_keys($requesters), $participants);
			DAO_Ticket::removeParticipantIds($id, $requesters_removed);
			
			// Add chooser requesters
			$requesters_new = array_diff($participants, array_keys($requesters));
			DAO_Ticket::addParticipantIds($id, $requesters_new);
			
			// Only update fields that changed
			$fields = Cerb_ORMHelper::uniqueFields($fields, $ticket);
			$error = null;
			
			if(!DAO_Ticket::validate($fields, $error, $id))
				throw new Exception_DevblocksAjaxValidationError($error);
			
			if(!DAO_Ticket::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
				throw new Exception_DevblocksAjaxValidationError($error);
			
			// Do it
			DAO_Ticket::update($id, $fields);
			DAO_Ticket::onUpdateByActor($active_worker, $fields, $id);
			
			// Log the ticket deletion, even though we have an undo window
			if(array_key_exists(DAO_Ticket::STATUS_ID, $fields) && Model_Ticket::STATUS_DELETED == $fields[DAO_Ticket::STATUS_ID]) {
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_TICKET, $ticket->id, sprintf("#%s: %s", $ticket->mask, $ticket->subject));
			}
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
			if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_TICKET, $id, $field_ids, $error))
				throw new Exception_DevblocksAjaxValidationError($error);
			
			// Comments
			DAO_Comment::handleFormPost(CerberusContexts::CONTEXT_TICKET, $id);
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => $subject, // [TODO] Mask?
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
	
	private function _profileAction_reply() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$is_forward = DevblocksPlatform::importGPC($_POST['forward'],'integer',0);
		@$is_confirmed = DevblocksPlatform::importGPC($_POST['is_confirmed'],'integer',0);
		@$reply_mode = DevblocksPlatform::importGPC($_POST['reply_mode'],'integer',0);
		@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer',0);
		@$reply_format = DevblocksPlatform::importGPC($_POST['reply_format'],'string','');
		
		$tpl->assign('id',$id);
		$tpl->assign('is_forward', $is_forward);
		$tpl->assign('reply_mode', $reply_mode);
		$tpl->assign('reply_format', $reply_format);
		
		$ticket = null;
		$message = null;
		
		if(false == ($message = DAO_Message::get($id))) {
			if(!$draft_id)
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(false == ($draft = DAO_MailQueue::get($draft_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(false == ($ticket = $draft->getTicket()))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(false == ($message = $ticket->getLastMessage()))
				DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(!($ticket instanceof Model_Ticket))
			$ticket = $message->getTicket();
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('message', $message);
		
		$message_headers = $message->getHeaders();
		$tpl->assign('message_headers', $message_headers);
		
		// Check to see if other activity has happened on this ticket since the worker started looking
		
		if(!$draft_id && !$is_forward && !$is_confirmed) {
			@$since_timestamp = DevblocksPlatform::importGPC($_POST['timestamp'],'integer',0);
			$recent_activity = $this->_checkRecentTicketActivity($message->ticket_id, $since_timestamp);
			
			if(!empty($recent_activity))
				$tpl->assign('recent_activity', $recent_activity);
		}
		
		// Requesters
		
		$requesters = $ticket->getRequesters();
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		$custom_field_values = @$custom_field_values[$ticket->id] ?: [];
		
		// Are we continuing a draft?
		if($draft_id) {
			if(false == ($draft = DAO_MailQueue::get($draft_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!Context_Draft::isWriteableByActor($draft, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if($draft->worker_id != $active_worker->id)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
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
			// [TODO] Create a draft first
			
			$to = '';
			$cc = '';
			$bcc = '';
			$subject = $ticket->subject;
			
			/*
			$draft = new Model_MailQueue();
			$draft->params['options_gpg_encrypt'] = true;
			$draft->params['options_gpg_sign'] = true;
			$tpl->assign('draft', $draft);
			*/
			
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
	
	private function _profileAction_validateReplyJson() {
		header('Content-Type: application/json; charset=utf-8');
		
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer');
		@$is_forward = DevblocksPlatform::importGPC($_POST['is_forward'],'integer',0);
		
		@$to = DevblocksPlatform::importGPC(@$_POST['to']);
		
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
				'message_id' => DevblocksPlatform::importGPC(@$_POST['id']),
				'ticket_id' => $ticket_id,
				'is_forward' => $is_forward,
				'to' => $to,
				'cc' => DevblocksPlatform::importGPC(@$_POST['cc']),
				'bcc' => DevblocksPlatform::importGPC(@$_POST['bcc']),
				'subject' => DevblocksPlatform::importGPC(@$_POST['subject'],'string'),
				'content' => DevblocksPlatform::importGPC(@$_POST['content']),
				'content_format' => DevblocksPlatform::importGPC(@$_POST['format'],'string',''),
				'html_template_id' => DevblocksPlatform::importGPC(@$_POST['html_template_id'],'integer',0),
				'status_id' => DevblocksPlatform::importGPC(@$_POST['status_id'],'integer',0),
				'group_id' => DevblocksPlatform::importGPC(@$_POST['group_id'],'integer',0),
				'bucket_id' => DevblocksPlatform::importGPC(@$_POST['bucket_id'],'integer',0),
				'owner_id' => DevblocksPlatform::importGPC(@$_POST['owner_id'],'integer',0),
				'ticket_reopen' => DevblocksPlatform::importGPC(@$_POST['ticket_reopen'],'string',''),
				'gpg_encrypt' => DevblocksPlatform::importGPC(@$_POST['options_gpg_encrypt'],'integer',0),
				'gpg_sign' => DevblocksPlatform::importGPC(@$_POST['options_gpg_sign'],'integer',0),
				'worker_id' => @$worker->id,
				'forward_files' => $file_ids,
				'link_forward_files' => true,
			);
			
			if(empty($properties['to']))
				throw new Exception_DevblocksAjaxValidationError("The 'To:' is required.");
			
			if(empty($properties['subject']))
				throw new Exception_DevblocksAjaxValidationError("The 'Subject:' is required.");
			
			// Validate GPG for signature
			if($properties['gpg_sign']) {
				@$group_id = $properties['group_id'];
				@$bucket_id = $properties['bucket_id'];
				$signing_key = null;
				
				if (false != ($group = DAO_Group::get($group_id))) {
					// [TODO] Validate the key can sign (do this on key import/update)
					$signing_key = $group->getReplySigningKey($bucket_id);
				}
				
				if(!$signing_key)
					throw new Exception_DevblocksAjaxValidationError(sprintf("Can't find a PGP signing key for group '%s'", $group->name));
			}
			
			// Validate GPG if used (we need public keys for all recipients)
			if($properties['gpg_encrypt']) {
				$gpg = DevblocksPlatform::services()->gpg();
				
				$email_addresses = DevblocksPlatform::parseCsvString(sprintf("%s%s%s",
					!empty($properties['to']) ? ($properties['to'] . ', ') : '',
					!empty($properties['cc']) ? ($properties['cc'] . ', ') : '',
					!empty($properties['bcc']) ? ($properties['bcc'] . ', ') : ''
				));
				
				$email_models = DAO_Address::lookupAddresses($email_addresses, true);
				$emails_to_check = array_flip(array_column(DevblocksPlatform::objectsToArrays($email_models), 'email'));
				
				foreach($email_models as $email_model) {
					if(false == ($info = $gpg->keyinfoPublic(sprintf("<%s>", $email_model->email))) || !is_array($info))
						continue;
					
					foreach($info as $key) {
						foreach($key['uids'] as $uid) {
							unset($emails_to_check[$uid['email']]);
						}
					}
				}
				
				if(!empty($emails_to_check)) {
					throw new Exception_DevblocksAjaxValidationError("Can't send encrypted message. We don't have a PGP public key for: " . implode(', ', array_keys($emails_to_check)));
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
	
	private function _profileAction_sendReply() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$drafts_ext = DevblocksPlatform::getExtension('core.page.profiles.draft', true);
		
		/* @var $drafts_ext PageSection_ProfilesDraft */
		if(false == ($result = $drafts_ext->saveDraftReply())) {
			return false;
		}
		
		$draft_id = $result['draft_id'];
		$ticket = $result['ticket'];
		
		if(false == ($draft = DAO_MailQueue::get($draft_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Draft::isWriteableByActor($draft, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$draft->send();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$ticket->mask)));
	}
	
	private function _profileAction_previewReplyMessage() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'],'string','');
		
		if(false == ($group = DAO_Group::get($group_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$html_template = $group->getReplyHtmlTemplate($bucket_id);
		
		// Parse #commands
		
		$message_properties = array(
			'group_id' => $group_id,
			'bucket_id' => $bucket_id,
			'content' => $content,
			'content_format' => $format,
			'html_template_id' => 0, // [TODO] From group/bucket?
		);
		
		$hash_commands = [];
		
		CerberusMail::parseReplyHashCommands($active_worker, $message_properties, $hash_commands);
		
		$output = $message_properties['content'];
		
		// Markdown
		
		if('parsedown' == $format) {
			$output = DevblocksPlatform::parseMarkdown($output);
			
			// Wrap the reply in a template if we have one
			
			if($html_template) {
				$dict = DevblocksDictionaryDelegate::instance([
					'message_body' => $output,
				]);
				
				$output = $tpl_builder->build($html_template->content, $dict);
			}
			
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			$output = DevblocksPlatform::purifyHTML($output, true, true, [$filter]);
			
		} else {
			$output = nl2br(DevblocksPlatform::strEscapeHtml($output));
		}
		
		$tpl->assign('content', $output);
		$tpl->display('devblocks:cerberusweb.core::internal/editors/preview_popup.tpl');
	}
	
	private function _profileAction_showMessageFullHeadersPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		if(!$id)
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($message = DAO_Message::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Message::isReadableByActor($message, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$raw_headers = $message->getHeaders(true);
		$tpl->assign('raw_headers', $raw_headers);
		
		$tpl->display('devblocks:cerberusweb.core::messages/popup_full_headers.tpl');
	}
	
	private function _profileAction_showResendMessagePopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		if(!$id)
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($message = DAO_Message::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Message::isReadableByActor($message, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('message', $message);
		
		$error = null;
		$source = CerberusMail::resend($message, $error, true);
		$tpl->assign('source', $source);
		
		$tpl->display('devblocks:cerberusweb.core::messages/resend_popup.tpl');
	}
	
	private function _profileAction_saveResendMessagePopupJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$id = DevblocksPlatform::importGPC($_POST['id'], 'integer', 0);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!$id)
				throw new Exception_DevblocksAjaxError("Invalid message ID.");
			
			if(false == ($message = DAO_Message::get($id)))
				throw new Exception_DevblocksAjaxError("Invalid message ID.");
			
			$error = null;
			
			if(!Context_Message::isWriteableByActor($message, $active_worker))
				throw new Exception_DevblocksAjaxError("You don't have permission to send this message.");
			
			if(!CerberusMail::resend($message, $error))
				throw new Exception_DevblocksAjaxError($error);
			
			echo json_encode([
				'status' => true,
			]);
			
		} catch (Exception_DevblocksAjaxError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);
			
		} catch (Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
	
	private function _profileAction_showBulkPopup() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_Ticket::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$tpl->assign('ids', $ids);
		}
		
		// Groups
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		// HTML templates
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		$token_labels = $token_values = [];
		
		// Broadcast
		if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_TICKET)))
			return [];
		
		/* @var $context_ext IDevblocksContextBroadcast */
		
		// Recipient fields
		$recipient_fields = $context_ext->broadcastRecipientFieldsGet();
		$tpl->assign('broadcast_recipient_fields', $recipient_fields);
		
		// Placeholders
		$token_values = $context_ext->broadcastPlaceholdersGet();
		@$token_labels = $token_values['_labels'] ?: [];
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::tickets/rpc/bulk.tpl');
	}
	
	// Ajax
	private function _profileAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_Ticket::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$ticket_id_str = DevblocksPlatform::importGPC($_POST['ids'],'string');
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');

		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Actions
		@$actions = DevblocksPlatform::importGPC($_POST['actions'],'array',array());
		@$params = DevblocksPlatform::importGPC($_POST['params'],'array',array());

		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
		@$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'],'array',array());
		
		$do = array();
		
		if(is_array($actions))
		foreach($actions as $action) {
			switch($action) {
				case 'importance':
				case 'move':
				case 'org':
				case 'owner':
				case 'spam':
				case 'status':
					if(isset($params[$action]))
						$do[$action] = $params[$action];
					break;
					
				case 'watchers_add':
				case 'watchers_remove':
					if(!isset($params[$action]))
						break;
						
					if(!isset($do['watchers']))
						$do['watchers'] = array();
					
					$do['watchers'][substr($action,9)] = $params[$action];
					break;
			}
		}
		
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			);
		}
		
		// Broadcast: Mass Reply
		if($active_worker->hasPriv('core.ticket.view.actions.broadcast_reply')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_POST['do_broadcast'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_POST['broadcast_message'],'string',null);
			@$broadcast_format = DevblocksPlatform::importGPC($_POST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_POST['broadcast_html_template_id'],'integer',0);
			@$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['broadcast_file_ids'],'array',array()), 'integer', array('nonzero','unique'));
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_POST['broadcast_is_queued'],'integer',0);
			
			if(0 != strlen($do_broadcast) && !empty($broadcast_message)) {
				$do['broadcast'] = array(
					'message' => $broadcast_message,
					'format' => $broadcast_format,
					'html_template_id' => $broadcast_html_template_id,
					'is_queued' => $broadcast_is_queued,
					'file_ids' => $broadcast_file_ids,
					'worker_id' => $active_worker->id,
				);
			}
		}
		
		$ids = [];
		
		switch($filter) {
			case 'checks':
				$filter = ''; // bulk update just looks for $ids == !null
				$ids = DevblocksPlatform::parseCsvString($ticket_id_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'],'integer',0),9999);
				$filter = '';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}
		
		// Restrict to current worker groups
		if(!$active_worker->is_superuser) {
			$memberships = $active_worker->getMemberships();
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_GROUP_ID, 'in', array_keys($memberships)), 'tmp');
		}
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_ID, 'in', $ids)
			], true);
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
	
	private function _profileAction_viewMarkClosed() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'],'array:integer');
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_CLOSED,
		];
		
		$models = DAO_Ticket::getIds($ticket_ids);
		
		// Privs
		$models = array_intersect_key(
			$models,
			array_flip(
				array_keys(
					Context_Ticket::isWriteableByActor($models, $active_worker),
					true
				)
			)
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_CLOSE;
		
		if($models) {
			foreach ($models as $model) {
				$last_action->ticket_ids[$model->id] = [
					DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN
				];
			}
		}
		
		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================
		
		foreach($models as $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model->id, $update_fields);
		}
		return;
	}
	
	private function _profileAction_viewMarkDeleted() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'],'array:integer');
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
		];
		
		$models = DAO_Ticket::getIds($ticket_ids);
		
		// Privs
		$models = array_intersect_key(
			$models,
			array_flip(
				array_keys(
					Context_Ticket::isWriteableByActor($models, $active_worker),
					true
				)
			)
		);
		
		// Log the deletions
		$model_labels = array_map(function($model) { return sprintf("%s: %s", $model->mask, $model->subject); }, $models);
		CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_TICKET, array_keys($models), $model_labels);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_DELETE;
		
		if($models) {
			foreach($models as $model) {
				$last_action->ticket_ids[$model->id] = array(
					DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
				);
			}
		}
		
		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id, $last_action);
		//====================================
		
		foreach($models as $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model->id, $update_fields);
		}
		return;
	}
	
	private function _profileAction_viewMarkSpam() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'],'array:integer');
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
			DAO_Ticket::SPAM_SCORE => 0.9999,
			DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::SPAM,
			DAO_Ticket::UPDATED_DATE => time(),
		];
		
		$models = DAO_Ticket::getIds($ticket_ids);
		
		// Privs
		$models = array_intersect_key(
			$models,
			array_flip(
				array_keys(
					Context_Ticket::isWriteableByActor($models, $active_worker),
					true
				)
			)
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_SPAM;
		
		if(is_array($models)) {
			foreach ($models as $model) {
				$last_action->ticket_ids[$model->id] = array(
					DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::BLANK,
					DAO_Ticket::SPAM_SCORE => 0.5000,
					DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
				);
			}
		}
		
		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================
		
		if(is_array($models)) {
			foreach($models as $model) {
				CerberusBayes::markTicketAsSpam($model->id);
				
				$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
				
				if(!empty($update_fields))
					DAO_Ticket::update($model->id, $update_fields);
			}
		}
		return;
	}
	
	private function _profileAction_viewMarkNotSpam() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'],'array:integer');
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
			DAO_Ticket::SPAM_SCORE => 0.0001,
			DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::NOT_SPAM,
			DAO_Ticket::UPDATED_DATE => time(),
		];
		
		$models = DAO_Ticket::getIds($ticket_ids);
		
		// Privs
		$models = array_intersect_key(
			$models,
			array_flip(
				array_keys(
					Context_Ticket::isWriteableByActor($models, $active_worker),
					true
				)
			)
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_NOT_SPAM;
		
		if(is_array($models))
			foreach($models as $model) {
				$last_action->ticket_ids[$model->id] = [
					DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::BLANK,
					DAO_Ticket::SPAM_SCORE => 0.5000,
					DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
				];
			}
		
		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================
		
		if(is_array($models)) {
			foreach ($models as $model) {
				CerberusBayes::markTicketAsNotSpam($model->id);
				
				$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
				
				if(!empty($update_fields))
					DAO_Ticket::update($model->id, $update_fields);
			}
		}
		
		DevblocksPlatform::exit();
	}
	
	private function _profileAction_viewMarkWaiting() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'],'array:integer');
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_WAITING,
		];
		
		$models = DAO_Ticket::getIds($ticket_ids);
		
		// Privs
		$models = array_intersect_key(
			$models,
			array_flip(
				array_keys(
					Context_Ticket::isWriteableByActor($models, $active_worker),
					true
				)
			)
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_WAITING;
		
		if(is_array($ticket_ids))
			foreach($ticket_ids as $ticket_id) {
				$last_action->ticket_ids[$ticket_id] = array(
					DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
				);
			}
		
		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================
		
		if(is_array($models))
		foreach($models as $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model->id, $update_fields);
		}
		
		DevblocksPlatform::exit();
	}
	
	private function _profileAction_viewMarkNotWaiting() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'],'array:integer');
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
		];
		
		$models = DAO_Ticket::getIds($ticket_ids);
		
		// Privs
		$models = array_intersect_key(
			$models,
			array_flip(
				array_keys(
					Context_Ticket::isWriteableByActor($models, $active_worker),
					true
				)
			)
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_NOT_WAITING;
		
		if(is_array($models))
			foreach($models as $model) {
				$last_action->ticket_ids[$model->id] = array(
					DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_WAITING,
				);
			}
		
		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================
		
		foreach($models as $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model->id, $update_fields);
		}
		
		DevblocksPlatform::exit();
	}
	
	private function _profileAction_viewMove() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'],'array');
		@$group_id = DevblocksPlatform::importGPC($_POST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'],'integer',0);
		
		if(empty($ticket_ids)) {
			return;
		}
		
		$fields = [
			DAO_Ticket::GROUP_ID => $group_id,
			DAO_Ticket::BUCKET_ID => $bucket_id,
		];
		
		$models = DAO_Ticket::getIds($ticket_ids);
		
		// Privs
		$models = array_intersect_key(
			$models,
			array_flip(
				array_keys(
					Context_Ticket::isWriteableByActor($models, $active_worker),
					true
				)
			)
		);
		
		//====================================
		// Undo functionality
		
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_MOVE;
		$last_action->action_params = $fields;
		
		if(is_array($models)) {
			foreach ($models as $model) {
				/* @var $model Model_Ticket */
				$last_action->ticket_ids[$model->id] = array(
					DAO_Ticket::GROUP_ID => $model->group_id,
					DAO_Ticket::BUCKET_ID => $model->bucket_id
				);
			}
		}
		
		View_Ticket::setLastAction($view_id, $last_action);
		
		if(is_array($models))
		foreach($models as $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model->id, $fields);
		}
		return;
	}
	
	private function _profileAction_viewUndo() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		@$clear = DevblocksPlatform::importGPC($_POST['clear'],'integer',0);
		$last_action = View_Ticket::getLastAction($view_id);
		
		if($clear || empty($last_action)) {
			View_Ticket::setLastAction($view_id,null);
			return;
		}
		
		if(is_array($last_action->ticket_ids) && !empty($last_action->ticket_ids))
			foreach($last_action->ticket_ids as $ticket_id => $fields) {
				DAO_Ticket::update($ticket_id, $fields);
			}
		
		$visit = CerberusApplication::getVisit();
		$visit->set(CerberusVisit::KEY_VIEW_LAST_ACTION, null);
		return;
	}
	
	private function _profileAction_viewExplore() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		$max_pages = defined('APP_OPT_EXPLORE_MAX_PAGES') ? APP_OPT_EXPLORE_MAX_PAGES : 4;
		
		do {
			$models = [];
			list($results, $total) = $view->getData();
			
			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					'worker_id' => $active_worker->id,
					'total' => min($total, $max_pages * $view->renderLimit),
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=tickets', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
				foreach($results as $ticket_id => $row) {
					// Set the first record to the conversation tab, but not subsequent (they persist)
					if($ticket_id==$explore_from)
						$orig_pos = $pos;
					
					$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=ticket&id=%s" . ($orig_pos == $pos ? '&tab=conversation' : ''), $row[SearchFields_Ticket::TICKET_MASK]), true);
					
					$model = new Model_ExplorerSet();
					$model->hash = $hash;
					$model->pos = $pos++;
					$model->params = array(
						'id' => $row[SearchFields_Ticket::TICKET_ID],
						'url' => $url,
					);
					$models[] = $model;
				}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results) && $view->renderPage <= $max_pages);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
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
	
	private function _profileAction_requesterAdd() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$email = DevblocksPlatform::importGPC($_POST['email'],'string');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(false == ($ticket = DAO_Ticket::get($ticket_id)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		DAO_Ticket::createRequester($email, $ticket_id);
	}
	
	private function _profileAction_splitMessage() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$message_id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(false == ($message = DAO_Message::get($message_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Message::isWriteableByActor($message, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$error = null;
		
		if(false == ($results = DAO_Ticket::split($message, $error)))
			return;
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('profiles','ticket',$results['mask'])));
	}
	
	private function _profileAction_quickMove() {
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'],'integer');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($bucket = DAO_Bucket::get($bucket_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$fields = [
			DAO_Ticket::GROUP_ID => $bucket->group_id,
			DAO_Ticket::BUCKET_ID => $bucket->id,
		];
		
		DAO_Ticket::update($ticket_id, $fields);
	}
	
	private function _profileAction_quickStatus() {
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$status = DevblocksPlatform::importGPC($_POST['status'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
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
			
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_TICKET, $ticket->id, sprintf("#%s: %s", $ticket->mask, $ticket->subject));
				break;
		}
		
		if(is_null($status_id))
			return;
		
		$fields = [
			DAO_Ticket::STATUS_ID => $status_id,
		];
		
		DAO_Ticket::update($ticket_id, $fields);
	}
	
	private function _profileAction_quickAssign() {
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$owner_id = DevblocksPlatform::importGPC($_POST['owner_id'],'integer');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// If we're assigning an owner
		if($owner_id) {
			if(null == ($worker = DAO_Worker::get($owner_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$owner_id = $worker->id;
			// Or unassigning
		} else {
			$owner_id = 0;
		}
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$fields = [
			DAO_Ticket::OWNER_ID => $owner_id,
		];
		
		DAO_Ticket::update($ticket_id, $fields);
	}
	
	private function _profileAction_quickSurrender() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if($ticket->owner_id == $active_worker->id) {
			$fields = array(
				DAO_Ticket::OWNER_ID => 0,
			);
			
			DAO_Ticket::update($ticket_id, $fields);
		}
	}
	
	private function _profileAction_quickSpam() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$is_spam = DevblocksPlatform::importGPC($_POST['is_spam'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($ticket_id))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($ticket = DAO_Ticket::get($ticket_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if($is_spam) {
			CerberusBayes::markTicketAsSpam($ticket->id);
			
			DAO_Ticket::update($ticket->id, [
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
			]);
			
		} else {
			CerberusBayes::markTicketAsNotSpam($ticket->id);
		}
	}
};