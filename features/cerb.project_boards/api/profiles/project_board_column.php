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

class PageSection_ProfilesProjectBoardColumn extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // project_board_column 
		@$context_id = intval(array_shift($stack)); // 123

		$context = Context_ProjectBoardColumn::ID;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch ($action) {
				case 'loadCards':
					return $this->_profileAction_loadCards();
				case 'savePeekJson':
					return $this->_profileAction_savePeekJson();
				case 'viewExplore':
					return $this->_profileAction_viewExplore();
			}
		}
		return false;
	}
	
	private function _profileAction_loadCards() {
		$column_id = DevblocksPlatform::importGPC($_POST['column_id'] ?? null, 'integer', 0);
		$since_id = DevblocksPlatform::importGPC($_POST['since'] ?? null, 'string', '');
		
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(false == ($column = DAO_ProjectBoardColumn::get($column_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($board = $column->getProjectBoard()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_ProjectBoardColumn::isReadableByActor($board, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$cards = $column->getCards($since_id);
		
		$tpl->assign('board', $board);
		$tpl->assign('column', $column);
		$tpl->assign('cards', $cards);
		
		$tpl->display('devblocks:cerb.project_boards::boards/board/cards.tpl');
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
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", Context_ProjectBoardColumn::ID)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_ProjectBoardColumn::get($id)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(!Context_ProjectBoardColumn::isDeletableByActor($model, $active_worker))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				CerberusContexts::logActivityRecordDelete(Context_ProjectBoardColumn::ID, $model->id, $model->name);
				
				DAO_ProjectBoardColumn::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
				$board_id = DevblocksPlatform::importGPC($_POST['board_id'] ?? null, 'integer', 0);
				$cards_kata = DevblocksPlatform::importGPC($_POST['cards_kata'] ?? null, 'string', '');
				$toolbar_kata = DevblocksPlatform::importGPC($_POST['toolbar_kata'] ?? null, 'string', '');
				$functions_kata = DevblocksPlatform::importGPC($_POST['functions_kata'] ?? null, 'string', '');
				
				$error = null;
				
				// DAO
				
				if(empty($id)) { // New
					$fields = array(
						DAO_ProjectBoardColumn::UPDATED_AT => time(),
						DAO_ProjectBoardColumn::BOARD_ID => $board_id,
						DAO_ProjectBoardColumn::NAME => $name,
						DAO_ProjectBoardColumn::CARDS_KATA => $cards_kata,
						DAO_ProjectBoardColumn::TOOLBAR_KATA => $toolbar_kata,
						DAO_ProjectBoardColumn::FUNCTIONS_KATA => $functions_kata,
					);
					
					if(!DAO_ProjectBoardColumn::validate($fields, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ProjectBoardColumn::onBeforeUpdateByActor($active_worker, $fields, null, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					$id = DAO_ProjectBoardColumn::create($fields);
					DAO_ProjectBoardColumn::onUpdateByActor($active_worker, $fields, $id);
					
					if(!empty($view_id) && !empty($id))
						C4_AbstractView::setMarqueeContextCreated($view_id, Context_ProjectBoardColumn::ID, $id);
					
				} else { // Edit
					$fields = array(
						DAO_ProjectBoardColumn::UPDATED_AT => time(),
						DAO_ProjectBoardColumn::BOARD_ID => $board_id,
						DAO_ProjectBoardColumn::NAME => $name,
						DAO_ProjectBoardColumn::CARDS_KATA => $cards_kata,
						DAO_ProjectBoardColumn::TOOLBAR_KATA => $toolbar_kata,
						DAO_ProjectBoardColumn::FUNCTIONS_KATA => $functions_kata,
					);
					
					if(!DAO_ProjectBoardColumn::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ProjectBoardColumn::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ProjectBoardColumn::update($id, $fields);
					DAO_ProjectBoardColumn::onUpdateByActor($active_worker, $fields, $id);
				}
				
				if($id) {
					// Custom field saves
					$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
					if(!DAO_CustomFieldValue::handleFormPost(Context_ProjectBoardColumn::ID, $id, $field_ids, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
				}
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'context' => Context_ProjectBoardColumn::ID,
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
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=project_board_column', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=project_board_column&id=%d-%s", $row[SearchFields_ProjectBoardColumn::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ProjectBoardColumn::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ProjectBoardColumn::ID],
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
