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

class PageSection_ProfilesContextSavedSearch extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // saved_search 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_SAVED_SEARCH;
		
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
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer', 0);
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_SAVED_SEARCH)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_ContextSavedSearch::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_ContextSavedSearch::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_SAVED_SEARCH, $model->id, $model->name);
				
				DAO_ContextSavedSearch::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string', '');
				$owner = DevblocksPlatform::importGPC($_POST['owner'] ?? null, 'string', '');
				$query = DevblocksPlatform::importGPC($_POST['query'] ?? null, 'string', '');
				$tag = DevblocksPlatform::importGPC($_POST['tag'] ?? null, 'string', '');
				
				list($owner_context, $owner_context_id) = array_pad(explode(':', DevblocksPlatform::importGPC($_POST['owner'] ?? null,'string','')), 2, null);
				
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
				
				$tag = DevblocksPlatform::strAlphaNum($tag, '-');
					
				if(empty($id)) { // New
					$fields = array(
						DAO_ContextSavedSearch::CONTEXT => $context,
						DAO_ContextSavedSearch::OWNER_CONTEXT => $owner_context,
						DAO_ContextSavedSearch::OWNER_CONTEXT_ID => $owner_context_id,
						DAO_ContextSavedSearch::NAME => $name,
						DAO_ContextSavedSearch::QUERY => $query,
						DAO_ContextSavedSearch::TAG => $tag,
						DAO_ContextSavedSearch::UPDATED_AT => time(),
					);
					
					// Validate fields from DAO
					if(!DAO_ContextSavedSearch::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ContextSavedSearch::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ContextSavedSearch::create($fields);
					DAO_ContextSavedSearch::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_SAVED_SEARCH, $id);
					
				} else { // Edit
					$fields = array(
						DAO_ContextSavedSearch::CONTEXT => $context,
						DAO_ContextSavedSearch::OWNER_CONTEXT => $owner_context,
						DAO_ContextSavedSearch::OWNER_CONTEXT_ID => $owner_context_id,
						DAO_ContextSavedSearch::NAME => $name,
						DAO_ContextSavedSearch::QUERY => $query,
						DAO_ContextSavedSearch::TAG => $tag,
						DAO_ContextSavedSearch::UPDATED_AT => time(),
					);
					
					// Validate fields from DAO
					if(!DAO_ContextSavedSearch::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ContextSavedSearch::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ContextSavedSearch::update($id, $fields);
					DAO_ContextSavedSearch::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_SAVED_SEARCH, $id, $field_ids, $error))
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=saved_search', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=saved_search&id=%d-%s", $row[SearchFields_ContextSavedSearch::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ContextSavedSearch::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ContextSavedSearch::ID],
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
