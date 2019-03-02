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
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
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
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
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
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
			@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer',0);
			@$status_id = DevblocksPlatform::importGPC($_REQUEST['status_id'],'integer',0);
			@$importance = DevblocksPlatform::importGPC($_REQUEST['importance'],'integer',0);
			@$owner_id = DevblocksPlatform::importGPC($_REQUEST['owner_id'],'integer',0);
			@$participants = DevblocksPlatform::importGPC($_REQUEST['participants'],'array',[]);
			@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
			@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
			@$spam_training = DevblocksPlatform::importGPC($_REQUEST['spam_training'],'string','');
			@$ticket_reopen = DevblocksPlatform::importGPC(@$_REQUEST['ticket_reopen'],'string','');
			@$comment = DevblocksPlatform::importGPC(@$_REQUEST['comment'],'string','');
			
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
			
			// Participiants
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
			if($id && !empty($comment)) {
				$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
				
				$fields = array(
					DAO_Comment::CREATED => time(),
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
					DAO_Comment::CONTEXT_ID => $id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
					DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
				);
				DAO_Comment::create($fields, $also_notify_worker_ids);
			}
			
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
			
			@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer');
	
			// Destination
			
			@$group_id = DevblocksPlatform::importGPC($_POST['group_id'],'integer',0);
			@$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'],'integer',0);
			
			// Headers
			
			@$org_name = DevblocksPlatform::importGPC($_POST['org_name'],'string');
			@$to = rtrim(DevblocksPlatform::importGPC($_POST['to'],'string'),' ,');
			@$cc = rtrim(DevblocksPlatform::importGPC($_POST['cc'],'string',''),' ,;');
			@$bcc = rtrim(DevblocksPlatform::importGPC($_POST['bcc'],'string',''),' ,;');
			@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','(no subject)');
			@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
			@$content_format = DevblocksPlatform::importGPC($_POST['format'],'string','');
			@$html_template_id = DevblocksPlatform::importGPC($_POST['html_template_id'],'integer',0);
	
			// Properties
			
			@$status_id = DevblocksPlatform::importGPC($_POST['status_id'],'integer',0);
			@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
			
			// Options
			
			@$options_gpg_encrypt = DevblocksPlatform::importGPC(@$_POST['options_gpg_encrypt'],'integer',0);
			
			// Attachments
			
			@$file_ids = DevblocksPlatform::importGPC($_POST['file_ids'],'array',array());
			$file_ids = DevblocksPlatform::sanitizeArray($file_ids, 'integer', array('unique', 'nonzero'));
			
			// Org
			
			$org_id = 0;
			if(!empty($org_name)) {
				$org_id = DAO_ContactOrg::lookup($org_name, true);
				
			} else {
				// If we weren't given an organization, use the first recipient
				$to_addys = CerberusMail::parseRfcAddresses($to);
				if(is_array($to_addys) && !empty($to_addys)) {
					if(null != ($to_addy = DAO_Address::lookupAddress(key($to_addys), true))) {
						if(!empty($to_addy->contact_org_id))
							$org_id = $to_addy->contact_org_id;
					}
				}
			}
			
			$properties = array(
				'draft_id' => $draft_id,
				'group_id' => intval($group_id),
				'bucket_id' => intval($bucket_id),
				'org_id' => intval($org_id),
				'to' => $to,
				'cc' => $cc,
				'bcc' => $bcc,
				'subject' => $subject,
				'content' => $content,
				'content_format' => $content_format,
				'html_template_id' => $html_template_id,
				'forward_files' => $file_ids,
				'status_id' => $status_id,
				'ticket_reopen' => $ticket_reopen,
				'link_forward_files' => true,
				'worker_id' => $active_worker->id,
				'gpg_encrypt' => !empty($options_gpg_encrypt),
			);
			
			if(!$properties['subject'])
				throw new Exception_DevblocksAjaxValidationError("A 'Subject:' is required.");
			
			if(!$properties['to'] && ($properties['cc'] || $properties['bcc']))
				throw new Exception_DevblocksAjaxValidationError("'To:' is required if you specify a 'Cc/Bcc:' recipient.");
			
			// Validate GPG if used (we need public keys for all recipients)
			// [TODO] Share this between compose/reply
			if($properties['gpg_encrypt']) {
				if(false == ($gpg = DevblocksPlatform::services()->gpg()) || !$gpg->isEnabled())
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
			
			//throw new Exception_DevblocksAjaxValidationError("Keep on keeping up.");
			
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
		
		if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.ticket.create'))
			return;
		
		@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer');
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');

		if(!empty($draft_id)) {
			$drafts_ext = DevblocksPlatform::getExtension('core.page.profiles.draft', true, true);
			/* @var $drafts_ext PageSection_ProfilesDraft */
			if(false === $drafts_ext->saveDraft()) {
				DAO_MailQueue::delete($draft_id);
				$draft_id = null;
			}
		}
		
		// Destination
		
		@$group_id = DevblocksPlatform::importGPC($_POST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_POST['bucket_id'],'integer',0);
		
		// Headers
		
		@$org_name = DevblocksPlatform::importGPC($_POST['org_name'],'string');
		@$to = rtrim(DevblocksPlatform::importGPC($_POST['to'],'string'),' ,');
		@$cc = rtrim(DevblocksPlatform::importGPC($_POST['cc'],'string',''),' ,;');
		@$bcc = rtrim(DevblocksPlatform::importGPC($_POST['bcc'],'string',''),' ,;');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','(no subject)');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$content_format = DevblocksPlatform::importGPC($_POST['format'],'string','');
		@$html_template_id = DevblocksPlatform::importGPC($_POST['html_template_id'],'integer',0);

		// Properties
		
		@$status_id = DevblocksPlatform::importGPC($_POST['status_id'],'integer',0);
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$owner_id = DevblocksPlatform::importGPC($_POST['owner_id'],'integer',0);
		
		// Options
		
		@$options_dont_send = DevblocksPlatform::importGPC($_POST['options_dont_send'],'integer',0);
		@$options_gpg_encrypt = DevblocksPlatform::importGPC(@$_POST['options_gpg_encrypt'],'integer',0);
		
		// Attachments
		
		@$file_ids = DevblocksPlatform::importGPC($_POST['file_ids'],'array',array());
		$file_ids = DevblocksPlatform::sanitizeArray($file_ids, 'integer', array('unique', 'nonzero'));
		
		// Org
		
		$org_id = 0;
		if(!empty($org_name)) {
			$org_id = DAO_ContactOrg::lookup($org_name, true);
		} else {
			// If we weren't given an organization, use the first recipient
			$to_addys = CerberusMail::parseRfcAddresses($to);
			if(is_array($to_addys) && !empty($to_addys)) {
				if(null != ($to_addy = DAO_Address::lookupAddress(key($to_addys), true))) {
					if(!empty($to_addy->contact_org_id))
						$org_id = $to_addy->contact_org_id;
				}
			}
		}

		$properties = array(
			'draft_id' => $draft_id,
			'group_id' => intval($group_id),
			'bucket_id' => intval($bucket_id),
			'org_id' => intval($org_id),
			'to' => $to,
			'cc' => $cc,
			'bcc' => $bcc,
			'subject' => $subject,
			'content' => $content,
			'content_format' => $content_format,
			'html_template_id' => $html_template_id,
			'forward_files' => $file_ids,
			'status_id' => $status_id,
			'ticket_reopen' => $ticket_reopen,
			'link_forward_files' => true,
			'worker_id' => $active_worker->id,
			'gpg_encrypt' => !empty($options_gpg_encrypt),
		);

		// #commands
		
		$hash_commands = array();
		
		$this->_parseComposeHashCommands($active_worker, $properties, $hash_commands);
		
		// Custom fields
		
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
		$field_values = DAO_CustomFieldValue::parseFormPost(CerberusContexts::CONTEXT_TICKET, $field_ids);
		if(!empty($field_values)) {
			$properties['custom_fields'] = $field_values;
		}
		
		// Options
		
		if(!empty($owner_id))
			$properties['owner_id'] = $owner_id;
		
		if(!empty($options_dont_send))
			$properties['dont_send'] = 1;
		
		$ticket_id = CerberusMail::compose($properties);
		
		if(!empty($ticket_id)) {
			if(!empty($draft_id))
				DAO_MailQueue::delete($draft_id);

			// Run hash commands
			if(!empty($hash_commands))
				$this->_handleComposeHashCommands($hash_commands, $ticket_id, $active_worker);
				
			// Watchers
			@$add_watcher_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST ['add_watcher_ids'], 'array', []), 'integer', ['unique','nonzero']);
			if(!empty($add_watcher_ids))
				CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, $add_watcher_ids);
			
			// Preferences
			
			DAO_WorkerPref::set($active_worker->id, 'compose.group_id', $group_id);
			DAO_WorkerPref::set($active_worker->id, 'compose.bucket_id', $bucket_id);

			// View marquee
			
			if(!empty($ticket_id) && !empty($view_id)) {
				C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_TICKET, $ticket_id);
			}
		}
		
		exit;
	}
	
	private function _parseComposeHashCommands(Model_Worker $worker, array &$message_properties, array &$commands) {
		$lines_in = DevblocksPlatform::parseCrlfString($message_properties['content'], true, false);
		$lines_out = array();
		
		$is_cut = false;
		
		foreach($lines_in as $line) {
			$handled = false;
			$matches = [];
			
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
								$line = $group->getReplySignature($bucket_id, $worker);
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
	
	private function _handleComposeHashCommands(array $commands, $ticket_id, Model_Worker $worker) {
		foreach($commands as $command_data) {
			switch($command_data['command']) {
				case 'comment':
					@$comment = $command_data['args'];
					
					if(!empty($comment)) {
						$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
						
						$fields = array(
							DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
							DAO_Comment::CONTEXT_ID => $ticket_id,
							DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
							DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
							DAO_Comment::CREATED => time()+2,
							DAO_Comment::COMMENT => $comment,
						);
						DAO_Comment::create($fields, $also_notify_worker_ids);
					}
					break;
		
				case 'watch':
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, array($worker->id));
					break;
		
				case 'unwatch':
					CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, array($worker->id));
					break;
			}
		}
	}	
	
	function getComposeSignatureAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
		@$raw = DevblocksPlatform::importGPC($_REQUEST['raw'],'integer',0);
		
		// Parsed or raw?
		$active_worker = !empty($raw) ? null : CerberusApplication::getActiveWorker();
		
		if($group_id && null != ($group = DAO_Group::get($group_id))) {
			echo $group->getReplySignature($bucket_id, $active_worker);
		}
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
