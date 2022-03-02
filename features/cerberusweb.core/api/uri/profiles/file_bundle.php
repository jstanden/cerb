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

class PageSection_ProfilesFileBundle extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // file_bundle
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_FILE_BUNDLE;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_savePeekJson() {
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
		$tag = DevblocksPlatform::importGPC($_POST['tag'] ?? null, 'string', '');
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		header('Content-Type: application/json; charset=utf-8');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		try {
			$fields = array(
				DAO_FileBundle::NAME => $name,
				DAO_FileBundle::TAG => $tag,
				DAO_FileBundle::UPDATED_AT => time(),
			);
			
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_FILE_BUNDLE)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_FileBundle::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_FileBundle::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_FILE_BUNDLE, $model->id, $model->name);
				
				DAO_FileBundle::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				
			} else {
				// Owner
				list($owner_context, $owner_context_id) = array_pad(explode(':', DevblocksPlatform::importGPC($_POST['owner'] ?? null,'string','')), 2, null);
				
				switch($owner_context) {
					case CerberusContexts::CONTEXT_APPLICATION:
					case CerberusContexts::CONTEXT_ROLE:
					case CerberusContexts::CONTEXT_GROUP:
					case CerberusContexts::CONTEXT_WORKER:
						break;
						
					default:
						$owner_context = null;
						$owner_context_id = null;
						break;
				}
				
				if(empty($owner_context)) {
					$owner_context = CerberusContexts::CONTEXT_WORKER;
					$owner_context_id = $active_worker->id;
				}
				
				// Create / Edit
				
				$fields[DAO_FileBundle::OWNER_CONTEXT] = $owner_context;
				$fields[DAO_FileBundle::OWNER_CONTEXT_ID] = $owner_context_id;
				
				if(empty($id)) { // New
					if(!DAO_FileBundle::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_FileBundle::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(false == ($id = DAO_FileBundle::create($fields)))
						throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred while creating the record.");
					
					DAO_FileBundle::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_FILE_BUNDLE, $id);
					
				} else { // Edit
					if(!DAO_FileBundle::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_FileBundle::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
	
					DAO_FileBundle::update($id, $fields);
					DAO_FileBundle::onUpdateByActor($active_worker, $fields, $id);
				}
	
				// Attachments
				
				$file_ids = DevblocksPlatform::importGPC($_POST['file_ids'] ?? null, 'array:integer', []);
				
				if(is_array($file_ids))
					DAO_Attachment::setLinks(CerberusContexts::CONTEXT_FILE_BUNDLE, $id, $file_ids);
				
				// If we're adding a comment
				
				if(!empty($comment)) {
					$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
					
					$fields = array(
						DAO_Comment::CREATED => time(),
						DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_FILE_BUNDLE,
						DAO_Comment::CONTEXT_ID => $id,
						DAO_Comment::COMMENT => $comment,
						DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
						DAO_Comment::OWNER_CONTEXT_ID => $active_worker->id,
					);
					$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_FILE_BUNDLE, $id, $field_ids, $error))
					throw new Exception_DevblocksAjaxValidationError($error);
		
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'label' => $name,
					'view_id' => $view_id,
				));
				return;
			}
				
		} catch (Exception_DevblocksAjaxValidationError $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => $e->getMessage(),
					'field' => $e->getFieldName(),
				));
				return;
			
		} catch (Exception $e) {
				echo json_encode(array(
					'status' => false,
					'id' => $id,
					'error' => 'An error occurred.',
				));
				return;
			
		}
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
//					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=file_bundle', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=file_bundle&id=%d-%s", $row[SearchFields_FileBundle::ID], DevblocksPlatform::strToPermalink($row[SearchFields_FileBundle::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_FileBundle::ID],
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
