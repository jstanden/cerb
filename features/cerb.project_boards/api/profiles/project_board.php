<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2014, Webgroup Media LLC
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
	
	function savePeekJsonAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(!empty($id) && !empty($do_delete)) { // Delete
				if(!$active_worker->hasPriv(sprintf("contexts.%s.delete", Context_ProjectBoard::ID)))
					throw new Exception_DevblocksAjaxValidationError(DevblocksPlatform::translate('error.core.no_acl.delete'));
				
				DAO_ProjectBoard::delete($id);
				
				echo json_encode(array(
					'status' => true,
					'id' => $id,
					'view_id' => $view_id,
				));
				return;
				
			} else {
				@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
				@$params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);
				
				// Sanitize $add_contexts
				if(isset($params['add_contexts'])) {
					$contexts = Extension_DevblocksContext::getAll(false, 'links');
					$params['add_contexts'] = array_intersect($params['add_contexts'], array_keys($contexts));
				}
				
				$params['card_queries'] = array_filter($params['card_queries'], function($value) {
					return !empty($value);
				});
				
				$params['card_templates'] = array_filter($params['card_templates'], function($value) {
					return !empty($value);
				});
				
				if(empty($id)) { // New
					$fields = array(
						DAO_ProjectBoard::UPDATED_AT => time(),
						DAO_ProjectBoard::NAME => $name,
						DAO_ProjectBoard::PARAMS_JSON => json_encode($params),
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
						DAO_ProjectBoard::UPDATED_AT => time(),
						DAO_ProjectBoard::NAME => $name,
						DAO_ProjectBoard::PARAMS_JSON => json_encode($params),
					);
					
					if(!DAO_ProjectBoard::validate($fields, $error, $id))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					if(!DAO_ProjectBoard::onBeforeUpdateByActor($active_worker, $fields, $id, $error))
						throw new Exception_DevblocksAjaxValidationError($error);
					
					DAO_ProjectBoard::update($id, $fields);
					DAO_ProjectBoard::onUpdateByActor($active_worker, $fields, $id);
				}
				
				// Custom field saves
				@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', []);
				if(!DAO_CustomFieldValue::handleFormPost(Context_ProjectBoard::ID, $id, $field_ids, $error))
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
	
	// [TODO] This should run on newly added cards too
	function moveCardAction() {
		@$card_context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$card_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$from_column_id = DevblocksPlatform::importGPC($_REQUEST['from'],'integer',0);
		@$to_column_id = DevblocksPlatform::importGPC($_REQUEST['to'],'integer',0);
		
		// [TODO] Validate everything (context/id/privs)
		
		if(false == ($to_column = DAO_ProjectBoardColumn::get($to_column_id)))
			return;
		
		DAO_ContextLink::deleteLink(Context_ProjectBoardColumn::ID, $from_column_id, $card_context, $card_id);
		DAO_ContextLink::setLink(Context_ProjectBoardColumn::ID, $to_column_id, $card_context, $card_id);
		
		// Setting links should trigger configured bot behaviors
		if(isset($to_column->params['behaviors'])) {
			@$behavior_params = $to_column->params['behaviors'];
			$behaviors = DAO_TriggerEvent::getIds(array_keys($behavior_params));
			
			if(is_array($behaviors))
			foreach($behaviors as $behavior) {
				$event_ext = $behavior->getEvent();
				
				// Only run events for this context
				if(@$event_ext->manifest->params['macro_context'] != $card_context)
					continue;
				
				$runners = call_user_func([$event_ext->manifest->class, 'trigger'], $behavior->id, $card_id, @$behavior_params[$behavior->id] ?: []);
			}
		}
	}
	
	function refreshColumnAction() {
		@$column_id = DevblocksPlatform::importGPC($_REQUEST['column_id'],'integer',0);
		
		// [TODO] Validate everything (context/id/privs)
		
		if(false == ($column = DAO_ProjectBoardColumn::get($column_id)))
			return;
		
		if(false == ($board = $column->getProjectBoard()))
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('board', $board);
		$tpl->assign('column', $column);
		
		$tpl->display('devblocks:cerb.project_boards::boards/board/column.tpl');
	}
	
	function refreshCardAction() {
		@$board_id = DevblocksPlatform::importGPC($_REQUEST['board_id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string',null);
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		// [TODO] Validate everything (context/id/privs)
		
		if(false == ($board = DAO_ProjectBoard::get($board_id)))
			return;
		
		$tpl->assign('board', $board);
		
		$dict = ['_context' => $context, 'id' => $id];
		
		// Figure out which column this card is in
		$columns = $board->getColumns();
		$links = DAO_ContextLink::getContextLinks($context, [$id], Context_ProjectBoardColumn::ID);
		@$column = array_shift(array_intersect_key($columns, $links[$id]));
		
		if(isset($column)) {
			$dict['column__context'] = Context_ProjectBoardColumn::ID;
			$dict['column_id'] = $column->id;
		}
		
		$card = new DevblocksDictionaryDelegate($dict);
		$tpl->assign('card', $card);
		
		$tpl->display('devblocks:cerb.project_boards::boards/board/card.tpl');
	}
	
	function reorderBoardAction() {
		@$board_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($board = DAO_ProjectBoard::get($board_id)))
			return;
		
		// Check permissions
		if(!Context_ProjectBoard::isWriteableByActor($board, $active_worker))
			return;
		
		DAO_ProjectBoard::update($board_id, [
			DAO_ProjectBoard::COLUMNS_JSON => json_encode(DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($columns), 'int')),
		]);
	}
	
	function reorderColumnAction() {
		@$column_id = DevblocksPlatform::importGPC($_REQUEST['column_id'],'integer',0);
		@$cards = DevblocksPlatform::importGPC($_REQUEST['cards'],'array',[]);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($column = DAO_ProjectBoardColumn::get($column_id)))
			return;
		
		if(false == ($board = $column->getProjectBoard()))
			return;
		
		// Check permissions
		if(!Context_ProjectBoard::isWriteableByActor($board, $active_worker))
			return;
		
		// [TODO] Validate $cards
		
		DAO_ProjectBoardColumn::update($column_id, [
			DAO_ProjectBoardColumn::CARDS_JSON => json_encode($cards),
		]);
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=search&type=project_board', true),
					'toolbar_extension_id' => 'cerberusweb.contexts.project.board.explore.toolbar',
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
};
