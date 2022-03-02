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

class PageSection_ProfilesCommunityPortal extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // community_tool 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = CerberusContexts::CONTEXT_PORTAL;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'showConfigTab':
					return $this->_profileAction_showConfigTab();
				case 'saveConfigTabJson':
					return $this->_profileAction_saveConfigTabJson();
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
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", CerberusContexts::CONTEXT_PORTAL)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_CommunityTool::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.record.not_found'));
				
				if(!Context_CommunityTool::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_PORTAL, $model->id, $model->name);
				
				DAO_CommunityTool::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$path = DevblocksPlatform::importGPC($_POST['path'] ?? null, 'string', '');
				
				$error = null;
				
				if(empty($id)) { // New
					$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'] ?? null, 'string', '');
					
					$fields = array(
						DAO_CommunityTool::EXTENSION_ID => $extension_id,
						DAO_CommunityTool::NAME => $name,
						DAO_CommunityTool::UPDATED_AT => time(),
						DAO_CommunityTool::URI => $path,
					);
					
					if(!DAO_CommunityTool::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_CommunityTool::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_CommunityTool::create($fields);
					DAO_CommunityTool::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_PORTAL, $id);
					
				} else { // Edit
					$fields = array(
						DAO_CommunityTool::NAME => $name,
						DAO_CommunityTool::UPDATED_AT => time(),
						DAO_CommunityTool::URI => $path,
					);
					
					if(!DAO_CommunityTool::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
						
					if(!DAO_CommunityTool::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_CommunityTool::update($id, $fields);
					DAO_CommunityTool::onUpdateByActor($active_worker, $fields, $id);
					
				}
				
				// Custom field saves
				$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_PORTAL, $id, $field_ids, $error))
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
			$models = [];
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=community_portal', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=community_portal&id=%d-%s", $row[SearchFields_CommunityTool::ID], DevblocksPlatform::strToPermalink($row[SearchFields_CommunityTool::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_CommunityTool::ID],
					'url' => $url,
				);
				$models[] = $model;
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	private function _profileAction_showConfigTab() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$portal_id = DevblocksPlatform::importGPC($_REQUEST['portal_id'] ?? null, 'integer', 0);
		
		if(false == ($portal = DAO_CommunityTool::get($portal_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_CommunityTool::isWriteableByActor($portal, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false == ($extension = $portal->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!($extension instanceof Extension_CommunityPortal))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$extension->configure($portal);
	}
	
	private function _profileAction_saveConfigTabJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$portal_id = DevblocksPlatform::importGPC($_POST['portal_id'] ?? null, 'integer', 0);
		
		if(false == ($portal = DAO_CommunityTool::get($portal_id)))
			return;
		
		if(!$active_worker || !Context_CommunityTool::isWriteableByActor($portal, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(false == ($extension = $portal->getExtension()))
			return;
		
		if(!($extension instanceof Extension_CommunityPortal))
			return;
		
		$extension->saveConfiguration($portal);
		
		DAO_CommunityTool::update($portal_id, [
			DAO_CommunityTool::UPDATED_AT => time(),
		]);
		
		echo json_encode([
			'message' => 'Saved!',
		]);
	}
};
