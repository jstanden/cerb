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

class PageSection_ProfilesSnippet extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // snippet 
		@$context_id = intval(array_shift($stack)); // 123

		$context = CerberusContexts::CONTEXT_SNIPPET;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function viewExploreAction() {
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=snippet', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=snippet&id=%d-%s", $row[SearchFields_Snippet::ID], DevblocksPlatform::strToPermalink($row[SearchFields_Snippet::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Snippet::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function showSnippetsPeekToolbarAction() {
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$form_id = DevblocksPlatform::importGPC($_REQUEST['form_id'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('context', $context);
		$tpl->assign('form_id', $form_id);
		
		if(false == (Extension_DevblocksContext::get($context)))
			return;
		
		$labels = [];
		$null = [];
		
		CerberusContexts::getContext($context, null, $labels, $null, '', true, false);
		
		$placeholders = Extension_DevblocksContext::getPlaceholderTree($labels);
		$tpl->assign('placeholders', $placeholders);
		
		$tpl->display('devblocks:cerberusweb.core::internal/snippets/peek_toolbar.tpl');
	}
	
	function savePeekJsonAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_POST['title'],'string','');
		@$context = DevblocksPlatform::importGPC($_POST['context'],'string','');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 403);

		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if($do_delete) {
				if(null == ($snippet = DAO_Snippet::get($id))) /* @var $snippet Model_Snippet */
					throw new Exception_DevblocksAjaxValidationError('Failed to delete the record.');
				
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_SNIPPET)) || !Context_Snippet::isWriteableByActor($snippet, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
					
				DAO_Snippet::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				
			} else { // Create || Update
				@list($owner_context, $owner_context_id) = explode(':', DevblocksPlatform::importGPC($_POST['owner'],'string',''));
			
				switch($owner_context) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
					case CerberusContexts::CONTEXT_BOT:
					case CerberusContexts::CONTEXT_WORKER:
						break;
						
					default:
						$owner_context = null;
						$owner_context_id = null;
						break;
				}

				$fields = array(
					DAO_Snippet::TITLE => $title,
					DAO_Snippet::CONTEXT => $context,
					DAO_Snippet::CONTENT => $content,
					DAO_Snippet::UPDATED_AT => time(),
					DAO_Snippet::OWNER_CONTEXT => $owner_context,
					DAO_Snippet::OWNER_CONTEXT_ID => $owner_context_id,
				);
				
				// Custom placeholders
				
				$placeholders = array();
				@$placeholder_keys = DevblocksPlatform::importGPC($_POST['placeholder_keys'],'array',array());
				
				if(is_array($placeholder_keys) && !empty($placeholder_keys)) {
					@$placeholder_types = DevblocksPlatform::importGPC($_POST['placeholder_types'],'array',array());
					@$placeholder_labels = DevblocksPlatform::importGPC($_POST['placeholder_labels'],'array',array());
					@$placeholder_defaults = DevblocksPlatform::importGPC($_POST['placeholder_defaults'],'array',array());
					@$placeholder_deletes = DevblocksPlatform::importGPC($_POST['placeholder_deletes'],'array',array());
					
					foreach($placeholder_keys as $placeholder_idx => $placeholder_key) {
						@$placeholder_type = $placeholder_types[$placeholder_idx];
						@$placeholder_label = $placeholder_labels[$placeholder_idx];
						@$placeholder_default = $placeholder_defaults[$placeholder_idx];
						@$placeholder_delete = $placeholder_deletes[$placeholder_idx];
						
						if(empty($placeholder_key) || !empty($placeholder_delete))
							continue;
						
						$placeholders[$placeholder_key] = array(
							'type' => $placeholder_type,
							'key' => $placeholder_key,
							'label' => $placeholder_label,
							'default' => $placeholder_default,
						);
					}
					
					$fields[DAO_Snippet::CUSTOM_PLACEHOLDERS_JSON] = json_encode($placeholders);
				}
				
				// Create / Update
				
				$error = null;
				
				if(empty($id)) {
					// Validate fields from DAO
					if(!DAO_Snippet::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Snippet::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(false == ($id = DAO_Snippet::create($fields)))
						throw new Exception_DevblocksAjaxValidationError('Failed to create the record.');
					
					DAO_Snippet::onUpdateByActor($active_worker, $fields, $id);
					
					// View marquee
					if(!empty($id) && !empty($view_id)) {
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_SNIPPET, $id);
					}
					
				} else {
					// Validate fields from DAO
					if(!DAO_Snippet::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_Snippet::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(null == ($snippet = DAO_Snippet::get($id)))
						throw new Exception_DevblocksAjaxValidationError('This record no longer exists.');
					
					DAO_Snippet::update($id, $fields);
					DAO_Snippet::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SNIPPET, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
			
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $title,
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
};
