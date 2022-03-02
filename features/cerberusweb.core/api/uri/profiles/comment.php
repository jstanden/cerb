<?php /** @noinspection PhpUnused */

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

class PageSection_ProfilesComment extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // comment 
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_COMMENT;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'togglePin':
					return $this->_profileAction_togglePin();
				case 'preview':
					return $this->_profileAction_preview();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_COMMENT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_Comment::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_Comment::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Comment::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
			}
				
			$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string');
			$context_id = DevblocksPlatform::importGPC($_POST['context_id'] ?? null, 'integer',0);
			$comment = DevblocksPlatform::importGPC($_POST['comment'] ?? null, 'string','');
			$file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_POST['file_ids'] ?? null,'array',[]), 'int');
			$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
			$is_markdown = DevblocksPlatform::importGPC($_POST['is_markdown'] ?? null, 'integer',0);
			
			$error = null;
			
			if(empty($id)) { // New
				$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
				
				// Validate the context
				if(false == ($context_ext = Extension_DevblocksContext::get($context)) || false == ($context_ext->getMeta($context_id)))
					throw new Exception_DevblocksAjaxValidationError("The 'Target' is invalid.", 'context');
				
				$fields = array(
					DAO_Comment::CONTEXT => $context,
					DAO_Comment::CONTEXT_ID => $context_id,
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
					DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::CREATED => time(),
					DAO_Comment::IS_MARKDOWN => $is_markdown,
				);
				
				if(!DAO_Comment::validate($fields, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_Comment::onBeforeUpdateByActor($active_worker, $fields, null, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				$id = DAO_Comment::create($fields, $also_notify_worker_ids, $file_ids);
				DAO_Comment::onUpdateByActor($active_worker, $fields, $id);
				
				if(!empty($view_id) && !empty($id))
					C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_COMMENT, $id);
				
			} else { // Edit
				$fields = array(
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::IS_MARKDOWN => $is_markdown,
				);
				
				if(isset($options['update_timestamp']) && $options['update_timestamp'])
					$fields[DAO_Comment::CREATED] = time();
				
				if(!DAO_Comment::validate($fields, $error, $id))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				if(!DAO_Comment::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				DAO_Comment::update($id, $fields);
				DAO_Comment::onUpdateByActor($active_worker, $fields, $id);
			}
			
			$html = null;
			
			if($id) {
				// Add attachments
				DAO_Attachment::setLinks(CerberusContexts::CONTEXT_COMMENT, $id, $file_ids);
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_COMMENT, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
				
				// Refresh HTML
				$model = DAO_Comment::get($id);
				if($model->context == CerberusContexts::CONTEXT_MESSAGE) {
					$tpl->assign('note', $model);
					$ticket = DAO_Ticket::getTicketByMessageId($model->context_id);
					$tpl->assign('ticket', $ticket);
					$html = $tpl->fetch('devblocks:cerberusweb.core::internal/comments/note.tpl');
					
				} else if($model->context == CerberusContexts::CONTEXT_DRAFT) {
					$tpl->assign('note', $model);
					$html = $tpl->fetch('devblocks:cerberusweb.core::internal/comments/note.tpl');
					
				} else if($model->context == CerberusContexts::CONTEXT_COMMENT) {
					$tpl->assign('note', $model);
					$html = $tpl->fetch('devblocks:cerberusweb.core::internal/comments/note.tpl');
					
				} else {
					$tpl->assign('comment', $model);
					
					if($model->context == CerberusContexts::CONTEXT_TICKET) {
						$ticket = DAO_Ticket::get($model->context_id);
						$tpl->assign('ticket', $ticket);
					}
					
					// Comment notes
					$notes = DAO_Comment::getByContext(CerberusContexts::CONTEXT_COMMENT, $id);
					$comment_notes = [];
					// Index notes by comment_id
					if(is_array($notes))
						foreach($notes as $note) {
							if(!isset($comment_notes[$note->context_id]))
								$comment_notes[$note->context_id] = [];
							$comment_notes[$note->context_id][$note->id] = $note;
						}
					$tpl->assign('comment_notes', $comment_notes);
					
					$html = $tpl->fetch('devblocks:cerberusweb.core::internal/comments/comment.tpl');
				}
			}
			
			echo json_encode(array(
				'status' => true,
				'id' => $id,
				'label' => $comment,
				'context' => $context,
				'comment_html' => $html,
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
	
	private function _profileAction_togglePin() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$is_pinned = DevblocksPlatform::importGPC($_POST['pin'] ?? null, 'integer', 0);
		
		if(false == ($comment = DAO_Comment::get($id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_Comment::isWriteableByActor($comment, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		DAO_Comment::update(
			$comment->id,
			[
				DAO_Comment::IS_PINNED => $is_pinned ? 1 : 0,
			]
		);
	}
	
	private function _profileAction_preview() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$comment = DevblocksPlatform::importGPC($_POST['comment'] ?? null, 'string');
		$is_markdown = DevblocksPlatform::importGPC($_POST['is_markdown'] ?? null, 'integer', 0);
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string');
		
		$model = new Model_Comment();
		$model->created = time();
		$model->owner_context = CerberusContexts::CONTEXT_WORKER;
		$model->owner_context_id = $active_worker->id;
		$model->context = $context;
		$model->context_id = null;
		$model->comment = $comment;
		$model->is_markdown = $is_markdown ? 1 : 0;
		
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/comments/preview_popup.tpl');
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		
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
		$explore_from = DevblocksPlatform::importGPC($_POST['explore_from'] ?? null, 'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}

		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
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
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=comment', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=comment&id=%d", $row[SearchFields_Comment::ID]), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Comment::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
}
