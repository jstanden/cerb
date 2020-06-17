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

class PageSection_ProfilesDraft extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // draft
		@$context_id = intval(array_shift($stack));
		
		$context = CerberusContexts::CONTEXT_DRAFT;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'get':
					return $this->_profileAction_get();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'deleteDraft':
					return $this->_profileAction_deleteDraft();
				case 'saveDraftCompose':
					return $this->_profileAction_saveDraftCompose();
				case 'saveDraftReply':
					return $this->_profileAction_saveDraftReply();
				case 'showDraftsBulkPanel':
					return $this->_profileAction_showDraftsBulkPanel();
				case 'startBulkUpdateJson':
					return $this->_profileAction_startBulkUpdateJson();
				case 'saveComposePeek':
					return $this->_profileAction_saveComposePeek();
				case 'validateComposeJson':
					return $this->_profileAction_validateComposeJson();
			}
		}
		return false;
	}
	
	private function _profileAction_get() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		if(false == ($draft = DAO_MailQueue::get($id)))
			DevblocksPlatform::dieWithHttpError(null,404);
		
		if(!Context_Draft::isReadableByActor($draft, $active_worker))
			DevblocksPlatform::dieWithHttpError(null,403);
		
		$tpl->assign('draft', $draft);
		$tpl->display('devblocks:cerberusweb.core::display/modules/conversation/draft.tpl');
	}
	
	private function _profileAction_savePeekJson() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_DRAFT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_MailQueue::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Draft::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_DRAFT, $model->id, $model->name);
				
				DAO_MailQueue::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else { // create/edit
				$error = null;
				
				// Load the existing model so we can detect changes
				if (!$id || false == ($draft = DAO_MailQueue::get($id)))
					throw new Exception_DevblocksAjaxValidationError("There was an unexpected error when loading this record.");
				
				$fields = [];
				
				// Fields
				@$is_queued = DevblocksPlatform::importGPC($_POST['is_queued'], 'bit', 0);
				@$send_at = DevblocksPlatform::importGPC($_POST['send_at'], 'string', '');
				
				$fields[DAO_MailQueue::IS_QUEUED] = $is_queued;
				$fields[DAO_MailQueue::QUEUE_FAILS] = 0;
				
				if($is_queued) {
					if(!$send_at)
						$send_at = 'now';
					
					$fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = strtotime($send_at);
					$draft->params['send_at'] = $send_at;
					
				} else {
					$fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = 0;
					$draft->params['send_at'] = $send_at;
				}
				
				$fields[DAO_MailQueue::PARAMS_JSON] = json_encode($draft->params);
				$fields[DAO_MailQueue::UPDATED] = time();
				
				// Save
				if (!empty($id)) {
					if (!DAO_MailQueue::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if (!DAO_MailQueue::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_MailQueue::update($id, $fields);
					DAO_MailQueue::onUpdateByActor($active_worker, $fields, $id);
					
				} else {
					if (!DAO_MailQueue::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if (!DAO_MailQueue::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if (false == ($id = DAO_MailQueue::create($fields)))
						return false;
					
					DAO_MailQueue::onUpdateByActor($active_worker, $fields, $id);
					
					// View marquee
					if (!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_DRAFT, $id);
					}
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if (!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_DRAFT, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => '', // [TODO]
					'view_id' => $view_id,
				));
				return;
			}
			
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
	
	private function _profileAction_saveDraftReply() {
		@$is_ajax = DevblocksPlatform::importGPC($_POST['is_ajax'],'integer',0);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(false === ($results = $this->saveDraftReply()))
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
	
	function saveDraftCompose() {
		$active_worker = CerberusApplication::getActiveWorker();
		@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer',0);

		@$to = DevblocksPlatform::importGPC($_POST['to'],'string','');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','');

		$params = [];
		
		$hint_to = null;
		$type = null;
		
		foreach($_POST as $k => $v) {
			if(substr($k,0,6) == 'field_')
				continue;
			
			$params[$k] = $v;
		}

		// We don't need these fields
		unset($params['c']);
		unset($params['a']);
		unset($params['module']);
		unset($params['action']);
		unset($params['view_id']);
		unset($params['draft_id']);
		unset($params['group_or_bucket_id']);
		unset($params['_csrf_token']);
		
		// Custom fields
		
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'],'array',array());
		$field_ids = DevblocksPlatform::sanitizeArray($field_ids, 'integer', array('nonzero','unique'));

		if(!empty($field_ids)) {
			$field_values = DAO_CustomFieldValue::parseFormPost(CerberusContexts::CONTEXT_TICKET, $field_ids);
			
			if(!empty($field_values)) {
				$params['custom_fields'] = DAO_CustomFieldValue::formatFieldValues($field_values);
			}
		}
		
		$type = 'mail.compose';
		$hint_to = $to;
		
		$fields = [
			DAO_MailQueue::TYPE => $type,
			DAO_MailQueue::TICKET_ID => 0,
			DAO_MailQueue::WORKER_ID => $active_worker->id,
			DAO_MailQueue::UPDATED => time(),
			DAO_MailQueue::HINT_TO => $hint_to,
			DAO_MailQueue::NAME => $subject,
			DAO_MailQueue::PARAMS_JSON => json_encode($params),
			DAO_MailQueue::IS_QUEUED => 0,
			DAO_MailQueue::QUEUE_DELIVERY_DATE => time(),
		];
		
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
		
		if(empty($draft_id)) {
			$draft_id = DAO_MailQueue::create($fields);
		} else {
			DAO_MailQueue::update($draft_id, $fields);
		}
		
		// If there are attachments, link them to this draft record
		if(isset($params['file_ids']) && is_array($params['file_ids']))
			DAO_Attachment::setLinks(CerberusContexts::CONTEXT_DRAFT, $draft_id, $params['file_ids']);
		
		return $draft_id;
	}
	
	function saveDraftReply() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer',0);
		@$msg_id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer',0);
		
		@$is_forward = DevblocksPlatform::importGPC($_POST['is_forward'],'integer',0);
		
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string','');
		
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
		unset($params['module']);
		unset($params['action']);
		unset($params['view_id']);
		unset($params['draft_id']);
		unset($params['is_ajax']);
		unset($params['ticket_id']);
		unset($params['ticket_mask']);
		unset($params['_csrf_token']);
		
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'],'array',[]);
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
	
	private function _profileAction_saveDraftCompose() {
		if(false == ($draft_id = $this->saveDraftCompose())) {
			echo json_encode([]);
			return;
		}
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('timestamp', time());
		$html = $tpl->fetch('devblocks:cerberusweb.core::mail/queue/saved.tpl');
		
		echo json_encode(['draft_id'=>$draft_id, 'html'=>$html]);
	}
	
	private function _profileAction_validateComposeJson() {
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if(false == ($active_worker = CerberusApplication::getActiveWorker()))
				throw new Exception_DevblocksAjaxValidationError("You are not logged in.");
			
			if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.ticket.create'))
				throw new Exception_DevblocksAjaxValidationError("You do not have permission to send mail.");
			
			if(false == (@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer')))
				throw new Exception_DevblocksAjaxValidationError("Invalid draft.");
			
			$drafts_ext = DevblocksPlatform::getExtension('core.page.profiles.draft', true);
			
			/* @var $drafts_ext PageSection_ProfilesDraft */
			if(false === $drafts_ext->saveDraftCompose()) {
				DAO_MailQueue::delete($draft_id);
				throw new Exception_DevblocksAjaxValidationError("Failed to save draft.");
			}
			
			if(false == ($draft = DAO_MailQueue::get($draft_id)))
				throw new Exception_DevblocksAjaxValidationError("Failed to load draft.");
			
			$properties = $draft->getMessageProperties();
			
			if(!array_key_exists('subject', $properties) || !$properties['subject'])
				throw new Exception_DevblocksAjaxValidationError("A 'Subject:' is required.");
			
			@$to = $properties['to'];
			@$cc = $properties['cc'];
			@$bcc = $properties['bcc'];
			
			if(!$to && ($cc || $bcc))
				throw new Exception_DevblocksAjaxValidationError("'To:' is required if you specify a 'Cc/Bcc:' recipient.");
			
			if(!@$properties['group_id'])
				throw new Exception_DevblocksAjaxValidationError("'From:' is required.");
			
			// Validate GPG if signing
			if(@$properties['gpg_sign']) {
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
			// [TODO] Share this between compose/reply
			if(@$properties['gpg_encrypt']) {
				$gpg = DevblocksPlatform::services()->gpg();
				
				$email_addresses = DevblocksPlatform::parseCsvString(sprintf("%s%s%s",
					$to ? ($to . ', ') : '',
					$cc ? ($cc . ', ') : '',
					$bcc ? ($bcc . ', ') : ''
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
	
	private function _profileAction_saveComposePeek() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', null);
		
		if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.ticket.create'))
			return;
		
		$drafts_ext = DevblocksPlatform::getExtension('core.page.profiles.draft', true, true);
		
		/* @var $drafts_ext PageSection_ProfilesDraft */
		if(false == ($draft_id = $drafts_ext->saveDraftCompose())) {
			DAO_MailQueue::delete($draft_id);
			return false;
		}
		
		if(false == ($draft = DAO_MailQueue::get($draft_id)))
			return false;
		
		if(false !== ($response = $draft->send()) && is_array($response)) {
			// View marquee
			if($view_id) {
				C4_AbstractView::setMarqueeContextCreated($view_id, $response[0], $response[1]);
			}
			
			$labels = $values = [];
			CerberusContexts::getContext($response[0], $response[1], $labels, $values, null, true, true);
			
			// Return the new record data
			echo json_encode($values);
		}
		DevblocksPlatform::exit();
	}
	
	private function _profileAction_deleteDraft() {
		@$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer');
		
		if(false == ($model = DAO_MailQueue::get($draft_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		if(!Context_Draft::isDeletableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_DRAFT, $model->id, $model->name);
		
		DAO_MailQueue::delete($draft_id);
	}
	
	private function _profileAction_showDraftsBulkPanel() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_Draft::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl->assign('view_id', $view_id);

		if(!empty($id_csv)) {
			$ids = DevblocksPlatform::parseCsvString($id_csv);
			$tpl->assign('ids', implode(',', $ids));
		}
		
		$tpl->display('devblocks:cerberusweb.core::mail/queue/bulk.tpl');
	}
	
	private function _profileAction_startBulkUpdateJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!$active_worker->hasPriv(sprintf('contexts.%s.update.bulk', Context_Draft::ID)))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_POST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Draft fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string'));

		$do = array();
		
		// Do: Status
		if(0 != strlen($status))
			$do['status'] = $status;
			
		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_POST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_POST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}
		
		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParams([
				new DevblocksSearchCriteria(SearchFields_MailQueue::ID, 'in', $ids)
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
}