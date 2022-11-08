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

class PageSection_ProfilesMessage extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // message
		@$context_id = array_shift($stack);
		
		$context = CerberusContexts::CONTEXT_MESSAGE;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);		
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'get':
					return $this->_profileAction_get();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'showRelayMessagePopup':
					return $this->_profileAction_showRelayMessagePopup();
				case 'saveRelayMessagePopup':
					return $this->_profileAction_saveRelayMessagePopup();
				case 'renderImagesPopup':
					return $this->_profileAction_renderImagesPopup();
				case 'saveImagesPopup':
					return $this->_profileAction_saveImagesPopup();
				case 'renderLinksPopup':
					return $this->_profileAction_renderLinksPopup();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(false == ($model = DAO_Message::get($id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if(!$active_worker->hasPriv('contexts.cerberusweb.contexts.message.delete'))
					throw new Exception_DevblocksAjaxValidationError("You are not authorized to delete this record.");
				
				if(!Context_Message::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError("You are not authorized to modify this record.");
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_MESSAGE, $model->id);
				
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
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
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
	
	private function _profileAction_get() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null); // message id
		$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'] ?? null, 'integer', 0);
		$hide = DevblocksPlatform::importGPC($_REQUEST['hide'] ?? null, 'integer',0);
		$format = DevblocksPlatform::importGPC($_REQUEST['format'] ?? null, 'string',null);
		$always_bright = DevblocksPlatform::importGPC($_REQUEST['light'] ?? null, 'integer',0);
		$images = DevblocksPlatform::importGPC($_REQUEST['images'] ?? null, 'integer',0);
		
		if(!($message = DAO_Message::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Message::isReadableByActor($message, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if($widget_id
				&& false != ($profile_widget = DAO_ProfileWidget::get($widget_id))
				&& $profile_widget->extension_id == ProfileWidget_TicketConvo::ID
			) {
			$tpl->assign('widget', $profile_widget);
		}
		
		if($images)
			$format = 'html';
		
		$tpl->assign('message', $message);
		$tpl->assign('message_id', $message->id);
		$tpl->assign('display_format', $format);
		
		// Sender info
		if(null != ($sender_addy = $message->getSender())) {
			if($images)
				$sender_addy->is_trusted = 1;
		}
		
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
		if(!$hide) {
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
		
		// Prefs
		$mail_reply_button = DAO_WorkerPref::get($active_worker->id, 'mail_reply_button', 0);
		$tpl->assign('mail_reply_button', $mail_reply_button);
		
		$tpl->assign('expanded', (!$hide ? true : false));
		$tpl->assign('is_refreshed', true);
		
		if($always_bright)
			$tpl->assign('always_bright', 1);
		
		$tpl->display('devblocks:cerberusweb.core::display/modules/conversation/message.tpl');
	}
	
	private function _profileAction_viewExplore() {
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time());
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Page start
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'integer',0);
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=messages', true),
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
	
	private function _profileAction_showRelayMessagePopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$message_id = DevblocksPlatform::importGPC($_REQUEST['id'] ?? null, 'integer',0);
		
		if(false == ($message = DAO_Message::get($message_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$tpl->assign('message', $message);
		
		if(false == ($ticket = DAO_Ticket::get($message->ticket_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Ticket::isReadableByActor($ticket, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('ticket', $ticket);
		
		if(false == ($sender = $message->getSender()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$tpl->assign('sender', $sender);
		
		$workers_with_relays = DAO_Address::getByWorkers();
		$tpl->assign('workers_with_relays', $workers_with_relays);
		
		$tpl->display('devblocks:cerberusweb.core::display/rpc/relay_message.tpl');
	}
	
	private function _profileAction_saveRelayMessagePopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$message_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		$emails = DevblocksPlatform::importGPC($_POST['emails'] ?? null, 'array',[]);
		$content = DevblocksPlatform::importGPC($_POST['content'] ?? null, 'string', '');
		$include_attachments = DevblocksPlatform::importGPC($_POST['include_attachments'] ?? null, 'integer', 0);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(!Context_Message::isReadableByActor($message_id, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		CerberusMail::relay($message_id, $emails, $include_attachments, $content, CerberusContexts::CONTEXT_WORKER, $active_worker->id);
	}
	
	private function _profileAction_renderImagesPopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$message_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		
		if(!$message_id)
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($message = DAO_Message::get($message_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Message::isReadableByActor($message, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$filtering_results = [];
		
		$message->getContentAsHtml(true, $filtering_results);
		$tpl->assign('filtering_results', $filtering_results);
		
		$tpl->assign('message', $message);
		$tpl->display('devblocks:cerberusweb.core::internal/security/email_images_popup.tpl');
	}
	
	private function _profileAction_saveImagesPopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$message_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		$sender_id = DevblocksPlatform::importGPC($_POST['sender_id'] ?? null, 'integer',0);
		$is_trusted = DevblocksPlatform::importGPC($_POST['is_trusted'] ?? null, 'integer',0);
		
		if(false == ($address = DAO_Address::get($sender_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Address::isWriteableByActor($address, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		DAO_Address::update($address->id, [
			DAO_Address::IS_TRUSTED => $is_trusted ? 1 : 0,
		]);
	}
	
	private function _profileAction_renderLinksPopup() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$message_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		
		if(!$message_id)
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($message = DAO_Message::get($message_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Message::isReadableByActor($message, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$filtering_results = [];
		
		$message->getContentAsHtml(false, $filtering_results);
		$tpl->assign('filtering_results', $filtering_results);
		
		$tpl->assign('message', $message);
		$tpl->display('devblocks:cerberusweb.core::internal/security/email_links_popup.tpl');
	}
};