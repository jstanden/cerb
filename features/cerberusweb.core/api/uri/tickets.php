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

class ChTicketsPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == (CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
	}
	
	function render() {
	}
	
	function viewTicketsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
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
			$models = array();
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
	
	function viewMessagesExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
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
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					//'worker_id' => $active_worker->id,
					'total' => min($total, $max_pages * $view->renderLimit),
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=tickets&tab=messages', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $id => $row) {
				if($id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $id,
					'url' => $url_writer->writeNoProxy(sprintf("c=profiles&type=ticket&id=%d&show=message&msgid=%d", $row[SearchFields_Message::TICKET_ID], $id), true),
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results) && $view->renderPage <= $max_pages);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	// Ajax
	function reportSpamAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		if(empty($id)) return;

		$fields = array(
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_SPAM;

		$last_action->ticket_ids[$id] = array(
			DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::BLANK,
			DAO_Ticket::SPAM_SCORE => 0.5000,
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
		);

		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================
		
		CerberusBayes::markTicketAsSpam($id);
		
		if(false == ($ticket = DAO_Ticket::get($id)))
			return;
		
		$fields = array(
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
		);
		
		// Only update fields that changed
		$fields = Cerb_ORMHelper::uniqueFields($fields, $ticket);
		
		if(!empty($fields))
			DAO_Ticket::update($id, $fields);
		
		$tpl = DevblocksPlatform::services()->template();

		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$tpl->assign('view', $view);
		
		if(!empty($last_action) && !is_null($last_action->ticket_ids)) {
			$tpl->assign('last_action_count', count($last_action->ticket_ids));
		}
		
		$tpl->assign('last_action', $last_action);
		$tpl->display('devblocks:cerberusweb.core::tickets/rpc/ticket_view_output.tpl');
	}
	
	// Ajax
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', '');
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(403);
		
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
			@$ticket_reopen = DevblocksPlatform::importGPC(@$_POST['ticket_reopen'],'string','');
			
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
	
	function validateComposeJsonAction() {
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(false == ($active_worker = CerberusApplication::getActiveWorker()))
				throw new Exception_DevblocksAjaxValidationError("You are not logged in.");
			
			if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.ticket.create'))
				throw new Exception_DevblocksAjaxValidationError("You do not have permission to send mail.");
			
			if(false == (@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer')))
				throw new Exception_DevblocksAjaxValidationError("Invalid draft.");
			
			$drafts_ext = DevblocksPlatform::getExtension('core.page.profiles.draft', true);
			
			/* @var $drafts_ext PageSection_ProfilesDraft */
			if(false === $drafts_ext->saveDraft()) {
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
			
			// Validate GPG if used (we need public keys for all recipients)
			// [TODO] Share this between compose/reply
			if(array_key_exists('gpg_encrypt', $properties)) {
				if(false == ($gpg = DevblocksPlatform::services()->gpg()) || !$gpg->isEnabled())
					throw new Exception_DevblocksAjaxValidationError("The 'gnupg' PHP extension is not installed.");
				
				$email_addresses = DevblocksPlatform::parseCsvString(sprintf("%s%s%s",
					$to ? ($to . ', ') : '',
					$cc ? ($cc . ', ') : '',
					$bcc ? ($bcc . ', ') : ''
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
	
	function saveComposePeekAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'], 'string', null);
		
		if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.ticket.create'))
			return;
		
		$drafts_ext = DevblocksPlatform::getExtension('core.page.profiles.draft', true, true);
		
		/* @var $drafts_ext PageSection_ProfilesDraft */
		if(false == ($draft_id = $drafts_ext->saveDraft())) {
			DAO_MailQueue::delete($draft_id);
			return false;
		}
		
		if(false == ($draft = DAO_MailQueue::get($draft_id)))
			return false;
		
		if($ticket_id = $draft->send()) {
			// View marquee
			
			if(!empty($ticket_id) && !empty($view_id)) {
				C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_TICKET, $ticket_id);
			}
		}
		exit;
	}
	
	function viewMoveTicketsAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
		
		if(empty($ticket_ids)) {
			$view = C4_AbstractViewLoader::getView($view_id);
			$view->setAutoPersist(false);
			$view->render();
			return;
		}
		
		$fields = [
			DAO_Ticket::GROUP_ID => $group_id,
			DAO_Ticket::BUCKET_ID => $bucket_id,
		];
		
		//====================================
		// Undo functionality
		$orig_tickets = DAO_Ticket::getIds($ticket_ids);
		
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_MOVE;
		$last_action->action_params = $fields;

		if(is_array($orig_tickets))
		foreach($orig_tickets as $orig_ticket_idx => $orig_ticket) { /* @var $orig_ticket Model_Ticket */
			$last_action->ticket_ids[$orig_ticket_idx] = array(
				DAO_Ticket::GROUP_ID => $orig_ticket->group_id,
				DAO_Ticket::BUCKET_ID => $orig_ticket->bucket_id
			);
			$orig_ticket->group_id = $group_id;
			$orig_ticket->bucket_id = $bucket_id;
			$orig_tickets[$orig_ticket_idx] = $orig_ticket;
		}
		
		View_Ticket::setLastAction($view_id,$last_action);
		
		// Only update tickets that are changing
		
		$models = DAO_Ticket::getIds($ticket_ids);
		
		foreach($models as $ticket_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($ticket_id, $fields);
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		return;
	}

	function viewCloseTicketsAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array:integer');
		
		$fields = array(
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_CLOSED,
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_CLOSE;

		if(is_array($ticket_ids))
		foreach($ticket_ids as $ticket_id) {
			$last_action->ticket_ids[$ticket_id] = array(
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN
			);
		}

		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================
		
		// Only update fields that changed
		$models = DAO_Ticket::getIds($ticket_ids);

		foreach($models as $model_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model_id, $update_fields);
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		return;
	}
	
	function viewWaitingTicketsAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array:integer');

		$fields = array(
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_WAITING,
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

		// Only update fields that changed
		$models = DAO_Ticket::getIds($ticket_ids);

		foreach($models as $model_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model_id, $update_fields);
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		return;
	}
	
	function viewNotWaitingTicketsAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array:integer');

		$fields = array(
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_NOT_WAITING;

		if(is_array($ticket_ids))
		foreach($ticket_ids as $ticket_id) {
			$last_action->ticket_ids[$ticket_id] = array(
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_WAITING,
			);
		}

		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================

		// Only update fields that changed
		$models = DAO_Ticket::getIds($ticket_ids);

		foreach($models as $model_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model_id, $update_fields);
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		return;
	}
	
	function viewNotSpamTicketsAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array:integer');

		$fields = array(
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
			DAO_Ticket::SPAM_SCORE => 0.0001,
			DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::NOT_SPAM,
			DAO_Ticket::UPDATED_DATE => time(),
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_NOT_SPAM;

		if(is_array($ticket_ids))
		foreach($ticket_ids as $ticket_id) {
			$last_action->ticket_ids[$ticket_id] = array(
				DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::BLANK,
				DAO_Ticket::SPAM_SCORE => 0.5000,
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
			);
		}

		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================

		// [TODO] Bayes should really be smart enough to allow training of batches of IDs
		if(!empty($ticket_ids))
		foreach($ticket_ids as $id) {
			CerberusBayes::markTicketAsNotSpam($id);
		}
		
		// Only update fields that changed
		$models = DAO_Ticket::getIds($ticket_ids);

		foreach($models as $model_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model_id, $update_fields);
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		return;
	}
	
	function viewSpamTicketsAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array:integer');

		$fields = array(
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
			DAO_Ticket::SPAM_SCORE => 0.9999,
			DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::SPAM,
			DAO_Ticket::UPDATED_DATE => time(),
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_SPAM;

		if(is_array($ticket_ids))
		foreach($ticket_ids as $ticket_id) {
			$last_action->ticket_ids[$ticket_id] = array(
				DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::BLANK,
				DAO_Ticket::SPAM_SCORE => 0.5000,
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
			);
		}

		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================
		
		// {TODO] Batch
		if(!empty($ticket_ids))
		foreach($ticket_ids as $id) {
			CerberusBayes::markTicketAsSpam($id);
		}
		
		// Only update fields that changed
		$models = DAO_Ticket::getIds($ticket_ids);

		foreach($models as $model_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model_id, $update_fields);
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		return;
	}
	
	function viewDeleteTicketsAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array:integer');

		$fields = array(
			DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
		);
		
		//====================================
		// Undo functionality
		$last_action = new Model_TicketViewLastAction();
		$last_action->action = Model_TicketViewLastAction::ACTION_DELETE;

		if(is_array($ticket_ids))
		foreach($ticket_ids as $ticket_id) {
			$last_action->ticket_ids[$ticket_id] = array(
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
			);
		}

		$last_action->action_params = $fields;
		
		View_Ticket::setLastAction($view_id,$last_action);
		//====================================
		
		// Only update fields that changed
		$models = DAO_Ticket::getIds($ticket_ids);

		foreach($models as $model_id => $model) {
			$update_fields = Cerb_ORMHelper::uniqueFields($fields, $model);
			
			if(!empty($update_fields))
				DAO_Ticket::update($model_id, $update_fields);
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		return;
	}
	
	function viewUndoAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$clear = DevblocksPlatform::importGPC($_REQUEST['clear'],'integer',0);
		$last_action = View_Ticket::getLastAction($view_id);
		
		if($clear || empty($last_action)) {
			View_Ticket::setLastAction($view_id,null);
			$view = C4_AbstractViewLoader::getView($view_id);
			$view->setAutoPersist(false);
			$view->render();
			return;
		}

		// [TODO] Check for changes
		if(is_array($last_action->ticket_ids) && !empty($last_action->ticket_ids))
		foreach($last_action->ticket_ids as $ticket_id => $fields) {
			DAO_Ticket::update($ticket_id, $fields);
		}
		
		$visit = CerberusApplication::getVisit();
		$visit->set(CerberusVisit::KEY_VIEW_LAST_ACTION,null);
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		return;
	}
	
};
