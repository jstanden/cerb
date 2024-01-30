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
		
		// Translate masks to IDs
		if(null == ($context_id = DAO_Ticket::getTicketIdByMask($id_string))) {
			$context_id = intval($id_string);
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
				case 'validateBeforeReplyJson':
					return $this->_profileAction_validateBeforeReplyJson();
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
		
		$context = DevblocksPlatform::importGPC($_REQUEST['context'] ?? null, 'string', '');
		$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'] ?? null, 'integer', 0);
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null, 'string', '');
		
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
				
				$tpl->display('devblocks:cerberusweb.core::tickets/peek_preview.tpl');
				break;
				
			case CerberusContexts::CONTEXT_COMMENT:
				if(false == ($comment = DAO_Comment::get($context_id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if(!Context_Comment::isReadableByActor($comment, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$is_writeable = Context_Comment::isWriteableByActor($comment, $active_worker);
				$tpl->assign('is_writeable', $is_writeable);
				
				$tpl->assign('comment', $comment);
				$tpl->display('devblocks:cerberusweb.core::tickets/peek_preview.tpl');
				break;
				
			case CerberusContexts::CONTEXT_DRAFT:
				if(false == ($draft = DAO_MailQueue::get($context_id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if(!Context_Draft::isReadableByActor($draft, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$is_writeable = Context_Draft::isWriteableByActor($draft, $active_worker);
				$tpl->assign('is_writeable', $is_writeable);
					
				$tpl->assign('draft', $draft);
				$tpl->display('devblocks:cerberusweb.core::tickets/peek_preview.tpl');
				break;
		}
	}
	
	private function _profileAction_savePeekJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			$subject = DevblocksPlatform::importGPC($_POST['subject'] ?? null,'string','');
			$org_id = DevblocksPlatform::importGPC($_POST['org_id'] ?? null,'integer',0);
			$status_id = DevblocksPlatform::importGPC($_POST['status_id'] ?? null,'integer',0);
			$importance = DevblocksPlatform::importGPC($_POST['importance'] ?? null,'integer',0);
			$owner_id = DevblocksPlatform::importGPC($_POST['owner_id'] ?? null,'integer',0);
			$participants = DevblocksPlatform::importGPC($_POST['participants'] ?? null,'array',[]);
			$group_id = DevblocksPlatform::importGPC($_POST['group_id'] ?? null,'integer',0);
			$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'] ?? null,'integer',0);
			$spam_training = DevblocksPlatform::importGPC($_POST['spam_training'] ?? null,'string','');
			$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'] ?? null,'string','');
			
			if(!$active_worker->hasPriv(sprintf('contexts.%s.update', CerberusContexts::CONTEXT_TICKET)))
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.edit'));
			
			// Load the existing model so we can detect changes
			if(false == ($ticket = DAO_Ticket::get($id)))
				throw new Exception_DevblocksAjaxValidationError("There was an unexpected error when loading this record.");
			
			$fields = array(
				DAO_Ticket::SUBJECT => $subject,
				DAO_Ticket::UPDATED_DATE => time(),
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
			$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
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

	private function _createReplyDraft($id, $reply_mode, $is_forward) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($message = DAO_Message::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($ticket = $message->getTicket()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$to = '';
		$cc = '';
		$bcc = '';
		$subject = $ticket->subject;
		$content = '';
		$message_headers = $message->getHeaders();
		$requesters = $ticket->getRequesters();
		
		$signature_pos = DAO_WorkerPref::get($active_worker->id, 'mail_signature_pos', 2);
		$mail_status_reply = DAO_WorkerPref::get($active_worker->id,'mail_status_reply','waiting');
		
		// Forward
		if($is_forward) {
			$subject = sprintf("Fwd: %s",
				$ticket->subject
			);
			
			$content = "\n\n\n";
			
			if(0 != $signature_pos)
				$content .= "#signature\n\n";
			
			$content .= DevblocksPlatform::translate('display.reply.forward.banner') . "\n";
			
			if(array_key_exists('subject', $message_headers))
				$content .= sprintf("%s: %s\n",
					DevblocksPlatform::translate('message.header.subject'),
					$message_headers['subject']
				);
			
			if(array_key_exists('from', $message_headers))
				$content .= sprintf("%s: %s\n",
					DevblocksPlatform::translate('message.header.from'),
					$message_headers['from']
				);
			
			if(array_key_exists('date', $message_headers))
				$content .= sprintf("%s: %s\n",
					DevblocksPlatform::translate('message.header.date'),
					$message_headers['date']
				);
			
			if(array_key_exists('to', $message_headers))
				$content .= sprintf("%s: %s\n",
					DevblocksPlatform::translate('message.header.to'),
					$message_headers['to']
				);
			
			$content .= "\n" . trim($message->getContent());
			
		// Normal reply (non-forward)
		} else {
			$recipients = [];
			
			if(!is_array($requesters))
				$requesters = [];
			
			// Only reply to these recipients
			if(2 == $reply_mode) {
				if (array_key_exists('to', $message_headers)) {
					$from = isset($message_headers['reply-to']) ? $message_headers['reply-to'] : $message_headers['from'];
					$addys = CerberusMail::parseRfcAddresses($from . ', ' . $message_headers['to'], true);
					
					if (is_array($addys))
						foreach ($addys as $addy) {
							$recipients[] = $addy['full_email'];
						}
					
					$to = implode(', ', $recipients);
				}
				
				if (array_key_exists('cc', $message_headers)) {
					$addys = CerberusMail::parseRfcAddresses($message_headers['cc'], true);
					$recipients = [];
					
					if (is_array($addys))
						foreach ($addys as $addy) {
							$recipients[] = $addy['full_email'];
						}
					
					$cc = implode(', ', $recipients);
				}
				
			} else {
				foreach($requesters as $requester) {
					if(false !== ($recipient = CerberusMail::writeRfcAddress($requester->email, $requester->getName())))
						$recipients[] = $recipient;
				}
				
				$to = implode(', ', $recipients);
			}
			
			$tpl->assign('suggested_recipients', DAO_Ticket::findMissingRequestersInHeaders($message_headers, $requesters));
			
			// If quoting content
			if(in_array($reply_mode, [0,2])) {
				$quoted_content = vsprintf(
					DevblocksPlatform::translate('display.reply.reply_banner'),
					[
						DevblocksPlatform::services()->date()->formatTime('D, d M Y', $message->created_date),
						$message->getSender()->getNameWithEmail() ?? ''
					]
				);
				
				$quoted_content .= "\n" . _DevblocksTemplateManager::modifier_devblocks_email_quote(DevblocksPlatform::services()->string()->indentWith(trim($message->getContent()), '> '));
			} else {
				$quoted_content = '';
			}
			
			// Content
			
			if(0 == $signature_pos) { // No signature
				$content .= $quoted_content;
			} elseif(1 == $signature_pos) { // Above with #cut
				$content .= "\n\n\n#signature\n#cut\n\n". $quoted_content;
			} elseif (2 == $signature_pos) { // Below
				$content .= $quoted_content . "\n\n\n#signature\n#cut\n";
			} elseif (3 == $signature_pos) { // Above
				$content .= "\n\n\n#signature\n\n" . $quoted_content;
			}
		}
		
		$draft_properties = [
			'to' => $to,
			'cc' => $cc,
			'bcc' => $bcc,
			'subject' => $subject,
			'content' => $content,
			'ticket_id' => $ticket->id,
			'message_id' => $message->id,
			'worker_id' => $active_worker->id,
			'is_forward' => $is_forward ? 1 : 0,
		];
		
		// Status
		if('open' == $mail_status_reply) {
			$draft_properties['status_id'] = Model_Ticket::STATUS_OPEN;
		} else if('waiting' == $mail_status_reply) {
			$draft_properties['status_id'] = Model_Ticket::STATUS_WAITING;
		} else if('closed' == $mail_status_reply) {
			$draft_properties['status_id'] = Model_Ticket::STATUS_CLOSED;
		}
		
		// Reopen
		if($ticket->reopen_at)
			$draft_properties['ticket_reopen'] = DevblocksPlatform::services()->date()->formatTime(null, $ticket->reopen_at);
		
		$draft_fields = DAO_MailQueue::getFieldsFromMessageProperties($draft_properties);
			
		$draft_fields[DAO_MailQueue::TYPE] = $is_forward ? Model_MailQueue::TYPE_TICKET_FORWARD : Model_MailQueue::TYPE_TICKET_REPLY;
		
		$draft_id = DAO_MailQueue::create($draft_fields);
		
		if(false == ($draft = DAO_MailQueue::get($draft_id)))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		$draft->setTicket($ticket);
		$draft->setMessage($message);
		
		return $draft;
	}
	
	private function _loadReplyDraft($draft_id) {
		if(false == ($draft = DAO_MailQueue::get($draft_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		return $draft;
	}
	
	private function _profileAction_validateBeforeReplyJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		$is_forward = DevblocksPlatform::importGPC($_POST['forward'] ?? null, 'integer',0);
		$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'] ?? null, 'integer',0);
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(!$draft_id && !$is_forward) {
			// Do we need to warn the worker about anything before they reply?
			if(null != ($automation_event = DAO_AutomationEvent::getByName('mail.reply.validate'))) {
				$automation_event_dict = DevblocksDictionaryDelegate::instance([]);
				
				if(!($message = DAO_Message::get($id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				$automation_event_dict->mergeKeys('message_', DevblocksDictionaryDelegate::getDictionaryFromModel($message, CerberusContexts::CONTEXT_MESSAGE));
				$automation_event_dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($active_worker, CerberusContexts::CONTEXT_WORKER));
				
				$event_kata = $automation_event->getKata($automation_event_dict);
				
				echo json_encode([
					'validation_interactions' => $event_kata,
				]);
				return;
			}
		}
		
		echo json_encode([
			'status' => true,
		]);
	}
	
	private function _profileAction_reply() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		$is_forward = DevblocksPlatform::importGPC($_POST['forward'] ?? null, 'integer',0);
		$reply_mode = DevblocksPlatform::importGPC($_POST['reply_mode'] ?? null, 'integer',0);
		$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'] ?? null, 'integer',0);
		$reply_format = DevblocksPlatform::importGPC($_POST['reply_format'] ?? null, 'string','');
		
		// Get a draft
		if(!$draft_id) {
			$is_resumed = false;
			$draft = $this->_createReplyDraft($id, $reply_mode, $is_forward);
		} else {
			$is_resumed = true;
			$draft = $this->_loadReplyDraft($draft_id);
		}
		
		// Permissions
		
		if(!Context_Draft::isWriteableByActor($draft, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if($draft->worker_id != $active_worker->id)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Load records
		
		if(false == ($ticket = $draft->getTicket()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Ticket::isWriteableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false == ($message = $draft->getMessage())) {
			if(false != ($message = $ticket->getLastMessage()))
				$draft->setMessage($message);
		}
		
		if(!is_a($message, 'Model_Message')) {
			$message = new Model_Message();
			$message->ticket_id = $ticket->id;
		}
		
		if(false == ($bucket = $ticket->getBucket()))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Form variables
		
		$tpl->assign('is_forward', $is_forward);
		$tpl->assign('reply_mode', $reply_mode);
		$tpl->assign('reply_format', $reply_format);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('message', $message);
		$tpl->assign('bucket', $bucket);
		
		// Changing the draft through an automation
		AutomationTrigger_MailDraft::trigger($draft, $is_resumed);
		
		// Custom fields
		
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		$custom_field_values = @$custom_field_values[$ticket->id] ?: [];
		
		// Fields
		
		if(array_key_exists('owner_id', $draft->params))
			$ticket->owner_id = $draft->params['owner_id'];
		
		if(array_key_exists('group_id', $draft->params))
			$ticket->group_id = $draft->params['group_id'];
		
		if(array_key_exists('bucket_id', $draft->params))
			$ticket->bucket_id = $draft->params['bucket_id'];
		
		if(array_key_exists('custom_fields', $draft->params))
			$draft->beforeEditingCustomFields($custom_field_values);
		
		$custom_fieldsets_available = DAO_CustomFieldset::getUsableByActorByContext($active_worker, CerberusContexts::CONTEXT_TICKET);
		$tpl->assign('custom_fieldsets_available', $custom_fieldsets_available);
		
		// Expanded custom fieldsets (including draft fields)
		
		$custom_fieldsets_linked = DAO_CustomFieldset::getByFieldIds(array_keys(array_filter($custom_field_values, fn($v) => !is_null($v))));
		$tpl->assign('custom_fieldsets_linked', $custom_fieldsets_linked);
		
		$tpl->assign('custom_field_values', $custom_field_values);
		
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
		
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));
		
		// Bot behaviors
		
		if(null != $active_worker && class_exists('Event_MailBeforeUiReplyByWorker')) {
			$actions = [];
			
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
		
		// Reply toolbars
		
		$toolbar_keyboard_shortcuts = [];
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'caller_name' => 'cerb.toolbar.mail.reply.formatting',
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id
		]);
		
		$toolbar_reply_formatting_kata = <<< EOD
interaction/link:
  icon: link
  tooltip: Insert Link
  uri: cerb.editor.toolbar.markdownLink
  keyboard: ctrl+k

interaction/image:
  icon: picture
  tooltip: Insert Image
  uri: cerb.editor.toolbar.markdownImage
  keyboard: ctrl+m

menu/formatting:
  icon: more
  #hover@bool: yes
  items:
    interaction/bold:
      label: Bold
      icon: bold
      uri: cerb.editor.toolbar.wrapSelection
      keyboard: ctrl+b
      inputs:
        start_with: **
    interaction/italics:
      label: Italics
      icon: italic
      uri: cerb.editor.toolbar.wrapSelection
      keyboard: ctrl+i
      inputs:
        start_with: _
    interaction/list:
      label: Unordered List
      icon: list
      uri: cerb.editor.toolbar.indentSelection
      inputs:
        prefix: * 
    interaction/quote:
      label: Quote
      icon: quote
      uri: cerb.editor.toolbar.indentSelection
      keyboard: ctrl+q
      inputs:
        prefix: > 
    interaction/variable:
      label: Variable
      icon: edit
      uri: cerb.editor.toolbar.wrapSelection
      inputs:
        start_with: `
    interaction/codeBlock:
      label: Code Block
      icon: embed
      uri: cerb.editor.toolbar.wrapSelection
      keyboard: ctrl+o
      inputs:
        start_with@text:
          ~~~
          
        end_with@text:
          ~~~
          
    interaction/table:
      label: Table
      icon: table
      uri: cerb.editor.toolbar.markdownTable
EOD;
		
		if(($toolbar_reply_formatting = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_reply_formatting_kata, $toolbar_dict))) {
			DevblocksPlatform::services()->ui()->toolbar()->extractKeyboardShortcuts($toolbar_reply_formatting, $toolbar_keyboard_shortcuts);
			$tpl->assign('toolbar_formatting', $toolbar_reply_formatting);
		}
		
		if(null != ($message_dict = DevblocksDictionaryDelegate::getDictionaryFromModel($message, CerberusContexts::CONTEXT_MESSAGE))) {
			$toolbar_dict = DevblocksDictionaryDelegate::instance($message_dict->getDictionary(null, false, 'message_'));
		} else {
			$toolbar_dict = DevblocksDictionaryDelegate::instance([]);
		}
		
		$toolbar_dict->set('caller_name', 'cerb.toolbar.mail.reply');
		$toolbar_dict->set('worker__context', CerberusContexts::CONTEXT_WORKER);
		$toolbar_dict->set('worker_id', $active_worker->id);
		
		if(($toolbar_reply_custom = DAO_Toolbar::getKataByName('mail.reply', $toolbar_dict))) {
			DevblocksPlatform::services()->ui()->toolbar()->extractKeyboardShortcuts($toolbar_reply_custom, $toolbar_keyboard_shortcuts);
			$tpl->assign('toolbar_custom', $toolbar_reply_custom);
		}
		
		$tpl->assign('draft', $draft);
		$tpl->assign('toolbar_keyboard_shortcuts', $toolbar_keyboard_shortcuts);
		
		// Display template
		$tpl->display('devblocks:cerberusweb.core::display/rpc/reply.tpl');
	}
	
	private function _profileAction_validateReplyJson() {
		header('Content-Type: application/json; charset=utf-8');
		
		$reply_mode = DevblocksPlatform::strLower(DevblocksPlatform::importGPC($_POST['reply_mode'] ?? null,'string'));
		$reply_modes = ['send','save','draft'];
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('common.access_denied'));

			if(null == ($active_worker = CerberusApplication::getActiveWorker()))
				throw new Exception_DevblocksAjaxValidationError("You're not signed in.");
			
			if(!in_array($reply_mode, $reply_modes))
				throw new Exception_DevblocksAjaxValidationError("Unknown reply mode.");
			
			$drafts_ext = DevblocksPlatform::getExtension('core.page.profiles.draft', true);
			
			/* @var $drafts_ext PageSection_ProfilesDraft */
			if(!($result = $drafts_ext->saveDraftReply()))
				throw new Exception_DevblocksAjaxValidationError("Failed to save draft.");
			
			$draft_id = $result['draft_id'];
			
			if(!($draft = DAO_MailQueue::get($draft_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			if(!Context_Draft::isWriteableByActor($draft, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if('draft' != $reply_mode) {
				if(empty($draft->params['to'] ?? null))
					throw new Exception_DevblocksAjaxValidationError("`To:` is required.");
				
				if(empty($draft->params['subject'] ?? null))
					throw new Exception_DevblocksAjaxValidationError("`Subject:` is required.");
				
				if(strlen($draft->params['subject'] ?? '') > 255)
					throw new Exception_DevblocksAjaxValidationError("`Subject:` must be shorter than 255 characters.");
				
				// Validate GPG for signature
				if($draft->params['options_gpg_sign']) {
					$group_id = $draft->params['group_id'] ?? 0;
					$bucket_id = $draft->params['bucket_id'] ?? 0;
					$signing_key = null;
					
					if (false != ($group = DAO_Group::get($group_id))) {
						// [TODO] Validate the key can sign (do this on key import/update)
						$signing_key = $group->getReplySigningKey($bucket_id);
					}
					
					if(!$signing_key)
						throw new Exception_DevblocksAjaxValidationError(sprintf("Can't find a PGP signing key for group '%s'", $group->name));
				}
				
				// Validate GPG if used (we need public keys for all recipients)
				if($draft->params['options_gpg_encrypt']) {
					$gpg = DevblocksPlatform::services()->gpg();
					
					$recipient_parts = [];
					
					if($draft->getParam('to'))
						$recipient_parts[] = $draft->getParam('to');
					
					if($draft->getParam('cc'))
						$recipient_parts[] = $draft->getParam('cc');
					
					if($draft->getParam('bcc'))
						$recipient_parts[] = $draft->getParam('bcc');
					
					$email_addresses = DevblocksPlatform::parseCsvString(implode(',', $recipient_parts));
					$email_models = DAO_Address::lookupAddresses($email_addresses, true);
					$emails_to_check = array_fill_keys(array_column(DevblocksPlatform::objectsToArrays($email_models), 'email'), true);
					
					foreach($email_models as $email_model) {
						if(!($info = $gpg->keyinfoPublic(sprintf("<%s>", $email_model->email))) || !is_array($info))
							continue;
						
						foreach($info as $key) {
							foreach($key['uids'] as $uid) {
								unset($emails_to_check[DevblocksPlatform::strLower($uid['email'])]);
							}
						}
					}
					
					if(!empty($emails_to_check)) {
						throw new Exception_DevblocksAjaxValidationError("Can't send encrypted message. We don't have a PGP public key for: " . implode(', ', array_keys($emails_to_check)));
					}
				}
			}
			
			// Only run validation interactions on send/save
			if('draft' != $reply_mode) {
				if(null != ($automation_event = DAO_AutomationEvent::getByName('mail.draft.validate'))) {
					$automation_event_dict = DevblocksDictionaryDelegate::instance([
						'mode' => sprintf("reply.%s", $reply_mode),
					]);
					
					$automation_event_dict->mergeKeys('draft_', DevblocksDictionaryDelegate::getDictionaryFromModel($draft, CerberusContexts::CONTEXT_DRAFT));
					$automation_event_dict->mergeKeys('worker_', DevblocksDictionaryDelegate::getDictionaryFromModel($active_worker, CerberusContexts::CONTEXT_WORKER));

					$event_kata = $automation_event->getKata($automation_event_dict);
					
					echo json_encode([
						'validation_interactions' => $event_kata,
					]);
					
					return;
				}
			}
			
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
		
		header('Content-Type: application/json; charset=utf-8');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$drafts_ext = DevblocksPlatform::getExtension('core.page.profiles.draft', true);
		
		/* @var $drafts_ext PageSection_ProfilesDraft */
		if(!($result = $drafts_ext->saveDraftReply())) {
			return false;
		}
		
		$draft_id = $result['draft_id'];
		
		if(!($draft = DAO_MailQueue::get($draft_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Draft::isWriteableByActor($draft, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false !== ($response = $draft->send()) && is_array($response)) {
			$labels = $values = [];
			CerberusContexts::getContext($response[0], $response[1], $labels, $values, null, true, true);

			// Return the new record data
			echo json_encode($values);
		}
	}
	
	private function _profileAction_previewReplyMessage() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$group_id = DevblocksPlatform::importGPC($_POST['group_id'] ?? null, 'integer',0);
		$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'] ?? null, 'integer',0);
		$content = DevblocksPlatform::importGPC($_POST['content'] ?? null, 'string','');
		$format = DevblocksPlatform::importGPC($_POST['format'] ?? null, 'string','');
		$html_template_id = DevblocksPlatform::importGPC($_POST['html_template_id'] ?? null, 'integer',0);
		
		if(!($group = DAO_Group::get($group_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!$html_template_id || !($html_template = DAO_MailHtmlTemplate::get($html_template_id)))
			$html_template = $group->getReplyHtmlTemplate($bucket_id);
		
		$in_reply_message_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		
		// Parse #commands
		
		$message_properties = [
			'message_id' => $in_reply_message_id,
			'group_id' => $group_id,
			'bucket_id' => $bucket_id,
			'content' => $content,
			'content_format' => $format,
			'html_template_id' => $html_template ? $html_template->id : 0,
		];
		
		$hash_commands = [];
		
		CerberusMail::parseReplyHashCommands($active_worker, $message_properties, $hash_commands);
		
		// Markdown
		
		if('parsedown' == $format) {
			$output = CerberusMail::getMailTemplateFromContent($message_properties, 'saved', 'html');
			
			// Wrap the reply in a template if we have one
			
			if($html_template) {
				$dict = DevblocksDictionaryDelegate::instance([
					'message_body' => $output,
					'group__context' => CerberusContexts::CONTEXT_GROUP,
					'group_id' => $group_id,
					'bucket__context' => CerberusContexts::CONTEXT_BUCKET,
					'bucket_id' => $bucket_id,
					'message_id_header' => sprintf("<%s@message.example>", sha1(random_bytes(32))),
				]);
				
				$output = $tpl_builder->build($html_template->content, $dict);
			}
			
			$filter = new Cerb_HTMLPurifier_URIFilter_Email(true);
			$output = DevblocksPlatform::purifyHTML($output, true, true, [$filter]);
			
		} else {
			$output = nl2br(DevblocksPlatform::strEscapeHtml(CerberusMail::getMailTemplateFromContent($message_properties, 'saved', 'text')));
		}
		
		$tpl->assign('is_inline', true);
		$tpl->assign('css_class', 'emailBodyHtmlLight');
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
		
		$ids = DevblocksPlatform::importGPC($_REQUEST['ids'] ?? null);
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null);

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
		
		$ticket_id_str = DevblocksPlatform::importGPC($_POST['ids'] ?? null, 'string');
		$filter = DevblocksPlatform::importGPC($_POST['filter'] ?? null, 'string','');

		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Actions
		$actions = DevblocksPlatform::importGPC($_POST['actions'] ?? null, 'array',[]);
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array',[]);
		$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);

		// Scheduled behavior
		$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'] ?? null, 'string','');
		$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'] ?? null, 'string','');
		$behavior_params = DevblocksPlatform::importGPC($_POST['behavior_params'] ?? null, 'array',[]);
		
		$do = [];
		
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
						$do['watchers'] = [];
					
					$do['watchers'][substr($action,9)] = $params[$action];
					break;
			}
		}
		
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = [
				'id' => $behavior_id,
				'when' => $behavior_when,
				'params' => $behavior_params,
			];
		}
		
		if(array_key_exists('skip_updated', $options)) {
			$do['skip_updated'] = true;
		}
		
		// Broadcast: Mass Reply
		if($active_worker->hasPriv('contexts.cerberusweb.contexts.ticket.broadcast')) {
			$do_broadcast = DevblocksPlatform::importGPC($_POST['do_broadcast'] ?? null, 'string',null);
			$broadcast_message = DevblocksPlatform::importGPC($_POST['broadcast_message'] ?? null, 'string',null);
			$broadcast_format = DevblocksPlatform::importGPC($_POST['broadcast_format'] ?? null, 'string',null);
			$broadcast_html_template_id = DevblocksPlatform::importGPC($_POST['broadcast_html_template_id'] ?? null, 'integer',0);
			$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['broadcast_file_ids'] ?? null,'array',array()), 'integer', array('nonzero','unique'));
			$broadcast_is_queued = DevblocksPlatform::importGPC($_POST['broadcast_is_queued'] ?? null, 'integer',0);
			
			if(0 != strlen($do_broadcast) && !empty($broadcast_message)) {
				$do['broadcast'] = [
					'message' => $broadcast_message,
					'format' => $broadcast_format,
					'html_template_id' => $broadcast_html_template_id,
					'is_queued' => $broadcast_is_queued,
					'file_ids' => $broadcast_file_ids,
					'worker_id' => $active_worker->id,
				];
			}
		}
		
		$ids = [];
		
		switch($filter) {
			case 'checks':
				$ids = DevblocksPlatform::parseCsvString($ticket_id_str);
				break;
				
			case 'sample':
				$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'] ?? null,'integer',0),9999);
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
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'array:integer');
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_CLOSED,
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
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'array:integer');
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
			DAO_Ticket::UPDATED_DATE => time(),
		];
		
		$models = DAO_Ticket::getIds($ticket_ids);
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.delete', CerberusContexts::CONTEXT_TICKET)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Privs
		$models = array_intersect_key(
			$models,
			array_flip(
				array_keys(
					Context_Ticket::isDeletableByActor($models, $active_worker),
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
	}
	
	private function _profileAction_viewMarkSpam() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'array:integer');
		
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
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'array:integer');
		
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
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'array:integer');
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_WAITING,
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
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'array:integer');
		
		$fields = [
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
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
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'array');
		$group_id = DevblocksPlatform::importGPC($_POST['group_id'] ?? null, 'integer',0);
		$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'] ?? null, 'integer',0);
		
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
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		$clear = DevblocksPlatform::importGPC($_POST['clear'] ?? null, 'integer',0);
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
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'int', 0);
		
		$http_response = Cerb_ORMHelper::generateRecordExploreSet($view_id, $explore_from);
		DevblocksPlatform::redirect($http_response);
	}
	
	private function _profileAction_requesterAdd() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'integer');
		$email = DevblocksPlatform::importGPC($_POST['email'] ?? null, 'string');
		
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
		
		$message_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		
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
		$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'integer');
		$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'] ?? null, 'integer');
		
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
	
	private function _profileAction_quickAssign() {
		$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'integer');
		$owner_id = DevblocksPlatform::importGPC($_POST['owner_id'] ?? null, 'integer');
		
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
		
		$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'integer');
		
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
		
		$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'] ?? null, 'integer');
		$is_spam = DevblocksPlatform::importGPC($_POST['is_spam'] ?? null, 'integer', 0);
		
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