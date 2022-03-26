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

class PageSection_ProfilesProjectBoard extends Extension_PageSection {
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@array_shift($stack); // profiles
		@array_shift($stack); // project_board 
		@$context_id = intval(array_shift($stack)); // 123
		
		$context = Context_ProjectBoard::ID;
		
		Page_Profiles::renderProfile($context, $context_id, $stack);
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('profileAction' == $scope) {
			switch($action) {
				case 'moveCard':
					return $this->_profileAction_moveCard();
				case 'refreshCard':
					return $this->_profileAction_refreshCard();
				case 'refreshColumn':
					return $this->_profileAction_refreshColumn();
				case 'reorderBoard':
					return $this->_profileAction_reorderBoard();
				case 'reorderColumn':
					return $this->_profileAction_reorderColumn();
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
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string', '');
		$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'] ?? null, 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", Context_ProjectBoard::ID)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				if(false == ($model = DAO_ProjectBoard::get($id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if(!Context_ProjectBoard::isDeletableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				CerberusContexts::logActivityRecordDelete(Context_ProjectBoard::ID, $model->id, $model->name);
				
				DAO_ProjectBoard::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				$package_uri = DevblocksPlatform::importGPC($_POST['package'] ?? null, 'string', '');
				
				$mode = 'build';
				
				if(!$id && $package_uri)
					$mode = 'library';
				
				switch($mode) {
					case 'library':
						$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
						
						if(empty($package_uri))
							throw new Exception_DevblocksAjaxValidationError("You must select a package from the library.");
						
						if(false == ($package = DAO_PackageLibrary::getByUri($package_uri)))
							throw new Exception_DevblocksAjaxValidationError("You selected an invalid package.");
						
						if($package->point != 'project_board')
							throw new Exception_DevblocksAjaxValidationError("The selected package is not for this extension point.");
						
						$package_json = $package->getPackageJson();
						$records_created = [];
						
						try {
							CerberusApplication::packages()->import($package_json, $prompts, $records_created);
							
						} catch(Exception_DevblocksValidationError $e) {
							throw new Exception_DevblocksAjaxValidationError($e->getMessage());
							
						} catch (Exception $e) {
							throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
						}
						
						if(!array_key_exists(Context_ProjectBoard::ID, $records_created))
							throw new Exception_DevblocksAjaxValidationError("There was an issue creating the record.");
						
						$new_board = reset($records_created[Context_ProjectBoard::ID]);
						
						if($view_id)
							C4_AbstractView::setMarqueeContextCreated($view_id, Context_ProjectBoard::ID, $new_board['id']);
						
						echo json_encode([
							'status' => true,
							'id' => $new_board['id'],
							'label' => $new_board['label'],
							'view_id' => $view_id,
						]);
						return;
						
					case 'build':
						$name = DevblocksPlatform::importGPC($_POST['name'] ?? null, 'string', '');
						$cards_kata = DevblocksPlatform::importGPC($_POST['cards_kata'] ?? null, 'string', '');
						
						$error = null;
						
						if(empty($id)) { // New
							
							$fields = array(
								DAO_ProjectBoard::CARDS_KATA => $cards_kata,
								DAO_ProjectBoard::NAME => $name,
								DAO_ProjectBoard::UPDATED_AT => time(),
							);
							
							if(!DAO_ProjectBoard::validate($fields, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_ProjectBoard::onBeforeUpdateByActor($active_worker, $fields, null, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							$id = DAO_ProjectBoard::create($fields);
							DAO_ProjectBoard::onUpdateByActor($active_worker, $fields, $id);
							
							if(!empty($view_id) && !empty($id))
								C4_AbstractView::setMarqueeContextCreated($view_id, Context_ProjectBoard::ID, $id);
							
						} else { // Edit
							$fields = array(
								DAO_ProjectBoard::CARDS_KATA => $cards_kata,
								DAO_ProjectBoard::NAME => $name,
								DAO_ProjectBoard::UPDATED_AT => time(),
							);
							
							if(!DAO_ProjectBoard::validate($fields, $error, $id))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							if(!DAO_ProjectBoard::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
							
							DAO_ProjectBoard::update($id, $fields);
							DAO_ProjectBoard::onUpdateByActor($active_worker, $fields, $id);
						}
						
						if($id) {
							// Custom field saves
							$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'] ?? null, 'array', []);
							if(!DAO_CustomFieldValue::handleFormPost(Context_ProjectBoard::ID, $id, $field_ids, $error))
								throw new Exception_DevblocksAjaxValidationError($error);
						}
						
						echo json_encode(array(
							'status' => true,
							'id' => $id,
							'context' => Context_ProjectBoard::ID,
							'label' => $name,
							'view_id' => $view_id,
						));
						return;
				}
			}
			
			throw new Exception_DevblocksAjaxValidationError("An unexpected error occurred.");
			
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
	
	private function _profileAction_moveCard() {
		$card_context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string','');
		$card_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		$from_column_id = DevblocksPlatform::importGPC($_POST['from'] ?? null, 'integer',0);
		$to_column_id = DevblocksPlatform::importGPC($_POST['to'] ?? null, 'integer',0);
		
 		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		// Make sure the context exists is legitimate
		if(false == ($card_context_ext = Extension_DevblocksContext::get($card_context)))
			return;
		
		// Make sure the card record exists
		if(false == $card_context_ext->getModelObject($card_id))
			return;
		
		// Make sure the source column exists
		if(false == (DAO_ProjectBoardColumn::get($from_column_id)))
			return;
		
		// Make sure the destination column exists
		if(false == ($to_column = DAO_ProjectBoardColumn::get($to_column_id)))
			return;
		
		if(!Context_ProjectBoardColumn::isWriteableByActor($to_column, $active_worker))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('error.core.no_acl.edit'), 403);
		
		DAO_ContextLink::deleteLink(Context_ProjectBoardColumn::ID, $from_column_id, $card_context, $card_id);
		DAO_ContextLink::setLink(Context_ProjectBoardColumn::ID, $to_column_id, $card_context, $card_id);
	}
	
	private function _profileAction_refreshColumn() {
		$column_id = DevblocksPlatform::importGPC($_POST['column_id'] ?? null, 'integer',0);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($column = DAO_ProjectBoardColumn::get($column_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($board = $column->getProjectBoard()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_ProjectBoard::isReadableByActor($board, $active_worker))
			DevblocksPlatform::dieWithHttpError(DevblocksPlatform::translate('error.core.no_acl.edit'), 403);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('board', $board);
		$tpl->assign('column', $column);
		
		$tpl->display('devblocks:cerb.project_boards::boards/board/column.tpl');
	}
	
	private function _profileAction_refreshCard() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$board_id = DevblocksPlatform::importGPC($_POST['board_id'] ?? null, 'integer',0);
		$context = DevblocksPlatform::importGPC($_POST['context'] ?? null, 'string',null);
		$id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(false == ($board = DAO_ProjectBoard::get($board_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_ProjectBoard::isReadableByActor($board, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('board', $board);
		
		$dict = ['_context' => $context, 'id' => $id];
		
		// Figure out which column this card is in
		$columns = $board->getColumns();
		$links = DAO_ContextLink::getContextLinks($context, [$id], Context_ProjectBoardColumn::ID);
		$column = current(array_intersect_key($columns, $links[$id] ?? []));
		
		$card = new DevblocksDictionaryDelegate($dict);
		
		if($column) {
			$tpl->assign('column', $column);
			
		} else { // Not on this board anymore
			$tpl->assign('card_is_removed', true);
			$tpl->assign('column', null);
		}
		
		$tpl->assign('card', $card);
		$tpl->display('devblocks:cerb.project_boards::boards/board/card.tpl');
	}
	
	private function _profileAction_reorderBoard() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$board_id = DevblocksPlatform::importGPC($_POST['id'] ?? null, 'integer',0);
		$columns = DevblocksPlatform::importGPC($_POST['columns'] ?? null, 'string','');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(false == ($board = DAO_ProjectBoard::get($board_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Check permissions
		if(!Context_ProjectBoard::isWriteableByActor($board, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		DAO_ProjectBoard::update($board_id, [
			DAO_ProjectBoard::COLUMNS_JSON => json_encode(DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($columns), 'int')),
		]);
	}
	
	private function _profileAction_reorderColumn() {
		$column_id = DevblocksPlatform::importGPC($_POST['column_id'] ?? null, 'integer',0);
		$cards = DevblocksPlatform::importGPC($_POST['cards'] ?? null, 'array',[]);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($column = DAO_ProjectBoardColumn::get($column_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($board = $column->getProjectBoard()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		// Check permissions
		if(!Context_ProjectBoard::isWriteableByActor($board, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		DAO_ProjectBoardColumn::update($column_id, [
			DAO_ProjectBoardColumn::CARDS_JSON => json_encode($cards),
		]);
	}
	
	private function _profileAction_viewExplore() {
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string');
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=project_board', true),
				);
				$models[] = $model;
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $opp_id => $row) {
				if($opp_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=profiles&type=project_board&id=%d-%s", $row[SearchFields_ProjectBoard::ID], DevblocksPlatform::strToPermalink($row[SearchFields_ProjectBoard::NAME])), true);
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_ProjectBoard::ID],
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