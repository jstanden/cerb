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
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if($id && !Context_Comment::isWriteableByActor($id, $active_worker))
				throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translateCapitalized('common.access_denied'));
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_COMMENT)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_Comment::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
			}
				
			@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
			@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
			@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
			@$file_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::importGPC($_REQUEST['file_ids'],'array',array()), 'int');
			@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
			@$is_markdown = DevblocksPlatform::importGPC($_REQUEST['is_markdown'],'integer',0);
			
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
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
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
					
				} else {
					$tpl->assign('comment', $model);
					
					if($model->context == CerberusContexts::CONTEXT_TICKET) {
						$ticket = DAO_Ticket::get($model->context_id);
						$tpl->assign('ticket', $ticket);
					}
					
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
	
	function previewAction() {
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string');
		@$is_markdown = DevblocksPlatform::importGPC($_REQUEST['is_markdown'],'integer', 0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string');
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
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
	
	function viewExploreAction() {
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
//					'worker_id' => $active_worker->id,
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
};
