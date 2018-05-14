<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2018, Webgroup Media LLC
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
		@$section = array_shift($stack);
		
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
		
		if(false == ($context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_TICKET, true)))
			return;

		// Trigger ticket view event (before we load it, in case we change it)
		Event_TicketViewedByWorker::trigger($ticket_id, $active_worker->id);
		
		// Permissions
		
		if(false == ($group = $ticket->getGroup()))
			return;
		
		// Check group membership ACL
		if(!Context_Ticket::isReadableByActor($ticket, $active_worker)) {
			echo DevblocksPlatform::translateCapitalized('common.access_denied');
			exit;
		}
		
		Page_Profiles::renderProfile($context, $context_id);
		
		@$mail_always_show_all = DAO_WorkerPref::get($active_worker->id,'mail_always_show_all',0);

		if($mail_always_show_all)
			$tpl->assign('expand_all', true);
		
		if(!empty($section)) {
			switch($section) {
				case 'conversation':
					@$tab_option = array_shift($stack);
			
					if($mail_always_show_all || 0==strcasecmp("read_all",$tab_option)) {
						$tpl->assign('expand_all', true);
					}
					break;
					
				case 'comment':
					@$focus_id = intval(array_shift($stack));
					$section = 'conversation';
					
					if(!empty($focus_id)) {
						$tpl->assign('convo_focus_ctx', CerberusContexts::CONTEXT_COMMENT);
						$tpl->assign('convo_focus_ctx_id', $focus_id);
					}
					
					break;
					
				case 'message':
					@$focus_id = intval(array_shift($stack));
					$section = 'conversation';
					
					if(!empty($focus_id)) {
						$tpl->assign('convo_focus_ctx', CerberusContexts::CONTEXT_MESSAGE);
						$tpl->assign('convo_focus_ctx_id', $focus_id);
					}
					
					break;
			}
			
			$tpl->assign('tab', $section);
		}
		
		// If deleted, check for a new merge parent URL
		if($dict->status_id == Model_Ticket::STATUS_DELETED) {
			if(false !== ($new_mask = DAO_Ticket::getMergeParentByMask($dict->mask))) {
				if(false !== ($merge_parent = DAO_Ticket::getTicketByMask($new_mask)))
					if(!empty($merge_parent->mask))
						$tpl->assign('merge_parent', $merge_parent);
			}
		}
	}
	
	function getPeekPreviewAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'], 'string', '');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		switch($context) {
			case CerberusContexts::CONTEXT_MESSAGE:
				if(false == ($message = DAO_Message::get($context_id)))
					return;
				
				$tpl->assign('message', $message);
				$tpl->display('devblocks:cerberusweb.core::tickets/peek_preview.tpl');
				break;
				
			case CerberusContexts::CONTEXT_COMMENT:
				if(false == ($comment = DAO_Comment::get($context_id)))
					return;
					
				$tpl->assign('comment', $comment);
				$tpl->display('devblocks:cerberusweb.core::tickets/peek_preview.tpl');
				break;
		}
	}
	
	function showMessageFullHeadersPopupAction() {
		$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		if(empty($id) || false == ($message = DAO_Message::get($id)))
			return;
		
		$raw_headers = $message->getHeaders(true);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('raw_headers', $raw_headers);
		
		$tpl->display('devblocks:cerberusweb.core::messages/popup_full_headers.tpl');
	}
	
	function showResendMessagePopupAction() {
		$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		if(empty($id) || false == ($message = DAO_Message::get($id)))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('message', $message);
		
		$error = null;
		$source = CerberusMail::resend($message, $error, true);
		$tpl->assign('source', $source);
		
		$tpl->display('devblocks:cerberusweb.core::messages/resend_popup.tpl');
	}
	
	function saveResendMessagePopupJsonAction() {
		$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(empty($id) || false == ($message = DAO_Message::get($id)))
				throw new Exception_DevblocksAjaxError("Invalid message ID.");
			
			$error = null;
			
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
	
	function showBulkPopupAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
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
		
		// Broadcast
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, null, $token_labels, $token_values);
		
		// Signature
		$translate = DevblocksPlatform::getTranslationService();
		$token_labels['signature'] = mb_convert_case($translate->_('common.signature'), MB_CASE_TITLE);
		asort($token_labels);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($token_labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::tickets/rpc/bulk.tpl');
	}
	
	// Ajax
	function startBulkUpdateJsonAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$ticket_id_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');

		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
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
			@$do_broadcast = DevblocksPlatform::importGPC($_REQUEST['do_broadcast'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_format = DevblocksPlatform::importGPC($_REQUEST['broadcast_format'],'string',null);
			@$broadcast_html_template_id = DevblocksPlatform::importGPC($_REQUEST['broadcast_html_template_id'],'integer',0);
			@$broadcast_file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['broadcast_file_ids'],'array',array()), 'integer', array('nonzero','unique'));
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_REQUEST['broadcast_is_queued'],'integer',0);
			
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
		
		$data = array();
		$ids = array();
		
		switch($filter) {
			case 'checks':
				$filter = ''; // bulk update just looks for $ids == !null
				$ids = DevblocksPlatform::parseCsvString($ticket_id_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
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
			$view->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_ID, 'in', $ids));
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
};